<?php /////////////////////////////////////////////// PHP //////////////////////////////////////////

    // ______________/ Gestion de la session \_____________________
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }


    // ______________/ Fonctions Utilitaires \_____________________

    /// Fonction pour échapper les caractères HTML
    if (!function_exists('safe_html_header')) {
        function safe_html_header($value) {
            return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        }
    }


    // ______________/ Logique du Header \_____________________

    /// Récupération du terme de recherche pour la barre (si présent dans l'URL)
    $header_search_query = isset($_GET['recherche']) ? $_GET['recherche'] : '';

?>

<!DOCTYPE html> <!-- ////////////////////////////////////////// HTML ///////////////////////////////////////////-->
<html lang="fr">
<!-- Pas de head ici, car c'est un fichier 'header.php' inclus, le <head> sera dans la page principale -->
<body> <!-- Le body s'ouvrira dans la page principale, on assume que ce header est inclus DANS le body -->

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
                <?php // ______________/ Section ADMIN \_____________________ ?>
                <?php
                /// Affichage conditionnel du lien ADMIN
                if (isset($_SESSION["user_id"]) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Admin') {
                    echo '<li><a href="admin_panel.php">ADMIN</a></li>';
                }
                ?>
            </ul>
        </div>

        <div class="nav-center">
            <?php // ______________/ Barre de Recherche \_____________________ ?>
            <form class="search-form" action="recherche.php" method="post">
                <input type="text" name="recherche" placeholder="Rechercher sur le site..." value="<?= safe_html_header($header_search_query) ?>">
                <button type="submit">Rechercher</button>
            </form>
        </div>

        <div class="nav-right">
            <?php // ______________/ Espace Utilisateur (Chat & Compte) \_____________________ ?>
            <?php if (isset($_SESSION["user_id"])) : ?>
                <?php
                // ______________/ Compteur de messages non lus (MySQLi) \_____________________

                /// Initialisation du compteur
                $unread_messages_count = 0;
                $conn_chat = null; // Initialisation pour la fermeture conditionnelle

                /// On se connecte a la base de donne pour le chat
                $db_host_chat = 'localhost';
                $db_user_chat = 'root';
                $db_pass_chat = '';
                $db_name_chat = 'base_donne_web';
                $conn_chat = mysqli_connect($db_host_chat, $db_user_chat, $db_pass_chat, $db_name_chat);

                /// Vérification de la connexion MySQLi
                if (!$conn_chat) {
                    error_log("Erreur de connexion BDD (chat): " . mysqli_connect_error());
                    // En cas d'erreur, on garde $unread_messages_count à 0
                } else {
                    mysqli_set_charset($conn_chat, 'utf8');

                    $current_user_id = $_SESSION["user_id"];
                    $count1 = 0;
                    $count2 = 0;

                    /// Requête pour les messages non lus où l'utilisateur est user1
                    $sql_unread_user1 = "SELECT SUM(user1_unread_count) AS total_unread FROM conversations WHERE user1_id = ?";
                    $stmt_unread_user1 = mysqli_prepare($conn_chat, $sql_unread_user1);

                    if ($stmt_unread_user1) {
                        mysqli_stmt_bind_param($stmt_unread_user1, "i", $current_user_id);
                        mysqli_stmt_execute($stmt_unread_user1);
                        $result_user1 = mysqli_stmt_get_result($stmt_unread_user1);
                        $row_user1 = mysqli_fetch_assoc($result_user1);
                        if ($row_user1 && $row_user1['total_unread'] !== null) {
                            $count1 = (int)$row_user1['total_unread'];
                        }
                        mysqli_stmt_close($stmt_unread_user1);
                    } else {
                        error_log("Erreur de préparation de la requête user1 (chat): " . mysqli_error($conn_chat));
                    }

                    /// Requête pour les messages non lus où l'utilisateur est user2
                    $sql_unread_user2 = "SELECT SUM(user2_unread_count) AS total_unread FROM conversations WHERE user2_id = ?";
                    $stmt_unread_user2 = mysqli_prepare($conn_chat, $sql_unread_user2);

                    if ($stmt_unread_user2) {
                        mysqli_stmt_bind_param($stmt_unread_user2, "i", $current_user_id);
                        mysqli_stmt_execute($stmt_unread_user2);
                        $result_user2 = mysqli_stmt_get_result($stmt_unread_user2);
                        $row_user2 = mysqli_fetch_assoc($result_user2);
                        if ($row_user2 && $row_user2['total_unread'] !== null) {
                            $count2 = (int)$row_user2['total_unread'];
                        }
                        mysqli_stmt_close($stmt_unread_user2);
                    } else {
                        error_log("Erreur de préparation de la requête user2 (chat): " . mysqli_error($conn_chat));
                    }

                    $unread_messages_count = $count1 + $count2;

                    /// On ferme la connexion a la BD
                    mysqli_close($conn_chat);
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
                        <span class="account-text"><?php echo safe_html_header($_SESSION["user_prenom"]); ?></span>
                    </div>
                </a>
            <?php else : ?>
                <?php // ______________/ Utilisateur non connecté \_____________________ ?>
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


<style> /* ////////////////////////////////////////// CSS /////////////////////////////////////////// */

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

<!-- Pas de </body> ou </html> ici, car c'est un fichier inclus -->