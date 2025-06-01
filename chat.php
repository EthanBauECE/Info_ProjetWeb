<?php
    session_start();//fonction pour les erreurs possible
    error_reporting(E_ALL);
    ini_set('display_errors',1);


//foncion pour la connexion utilisateur, si pas reussite alors redirige 
   if (!isset($_SESSION["user_id"])) {
        header("Location: login.php?redirect=" . urlencode('chat.php'));
        exit();

    }
//fonction pour inclure
    require 'includes/head.php';
    require 'includes/header.php';//pour mettre le header avec le badge

//fonction pour securisé l'affichage
     function safe_html($value){
        return $value !==null? htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8'): '';
    }


//les variables principales
    $current_user_id = $_SESSION["user_id"];
    $selected_conversation_id = isset($_GET['conv_id']) ? intval($_GET['conv_id']):0;
    $target_user_id = isset($_GET['target_id']) ? intval($_GET['target_id']):0;//pr le redemarrage d'une conv


//pour la connexion a notre base de donnée
    $db_host='localhost';
    $db_user='root';
    $db_pass='';
    $db_name='base_donne_web';
    $conn=mysqli_connect($db_host,$db_user,$db_pass,$db_name);

//veirf connexio avec MySQLi
    if (!$conn) {
        die("erreur lors de la connexion BDD: " . mysqli_connect_error());
    }
    mysqli_set_charset($conn,'utf8');


//fonction pr le redemarrage d'une nouvelle conv (que si target_id est passé) 
    if ($target_user_id > 0 && $target_user_id !== $current_user_id) {
        $u1 = min($current_user_id, $target_user_id);//pr detterminer les deux utilisateurs (du plus petit au plus grand)
        $u2 = max($current_user_id, $target_user_id);
        //pr verif si la conv existerait pas deja
        $sql_check_conv="SELECT ID FROM conversations WHERE user1_id = ? AND user2_id = ?";
        $stmt_check_conv=mysqli_prepare($conn,$sql_check_conv);
        mysqli_stmt_bind_param($stmt_check_conv,"ii",$u1,$u2);
        mysqli_stmt_execute($stmt_check_conv);
        $result_check_conv=mysqli_stmt_get_result($stmt_check_conv);
        $existing_conv=mysqli_fetch_assoc($result_check_conv);
        mysqli_free_result($result_check_conv);
        mysqli_stmt_close($stmt_check_conv);

        if ($existing_conv){//que si la conv existe deja 
            $selected_conversation_id = $existing_conv['ID'];
        } 
        else {//sinon on creer une nouvelle conversation
            mysqli_begin_transaction($conn);
            try {
                $sql_create_conv="INSERT INTO conversations (user1_id, user2_id) VALUES (?, ?)";
                $stmt_create_conv=mysqli_prepare($conn, $sql_create_conv);
                mysqli_stmt_bind_param($stmt_create_conv,"ii",$u1,$u2);
                mysqli_stmt_execute($stmt_create_conv);
                $selected_conversation_id=mysqli_insert_id($conn);
                mysqli_stmt_close($stmt_create_conv);
                mysqli_commit($conn);
            } 
            catch (Exception $e)
            {
            mysqli_rollback($conn);
            error_log("la conversation n'a pas pu etre cree suite a une erreur:" . $e->getMessage() . " | MySQLi Error: " . mysqli_error($conn));
            $selected_conversation_id = 0;//pr empecher d'afficher un conv nn crée
            }
        }
//fonction pour netoyé l'url et rediriger vers la bonne conversation
        if ($selected_conversation_id>0){
            header("Location:chat.php?conv_id=" . $selected_conversation_id);
            exit();
        }
    }

