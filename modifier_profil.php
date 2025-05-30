<?php /////////////////////////////////////////////// PHP //////////////////////////////////////////

    // ______________/ Démarrage de la session et vérification de la connexion utilisateur \_____________________
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    /// Redirection si l'utilisateur n'est pas connecté
    if (!isset($_SESSION["user_id"])) {
        header("Location: login.php");
        exit();
    }

    // ______________/ Connexion à la base de données \_____________________
    $db_host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'base_donne_web';
    $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

    /// Vérification de la connexion MySQLi
    if (!$conn) {
        error_log("Erreur de connexion BDD: " . mysqli_connect_error()); // Log for admin
        $_SESSION['profile_error'] = "Erreur de connexion à la base de données. Veuillez réessayer plus tard.";
        header("Location: profil.php"); // Redirect to profile page, which should display this session error
        exit();
    }
    mysqli_set_charset($conn, 'utf8');

    $user_id = $_SESSION["user_id"];
    $user_data = [];
    $form_errors = [];
    $profile_error_message = ''; // For database operation errors displayed to user
    $profile_success_message = ''; // For success messages

    // ______________/ Fonction de sécurité HTML \_____________________
    /// S'assure que htmlspecialchars reçoit toujours une chaîne
    function safe_html($value) {
        return $value !== null ? htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') : '';
    }


    // ______________/ Récupération des données utilisateur (pour pré-remplir le formulaire) \_____________________
    $sql_fetch = "
        SELECT
            u.ID, u.Nom, u.Prenom, u.Email, u.TypeCompte,
            a.ID AS AdresseID, a.Adresse AS AdresseLigne, a.Ville, a.CodePostal, a.InfosComplementaires,
            uc.Telephone AS ClientTelephone, uc.CarteVitale,
            up.Telephone AS PersonnelTelephone, up.Description, up.Type AS Specialite
        FROM utilisateurs u
        LEFT JOIN utilisateurs_client uc ON u.ID = uc.ID AND u.TypeCompte = 'client'
        LEFT JOIN utilisateurs_personnel up ON u.ID = up.ID AND u.TypeCompte = 'personnel'
        LEFT JOIN adresse a ON (
            (u.TypeCompte = 'client' AND uc.ID_Adresse = a.ID) OR
            (u.TypeCompte = 'personnel' AND up.ID_Adresse = a.ID)
        )
        WHERE u.ID = ?
    ";

    $stmt_fetch = mysqli_prepare($conn, $sql_fetch);
    if ($stmt_fetch) {
        mysqli_stmt_bind_param($stmt_fetch, "i", $user_id);
        mysqli_stmt_execute($stmt_fetch);
        $result_fetch = mysqli_stmt_get_result($stmt_fetch);
        $user_data = mysqli_fetch_assoc($result_fetch);
        mysqli_free_result($result_fetch);
        mysqli_stmt_close($stmt_fetch);
    } else {
        error_log("Erreur de préparation de la requête de récupération de profil: " . mysqli_error($conn));
        $profile_error_message = "Erreur lors de la préparation de la récupération des données.";
    }

    if (empty($user_data) && empty($profile_error_message)) { // If no error set yet, but no data
        $_SESSION['profile_error'] = "Impossible de charger les informations de profil.";
        // No need to close $conn here as it might be needed by profil.php or for subsequent operations if we weren't exiting
        mysqli_close($conn);
        header("Location: profil.php");
        exit();
    }
    // If $profile_error_message is set from prepare failure, it will be displayed on the form page.

    $type = $user_data['TypeCompte'] ?? null; // Handle case where user_data might be empty due to fetch error


    // ______________/ Traitement du formulaire de modification \_____________________
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        /// Collecte des données - utilise user_data comme fallback
        $nom = trim($_POST['nom'] ?? $user_data['Nom']);
        $prenom = trim($_POST['prenom'] ?? $user_data['Prenom']);
        $email = trim($_POST['email'] ?? $user_data['Email']);

        /// Champs d'adresse
        $adresse_ligne = trim($_POST['adresse_ligne'] ?? $user_data['AdresseLigne']);
        $ville = trim($_POST['ville'] ?? $user_data['Ville']);
        $code_postal = trim($_POST['code_postal'] ?? $user_data['CodePostal']);
        $infos_complementaires = trim($_POST['infos_complementaires'] ?? $user_data['InfosComplementaires']);
        $adresse_id = $user_data['AdresseID'] ?? null; // ID d'adresse existant

        /// Champs spécifiques au type
        $telephone = '';
        $carte_vitale = '';
        $description = '';
        $specialite = '';

        if ($type === 'client') {
            $telephone = trim($_POST['telephone'] ?? $user_data['ClientTelephone']);
            $carte_vitale = trim($_POST['carte_vitale'] ?? $user_data['CarteVitale']);
        } elseif ($type === 'personnel') {
            $telephone = trim($_POST['telephone'] ?? $user_data['PersonnelTelephone']);
            $description = trim($_POST['description'] ?? $user_data['Description']);
            $specialite = trim($_POST['specialite'] ?? $user_data['Specialite']);
        }

        /// Validation de base
        if (empty($nom)) $form_errors['nom'] = "Le nom est requis.";
        if (empty($prenom)) $form_errors['prenom'] = "Le prénom est requis.";
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $form_errors['email'] = "Email invalide.";

        /// Validation de l'adresse
        if (empty($adresse_ligne)) $form_errors['adresse_ligne'] = "L'adresse (numéro et rue) est requise.";
        if (empty($ville)) $form_errors['ville'] = "La ville est requise.";
        if (empty($code_postal) || !ctype_digit($code_postal) || strlen($code_postal) != 5) $form_errors['code_postal'] = "Code postal invalide (5 chiffres requis).";

        /// Validation spécifique au type
        if ($type === 'client' || $type === 'personnel') {
            if (empty($telephone) || !preg_match('/^0[67]\d{8}$/', $telephone)) {
                $form_errors['telephone'] = "Numéro de téléphone invalide (Ex: 0612345678).";
            }
        }
        if ($type === 'client') {
            if (empty($carte_vitale) || !ctype_digit($carte_vitale) || strlen($carte_vitale) < 13 || strlen($carte_vitale) > 15) {
                $form_errors['carte_vitale'] = "Numéro de carte vitale invalide (13 à 15 chiffres).";
            }
        }

        /// Si pas d'erreurs de validation, on procède à la mise à jour
        if (empty($form_errors)) {
            mysqli_begin_transaction($conn);
            $all_queries_success = true;

            /// Mise à jour de la table utilisateurs
            $sql_update_user = "UPDATE utilisateurs SET Nom = ?, Prenom = ?, Email = ? WHERE ID = ?";
            $stmt_update_user = mysqli_prepare($conn, $sql_update_user);
            if ($stmt_update_user) {
                mysqli_stmt_bind_param($stmt_update_user, "sssi", $nom, $prenom, $email, $user_id);
                if (!mysqli_stmt_execute($stmt_update_user)) {
                    $all_queries_success = false;
                    error_log("Échec maj utilisateurs: " . mysqli_stmt_error($stmt_update_user));
                }
                mysqli_stmt_close($stmt_update_user);
            } else {
                $all_queries_success = false;
                error_log("Erreur préparation maj utilisateurs: " . mysqli_error($conn));
            }


            /// Mise à jour ou Insertion de l'Adresse
            if ($all_queries_success) {
                if ($adresse_id) {
                    $sql_update_address = "UPDATE adresse SET Adresse = ?, Ville = ?, CodePostal = ?, InfosComplementaires = ? WHERE ID = ?";
                    $stmt_update_address = mysqli_prepare($conn, $sql_update_address);
                    if ($stmt_update_address) {
                        mysqli_stmt_bind_param($stmt_update_address, "ssssi", $adresse_ligne, $ville, $code_postal, $infos_complementaires, $adresse_id);
                        if (!mysqli_stmt_execute($stmt_update_address)) {
                            $all_queries_success = false;
                            error_log("Échec maj adresse: " . mysqli_stmt_error($stmt_update_address));
                        }
                        mysqli_stmt_close($stmt_update_address);
                    } else {
                        $all_queries_success = false;
                        error_log("Erreur préparation maj adresse: " . mysqli_error($conn));
                    }
                } else {
                    /// Si l'utilisateur n'avait pas d'adresse, on en crée une
                    $sql_insert_address = "INSERT INTO adresse (Adresse, Ville, CodePostal, InfosComplementaires) VALUES (?, ?, ?, ?)";
                    $stmt_insert_address = mysqli_prepare($conn, $sql_insert_address);
                    if ($stmt_insert_address) {
                        mysqli_stmt_bind_param($stmt_insert_address, "ssss", $adresse_ligne, $ville, $code_postal, $infos_complementaires);
                        if (mysqli_stmt_execute($stmt_insert_address)) {
                            $new_adresse_id = mysqli_insert_id($conn);
                            /// Lier le nouvel ID d'adresse à l'utilisateur
                            if ($type === 'client') {
                                $sql_link_address = "UPDATE utilisateurs_client SET ID_Adresse = ? WHERE ID = ?";
                            } else { // personnel
                                $sql_link_address = "UPDATE utilisateurs_personnel SET ID_Adresse = ? WHERE ID = ?";
                            }
                            $stmt_link_address = mysqli_prepare($conn, $sql_link_address);
                            if ($stmt_link_address) {
                                mysqli_stmt_bind_param($stmt_link_address, "ii", $new_adresse_id, $user_id);
                                if (!mysqli_stmt_execute($stmt_link_address)) {
                                    $all_queries_success = false;
                                    error_log("Échec liaison adresse: " . mysqli_stmt_error($stmt_link_address));
                                }
                                mysqli_stmt_close($stmt_link_address);
                            } else {
                                $all_queries_success = false;
                                error_log("Erreur préparation liaison adresse: " . mysqli_error($conn));
                            }
                        } else {
                            $all_queries_success = false;
                            error_log("Échec insertion adresse: " . mysqli_stmt_error($stmt_insert_address));
                        }
                        mysqli_stmt_close($stmt_insert_address);
                    } else {
                        $all_queries_success = false;
                        error_log("Erreur préparation insertion adresse: " . mysqli_error($conn));
                    }
                }
            }

            /// Mise à jour de la table spécifique au type
            if ($all_queries_success) {
                if ($type === 'client') {
                    $sql_update_client = "UPDATE utilisateurs_client SET Telephone = ?, CarteVitale = ? WHERE ID = ?";
                    $stmt_update_client = mysqli_prepare($conn, $sql_update_client);
                    if ($stmt_update_client) {
                        mysqli_stmt_bind_param($stmt_update_client, "ssi", $telephone, $carte_vitale, $user_id);
                        if (!mysqli_stmt_execute($stmt_update_client)) {
                            $all_queries_success = false;
                            error_log("Échec maj client: " . mysqli_stmt_error($stmt_update_client));
                        }
                        mysqli_stmt_close($stmt_update_client);
                    } else {
                        $all_queries_success = false;
                        error_log("Erreur préparation maj client: " . mysqli_error($conn));
                    }
                } elseif ($type === 'personnel') {
                    $sql_update_personnel = "UPDATE utilisateurs_personnel SET Telephone = ?, Description = ?, Type = ? WHERE ID = ?";
                    $stmt_update_personnel = mysqli_prepare($conn, $sql_update_personnel);
                    if ($stmt_update_personnel) {
                        mysqli_stmt_bind_param($stmt_update_personnel, "sssi", $telephone, $description, $specialite, $user_id);
                        if (!mysqli_stmt_execute($stmt_update_personnel)) {
                            $all_queries_success = false;
                            error_log("Échec maj personnel: " . mysqli_stmt_error($stmt_update_personnel));
                        }
                        mysqli_stmt_close($stmt_update_personnel);
                    } else {
                        $all_queries_success = false;
                        error_log("Erreur préparation maj personnel: " . mysqli_error($conn));
                    }
                }
            }

            /// Finalisation de la transaction
            if ($all_queries_success) {
                mysqli_commit($conn);
                $_SESSION['profile_success'] = "Votre profil a été mis à jour avec succès.";
                mysqli_close($conn);
                header("Location: profil.php");
                exit();
            } else {
                mysqli_rollback($conn);
                $_SESSION['profile_error'] = "Erreur lors de la mise à jour de votre profil. Veuillez réessayer.";
                // Fusionner les données soumises avec user_data pour réafficher en cas d'erreur BDD
                $user_data = array_merge($user_data, $_POST);
                $user_data['AdresseLigne'] = $_POST['adresse_ligne'] ?? $user_data['AdresseLigne'];
                $user_data['Ville'] = $_POST['ville'] ?? $user_data['Ville'];
                $user_data['CodePostal'] = $_POST['code_postal'] ?? $user_data['CodePostal'];
                $user_data['InfosComplementaires'] = $_POST['infos_complementaires'] ?? $user_data['InfosComplementaires'];
            }

        } else {
            /// S'il y a des erreurs de validation, ré-afficher le formulaire avec les données POST
            $user_data = array_merge($user_data, $_POST);
            $user_data['AdresseLigne'] = $_POST['adresse_ligne'] ?? $user_data['AdresseLigne'];
            $user_data['Ville'] = $_POST['ville'] ?? $user_data['Ville'];
            $user_data['CodePostal'] = $_POST['code_postal'] ?? $user_data['CodePostal'];
            $user_data['InfosComplementaires'] = $_POST['infos_complementaires'] ?? $user_data['InfosComplementaires'];
            $profile_error_message = "Veuillez corriger les erreurs dans le formulaire.";
        }
    }

    // ______________/ Récupération des messages de session (après redirection potentielle) \_____________________
    if (isset($_SESSION['profile_error'])) {
        $profile_error_message = $_SESSION['profile_error'];
        unset($_SESSION['profile_error']);
    }
    if (isset($_SESSION['profile_success'])) {
        $profile_success_message = $_SESSION['profile_success'];
        unset($_SESSION['profile_success']);
    }

    // On ne ferme la connexion que si elle n'a pas été fermée après une redirection réussie
    // et si elle est toujours valide.
    // Normalement, à ce stade, si une redirection a eu lieu, le script a déjà exit().
    // Si on arrive ici, c'est qu'on affiche la page.
    // La connexion $conn sera fermée implicitement à la fin du script si elle n'est pas explicitement fermée.
    // Toutefois, pour la propreté, on pourrait la fermer ici ou avant le tag </html>.
    // Mais comme le footer pourrait en avoir besoin (improbable mais possible), on la laisse ouverte.
