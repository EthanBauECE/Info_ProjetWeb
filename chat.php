<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Rediriger si l'utilisateur n'est pas connecté
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php?redirect=" . urlencode('chat.php'));
    exit();
}

require 'includes/head.php';
require 'includes/header.php'; // Pour inclure le header avec le badge de chat

function safe_html($value) {
    return $value !== null ? htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') : '';
}

$current_user_id = $_SESSION["user_id"];
$selected_conversation_id = isset($_GET['conv_id']) ? intval($_GET['conv_id']) : 0;
$target_user_id = isset($_GET['target_id']) ? intval($_GET['target_id']) : 0; // Pour démarrer une nouvelle conversation

// Connexion à la base de données
$pdo = null;
try {
    $pdo = new PDO("mysql:host=localhost;dbname=base_donne_web;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// --- Logique pour démarrer une nouvelle conversation si target_id est passé ---
if ($target_user_id > 0 && $target_user_id !== $current_user_id) {
    // Déterminer user1_id et user2_id de manière canonique (plus petit d'abord)
    $u1 = min($current_user_id, $target_user_id);
    $u2 = max($current_user_id, $target_user_id);

    // Vérifier si une conversation existe déjà
    $stmt_check_conv = $pdo->prepare("SELECT ID FROM conversations WHERE user1_id = ? AND user2_id = ?");
    $stmt_check_conv->execute([$u1, $u2]);
    $existing_conv = $stmt_check_conv->fetch();

    if ($existing_conv) {
        $selected_conversation_id = $existing_conv['ID'];
    } else {
        // Créer une nouvelle conversation
        $pdo->beginTransaction();
        try {
            $stmt_create_conv = $pdo->prepare("INSERT INTO conversations (user1_id, user2_id) VALUES (?, ?)");
            $stmt_create_conv->execute([$u1, $u2]);
            $selected_conversation_id = $pdo->lastInsertId();
            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Erreur lors de la création de la conversation: " . $e->getMessage());
            $selected_conversation_id = 0; // Empêche d'afficher une conversation non créée
        }
    }
    // Rediriger pour nettoyer l'URL et passer à la conversation
    if ($selected_conversation_id > 0) {
        header("Location: chat.php?conv_id=" . $selected_conversation_id);
        exit();
    }
}

// --- Récupérer la liste des conversations ---
$conversations = [];
try {
    $stmt_conversations = $pdo->prepare("
        SELECT 
            c.ID AS conversation_id,
            CASE WHEN c.user1_id = ? THEN u2.ID ELSE u1.ID END AS other_user_id,
            CASE WHEN c.user1_id = ? THEN u2.Prenom ELSE u1.Prenom END AS other_user_prenom,
            CASE WHEN c.user1_id = ? THEN u2.Nom ELSE u1.Nom END AS other_user_nom,
            CASE WHEN c.user1_id = ? THEN u2.TypeCompte ELSE u1.TypeCompte END AS other_user_type_compte,
            -- Récupère la spécialité du personnel si applicable
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
    ");
    // Les 8 paramètres : un pour chaque CASE WHEN c.user1_id = ?, et deux pour la clause WHERE
    $stmt_conversations->execute([
        $current_user_id, // other_user_id
        $current_user_id, // other_user_prenom
        $current_user_id, // other_user_nom
        $current_user_id, // other_user_type_compte
        $current_user_id, // other_user_specialty
        $current_user_id, // unread_count
        $current_user_id, // WHERE user1_id
        $current_user_id  // OR user2_id
    ]);
    $conversations = $stmt_conversations->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des conversations: " . $e->getMessage());
    // Affiche une erreur plus visible si la requête des conversations échoue
    echo "<p style='color:red; text-align: center; padding: 20px;'>Erreur lors du chargement des conversations: " . safe_html($e->getMessage()) . "</p>";
}

// --- Récupérer les messages de la conversation sélectionnée ---
$messages = [];
$selected_conversation_partner_name = '';

if ($selected_conversation_id > 0) {
    try {
        // Vérifier que l'utilisateur fait bien partie de cette conversation
        $stmt_verify_conv = $pdo->prepare("SELECT user1_id, user2_id FROM conversations WHERE ID = ?");
        $stmt_verify_conv->execute([$selected_conversation_id]);
        $conv_check = $stmt_verify_conv->fetch();

        if ($conv_check && ($conv_check['user1_id'] == $current_user_id || $conv_check['user2_id'] == $current_user_id)) {
            // Déterminer le partenaire de conversation
            $partner_id = ($conv_check['user1_id'] == $current_user_id) ? $conv_check['user2_id'] : $conv_check['user1_id'];
            // Récupérer les détails de l'utilisateur partenaire
            $stmt_partner_details = $pdo->prepare("SELECT u.Prenom, u.Nom, u.TypeCompte, up.Type AS specialty FROM utilisateurs u LEFT JOIN utilisateurs_personnel up ON u.ID = up.ID WHERE u.ID = ?");
            $stmt_partner_details->execute([$partner_id]);
            $partner_info = $stmt_partner_details->fetch();

            if ($partner_info) {
                $prenom_partner = safe_html($partner_info['Prenom']);
                $nom_partner = safe_html($partner_info['Nom']);
                $type_compte_partner = safe_html($partner_info['TypeCompte']);
                $specialty_partner = safe_html($partner_info['specialty']);

                $selected_conversation_partner_name = '';

                // Priorité au Prénom Nom si disponibles
                if (!empty($prenom_partner) && !empty($nom_partner)) {
                    $selected_conversation_partner_name = $prenom_partner . ' ' . $nom_partner;
                } elseif (!empty($prenom_partner)) {
                    $selected_conversation_partner_name = $prenom_partner;
                } elseif (!empty($nom_partner)) {
                    $selected_conversation_partner_name = $nom_partner;
                } else {
                    $selected_conversation_partner_name = 'Utilisateur ID: ' . $partner_id;
                }

                // Ajouter les informations entre parenthèses
                if ($type_compte_partner === 'Personnel' && !empty($specialty_partner)) {
                    $selected_conversation_partner_name .= ' (' . $specialty_partner . ')';
                } elseif (!empty($type_compte_partner)) {
                    $selected_conversation_partner_name .= ' (' . $type_compte_partner . ')';
                }
            } else {
                $selected_conversation_partner_name = 'Utilisateur inconnu';
            }

            // Récupérer les messages
            $stmt_messages = $pdo->prepare("SELECT sender_id, message_text, sent_at FROM messages WHERE conversation_id = ? ORDER BY sent_at ASC");
            $stmt_messages->execute([$selected_conversation_id]);
            $messages = $stmt_messages->fetchAll();

            // Marquer les messages comme lus pour l'utilisateur actuel
            if ($conv_check['user1_id'] == $current_user_id) {
                $stmt_mark_read = $pdo->prepare("UPDATE conversations SET user1_unread_count = 0 WHERE ID = ?");
            } else {
                $stmt_mark_read = $pdo->prepare("UPDATE conversations SET user2_unread_count = 0 WHERE ID = ?");
            }
            $stmt_mark_read->execute([$selected_conversation_id]);

        } else {
            $selected_conversation_id = 0; // Conversation invalide ou non autorisée
        }
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des messages: " . $e->getMessage());
        $selected_conversation_id = 0;
    }
}

// --- Traitement de l'envoi de message via AJAX ---
// Ce bloc doit OBLIGATOIREMENT être avant tout output HTML,
// et doit exit() après avoir renvoyé sa réponse JSON.
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    header('Content-Type: application/json'); // Réponse JSON pour AJAX

    $message_text = trim($_POST['message_text'] ?? '');
    $conv_id = intval($_POST['conv_id'] ?? 0);

    if (empty($message_text) || $conv_id === 0) {
        echo json_encode(['success' => false, 'error' => 'Message vide ou conversation invalide.']);
        exit();
    }

    try {
        $pdo->beginTransaction();

        // Vérifier que l'utilisateur fait bien partie de cette conversation
        $stmt_verify_conv = $pdo->prepare("SELECT user1_id, user2_id FROM conversations WHERE ID = ?");
        $stmt_verify_conv->execute([$conv_id]);
        $conv_check = $stmt_verify_conv->fetch();

        if (!$conv_check || ($conv_check['user1_id'] != $current_user_id && $conv_check['user2_id'] != $current_user_id)) {
            throw new Exception("Non autorisé à envoyer des messages dans cette conversation.");
        }

        // Insérer le message
        $stmt_insert_msg = $pdo->prepare("INSERT INTO messages (conversation_id, sender_id, message_text) VALUES (?, ?, ?)");
        $stmt_insert_msg->execute([$conv_id, $current_user_id, $message_text]);

        // Mettre à jour last_message_at et le compteur de non lus pour le RECEVEUR
        $receiver_id = ($conv_check['user1_id'] == $current_user_id) ? $conv_check['user2_id'] : $conv_check['user1_id'];
        $update_count_column = ($conv_check['user1_id'] == $receiver_id) ? 'user1_unread_count' : 'user2_unread_count';

        $stmt_update_conv = $pdo->prepare("UPDATE conversations SET last_message_at = NOW(), $update_count_column = $update_count_column + 1 WHERE ID = ?");
        $stmt_update_conv->execute([$conv_id]);

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Erreur envoi message: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit(); // IMPORTANT : Arrête l'exécution du script après l'envoi de la réponse JSON
}

?>

<body class="chat-page-body">
    <main class="chat-main">
        <div class="chat-container">
            <div class="conversation-list">
                <h2>Discussions</h2>
                <?php if (empty($conversations)): ?>
                    <p class="no-conversations">Aucune conversation. <br>Utilisez les boutons "Communiquer" sur les profils des médecins ou laboratoires.</p>
                <?php else: ?>
                    <?php foreach ($conversations as $conv): ?>
                        <?php
                            // Logique pour construire le nom d'affichage de l'interlocuteur
                            $prenom = safe_html($conv['other_user_prenom']);
                            $nom = safe_html($conv['other_user_nom']);
                            $type_compte = safe_html($conv['other_user_type_compte']);
                            $specialty = safe_html($conv['other_user_specialty']);

                            $display_name_conv = '';

                            // Priorité au Prénom Nom si disponibles
                            if (!empty($prenom) && !empty($nom)) {
                                $display_name_conv = $prenom . ' ' . $nom;
                            } elseif (!empty($prenom)) { // Si seulement le prénom existe
                                $display_name_conv = $prenom;
                            } elseif (!empty($nom)) { // Si seulement le nom existe
                                $display_name_conv = $nom;
                            } else { // Fallback si ni prénom ni nom n'est disponible (improbable si utilisateurs.ID est FK)
                                $display_name_conv = 'Utilisateur ID: ' . $conv['other_user_id'];
                            }

                            // Ajouter les informations entre parenthèses (spécialité ou type de compte)
                            if ($type_compte === 'Personnel' && !empty($specialty)) {
                                // Pour le personnel avec une spécialité définie
                                $display_name_conv .= ' (' . $specialty . ')';
                            } elseif (!empty($type_compte)) {
                                // Pour les clients, les admins, ou le personnel sans spécialité définie
                                // Assure que le type de compte (Client, Admin, Personnel) est toujours affiché.
                                $display_name_conv .= ' (' . $type_compte . ')';
                            }
                        ?>
                        <a href="chat.php?conv_id=<?php echo safe_html($conv['conversation_id']); ?>" 
                           class="conversation-item <?php echo ($selected_conversation_id == $conv['conversation_id']) ? 'active' : ''; ?>">
                            <span class="partner-name"><?php echo $display_name_conv; ?></span>
                            <?php if ($conv['unread_count'] > 0): ?>
                                <span class="unread-badge"><?php echo safe_html($conv['unread_count']); ?></span>
                            <?php endif; ?>
                            <span class="last-message-time"><?php echo date('H:i', strtotime($conv['last_message_at'])); ?></span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="chat-window">
                <?php if ($selected_conversation_id > 0): ?>
                    <div class="chat-header">
                        <h3>Conversation avec <?php echo safe_html($selected_conversation_partner_name); ?></h3>
                    </div>
                    <div class="messages-display" id="messages-display">
                        <?php if (empty($messages)): ?>
                            <p class="no-messages">Aucun message dans cette conversation.</p>
                        <?php else: ?>
                            <?php foreach ($messages as $msg): ?>
                                <div class="message-bubble <?php echo ($msg['sender_id'] == $current_user_id) ? 'sent' : 'received'; ?>">
                                    <p class="message-text"><?php echo nl2br(safe_html($msg['message_text'])); ?></p>
                                    <span class="message-time"><?php echo date('H:i', strtotime($msg['sent_at'])); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="message-input-area">
                        <textarea id="message-input" placeholder="Écrivez votre message..." rows="3"></textarea>
                        <button id="send-message-btn">Envoyer</button>
                    </div>
                <?php else: ?>
                    <div class="no-chat-selected">
                        <p>Sélectionnez une conversation ou démarrez-en une nouvelle.</p>
                        <img src="./images/chat_placeholder.png" alt="Sélectionnez un chat" style="max-width: 200px; margin-top: 20px;">
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php require 'includes/footer.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const messagesDisplay = document.getElementById('messages-display');
        if (messagesDisplay) {
            messagesDisplay.scrollTop = messagesDisplay.scrollHeight; // Scroll to bottom on load
        }

        const sendMessageBtn = document.getElementById('send-message-btn');
        const messageInput = document.getElementById('message-input');
        const selectedConvId = <?php echo $selected_conversation_id; ?>;
        const currentUserId = <?php echo $current_user_id; ?>;

        if (sendMessageBtn && messageInput && selectedConvId > 0) {
            sendMessageBtn.addEventListener('click', sendMessage);
            messageInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault(); // Empêche le retour à la ligne
                    sendMessage();
                }
            });

            function sendMessage() {
                const messageText = messageInput.value.trim();
                if (messageText === '') return;

                fetch('chat.php', { // Envoyer à la même page pour traiter le POST AJAX
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=send_message&conv_id=${selectedConvId}&message_text=${encodeURIComponent(messageText)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        messageInput.value = ''; // Efface l'input après envoi réussi
                        fetchMessages(selectedConvId); // Rafraîchit les messages
                        updateHeaderUnreadCount(); // Met à jour le badge du header
                    } else {
                        // Si le PHP a renvoyé success: false (ex: message vide, conversation invalide)
                        alert('Erreur lors de l\'envoi du message: ' + data.error);
                    }
                })
            
            }

            function fetchMessages(convId) {
                fetch(`chat_api.php?action=get_messages&conv_id=${convId}`) // Utilise chat_api.php pour rafraîchir les messages
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            messagesDisplay.innerHTML = ''; // Efface les messages actuels
                            data.messages.forEach(msg => {
                                const bubble = document.createElement('div');
                                bubble.classList.add('message-bubble');
                                bubble.classList.add(msg.sender_id == currentUserId ? 'sent' : 'received');
                                bubble.innerHTML = `<p class="message-text">${nl2br(safe_html(msg.message_text))}</p><span class="message-time">${msg.sent_at.substring(11, 16)}</span>`;
                                messagesDisplay.appendChild(bubble);
                            });
                            messagesDisplay.scrollTop = messagesDisplay.scrollHeight; // Défile vers le bas
                            updateHeaderUnreadCount(); // Met à jour le compteur de non lus (car les messages sont maintenant lus)
                        } else {
                            console.error('Erreur chargement messages:', data.error);
                        }
                    })
                    .catch(error => console.error('Erreur réseau chargement messages:', error));
            }

            // Fonction pour mettre à jour le badge de la barre de navigation
            function updateHeaderUnreadCount() {
                fetch('chat_api.php?action=get_unread_count')
                    .then(response => response.json())
                    .then(data => {
                        const chatBadge = document.querySelector('.chat-badge');
                        if (data.unread_count > 0) {
                            if (chatBadge) {
                                chatBadge.textContent = data.unread_count;
                            } else {
                                // Crée le badge s'il n'existe pas
                                const chatLink = document.querySelector('.chat-link');
                                if (chatLink) {
                                    const newBadge = document.createElement('span');
                                    newBadge.classList.add('chat-badge');
                                    newBadge.textContent = data.unread_count;
                                    chatLink.appendChild(newBadge);
                                }
                            }
                        } else {
                            if (chatBadge) {
                                chatBadge.remove(); // Supprime le badge s'il n'y a pas de messages non lus
                            }
                        }
                    })
                    .catch(error => console.error('Erreur mise à jour badge:', error));
            }

            // Rafraîchir les messages toutes les 5 secondes (polling)
            setInterval(() => {
                if (selectedConvId > 0) {
                    fetchMessages(selectedConvId);
                }
            }, 5000); // 5 secondes

            // Helper pour nl2br (simule la fonction PHP)
            function nl2br(str) {
                return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1<br>$2');
            }
             // Helper pour safe_html (simule la fonction PHP)
             function safe_html(str) {
                const div = document.createElement('div');
                div.appendChild(document.createTextNode(str));
                return div.innerHTML;
            }

        }
    });
    </script>

    <style>
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
        }
        .conversation-item .last-message-time {
            font-size: 0.8rem;
            color: #6c757d;
            margin-left: 10px;
        }

        .chat-window {
            flex: 1; /* Prend l'espace restant */
            display: flex;
            flex-direction: column;
        }
        .chat-header {
            padding: 15px 20px;
            background-color: #0a7abf;
            color: white;
            font-size: 1.2rem;
            border-bottom: 1px solid #0056b3;
        }
        .chat-header h3 {
            margin: 0;
            font-size: 1.3rem;
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
            margin-top: auto; /* Aligne le message en bas si pas de messages */
        }
        .message-bubble {
            max-width: 70%;
            padding: 10px 15px;
            border-radius: 18px;
            line-height: 1.4;
            font-size: 0.95rem;
            position: relative;
        }
        .message-bubble p {
            margin: 0;
            padding-bottom: 5px; /* Espace pour l'heure */
            word-wrap: break-word; /* Casse les mots longs */
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
        }
        .message-input-area textarea {
            flex-grow: 1;
            border: 1px solid #ccc;
            border-radius: 18px;
            padding: 10px 15px;
            font-size: 1rem;
            resize: none; /* Empêche le redimensionnement vertical */
            min-height: 50px;
            box-sizing: border-box;
            transition: border-color 0.2s ease;
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
            padding: 10px 20px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: background-color 0.2s ease;
        }
        .message-input-area button:hover {
            background-color: #075c92;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .chat-container {
                flex-direction: column;
                min-height: auto;
            }
            .conversation-list {
                flex: none;
                width: 100%;
                border-right: none;
                border-bottom: 1px solid #e0e0e0;
                max-height: 300px; /* Limite la hauteur de la liste sur mobile */
            }
            .chat-window {
                flex: 1;
            }
            .chat-container {
                max-width: 100%;
            }
        }
    </style>
</body>
</html>