//maintenant pour recuperer les listes de conversation
    $conversations=[];
    try 
    {
        $sql_conversations="
            SELECT
                c.ID AS conversation_id,
                CASE WHEN c.user1_id=? THEN u2.ID ELSE u1.ID END AS other_user_id,
                CASE WHEN c.user1_id=? THEN u2.Prenom ELSE u1.Prenom END AS other_user_prenom,
                CASE WHEN c.user1_id=? THEN u2.Nom ELSE u1.Nom END AS other_user_nom,
                CASE WHEN c.user1_id=? THEN u2.TypeCompte ELSE u1.TypeCompte END AS other_user_type_compte,
                CASE WHEN c.user1_id=? THEN up2.Type ELSE up1.Type END AS other_user_specialty,
                c.last_message_at,
                CASE WHEN c.user1_id = ? THEN c.user1_unread_count ELSE c.user2_unread_count END AS unread_count
            FROM conversations c
            JOIN utilisateurs u1 ON c.user1_id = u1.ID
            LEFT JOIN utilisateurs_personnel up1 ON u1.ID = up1.ID
            JOIN utilisateurs u2 ON c.user2_id = u2.ID
            LEFT JOIN utilisateurs_personnel up2 ON u2.ID = up2.ID
            WHERE c.user1_id = ? OR c.user2_id= ?
            ORDER BY c.last_message_at DESC
        ";
        $stmt_conversations=mysqli_prepare($conn,$sql_conversations);
        mysqli_stmt_bind_param($stmt_conversations,"iiiiiiii",
            $current_user_id,//other_user_id
            $current_user_id,//other_user_prenom
            $current_user_id,//other_user_nom
            $current_user_id,//other_user_type_compte
            $current_user_id,//other_user_specialty
            $current_user_id,//unread_count
            $current_user_id,//WHERE user1_id
            $current_user_id //OR user2_id
        );


        mysqli_stmt_execute($stmt_conversations);
        $result_conversations = mysqli_stmt_get_result($stmt_conversations);
        $conversations = mysqli_fetch_all($result_conversations, MYSQLI_ASSOC);
        mysqli_free_result($result_conversations);
        mysqli_stmt_close($stmt_conversations);

    }catch (Exception $e){//pr les erreurs imprévues,mysqli_prepare/execute permettent de lever des warnings/false.
        error_log("Impossible de recupperer les cnversations: " . $e->getMessage() . " | MySQLi Error: " . mysqli_error($conn));
        echo "<p style='color:red; text-align: center; padding: 20px;'> Le changement de conversation a conduit a une erreur: " . safe_html(mysqli_error($conn)?:$e->getMessage()) ."</p>";
    }