?>

<!DOCTYPE html> <!-- ////////////////////////////////////////// HTML ///////////////////////////////////////////-->
<html lang="fr">

    <!-- Importation head -->
    <?php require 'includes/head.php'; ?>

    <body>

        <!-- Importation header -->
        <?php require 'includes/header.php'; ?>

        <main class="profile-main">
            <div class="profile-container">
                <h2 class="profile-title">Modifier Mon Profil</h2>

                <!-- Messages d'erreur/succès généraux -->
                <?php if (!empty($profile_error_message)) : ?>
                    <div class="profile-alert error">
                        <p><?php echo safe_html($profile_error_message); ?></p>
                    </div>
                <?php endif; ?>
                <?php if (!empty($profile_success_message)) : ?>
                    <div class="profile-alert success">
                        <p><?php echo safe_html($profile_success_message); ?></p>
                    </div>
                <?php endif; ?>


                <!-- Affichage des erreurs de validation du formulaire -->
                <?php if (!empty($form_errors)): ?>
                    <div class="profile-alert error">
                        <p>Veuillez corriger les erreurs suivantes :</p>
                        <ul>
                            <?php foreach ($form_errors as $error): ?>
                                <li><?= safe_html($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="modifier_profil.php" method="POST" class="profile-edit-form">
                    
                    <!-- ______________/ Informations Générales \_____________________ -->
                    <section class="profile-section">
                        <h3 class="section-title">Informations Générales</h3>
                        <div class="form-group">
                            <label for="nom">Nom :</label>
                            <input type="text" id="nom" name="nom" value="<?= safe_html($user_data['Nom'] ?? '') ?>" required>
                            <?php if (isset($form_errors['nom'])) echo "<p class='error-message'>".safe_html($form_errors['nom'])."</p>"; ?>
                        </div>
                        <div class="form-group">
                            <label for="prenom">Prénom :</label>
                            <input type="text" id="prenom" name="prenom" value="<?= safe_html($user_data['Prenom'] ?? '') ?>" required>
                            <?php if (isset($form_errors['prenom'])) echo "<p class='error-message'>".safe_html($form_errors['prenom'])."</p>"; ?>
                        </div>
                        <div class="form-group">
                            <label for="email">Email :</label>
                            <input type="email" id="email" name="email" value="<?= safe_html($user_data['Email'] ?? '') ?>" required>
                            <?php if (isset($form_errors['email'])) echo "<p class='error-message'>".safe_html($form_errors['email'])."</p>"; ?>
                        </div>
                    </section>

                    <!-- ______________/ Adresse \_____________________ -->
                    <section class="profile-section">
                        <h3 class="section-title">Adresse</h3>
                        <div class="form-group">
                            <label for="adresse_ligne">Adresse (N° et Rue) :</label>
                            <input type="text" id="adresse_ligne" name="adresse_ligne" value="<?= safe_html($user_data['AdresseLigne'] ?? '') ?>" required>
                            <?php if (isset($form_errors['adresse_ligne'])) echo "<p class='error-message'>".safe_html($form_errors['adresse_ligne'])."</p>"; ?>
                        </div>
                        <div class="form-group">
                            <label for="ville">Ville :</label>
                            <input type="text" id="ville" name="ville" value="<?= safe_html($user_data['Ville'] ?? '') ?>" required>
                            <?php if (isset($form_errors['ville'])) echo "<p class='error-message'>".safe_html($form_errors['ville'])."</p>"; ?>
                        </div>
                        <div class="form-group">
                            <label for="code_postal">Code Postal :</label>
                            <input type="text" id="code_postal" name="code_postal" value="<?= safe_html($user_data['CodePostal'] ?? '') ?>" required maxlength="5" pattern="\d{5}" title="Cinq chiffres requis.">
                            <?php if (isset($form_errors['code_postal'])) echo "<p class='error-message'>".safe_html($form_errors['code_postal'])."</p>"; ?>
                        </div>
                        <div class="form-group">
                            <label for="infos_complementaires">Infos Complémentaires (optionnel) :</label>
                            <textarea id="infos_complementaires" name="infos_complementaires"><?= safe_html($user_data['InfosComplementaires'] ?? '') ?></textarea>
                        </div>
                    </section>

                    <!-- ______________/ Informations Spécifiques au Type d'Utilisateur \_____________________ -->
                    <?php if ($type === 'client'): ?>
                        <section class="profile-section">
                            <h3 class="section-title">Informations Client</h3>
                            <div class="form-group">
                                <label for="telephone">Téléphone :</label>
                                <input type="tel" id="telephone" name="telephone" value="<?= safe_html($user_data['ClientTelephone'] ?? '') ?>" required pattern="^0[67]\d{8}$" title="Numéro de téléphone invalide (Ex: 0612345678).">
                                <?php if (isset($form_errors['telephone'])) echo "<p class='error-message'>".safe_html($form_errors['telephone'])."</p>"; ?>
                            </div>
                            <div class="form-group">
                                <label for="carte_vitale">Carte Vitale :</label>
                                <input type="text" id="carte_vitale" name="carte_vitale" value="<?= safe_html($user_data['CarteVitale'] ?? '') ?>" required pattern="\d{13,15}" title="Numéro de carte vitale invalide (13 à 15 chiffres).">
                                <?php if (isset($form_errors['carte_vitale'])) echo "<p class='error-message'>".safe_html($form_errors['carte_vitale'])."</p>"; ?>
                            </div>
                        </section>
                    <?php elseif ($type === 'personnel'): ?>
                        <section class="profile-section">
                            <h3 class="section-title">Informations Professionnelles</h3>
                            <div class="form-group">
                                <label for="telephone">Téléphone :</label>
                                <input type="tel" id="telephone" name="telephone" value="<?= safe_html($user_data['PersonnelTelephone'] ?? '') ?>" required pattern="^0[67]\d{8}$" title="Numéro de téléphone invalide (Ex: 0612345678).">
                                <?php if (isset($form_errors['telephone'])) echo "<p class='error-message'>".safe_html($form_errors['telephone'])."</p>"; ?>
                            </div>
                            <div class="form-group">
                                <label for="specialite">Spécialité :</label>
                                <input type="text" id="specialite" name="specialite" value="<?= safe_html($user_data['Specialite'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="description">Description :</label>
                                <textarea id="description" name="description"><?= safe_html($user_data['Description'] ?? '') ?></textarea>
                            </div>
                            <p style="font-size:0.9em; color:#6c757d; margin-top:1.5rem;">Pour modifier votre photo ou vidéo, veuillez contacter l'administration.</p>
                        </section>
                    <?php endif; ?>

                    <!-- ______________/ Actions du Formulaire \_____________________ -->
                    <div class="profile-actions">
                        <button type="submit" class="btn-profile-action btn-save-profile">Enregistrer les modifications</button>
                        <a href="profil.php" class="btn-profile-action btn-cancel-edit">Annuler</a>
                    </div>
                </form>
            </div>
        </main>

        <!-- Importation footer -->
        <?php require 'includes/footer.php'; ?>

        <!-- ______________/ Fermeture de la connexion (si toujours ouverte) \_____________________ -->
        <?php
            if (isset($conn) && $conn) {
                mysqli_close($conn);
            }
        ?>
    </body>
</html>

<style>
/* Re-using styles from profil.php for consistency */
.profile-main {
    padding: 2rem;
    background-color: #f2f2f2;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    min-height: calc(100vh - 160px); /* Assuming header+footer is 160px */
}

.profile-container {
    max-width: 750px;
    width: 100%;
    margin: auto;
    background: #fff;
    padding: 2.5rem;
    border-radius: 10px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    box-sizing: border-box;
}

.profile-title {
    text-align: center;
    color: #0a7abf;
    margin-bottom: 2.5rem;
    font-size: 2.2rem;
    font-weight: 600;
}

.profile-section {
    background-color: #f8f9fa;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.section-title {
    color: #0a7abf;
    font-size: 1.4rem;
    margin-top: 0;
    margin-bottom: 1.5rem;
    padding-bottom: 0.8rem;
    border-bottom: 2px solid #eaf5ff;
}

.profile-edit-form .form-group {
    margin-bottom: 1rem;
}

.profile-edit-form label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #333;
}

