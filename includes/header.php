<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fonction pour échapper les caractères HTML (déjà présente dans ton code précédent, je l'ajoute ici pour s'assurer qu'elle est bien définie si ce fichier est le seul à être inclus directement)
if (!function_exists('safe_html_header')) {
    function safe_html_header($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

// Récupère le terme de recherche si présent dans l'URL (pour que la barre reste remplie après une recherche)
// La page recherche.php redirige en GET, donc on lit le paramètre GET ici.
$header_search_query = isset($_GET['recherche']) ? $_GET['recherche'] : '';

?>
<header>
    <div class="header-container">
        <img src="./images/medicare_logo.png" alt="Logo Medicare" class="logo">
        <h1>Medicare : Services Médicaux</h1>
    </div>

    <nav>
        <div class="nav-left">
            <ul>
                <li><a href="index.php">Accueil</a></li>
                <li class="dropdown">
                    <a href="parcourir.php">Tout Parcourir</a>
                    <ul class="dropdown-menu">
                        <li><a href="medecine_general.php">Médecine générale</a></li>
                        <li><a href="medecins_special.php">Médecins spécialisés</a></li>
                        <li><a href="laboratoire.php">Laboratoire de biologie médicale</a></li>
                    </ul>
                </li>
                <li><a href="rdv.php">Rendez-vous</a></li>
                <?php
                // La condition correcte pour afficher le bouton ADMIN
                // Assurez-vous que la valeur 'Admin' (avec le A majuscule) correspond à ce qui est stocké dans la session
                if (isset($_SESSION["user_id"]) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Admin') {
                    echo '<li><a href="admin_panel.php">ADMIN</a></li>';
                }
                ?>
            </ul>
        </div>

        <div class="nav-center">
            <!-- DÉBUT DE LA MODIFICATION -->
            <form class="search-form" action="recherche.php" method="post">
                <input type="text" name="recherche" placeholder="Rechercher sur le site..." value="<?= safe_html_header($header_search_query) ?>">
                <button type="submit">Rechercher</button>
            </form>
            <!-- FIN DE LA MODIFICATION -->
        </div>

        <div class="nav-right">
    <?php if (isset($_SESSION["user_id"])) : ?>
        <?php
        // Compter les messages non lus pour l'utilisateur actuel
        $unread_messages_count = 0;
        try {
            $pdo_chat = new PDO('mysql:host=localhost;dbname=base_donne_web;charset=utf8', 'root', '');
            $pdo_chat->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $current_user_id = $_SESSION["user_id"];

            // Requête pour les messages non lus où l'utilisateur est user1
            $stmt_unread_user1 = $pdo_chat->prepare("SELECT SUM(user1_unread_count) FROM conversations WHERE user1_id = ?");
            $stmt_unread_user1->execute([$current_user_id]);
            $count1 = (int)$stmt_unread_user1->fetchColumn();

            // Requête pour les messages non lus où l'utilisateur est user2
            $stmt_unread_user2 = $pdo_chat->prepare("SELECT SUM(user2_unread_count) FROM conversations WHERE user2_id = ?");
            $stmt_unread_user2->execute([$current_user_id]);
            $count2 = (int)$stmt_unread_user2->fetchColumn();

            $unread_messages_count = $count1 + $count2;

        } catch (PDOException $e) {
            error_log("Erreur de récupération des messages non lus: " . $e->getMessage());
            // En cas d'erreur, ne pas afficher de nombre pour éviter un bug visuel
            $unread_messages_count = 0; 
        }
        ?>
        <a href="chat.php" class="chat-link" title="Discussions">
            <img src="./images/chat.png" alt="Icône Chat" class="chat-icon">
            <?php if ($unread_messages_count > 0): ?>
                <span class="chat-badge"><?php echo $unread_messages_count; ?></span>
            <?php endif; ?>
        </a>
        <a href="profil.php" class="account-profile-link">
            <div class="account-icon">
                <img src="./images/monCompte.png" alt="Icône Compte">
                <span class="account-text"><?php echo htmlspecialchars($_SESSION["user_prenom"]); ?></span>
            </div>
        </a>
    <?php else : ?>
        <a href="login.php" class="account-profile-link">
            <div class="account-icon">
                <img src="./images/monCompte.png" alt="Icône Compte">
                <span class="account-text">Connexion</span>
            </div>
        </a>
    <?php endif; ?>
</div>
    </nav>
</header>


<style>

/* Styles pour le chat link et le badge */
.chat-link {
    position: relative;
    margin-right: 20px; /* Espace entre l'icône chat et l'icône utilisateur */
    display: flex;
    align-items: center;
    text-decoration: none;
}

.chat-icon {
    height: 40px; /* Ajustez la taille de l'icône */
    width: 40px;
    border-radius: 50%; /* Pour une icône ronde si souhaité */
    transition: transform 0.2s ease;
}

.chat-icon:hover {
    transform: scale(1.1);
}

.chat-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background-color: #dc3545; /* Couleur rouge pour notification */
    color: white;
    font-size: 0.75rem;
    font-weight: bold;
    padding: 3px 7px;
    border-radius: 50%;
    min-width: 20px; /* Assure une taille minimale */
    text-align: center;
    line-height: 1;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}

</style>