//pr recup les message d'une conversation qu'on a selectionné
    $messages=[];
    $selected_conversation_partner_name='';

    if ($selected_conversation_id>0){//permet de verifier que l'utilisateur fait bien partie de la conv donnée
        try 
        {
        
            $sql_verify_conv="SELECT user1_id, user2_id FROM conversations WHERE ID =?";
            $stmt_verify_conv=mysqli_prepare($conn,$sql_verify_conv);
            mysqli_stmt_bind_param($stmt_verify_conv,"i",$selected_conversation_id);
            mysqli_stmt_execute($stmt_verify_conv);
            $result_verify_conv=mysqli_stmt_get_result($stmt_verify_conv);
            $conv_check=mysqli_fetch_assoc($result_verify_conv);
            mysqli_free_result($result_verify_conv);
            mysqli_stmt_close($stmt_verify_conv);

            if ($conv_check &&($conv_check['user1_id'] == $current_user_id || $conv_check['user2_id'] == $current_user_id)) {
                //permet de savoir qui est l'autre personne concerné par la conv
                $partner_id = ($conv_check['user1_id'] == $current_user_id) ? $conv_check['user2_id'] : $conv_check['user1_id'];
                
                //permet de recuperer les details de l'autre personne
                $sql_partner_details="SELECT u.Prenom, u.Nom, u.TypeCompte, up.Type AS specialty FROM utilisateurs u LEFT JOIN utilisateurs_personnel up ON u.ID=up.ID WHERE u.ID=?";
                $stmt_partner_details=mysqli_prepare($conn,$sql_partner_details);
                mysqli_stmt_bind_param($stmt_partner_details,"i",$partner_id);
                mysqli_stmt_execute($stmt_partner_details);
                $result_partner_details = mysqli_stmt_get_result($stmt_partner_details);
                $partner_info = mysqli_fetch_assoc($result_partner_details);
                mysqli_free_result($result_partner_details);
                mysqli_stmt_close($stmt_partner_details);

                if ($partner_info){//alors on affiche les informations recoltés
                    $prenom_partner=safe_html($partner_info['Prenom']);
                    $nom_partner=safe_html($partner_info['Nom']);
                    $type_compte_partner=safe_html($partner_info['TypeCompte']);
                    $specialty_partner=safe_html($partner_info['specialty']);

                    $selected_conversation_partner_name = '';

                    //pour mettre le nom et prenom en priorité
                    if (!empty($prenom_partner)&&!empty($nom_partner))
                    {
                        $selected_conversation_partner_name=$prenom_partner . ' ' . $nom_partner;
                    } elseif (!empty($prenom_partner)){
                        $selected_conversation_partner_name=$prenom_partner;
                    } elseif (!empty($nom_partner)){
                        $selected_conversation_partner_name=$nom_partner;
                    } else{
                        $selected_conversation_partner_name='ID de l utilisateur:' . $partner_id;
                    }

                //infromations qui vont etre ajoutées entre parenthese 
                if ($type_compte_partner==='Personnel' &&!empty($specialty_partner)){
                        $selected_conversation_partner_name .= ' (' . $specialty_partner . ')';
                } elseif(!empty($type_compte_partner)){
                        $selected_conversation_partner_name .= ' (' . $type_compte_partner . ')';
                    }
                } else{
                    $selected_conversation_partner_name ='L utilisateur pas connu';
                }

               //pour recuperer les messages envoyés
                $sql_messages="SELECT sender_id, message_text,sent_at FROM messages WHERE conversation_id=? ORDER BY sent_at ASC";
                $stmt_messages=mysqli_prepare($conn,$sql_messages);
                mysqli_stmt_bind_param($stmt_messages,"i",$selected_conversation_id);
                mysqli_stmt_execute($stmt_messages);
                $result_messages=mysqli_stmt_get_result($stmt_messages);
                $messages=mysqli_fetch_all($result_messages,MYSQLI_ASSOC);
                mysqli_free_result($result_messages);
                mysqli_stmt_close($stmt_messages);

                //les messages sont donc marqués comme lu pr l'utilisateur en question
                if($conv_check['user1_id']==$current_user_id) {
                    $sql_mark_read="UPDATE conversations SET user1_unread_count=0 WHERE ID=?";
                } else 
                {
                    $sql_mark_read="UPDATE conversations SET user2_unread_count=0 WHERE ID=?";
                }

                $stmt_mark_read=mysqli_prepare($conn, $sql_mark_read);
                mysqli_stmt_bind_param($stmt_mark_read,"i",$selected_conversation_id);
                mysqli_stmt_execute($stmt_mark_read);
                mysqli_stmt_close($stmt_mark_read);

            }else{
                $selected_conversation_id = 0;//pr les conversatio invalide on n'affiche rien
            }
        } catch (Exception $e) {//
            error_log("les messages n'ont pas pu etre recuperes: " . $e->getMessage() . " | MySQLi Error: " . mysqli_error($conn));
            $selected_conversation_id =0;
        }
    }