.profile-edit-form input[type="text"],
.profile-edit-form input[type="email"],
.profile-edit-form input[type="tel"],
.profile-edit-form textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ccc;
    border-radius: 5px;
    font-size: 1rem;
    box-sizing: border-box; /* Include padding in width */
    transition: border-color 0.2s ease;
}

.profile-edit-form input:focus,
.profile-edit-form textarea:focus {
    outline: none;
    border-color: #0a7abf;
    box-shadow: 0 0 0 3px rgba(10, 122, 191, 0.2);
}

.profile-edit-form textarea {
    min-height: 80px;
    resize: vertical;
}

.profile-actions {
    text-align: center;
    margin-top: 3rem;
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 1rem;
}

.btn-profile-action {
    display: inline-block;
    padding: 12px 25px;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-weight: bold;
    transition: background-color 0.2s ease, transform 0.2s ease;
    min-width: 180px;
    text-align: center;
    border: none; /* Buttons should not have default border */
    cursor: pointer;
}

.btn-save-profile {
    background-color: #28a745; /* Success color */
}
.btn-save-profile:hover {
    background-color: #218838;
    transform: translateY(-2px);
}

.btn-cancel-edit {
    background-color: #6c757d; /* Neutral color */
}
.btn-cancel-edit:hover {
    background-color: #5a6268;
    transform: translateY(-2px);
}

.error-message {
    color: #dc3545;
    font-size: 0.85em;
    margin-top: 5px;
}

/* Re-use from profil.php */
.profile-alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 5px;
    /* text-align: center; */ /* Removed to allow ul to align left */
    font-weight: 500;
}
.profile-alert.success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
    text-align: center; /* Center success messages */
}
.profile-alert.error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
.profile-alert.error ul {
    margin-top: 10px;
    margin-bottom: 0;
    padding-left: 20px;
    text-align: left;
}
.profile-alert.error ul li {
    margin-bottom: 5px;
}
</style>