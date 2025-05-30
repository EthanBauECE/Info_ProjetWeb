<?php /////////////////////////////////////////////// PHP //////////////////////////////////////////

    // ______________/ Initialisation de la session et erreurs \_____________________
    session_start();
    error_reporting(E_ALL);
    ini_set('display_errors', 1);


    // ______________/ Vérification de la connexion utilisateur \_____________________
    /// Rediriger si l'utilisateur n'est pas connecté
    if (!isset($_SESSION["user_id"])) {
        header("Location: login.php?redirect=" . urlencode('chat.php'));
        exit();
    }


    // ______________/ Inclusions \_____________________
    require 'includes/head.php';
    require 'includes/header.php'; // Pour inclure le header avec le badge de chat


    // ______________/ Fonctions Utilitaires \_____________________
    /// Fonction pour sécuriser l'affichage HTML
    function safe_html($value) {
        return $value !== null ? htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') : '';
    }


    // ______________/ Variables principales \_____________________
    $current_user_id = $_SESSION["user_id"];
    $selected_conversation_id = isset($_GET['conv_id']) ? intval($_GET['conv_id']) : 0;
    $target_user_id = isset($_GET['target_id']) ? intval($_GET['target_id']) : 0; /// Pour démarrer une nouvelle conversation


    // ______________/ Connexion à la base de données \_____________________
    $db_host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'base_donne_web';
    $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

    /// Vérification de la connexion MySQLi
    if (!$conn) {
        die("Erreur de connexion BDD: " . mysqli_connect_error());
    }
    mysqli_set_charset($conn, 'utf8');


    // ______________/ Démarrer une nouvelle conversation (si target_id est passé) \_____________________
    if ($target_user_id > 0 && $target_user_id !== $current_user_id) {
        /// Déterminer user1_id et user2_id de manière canonique (plus petit d'abord)
        $u1 = min($current_user_id, $target_user_id);
        $u2 = max($current_user_id, $target_user_id);

        /// Vérifier si une conversation existe déjà
        $sql_check_conv = "SELECT ID FROM conversations WHERE user1_id = ? AND user2_id = ?";
        $stmt_check_conv = mysqli_prepare($conn, $sql_check_conv);
        mysqli_stmt_bind_param($stmt_check_conv, "ii", $u1, $u2);
        mysqli_stmt_execute($stmt_check_conv);
        $result_check_conv = mysqli_stmt_get_result($stmt_check_conv);
        $existing_conv = mysqli_fetch_assoc($result_check_conv);
        mysqli_free_result($result_check_conv);
        mysqli_stmt_close($stmt_check_conv);

        if ($existing_conv) {
            $selected_conversation_id = $existing_conv['ID'];
        } else {
            /// Créer une nouvelle conversation
            mysqli_begin_transaction($conn);
            try {
                $sql_create_conv = "INSERT INTO conversations (user1_id, user2_id) VALUES (?, ?)";
                $stmt_create_conv = mysqli_prepare($conn, $sql_create_conv);
                mysqli_stmt_bind_param($stmt_create_conv, "ii", $u1, $u2);
                mysqli_stmt_execute($stmt_create_conv);
                $selected_conversation_id = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt_create_conv);
                mysqli_commit($conn);
            } catch (Exception $e) {
                mysqli_rollback($conn);
                error_log("Erreur lors de la création de la conversation: " . $e->getMessage() . " | MySQLi Error: " . mysqli_error($conn));
                $selected_conversation_id = 0; /// Empêche d'afficher une conversation non créée
            }
        }
        /// Rediriger pour nettoyer l'URL et passer à la conversation
        if ($selected_conversation_id > 0) {
            header("Location: chat.php?conv_id=" . $selected_conversation_id);
            exit();
        }
    }


    // ______________/ Récupérer la liste des conversations \_____________________
    $conversations = [];
    try {
        $sql_conversations = "
            SELECT
                c.ID AS conversation_id,
                CASE WHEN c.user1_id = ? THEN u2.ID ELSE u1.ID END AS other_user_id,
                CASE WHEN c.user1_id = ? THEN u2.Prenom ELSE u1.Prenom END AS other_user_prenom,
                CASE WHEN c.user1_id = ? THEN u2.Nom ELSE u1.Nom END AS other_user_nom,
                CASE WHEN c.user1_id = ? THEN u2.TypeCompte ELSE u1.TypeCompte END AS other_user_type_compte,
                CASE WHEN c.user1_id = ? THEN up2.Type ELSE up1.Type END AS other_user_specialty,
                c.last_message_at,
                CASE WHEN c.user1_id = ? THEN c.user1_unread_count ELSE c.user2_unread_count END AS unread_count
            FROM conversations c
            JOIN utilisateurs u1 ON c.user1_id = u1.ID
            LEFT JOIN utilisateurs_personnel up1 ON u1.ID = up1.ID
            JOIN utilisateurs u2 ON c.user2_id = u2.ID
            LEFT JOIN utilisateurs_personnel up2 ON u2.ID = up2.ID
            WHERE c.user1_id = ? OR c.user2_id = ?
            ORDER BY c.last_message_at DESC
        ";
        $stmt_conversations = mysqli_prepare($conn, $sql_conversations);
        mysqli_stmt_bind_param($stmt_conversations, "iiiiiiii",
            $current_user_id, /// other_user_id
            $current_user_id, /// other_user_prenom
            $current_user_id, /// other_user_nom
            $current_user_id, /// other_user_type_compte
            $current_user_id, /// other_user_specialty
            $current_user_id, /// unread_count
            $current_user_id, /// WHERE user1_id
            $current_user_id  /// OR user2_id
        );
        mysqli_stmt_execute($stmt_conversations);
        $result_conversations = mysqli_stmt_get_result($stmt_conversations);
        $conversations = mysqli_fetch_all($result_conversations, MYSQLI_ASSOC);
        mysqli_free_result($result_conversations);
        mysqli_stmt_close($stmt_conversations);

    } catch (Exception $e) { /// Catch générique pour les erreurs imprévues, mysqli_prepare/execute lèvent des warnings/false.
        error_log("Erreur lors de la récupération des conversations: " . $e->getMessage() . " | MySQLi Error: " . mysqli_error($conn));
        echo "<p style='color:red; text-align: center; padding: 20px;'>Erreur lors du chargement des conversations: " . safe_html(mysqli_error($conn) ?: $e->getMessage()) . "</p>";
    }


    // ______________/ Récupérer les messages de la conversation sélectionnée \_____________________
    $messages = [];
    $selected_conversation_partner_name = '';

    if ($selected_conversation_id > 0) {
        try {
            /// Vérifier que l'utilisateur fait bien partie de cette conversation
            $sql_verify_conv = "SELECT user1_id, user2_id FROM conversations WHERE ID = ?";
            $stmt_verify_conv = mysqli_prepare($conn, $sql_verify_conv);
            mysqli_stmt_bind_param($stmt_verify_conv, "i", $selected_conversation_id);
            mysqli_stmt_execute($stmt_verify_conv);
            $result_verify_conv = mysqli_stmt_get_result($stmt_verify_conv);
            $conv_check = mysqli_fetch_assoc($result_verify_conv);
            mysqli_free_result($result_verify_conv);
            mysqli_stmt_close($stmt_verify_conv);

            if ($conv_check && ($conv_check['user1_id'] == $current_user_id || $conv_check['user2_id'] == $current_user_id)) {
                /// Déterminer le partenaire de conversation
                $partner_id = ($conv_check['user1_id'] == $current_user_id) ? $conv_check['user2_id'] : $conv_check['user1_id'];
                
                /// Récupérer les détails de l'utilisateur partenaire
                $sql_partner_details = "SELECT u.Prenom, u.Nom, u.TypeCompte, up.Type AS specialty FROM utilisateurs u LEFT JOIN utilisateurs_personnel up ON u.ID = up.ID WHERE u.ID = ?";
                $stmt_partner_details = mysqli_prepare($conn, $sql_partner_details);
                mysqli_stmt_bind_param($stmt_partner_details, "i", $partner_id);
                mysqli_stmt_execute($stmt_partner_details);
                $result_partner_details = mysqli_stmt_get_result($stmt_partner_details);
                $partner_info = mysqli_fetch_assoc($result_partner_details);
                mysqli_free_result($result_partner_details);
                mysqli_stmt_close($stmt_partner_details);

                if ($partner_info) {
                    $prenom_partner = safe_html($partner_info['Prenom']);
                    $nom_partner = safe_html($partner_info['Nom']);
                    $type_compte_partner = safe_html($partner_info['TypeCompte']);
                    $specialty_partner = safe_html($partner_info['specialty']);

                    $selected_conversation_partner_name = '';

                    /// Priorité au Prénom Nom si disponibles
                    if (!empty($prenom_partner) && !empty($nom_partner)) {
                        $selected_conversation_partner_name = $prenom_partner . ' ' . $nom_partner;
                    } elseif (!empty($prenom_partner)) {
                        $selected_conversation_partner_name = $prenom_partner;
                    } elseif (!empty($nom_partner)) {
                        $selected_conversation_partner_name = $nom_partner;
                    } else {
                        $selected_conversation_partner_name = 'Utilisateur ID: ' . $partner_id;
                    }

                    /// Ajouter les informations entre parenthèses
                    if ($type_compte_partner === 'Personnel' && !empty($specialty_partner)) {
                        $selected_conversation_partner_name .= ' (' . $specialty_partner . ')';
                    } elseif (!empty($type_compte_partner)) {
                        $selected_conversation_partner_name .= ' (' . $type_compte_partner . ')';
                    }
                } else {
                    $selected_conversation_partner_name = 'Utilisateur inconnu';
                }

                /// Récupérer les messages
                $sql_messages = "SELECT sender_id, message_text, sent_at FROM messages WHERE conversation_id = ? ORDER BY sent_at ASC";
                $stmt_messages = mysqli_prepare($conn, $sql_messages);
                mysqli_stmt_bind_param($stmt_messages, "i", $selected_conversation_id);
                mysqli_stmt_execute($stmt_messages);
                $result_messages = mysqli_stmt_get_result($stmt_messages);
                $messages = mysqli_fetch_all($result_messages, MYSQLI_ASSOC);
                mysqli_free_result($result_messages);
                mysqli_stmt_close($stmt_messages);

                /// Marquer les messages comme lus pour l'utilisateur actuel
                if ($conv_check['user1_id'] == $current_user_id) {
                    $sql_mark_read = "UPDATE conversations SET user1_unread_count = 0 WHERE ID = ?";
                } else {
                    $sql_mark_read = "UPDATE conversations SET user2_unread_count = 0 WHERE ID = ?";
                }
                $stmt_mark_read = mysqli_prepare($conn, $sql_mark_read);
                mysqli_stmt_bind_param($stmt_mark_read, "i", $selected_conversation_id);
                mysqli_stmt_execute($stmt_mark_read);
                mysqli_stmt_close($stmt_mark_read);

            } else {
                $selected_conversation_id = 0; /// Conversation invalide ou non autorisée
            }
        } catch (Exception $e) { /// Catch générique
            error_log("Erreur lors de la récupération des messages: " . $e->getMessage() . " | MySQLi Error: " . mysqli_error($conn));
            $selected_conversation_id = 0;
        }
    }


    // ______________/ Traitement de l'envoi de message via AJAX \_____________________
    /// Ce bloc doit OBLIGATOIREMENT être avant tout output HTML,
    /// et doit exit() après avoir renvoyé sa réponse JSON.
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'send_message') {
        header('Content-Type: application/json'); /// Réponse JSON pour AJAX

        $message_text = trim($_POST['message_text'] ?? '');
        $conv_id = intval($_POST['conv_id'] ?? 0);

        if (empty($message_text) || $conv_id === 0) {
            echo json_encode(['success' => false, 'error' => 'Message vide ou conversation invalide.']);
            exit();
        }

        mysqli_begin_transaction($conn);
        try {
            /// Vérifier que l'utilisateur fait bien partie de cette conversation
            $sql_verify_conv_send = "SELECT user1_id, user2_id FROM conversations WHERE ID = ?";
            $stmt_verify_conv_send = mysqli_prepare($conn, $sql_verify_conv_send);
            mysqli_stmt_bind_param($stmt_verify_conv_send, "i", $conv_id);
            mysqli_stmt_execute($stmt_verify_conv_send);
            $result_verify_conv_send = mysqli_stmt_get_result($stmt_verify_conv_send);
            $conv_check_send = mysqli_fetch_assoc($result_verify_conv_send);
            mysqli_free_result($result_verify_conv_send);
            mysqli_stmt_close($stmt_verify_conv_send);

            if (!$conv_check_send || ($conv_check_send['user1_id'] != $current_user_id && $conv_check_send['user2_id'] != $current_user_id)) {
                throw new Exception("Non autorisé à envoyer des messages dans cette conversation.");
            }

            /// Insérer le message
            $sql_insert_msg = "INSERT INTO messages (conversation_id, sender_id, message_text) VALUES (?, ?, ?)";
            $stmt_insert_msg = mysqli_prepare($conn, $sql_insert_msg);
            mysqli_stmt_bind_param($stmt_insert_msg, "iis", $conv_id, $current_user_id, $message_text);
            mysqli_stmt_execute($stmt_insert_msg);
            mysqli_stmt_close($stmt_insert_msg);

            /// Mettre à jour last_message_at et le compteur de non lus pour le RECEVEUR
            $receiver_id = ($conv_check_send['user1_id'] == $current_user_id) ? $conv_check_send['user2_id'] : $conv_check_send['user1_id'];
            $update_count_column = ($conv_check_send['user1_id'] == $receiver_id) ? 'user1_unread_count' : 'user2_unread_count';

            // Attention: L'interpolation de $update_count_column est généralement déconseillée
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
        exit(); /// IMPORTANT : Arrête l'exécution du script après l'envoi de la réponse JSON
    }

?>

<!DOCTYPE html> <!-- ////////////////////////////////////////// HTML ///////////////////////////////////////////-->
<html lang="fr">

    <!-- Importation head -->
    <?php // Pas de PHP ici, c'est géré au début du script ?>

    <body class="chat-page-body">

        <!-- Importation header -->
        <?php // Pas de PHP ici, c'est géré au début du script ?>

        <main class="chat-main">
            <div class="chat-container">

                <div class="conversation-list"> <!-- /////////////////////// LISTE DES CONVERSATIONS ///////////////////////-->
                    <h2>Discussions</h2>
                    <?php if (empty($conversations)): ?>
                        <p class="no-conversations">Aucune conversation. <br>Utilisez les boutons "Communiquer" sur les profils des médecins ou laboratoires.</p>
                    <?php else: ?>
                        <?php foreach ($conversations as $conv): ?>
                            <?php
                                /// Logique pour construire le nom d'affichage de l'interlocuteur
                                $prenom = safe_html($conv['other_user_prenom']);
                                $nom = safe_html($conv['other_user_nom']);
                                $type_compte = safe_html($conv['other_user_type_compte']);
                                $specialty = safe_html($conv['other_user_specialty']);

                                $display_name_conv = '';

                                /// Priorité au Prénom Nom si disponibles
                                if (!empty($prenom) && !empty($nom)) {
                                    $display_name_conv = $prenom . ' ' . $nom;
                                } elseif (!empty($prenom)) { /// Si seulement le prénom existe
                                    $display_name_conv = $prenom;
                                } elseif (!empty($nom)) { /// Si seulement le nom existe
                                    $display_name_conv = $nom;
                                } else { /// Fallback si ni prénom ni nom n'est disponible
                                    $display_name_conv = 'Utilisateur ID: ' . safe_html($conv['other_user_id']);
                                }

                                /// Ajouter les informations entre parenthèses (spécialité ou type de compte)
                                if ($type_compte === 'Personnel' && !empty($specialty)) {
                                    $display_name_conv .= ' (' . $specialty . ')';
                                } elseif (!empty($type_compte)) {
                                    $display_name_conv .= ' (' . $type_compte . ')';
                                }
                            ?>
                            <a href="chat.php?conv_id=<?php echo safe_html($conv['conversation_id']); ?>"
                               class="conversation-item <?php echo ($selected_conversation_id == $conv['conversation_id']) ? 'active' : ''; ?>">
                                <span class="partner-name"><?php echo $display_name_conv; ?></span>
                                <?php if ($conv['unread_count'] > 0): ?>
                                    <span class="unread-badge"><?php echo safe_html($conv['unread_count']); ?></span>
                                <?php endif; ?>
                                <span class="last-message-time"><?php echo date('H:i', strtotime(safe_html($conv['last_message_at']))); ?></span>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>


                <div class="chat-window"> <!-- /////////////////////// FENETRE DE CHAT ///////////////////////-->
                    <?php if ($selected_conversation_id > 0): ?>
                        <div class="chat-header"> <!-- HEADER DU CHAT -->
                            <h3>Conversation avec <?php echo safe_html($selected_conversation_partner_name); ?></h3>
                        </div>
                        <div class="messages-display" id="messages-display"> <!-- AFFICHAGE DES MESSAGES -->
                            <?php if (empty($messages)): ?>
                                <p class="no-messages">Aucun message dans cette conversation.</p>
                            <?php else: ?>
                                <?php foreach ($messages as $msg): ?>
                                    <div class="message-bubble <?php echo ($msg['sender_id'] == $current_user_id) ? 'sent' : 'received'; ?>">
                                        <p class="message-text"><?php echo nl2br(safe_html($msg['message_text'])); ?></p>
                                        <span class="message-time"><?php echo date('H:i', strtotime(safe_html($msg['sent_at']))); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="message-input-area"> <!-- ZONE DE SAISIE DE MESSAGE -->
                            <textarea id="message-input" placeholder="Écrivez votre message..." rows="3"></textarea>
                            <button id="send-message-btn">Envoyer</button>
                        </div>
                    <?php else: ?>
                        <div class="no-chat-selected"> <!-- AUCUN CHAT SELECTIONNE -->
                            <p>Sélectionnez une conversation ou démarrez-en une nouvelle.</p>
                            <img src="./images/chat_placeholder.png" alt="Sélectionnez un chat" style="max-width: 200px; margin-top: 20px;">
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </main>

        <!-- Importation footer -->
        <?php require 'includes/footer.php'; ?>

        <?php
        // ______________/ Fermeture de la connexion BDD \_____________________
        /// On ferme la connexion a la BD
        if (isset($conn)) {
            mysqli_close($conn);
        }
        ?>

    <script> ////////////////////////////////////////// JAVASCRIPT ///////////////////////////////////////////
    document.addEventListener('DOMContentLoaded', function() {
        const messagesDisplay = document.getElementById('messages-display');
        if (messagesDisplay) {
            messagesDisplay.scrollTop = messagesDisplay.scrollHeight; /// Scroll to bottom on load
        }

        const sendMessageBtn = document.getElementById('send-message-btn');
        const messageInput = document.getElementById('message-input');
        const selectedConvId = <?php echo json_encode($selected_conversation_id); ?>; /// Utilisation de json_encode pour plus de sécurité
        const currentUserId = <?php echo json_encode($current_user_id); ?>; /// Utilisation de json_encode

        if (sendMessageBtn && messageInput && selectedConvId > 0) {
            sendMessageBtn.addEventListener('click', sendMessage);
            messageInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault(); /// Empêche le retour à la ligne
                    sendMessage();
                }
            });

            function sendMessage() {
                const messageText = messageInput.value.trim();
                if (messageText === '') return;

                fetch('chat.php', { /// Envoyer à la même page pour traiter le POST AJAX
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=send_message&conv_id=${selectedConvId}&message_text=${encodeURIComponent(messageText)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        messageInput.value = ''; /// Efface l'input après envoi réussi
                        fetchMessages(selectedConvId); /// Rafraîchit les messages
                        updateHeaderUnreadCount(); /// Met à jour le badge du header
                        // Mettre à jour la liste des conversations (pour l'ordre et le nombre de non-lus)
                        // Cela pourrait être fait en rechargeant la partie gauche ou via une autre API call
                        // Pour l'instant, on se contente de rafraîchir les messages et le header
                    } else {
                        alert('Erreur lors de l\'envoi du message: ' + (data.error || 'Erreur inconnue'));
                    }
                })
                .catch(error => {
                    console.error('Erreur réseau ou JSON:', error);
                    alert('Erreur réseau lors de l\'envoi du message.');
                });
            }

            function fetchMessages(convId) {
                fetch(`chat_api.php?action=get_messages&conv_id=${convId}`) /// Utilise chat_api.php pour rafraîchir les messages
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.messages) {
                            messagesDisplay.innerHTML = ''; /// Efface les messages actuels
                            if (data.messages.length === 0) {
                                const noMsgP = document.createElement('p');
                                noMsgP.classList.add('no-messages');
                                noMsgP.textContent = 'Aucun message dans cette conversation.';
                                messagesDisplay.appendChild(noMsgP);
                            } else {
                                data.messages.forEach(msg => {
                                    const bubble = document.createElement('div');
                                    bubble.classList.add('message-bubble');
                                    bubble.classList.add(msg.sender_id == currentUserId ? 'sent' : 'received');

                                    const textP = document.createElement('p');
                                    textP.classList.add('message-text');
                                    textP.innerHTML = nl2br(safe_html_js(msg.message_text)); /// Sécurisation et nl2br

                                    const timeSpan = document.createElement('span');
                                    timeSpan.classList.add('message-time');
                                    timeSpan.textContent = msg.sent_at ? msg.sent_at.substring(11, 16) : '??:??';

                                    bubble.appendChild(textP);
                                    bubble.appendChild(timeSpan);
                                    messagesDisplay.appendChild(bubble);
                                });
                            }
                            messagesDisplay.scrollTop = messagesDisplay.scrollHeight; /// Défile vers le bas
                            updateHeaderUnreadCount(); /// Met à jour le compteur de non lus (car les messages sont maintenant lus)
                        } else {
                            console.error('Erreur chargement messages:', data.error || 'Format de réponse incorrect');
                        }
                    })
                    .catch(error => console.error('Erreur réseau chargement messages:', error));
            }

            function updateHeaderUnreadCount() {
                fetch('chat_api.php?action=get_unread_count')
                    .then(response => response.json())
                    .then(data => {
                        const chatBadge = document.querySelector('.chat-badge');
                        if (data.success && typeof data.unread_count !== 'undefined') {
                            if (data.unread_count > 0) {
                                if (chatBadge) {
                                    chatBadge.textContent = data.unread_count;
                                    chatBadge.style.display = '';
                                } else {
                                    const chatLink = document.querySelector('.chat-link a'); // Cible le lien <a> dans .chat-link
                                    if (chatLink) {
                                        const newBadge = document.createElement('span');
                                        newBadge.classList.add('chat-badge');
                                        newBadge.textContent = data.unread_count;
                                        chatLink.appendChild(newBadge); // Ajoute le badge au lien
                                    }
                                }
                            } else {
                                if (chatBadge) {
                                    // chatBadge.remove(); // Ou masquer
                                    chatBadge.style.display = 'none';
                                }
                            }
                        } else {
                             console.error('Erreur mise à jour badge:', data.error || 'Format de réponse incorrect');
                        }
                    })
                    .catch(error => console.error('Erreur réseau mise à jour badge:', error));
            }

            /// Rafraîchir les messages toutes les 5 secondes (polling)
            setInterval(() => {
                if (document.visibilityState === 'visible' && selectedConvId > 0) { // Ne rafraîchir que si la page est visible
                    fetchMessages(selectedConvId);
                }
            }, 5000); // 5 secondes

            /// Helper pour nl2br (simule la fonction PHP en JS)
            function nl2br(str) {
                if (typeof str === 'undefined' || str === null) {
                    return '';
                }
                return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1<br>$2');
            }

            /// Helper pour safe_html (simule la fonction PHP en JS)
            function safe_html_js(str) {
                if (typeof str === 'undefined' || str === null) {
                    return '';
                }
                const div = document.createElement('div');
                div.appendChild(document.createTextNode(str));
                return div.innerHTML;
            }
        }
    });
    </script>

    <style> /* ////////////////////////////////////////// CSS /////////////////////////////////////////////// */
        /* Styles spécifiques pour la page chat.php */
        .chat-page-body {
            display: flex;
            flex-direction: column;
            min-height: 100vh; /* Assure que la page prend au moins toute la hauteur du viewport */
        }
        .chat-main {
            flex-grow: 1; /* Permet au contenu principal de prendre l'espace disponible */
            padding: 20px;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center; /* Centre le conteneur du chat */
        }
        .chat-container {
            display: flex;
            width: 100%;
            max-width: 1200px; /* Largeur maximale pour le chat */
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            overflow: hidden; /* Cache les débordements */
            min-height: 700px; /* Hauteur minimale pour un affichage agréable */
        }
        .conversation-list {
            flex: 0 0 300px; /* Largeur fixe pour la liste des conversations */
            border-right: 1px solid #e0e0e0;
            padding: 20px 0;
            background-color: #f8f9fa;
            overflow-y: auto; /* Scroll si trop de conversations */
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
            flex: 1; /* Prend l'espace restant */
            display: flex;
            flex-direction: column;
            min-width: 0; /* Important pour flexbox pour permettre le rétrécissement */
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
            flex-grow: 1; /* Permet à la zone de messages de prendre l'espace */
            padding: 20px;
            overflow-y: auto; /* Scroll si trop de messages */
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .no-messages {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            margin: auto; /* Centre le message s'il est seul */
        }
        .message-bubble {
            max-width: 70%;
            padding: 10px 15px;
            border-radius: 18px;
            line-height: 1.4;
            font-size: 0.95rem;
            position: relative;
            word-wrap: break-word; /* Casse les mots longs */
        }
        .message-bubble p.message-text { /* Cible plus spécifiquement le paragraphe du message */
            margin: 0;
            padding-bottom: 5px; /* Espace pour l'heure */
        }
        .message-bubble .message-time {
            font-size: 0.75rem;
            color: rgba(0,0,0,0.5);
            display: block;
            text-align: right;
            margin-top: 5px;
        }
        .message-bubble.sent {
            background-color: #e0f7fa; /* Bleu clair pour messages envoyés */
            align-self: flex-end; /* Aligner à droite */
            border-bottom-right-radius: 4px; /* Coin carré en bas à droite */
        }
        .message-bubble.received {
            background-color: #f1f0f0; /* Gris clair pour messages reçus */
            align-self: flex-start; /* Aligner à gauche */
            border-bottom-left-radius: 4px; /* Coin carré en bas à gauche */
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
            resize: none; /* Empêche le redimensionnement vertical */
            min-height: 50px; /* Hauteur minimale du textarea */
            max-height: 150px; /* Hauteur maximale avant scroll interne */
            box-sizing: border-box;
            transition: border-color 0.2s ease;
            overflow-y: auto; /* Permet le scroll si le texte dépasse max-height */
        }
        .message-input-area textarea:focus {
            outline: none;
            border-color: #0a7abf;
        }
        .message-input-area button {
            background-color: #0a7abf;
            color: white;
            border: none;
            border-radius: 18px;
            padding: 0 20px; /* Ajustement du padding pour s'aligner avec la hauteur du textarea */
            height: 50px; /* S'assurer que le bouton a une hauteur fixe */
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: background-color 0.2s ease;
            align-self: flex-end; /* Aligne le bouton en bas si le textarea grandit */
        }
        .message-input-area button:hover {
            background-color: #075c92;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .chat-container {
                flex-direction: column;
                min-height: calc(100vh - 40px); /* Prend la hauteur du viewport moins le padding du main */
                max-height: calc(100vh - 40px);
            }
            .conversation-list {
                flex: none; /* Ne grandit/rétrécit pas */
                width: 100%;
                border-right: none;
                border-bottom: 1px solid #e0e0e0;
                max-height: 200px; /* Limite la hauteur de la liste sur mobile */
                min-height: 100px; /* Hauteur minimale pour voir quelques conversations */
            }
            .chat-window {
                flex: 1; /* Prend le reste de la place disponible */
                min-height: 0; /* Permet au contenu de rétrécir */
            }
            .chat-main {
                padding: 0; /* Enlève le padding sur mobile pour utiliser toute la largeur/hauteur */
            }
             .chat-container {
                border-radius: 0; /* Pas de coins arrondis en plein écran */
            }
        }
    </style>
</body>
</html>