//le message va etre traité  via AJAX 
//code en output HTML,

    if ($_SERVER["REQUEST_METHOD"]=="POST" && isset($_POST['action']) && $_POST['action']==='send_message') {
        header('Content-Type: application/json');//la réponse JSON pour AJAX

        $message_text=trim($_POST['message_text'] ?? '');
        $conv_id=intval($_POST['conv_id'] ?? 0);

        if(empty($message_text)||$conv_id ===0){
            echo json_encode(['success'=> false, 'error' => 'Pas de message ou conversation pas disponible.']);
            exit();
        }

        mysqli_begin_transaction($conn);
        try {//fonction pour verifier que l'utlisisateur fait bien partie de cette conversation
            $sql_verify_conv_send="SELECT user1_id, user2_id FROM conversations WHERE ID =?";
            $stmt_verify_conv_send=mysqli_prepare($conn,$sql_verify_conv_send);
            mysqli_stmt_bind_param($stmt_verify_conv_send,"i",$conv_id);
            mysqli_stmt_execute($stmt_verify_conv_send);
            $result_verify_conv_send=mysqli_stmt_get_result($stmt_verify_conv_send);
            $conv_check_send=mysqli_fetch_assoc($result_verify_conv_send);
            mysqli_free_result($result_verify_conv_send);
            mysqli_stmt_close($stmt_verify_conv_send);

            if (!$conv_check_send || ($conv_check_send['user1_id'] != $current_user_id && $conv_check_send['user2_id'] != $current_user_id)) {
                throw new Exception("l utilisateur n est pas autorise a envoye des messages dans cette conversation.");
            }

        //pour mettre le message
            $sql_insert_msg = "INSERT INTO messages (conversation_id, sender_id, message_text) VALUES (?, ?, ?)";
            $stmt_insert_msg = mysqli_prepare($conn, $sql_insert_msg);
            mysqli_stmt_bind_param($stmt_insert_msg, "iis", $conv_id, $current_user_id, $message_text);
            mysqli_stmt_execute($stmt_insert_msg);
            mysqli_stmt_close($stmt_insert_msg);

        //pour la mise a jour de 'last_message_at" et aussi le compteur des messages non lus pour le receveur
            $receiver_id = ($conv_check_send['user1_id'] == $current_user_id) ? $conv_check_send['user2_id'] : $conv_check_send['user1_id'];
            $update_count_column = ($conv_check_send['user1_id'] == $receiver_id) ? 'user1_unread_count' : 'user2_unread_count';

        
        // Mais ici, sa valeur est contrôlée (soit 'user1_unread_count', soit 'user2_unread_count')
            $sql_update_conv = "UPDATE conversations SET last_message_at = NOW(), $update_count_column = $update_count_column + 1 WHERE ID = ?";
            $stmt_update_conv = mysqli_prepare($conn, $sql_update_conv);
            mysqli_stmt_bind_param($stmt_update_conv, "i", $conv_id);
            mysqli_stmt_execute($stmt_update_conv);
            mysqli_stmt_close($stmt_update_conv);

            mysqli_commit($conn);
            echo json_encode(['success' => true]);

        } catch (Exception $e) {
            mysqli_rollback($conn);
            error_log("Erreur envoi message: " . $e->getMessage() . " | MySQLi Error: " . mysqli_error($conn));
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit(); //pour permettre d arrêter l'exécution du script après l'envoi de la réponse JSON
    }

?>

<!DOCTYPE html> <!--pour la partie HTML-->
<html lang="fr">

    
    <?php  ?>

    <body class="chat-page-body">
        <?php  ?>

        <main class="chat-main">
            <div class="chat-container">

                <div class="conversation-list"> <!-- pour les listes des conversations-->
                    <h2>Discussions</h2>
                    <?php if(empty($conversations)):?><!--message d'information si aucune conv n'est reperée-->
                        <p class="no-conversations">Aucune conversation detectee. <br>Utilisez les boutons "Communiquer" sur les profils des médecins ou laboratoires.</p>
                    <?php else:?><!--sinon on affiche tout une part une-->
                        <?php foreach ($conversations as $conv): ?>
                            <?php
                            //on securise puis on recupere les infos de l'autre utilisateur
                                $prenom=safe_html($conv['other_user_prenom']);//permet d'afficher le nom d'usage de l'utilisateur
                                $nom=safe_html($conv['other_user_nom']);
                                $type_compte=safe_html($conv['other_user_type_compte']);
                                $specialty=safe_html($conv['other_user_specialty']);
                                $display_name_conv='';//pour le nom qu'on va afficher

                                if (!empty($prenom)&&!empty($nom)) {//selon les données qu'on va avoir on construit le nom a afficher
                                    $display_name_conv=$prenom . ' ' . $nom;
                                } elseif (!empty($prenom)) { //que si le prenom exciste
                                    $display_name_conv=$prenom;
                                } elseif (!empty($nom)){ //la encore que si le nom existe
                                    $display_name_conv=$nom;
                                } else{ //si pas d'info rentré alors on affiche l'identifiant
                                    $display_name_conv='Utilisateur ID:' . safe_html($conv['other_user_id']);
                                }

                                //si le compte va etre un personnel medical avec une spécialité alors on l'ajoute
                                if ($type_compte==='Personnel' &&!empty($specialty)){
                                    $display_name_conv .=' (' . $specialty . ')';
                                } elseif(!empty($type_compte)){
                                    $display_name_conv .=' (' . $type_compte . ')';
                                }
                            ?>
                            <!--pour aller vers la conv selectionnée-->
                            <a href="chat.php?conv_id=<?php echo safe_html($conv['conversation_id']); ?>"
                               class="conversation-item <?php echo ($selected_conversation_id == $conv['conversation_id']) ? 'active' : ''; ?>">
                                <span class="partner-name">
                                <!--pour le nom de la personne-->
                                <?php echo $display_name_conv; ?>
                                </span>
                               
                                <!--si des messages sont non lus alors le badge est affiché-->
                                <?php if ($conv['unread_count'] > 0):?>
                                    <span class="unread-badge"><?php echo safe_html($conv['unread_count']); ?></span>
                                <?php endif; ?>

                                <!--affiche l'heure de dernier message-->
                                <span class="last-message-time">
                                    <?php echo date('H:i', strtotime(safe_html($conv['last_message_at']))); ?>
                                </span>
                            </a>
                        <?php endforeach;?>
                    <?php endif; ?>
                </div>

 <!--pour la fenetre de la discussion-->
                <div class="chat-window"> 

                <!--ouverture si une fenetre est selectionnée-->
                    <?php if($selected_conversation_id>0): ?>
                        <div class="chat-header"> <!--en tete de la fenetre de chat-->
                            <h3>Conversation avec<?php echo safe_html($selected_conversation_partner_name); ?></h3>
                        </div>

                        <!--pour la zone d'affichage des messages-->
                        <div class="messages-display" id="messages-display"> 
                            <?php if(empty($messages)):?>
                                <!--  si pas de messages envoyé -->
                                <p class="no-messages">Aucun messages dans cette conversation.</p>

                            <?php else:?> 
                                 <!--  pour afficher les message un par un  -->
                                <?php foreach ($messages as $msg): ?>
                                    <div class="message-bubble <?php echo ($msg['sender_id'] == $current_user_id) ? 'sent':'received'; ?>">
                                     <!--  contenu du message  -->
                                        <p class="message-text"><?php echo nl2br(safe_html($msg['message_text'])); ?></p>
                                         <!-- heure denvoi  -->
                                        <span class="message-time"><?php echo date('H:i', strtotime(safe_html($msg['sent_at']))); ?></span>
                                    </div>
                                <?php endforeach;?>
                            <?php endif; ?>
                        </div>

                         <!--  ici pour la zone d'ecriture et envoyé un message -->
                        <div class="message-input-area"> 
                            <textarea id="message-input" placeholder="Écrivez votre message ici..." rows="3"></textarea>
                            <button id="send-message-btn">Envoyer</button>
                        </div>
                    <?php else: ?>
                         <!--   dans le cas ou pas de conversation selectionné-->
                        <div class="no-chat-selected"> 
                            <p>Sélectionnez une conversation ou démarrez une nouvelle.</p>
                            <img src="./images/chat_placeholder.png" alt="Sélectionnez un chat" style="max-width: 200px; margin-top: 20px;">
                        </div>

                    <?php endif; ?>
                </div>
            </div>
        </main>

        <!-- inclusion du pieds de page -->
        <?php require 'includes/footer.php'; ?>

        <?php
        // fermeture de la connexion a la base de données
        if (isset($conn)) {
            mysqli_close($conn);//pour fermer la connexion proprement
        }
        ?>

    <script> //pour le javascript
    document.addEventListener('DOMContentLoaded',function() {//des que la page est chargee
        const messagesDisplay = document.getElementById('messages-display');
        if (messagesDisplay) {
            messagesDisplay.scrollTop = messagesDisplay.scrollHeight;//pr faire deflié automatiquement en bas de la zoe de message
        }
//pr recuperé les info HTML utiles
        const sendMessageBtn=document.getElementById('send-message-btn');
        const messageInput=document.getElementById('message-input');
        const selectedConvId=<?php echo json_encode($selected_conversation_id); ?>;
        const currentUserId=<?php echo json_encode($current_user_id); ?>;
       //on recup les valeurs php dans le javascript de maniere securisee, evite les erreurs de formet et injections
       //source https://www.php.net/manual/fr/function.json-encode.php



        if (sendMessageBtn && messageInput && selectedConvId > 0){//si tout est ok et qu'une conv est selctionné
            sendMessageBtn.addEventListener('click',sendMessage);//fonction sendMessage activé si bouton "envoyer" appuyé
            messageInput.addEventListener('keypress',function(e){//oareil juste avec la touche 'entree' du clavier
                if (e.key==='Enter'&&!e.shiftKey){
                    e.preventDefault();
                    sendMessage();

                }
            });


            //la fonction pour envoyer un message
            function sendMessage(){
                const messageText=messageInput.value.trim();//pr recupérer et nettoier le texte
                if (messageText==='') return;//si c'est vide alors rien est fait

                fetch('chat.php',{//on envoie le mess part le biaie d'une requete POST
                //source : https://blog.questio.fr/la-codification-a-posteriori-ou-post-codage#:~:text=La%20post%2Dcodification%20manuelle,qui%20apparaissent%20de%20fa%C3%A7on%20r%C3%A9currente.
                    method:'POST',
                   headers:{
                    'Content-Type':'application/x-www-form-urlencoded',
                    },
                    body: `action=send_message&conv_id=${selectedConvId}&message_text=${encodeURIComponent(messageText)}`
                })

                .then(response=>response.json())
                .then(data=>{
                    if (data.success) {
                        messageInput.value = '';//pour vider le champ
                        fetchMessages(selectedConvId);//rechargement des messages
                        updateHeaderUnreadCount();//met  a jour les notificztions, mettre aussi à jour la liste des conversations
                       
                    }else{
                        alert('probleme detecté lors de l\'envoi du message:' + (data.error || 'Erreur inconnue'));
                    
                    }
                })

                .catch(error=>{
                    console.error('Erreur réseau ou JSON:',error);
                    alert('Erreur du reseau lors de l envoi du message.');
                });

            }

            //fonction de recup des mess en AJAX
            function fetchMessages(convId) {
                fetch(`chat_api.php?action=get_messages&conv_id=${convId}`)
                    .then(data => {
                        if (data.success && data.messages) {
                            messagesDisplay.innerHTML = '';//on vide les anciens messages 
                            if (data.messages.length === 0) {
                                const noMsgP = document.createElement('p');
                                noMsgP.classList.add('no-messages');
                                noMsgP.textContent='Aucun message dans cette conversation.';
                                messagesDisplay.appendChild(noMsgP);
                            } else {//sinon on va creer chaque bulle de message
                                data.messages.forEach(msg=>{
                                    const bubble=document.createElement('div');
                                    bubble.classList.add('message-bubble');
                                    bubble.classList.add(msg.sender_id==currentUserId ? 'sent' : 'received');

                                    const textP = document.createElement('p');
                                    textP.classList.add('message-text');
                                    textP.innerHTML=nl2br(safe_html_js(msg.message_text)); /// Sécurisation et nl2br


                                    const timeSpan=document.createElement('span');
                                    timeSpan.classList.add('message-time');

                                    timeSpan.textContent=msg.sent_at ? msg.sent_at.substring(11, 16) : '??:??';



                                    bubble.appendChild(textP);
                                    bubble.appendChild(timeSpan);

                                    messagesDisplay.appendChild(bubble);
                                });

                            }

                            messagesDisplay.scrollTop=messagesDisplay.scrollHeight;//pour scroller tout en bas 
                            updateHeaderUnreadCount();//mise a jour des messages non lus
                        }else{
                            console.error('Erreur chargement messages:',data.error || 'Format de réponse incorrect');
                       
                        }
                    })

                    .catch(error=>console.error('Erreur réseau chargement messages:',error));
            }

            //fonction qui va nous permettre de mettre a jour le badge des messages non lus dans le header
            function updateHeaderUnreadCount(){
                fetch('chat_api.php?action=get_unread_count')
                    .then(response=>response.json())
                    .then(data=>{
                        const chatBadge=document.querySelector('.chat-badge');
                        if (data.success&& typeof data.unread_count !=='undefined') {
                            if (data.unread_count>0)
                             {
                                if(chatBadge){
                                    chatBadge.textContent=data.unread_count;
                                    chatBadge.style.display='';

                                }
                                else{
                                    const chatLink=document.querySelector('.chat-link a');//Cible le lien <a> dans .chat-link
                                    if (chatLink){
                                        const newBadge=document.createElement('span');
                                        newBadge.classList.add('chat-badge');
                                        newBadge.textContent = data.unread_count;
                                        chatLink.appendChild(newBadge);//pour ajouter le bagde
                                    }
                                }
                               } else {
                                if (chatBadge) {
                                    chatBadge.style.display ='none';//pour cacher le badge
                                }
                            }
                        }else{
                             console.error('Erreur mise à jour badge:', data.error || 'Format de réponse pas correct');
                        }
                    })
                    .catch(error=>console.error('Erreur réseau mise à jour badge:', error));
            }

        //pour rafraichir les messages toutes les 5secondes
            setInterval(() =>{
                if(document.visibilityState ==='visible' && selectedConvId >0){ 
                }
            }, 5000); 

           //fonction ppour convertir les \n en <br>
            function nl2br(str) {
                if (typeof str === 'undefined' || str === null) {
                    return '';
                }
                return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1<br>$2');
            }

            //fonction utilitaire pour echapper le texte 
            function safe_html_js(str) {
                if (typeof str==='undefined'|| str === null) {
                    return '';
                }
                const div = document.createElement('div');
                div.appendChild(document.createTextNode(str));
                return div.innerHTML;
            }
        }
    });
    </script>

    <style> /*  le code css */
        /*  pour la page chat.php */
        .chat-page-body {
            display: flex;
            flex-direction: column;
            min-height: 100vh; /* pr que la page prenne au moins toute la hauteur du viewport */
        }
        .chat-main {
            flex-grow: 1; /* le contenu principal de prendre l'espace disponible */
            padding: 20px;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center; 
        }


        .chat-container {
            display: flex;
            width: 100%;
            max-width: 1200px; 
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            overflow: hidden;/* pr cacher les débordements */
            min-height: 700px;
        }


        .conversation-list {
            flex: 0 0 300px; 
            border-right: 1px solid #e0e0e0;
            padding: 20px 0;
            background-color: #f8f9fa;
            overflow-y: auto; /* il scroll si trop de conversations */
        }
        .conversation-list h2 {
            font-size: 1.5rem;
            color: #0a7abf;
            padding: 0 20px 15px;
            border-bottom: 1px solid #e0e0e0;
            margin-bottom: 15px;
        }
        .no-conversations {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            padding: 20px;
        }
        .conversation-item {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            text-decoration: none;
            color: #333;
            border-bottom: 1px solid #f0f0f0;
            transition: background-color 0.2s ease;
            position: relative;
        }
        .conversation-item:hover {
            background-color: #e9ecef;
        }
        .conversation-item.active {
            background-color: #d1e7ff;
            color: #004085;
            font-weight: 600;
        }
        .conversation-item .partner-name {
            flex-grow: 1;
            font-size: 1.05rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .conversation-item .unread-badge {
            background-color: #28a745; /* Vert pour les non lus */
            color: white;
            font-size: 0.75rem;
            font-weight: bold;
            padding: 3px 8px;
            border-radius: 12px;
            margin-left: 10px;
            min-width: 25px;
            text-align: center;
            flex-shrink: 0;
        }
        .conversation-item .last-message-time {
            font-size: 0.8rem;
            color: #6c757d;
            margin-left: 10px;
            flex-shrink: 0;
        }

        .chat-window {
            flex: 1;/* Prend l'espace restant */
            display: flex;
            flex-direction: column;
            min-width: 0;
        }
        .chat-header {
            padding: 15px 20px;
            background-color: #0a7abf;
            color: white;
            font-size: 1.2rem;
            border-bottom: 1px solid #0056b3;
            flex-shrink: 0;
        }
        .chat-header h3 {
            margin: 0;
            font-size: 1.3rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .no-chat-selected {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100%;
            color: #6c757d;
            font-size: 1.1rem;
            text-align: center;
            padding: 20px;
        }

        .messages-display {
            flex-grow: 1;
            padding: 20px;
            overflow-y: auto;/* pr scroll si trop de messages */
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .no-messages {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            margin: auto;
        }

        .message-bubble {
            max-width: 70%;
            padding: 10px 15px;
            border-radius: 18px;
            line-height: 1.4;
            font-size: 0.95rem;
            position: relative;
            word-wrap: break-word;
        }
        .message-bubble p.message-text {
            margin: 0;
            padding-bottom: 5px;
        }
        .message-bubble .message-time {
            font-size: 0.75rem;
            color: rgba(0,0,0,0.5);
            display: block;
            text-align: right;
            margin-top: 5px;
        }
        .message-bubble.sent {
            background-color: #e0f7fa;
            align-self: flex-end;
            border-bottom-right-radius: 4px;
        }
        .message-bubble.received {
            background-color: #f1f0f0;
            align-self: flex-start;
            border-bottom-left-radius: 4px;
        }

        .message-input-area {
            border-top: 1px solid #e0e0e0;
            padding: 15px 20px;
            display: flex;
            gap: 10px;
            background-color: #f8f9fa;
            flex-shrink: 0;
        }
        .message-input-area textarea {
            flex-grow: 1;
            border: 1px solid #ccc;
            border-radius: 18px;
            padding: 10px 15px;
            font-size: 1rem;
            resize: none;
            min-height: 50px;
            max-height: 150px;
            box-sizing: border-box;
            transition: border-color 0.2s ease;
            overflow-y: auto;
        }
        .message-input-area textarea:focus{
            outline: none;
            border-color: #0a7abf;
        }
        .message-input-area button{
            background-color: #0a7abf;
            color:white;
            border: none;
            border-radius: 18px;
            padding: 0 20px;
            height: 50px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: background-color 0.2s ease;
            align-self: flex-end;
        }
        .message-input-area button:hover{
            background-color: #075c92;
        }

       

        @media (max-width: 768px){
            .chat-container {
                flex-direction: column;
                min-height: calc(100vh - 40px);
                max-height: calc(100vh - 40px);
            }
            .conversation-list {
                flex: none;
                width: 100%;
                border-right: none;
                border-bottom: 1px solid #e0e0e0;
                max-height: 200px;
                min-height: 100px;
            }
            .chat-window 
            {
                flex: 1;
                min-height: 0;
            }
            .chat-main {
                padding: 0;
            }
             .chat-container {
                border-radius: 0; 
            }
        }
    </style>
</body>
</html>