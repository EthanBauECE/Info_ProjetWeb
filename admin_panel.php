<?php 

    if (session_status() === PHP_SESSION_NONE) { 
        session_start();
    }
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    if (!isset($_SESSION["user_id"]) || $_SESSION["user_type"] !== "Admin") {
        header("Location: login.php");
        exit();
    }

    $db_host = 'localhost';//POUR SE COINNECTER A LA BDD
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'base_donne_web';
    $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

    if (!$conn) {
        die("Erreur de connexion BDD: " . mysqli_connect_error());//INDIQUER LE PB
    }
    mysqli_set_charset($conn, 'utf8');


    $success_message = '';
    $error_message = '';
    $form_errors = []; //INIT POOUR ERRERUS

    function safe_html($value) {
        return $value !== null ? htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') : '';
    }

    /// Liste du Personnel
    $personnel_list = [];
    $sql_personnel = "SELECT u.ID, u.Nom, u.Prenom, up.Type FROM utilisateurs u JOIN utilisateurs_personnel up ON u.ID = up.ID ORDER BY u.Nom, u.Prenom";
    $result_personnel = mysqli_query($conn, $sql_personnel);
    if ($result_personnel) {
        while ($row = mysqli_fetch_assoc($result_personnel)) {
            $personnel_list[] = $row;
        }
        mysqli_free_result($result_personnel);
    } else {
        $error_message .= " Erreur chargement personnel: " . mysqli_error($conn);//INIDQUER ERREUR
    }

    $laboratories_list = [];//INIT LES LABO
    $sql_labs = "SELECT ID, Nom FROM laboratoire ORDER BY Nom";//RELIER A LA BASE DE DONE
    $result_labs = mysqli_query($conn, $sql_labs);
    if ($result_labs) {
        while ($row = mysqli_fetch_assoc($result_labs)) {
            $laboratories_list[] = $row;
        }
        mysqli_free_result($result_labs);
    } else {
        $error_message .= " Erreur chargement laboratoires: " . mysqli_error($conn);//INIDQUER ERREUR TROUBVE
    }

    $services_list = [];//INIT LES SERVICE 
    $sql_services = "SELECT sl.ID, sl.NomService, l.Nom AS LaboNom FROM service_labo sl JOIN laboratoire l ON sl.ID_Laboratoire = l.ID ORDER BY l.Nom, sl.NomService";//FAIT APPEL BASE DE DONEE
    $result_services = mysqli_query($conn, $sql_services);
    if ($result_services) {
        while ($row = mysqli_fetch_assoc($result_services)) {
            $services_list[] = $row;
        }
        mysqli_free_result($result_services);
    } else {
        $error_message .= " Erreur chargement services: " . mysqli_error($conn);
    }

    $dispo_list = [];//ON INIT LES DISPO
    $sql_dispo = "
        SELECT
            d.ID, d.Date, d.HeureDebut, d.HeureFin, d.Prix,
            CASE
                WHEN d.IdPersonnel != 0 THEN CONCAT(u.Prenom, ' ', u.Nom, ' (', up.Type, ')')
                WHEN d.IdServiceLabo != 0 THEN CONCAT(l.Nom, ' - ', sl.NomService)
                ELSE 'Inconnu'
            END AS TargetName
        FROM dispo d
        LEFT JOIN utilisateurs u ON d.IdPersonnel = u.ID
        LEFT JOIN utilisateurs_personnel up ON d.IdPersonnel = up.ID
        LEFT JOIN service_labo sl ON d.IdServiceLabo = sl.ID
        LEFT JOIN laboratoire l ON sl.ID_Laboratoire = l.ID
        ORDER BY d.Date ASC, d.HeureDebut ASC";
    $result_dispo = mysqli_query($conn, $sql_dispo);
    if ($result_dispo) {
        while ($row = mysqli_fetch_assoc($result_dispo)) {
            $dispo_list[] = $row;
        }
        mysqli_free_result($result_dispo);
    } else {
        $error_message .= " Erreur chargement disponibilités: " . mysqli_error($conn);//PREVENIR ERREUR
    }

    $all_users_list = [];//ON INIT
    $sql_all_users = "SELECT ID, Nom, Prenom, TypeCompte FROM utilisateurs ORDER BY Nom, Prenom";//RIELIER CA A LA LA BASE DE DONNE AVEC BONNE INFO
    $result_all_users = mysqli_query($conn, $sql_all_users);
    if ($result_all_users) {
        while ($row = mysqli_fetch_assoc($result_all_users)) {
            $all_users_list[] = $row;
        }
        mysqli_free_result($result_all_users);
    } else {
        $error_message .= " Erreur  " . mysqli_error($conn);
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $action = $_POST['action'] ?? '';

        mysqli_begin_transaction($conn); 

        try {
            switch ($action) {
                case 'delete_account':
                    /// SUPPRIMER UN COMPTE UTILISATEUR
                    $user_id_to_delete = intval($_POST['user_id_to_delete'] ?? 0);
                    if ($user_id_to_delete === 0) {
                        $form_errors['delete_account']['user_id_to_delete'] = "Veuillez sélectionner un compte à supprimer.";
                    } elseif ($user_id_to_delete == $_SESSION['user_id']) {
                        $form_errors['delete_account']['self_delete'] = "Vous ne pouvez pas supprimer votre propre compte.";
                    }

                    if (empty($form_errors['delete_account'])) {
                        $sql_type = "SELECT TypeCompte FROM utilisateurs WHERE ID = ?";
                        $stmt_type = mysqli_prepare($conn, $sql_type);
                        mysqli_stmt_bind_param($stmt_type, "i", $user_id_to_delete);
                        mysqli_stmt_execute($stmt_type);
                        $result_type_query = mysqli_stmt_get_result($stmt_type);
                        $user_data = mysqli_fetch_assoc($result_type_query);
                        $user_type_to_delete = $user_data ? $user_data['TypeCompte'] : null;
                        mysqli_free_result($result_type_query);
                        mysqli_stmt_close($stmt_type);

                        if (!$user_type_to_delete) {
                             throw new Exception("compte non trouvé pour l'utilisateur ID: " . $user_id_to_delete);
                        }

                        if ($user_type_to_delete === 'Client') { //CAS POUR UN CLIENT
                            $stmt_del_rdv_client = mysqli_prepare($conn, "DELETE FROM rdv WHERE ID_Client = ?");
                            mysqli_stmt_bind_param($stmt_del_rdv_client, "i", $user_id_to_delete);
                            mysqli_stmt_execute($stmt_del_rdv_client);
                            mysqli_stmt_close($stmt_del_rdv_client);

                            $stmt_del_client = mysqli_prepare($conn, "DELETE FROM utilisateurs_client WHERE ID = ?");
                            mysqli_stmt_bind_param($stmt_del_client, "i", $user_id_to_delete);
                            mysqli_stmt_execute($stmt_del_client);
                            mysqli_stmt_close($stmt_del_client);
                        } elseif ($user_type_to_delete === 'Personnel') {//SI C EST POUR UN OPERSONEL
                            $stmt_del_rdv_personnel = mysqli_prepare($conn, "DELETE FROM rdv WHERE ID_Personnel = ?");
                            mysqli_stmt_bind_param($stmt_del_rdv_personnel, "i", $user_id_to_delete);
                            mysqli_stmt_execute($stmt_del_rdv_personnel);
                            mysqli_stmt_close($stmt_del_rdv_personnel);

                            $stmt_del_dispo_personnel = mysqli_prepare($conn, "DELETE FROM dispo WHERE IdPersonnel = ?");
                            mysqli_stmt_bind_param($stmt_del_dispo_personnel, "i", $user_id_to_delete);
                            mysqli_stmt_execute($stmt_del_dispo_personnel);
                            mysqli_stmt_close($stmt_del_dispo_personnel);

                            $stmt_del_cv = mysqli_prepare($conn, "DELETE FROM cv WHERE ID_Personnel = ?");
                            mysqli_stmt_bind_param($stmt_del_cv, "i", $user_id_to_delete);
                            mysqli_stmt_execute($stmt_del_cv);
                            mysqli_stmt_close($stmt_del_cv);

                            $stmt_del_personnel = mysqli_prepare($conn, "DELETE FROM utilisateurs_personnel WHERE ID = ?");
                            mysqli_stmt_bind_param($stmt_del_personnel, "i", $user_id_to_delete);
                            mysqli_stmt_execute($stmt_del_personnel);
                            mysqli_stmt_close($stmt_del_personnel);
                        } elseif ($user_type_to_delete === 'Admin') {//CAS DE L ADMIN
                            mysqli_rollback($conn); 
                            $error_message = "Impossible de supprimer un compte Administrateur";
                            break;
                        }

                        $stmt_del_user = mysqli_prepare($conn, "DELETE FROM utilisateurs WHERE ID = ?");
                        mysqli_stmt_bind_param($stmt_del_user, "i", $user_id_to_delete);
                        mysqli_stmt_execute($stmt_del_user);
                        mysqli_stmt_close($stmt_del_user);

                        $success_message = "Compte utilisateur supprimé ";
                    } else {
                        $error_message = "ERREUR 'Supprimer un compte'.";
                        mysqli_rollback($conn); 
                    }
                    break;


                case 'delete_professional':
                    /// SUPPRIMER UN PROFESSIONNEL
                    $personnel_id_to_delete = intval($_POST['personnel_id_to_delete'] ?? 0);
                    if ($personnel_id_to_delete === 0) {
                        $form_errors['delete_professional']['personnel_id_to_delete'] = "Veuillez sélectionner un professionnel à supprimer.";
                    } elseif ($personnel_id_to_delete == $_SESSION['user_id'] && $_SESSION['user_type'] === 'Personnel') {
                         $form_errors['delete_professional']['self_delete'] = "Vous ne pouvez pas supprimer votre propre compte professionnel.";
                    }

                    if (empty($form_errors['delete_professional'])) {
                        //SUPP LES RDV
                        $stmt_del_rdv = mysqli_prepare($conn, "DELETE FROM rdv WHERE ID_Personnel = ?");
                        mysqli_stmt_bind_param($stmt_del_rdv, "i", $personnel_id_to_delete);
                        mysqli_stmt_execute($stmt_del_rdv);
                        mysqli_stmt_close($stmt_del_rdv);
                        //SUPP LES DISPO
                        $stmt_del_dispo = mysqli_prepare($conn, "DELETE FROM dispo WHERE IdPersonnel = ?");
                        mysqli_stmt_bind_param($stmt_del_dispo, "i", $personnel_id_to_delete);
                        mysqli_stmt_execute($stmt_del_dispo);
                        mysqli_stmt_close($stmt_del_dispo);
                        //SUPP LE CV DE LA PERSONNE
                        $stmt_del_cv = mysqli_prepare($conn, "DELETE FROM cv WHERE ID_Personnel = ?");
                        mysqli_stmt_bind_param($stmt_del_cv, "i", $personnel_id_to_delete);
                        mysqli_stmt_execute($stmt_del_cv);
                        mysqli_stmt_close($stmt_del_cv);
                        //SUPP UN PERSONNEL
                        $stmt_del_personnel_detail = mysqli_prepare($conn, "DELETE FROM utilisateurs_personnel WHERE ID = ?");
                        mysqli_stmt_bind_param($stmt_del_personnel_detail, "i", $personnel_id_to_delete);
                        mysqli_stmt_execute($stmt_del_personnel_detail);
                        mysqli_stmt_close($stmt_del_personnel_detail);
                        //SUPPRIMER UN UTILISATEUR
                        $stmt_del_user_main = mysqli_prepare($conn, "DELETE FROM utilisateurs WHERE ID = ?");
                        mysqli_stmt_bind_param($stmt_del_user_main, "i", $personnel_id_to_delete);
                        mysqli_stmt_execute($stmt_del_user_main);
                        mysqli_stmt_close($stmt_del_user_main);

                        $success_message = "Compte professionnel supprimé avec succès.";
                    } else {
                        $error_message = "Veuillez corriger les erreurs dans le formulaire 'Supprimer un professionnel'.";
                        mysqli_rollback($conn);
                    }
                    break;


                case 'delete_laboratory':
                    /// SUPPRIMER UN LABORATOIRE
                    $labo_id_to_delete = intval($_POST['labo_id_to_delete'] ?? 0);
                    if ($labo_id_to_delete === 0) {
                        $form_errors['delete_laboratory']['labo_id_to_delete'] = "Veuillez sélectionner un laboratoire à supprimer.";
                    }

                    if (empty($form_errors['delete_laboratory'])) {
                        $sql_get_services = "SELECT ID FROM service_labo WHERE ID_Laboratoire = ?";//ON VA CHERCHER DANS LA BASE DE DONNE LEW SERVICES
                        $stmt_get_services = mysqli_prepare($conn, $sql_get_services);
                        mysqli_stmt_bind_param($stmt_get_services, "i", $labo_id_to_delete);
                        mysqli_stmt_execute($stmt_get_services);
                        $result_services_query = mysqli_stmt_get_result($stmt_get_services);
                        $service_ids = [];
                        while ($row = mysqli_fetch_assoc($result_services_query)) {
                            $service_ids[] = $row['ID'];
                        }
                        mysqli_free_result($result_services_query);
                        mysqli_stmt_close($stmt_get_services);

                        if (!empty($service_ids)) {//ON ENLEVE TOUT CE QUI VA AVEC 
                            $placeholders = implode(',', array_fill(0, count($service_ids), '?'));
                            $types = str_repeat('i', count($service_ids));

                            $sql_del_rdv_services = "DELETE FROM rdv WHERE ID_ServiceLabo IN ($placeholders)";
                            $stmt_del_rdv_services = mysqli_prepare($conn, $sql_del_rdv_services);
                            mysqli_stmt_bind_param($stmt_del_rdv_services, $types, ...$service_ids);
                            mysqli_stmt_execute($stmt_del_rdv_services);
                            mysqli_stmt_close($stmt_del_rdv_services);

                            $sql_del_dispo_services = "DELETE FROM dispo WHERE IdServiceLabo IN ($placeholders)";
                            $stmt_del_dispo_services = mysqli_prepare($conn, $sql_del_dispo_services);
                            mysqli_stmt_bind_param($stmt_del_dispo_services, $types, ...$service_ids);
                            mysqli_stmt_execute($stmt_del_dispo_services);
                            mysqli_stmt_close($stmt_del_dispo_services);
                        }

                        $stmt_del_services_labo = mysqli_prepare($conn, "DELETE FROM service_labo WHERE ID_Laboratoire = ?");//SUPP UN SERVICE DU LABO
                        mysqli_stmt_bind_param($stmt_del_services_labo, "i", $labo_id_to_delete);
                        mysqli_stmt_execute($stmt_del_services_labo);
                        mysqli_stmt_close($stmt_del_services_labo);

                        $stmt_del_labo = mysqli_prepare($conn, "DELETE FROM laboratoire WHERE ID = ?");//SUPP UN LABO
                        mysqli_stmt_bind_param($stmt_del_labo, "i", $labo_id_to_delete);
                        mysqli_stmt_execute($stmt_del_labo);//ON FAIT CE QUI EST DEMANDE
                        mysqli_stmt_close($stmt_del_labo);

                        $success_message = "Laboratoire et tous ses services associés supprimés avec succès.";//PREVENIR
                    } else {
                        $error_message = "Veuillez corriger les erreurs dans le formulaire 'Supprimer un laboratoire'.";
                        mysqli_rollback($conn);
                    }
                    break;


                case 'delete_service':
                    /// SUPPRIMER UN SERVICE
                    $service_id_to_delete = intval($_POST['service_id_to_delete'] ?? 0);
                    if ($service_id_to_delete === 0) {
                        $form_errors['delete_service']['service_id_to_delete'] = "Veuillez sélectionner un service à supprimer.";
                    }

                    if (empty($form_errors['delete_service'])) {
                        $stmt_del_rdv_service = mysqli_prepare($conn, "DELETE FROM rdv WHERE ID_ServiceLabo = ?");//ON ENLEVE LES RDV
                        mysqli_stmt_bind_param($stmt_del_rdv_service, "i", $service_id_to_delete);
                        mysqli_stmt_execute($stmt_del_rdv_service);
                        mysqli_stmt_close($stmt_del_rdv_service);
                        $stmt_del_dispo_service = mysqli_prepare($conn, "DELETE FROM dispo WHERE IdServiceLabo = ?");//ON ENLEVE LES DISPO AUSIS
                        mysqli_stmt_bind_param($stmt_del_dispo_service, "i", $service_id_to_delete);
                        mysqli_stmt_execute($stmt_del_dispo_service);
                        mysqli_stmt_close($stmt_del_dispo_service);
                        $stmt_del_service = mysqli_prepare($conn, "DELETE FROM service_labo WHERE ID = ?");//SUPP UN SERVICE QU ON VEUT PLUS
                        mysqli_stmt_bind_param($stmt_del_service, "i", $service_id_to_delete);
                        mysqli_stmt_execute($stmt_del_service);
                        mysqli_stmt_close($stmt_del_service);

                        $success_message = "Service et tous ses créneaux/RDV associés supprimés avec succès.";
                    } else {
                        $error_message = "Veuillez corriger les erreurs dans le formulaire 'Supprimer un service'.";
                        mysqli_rollback($conn);
                    }
                    break;


                case 'delete_dispo':
                    /// SUPPRIMER UNE DISPONIBILITÉ
                    $dispo_id_to_delete = intval($_POST['dispo_id_to_delete'] ?? 0);
                    if ($dispo_id_to_delete === 0) {
                        $form_errors['delete_dispo']['dispo_id_to_delete'] = "Veuillez sélectionner un créneau de disponibilité à supprimer.";
                    }

                    if (empty($form_errors['delete_dispo'])) {// POUR ENLEVER LA DISPO
                        $stmt_del_dispo_single = mysqli_prepare($conn, "DELETE FROM dispo WHERE ID = ?");
                        mysqli_stmt_bind_param($stmt_del_dispo_single, "i", $dispo_id_to_delete);
                        mysqli_stmt_execute($stmt_del_dispo_single);
                        mysqli_stmt_close($stmt_del_dispo_single);

                        $success_message = "Créneau de disponibilité supprimé ";//BIEN SUPPRIME
                    } else {
                        $error_message = "ERREUR.";//MESSAGE
                        mysqli_rollback($conn);
                    }
                    break;
            } 

            mysqli_commit($conn); //ON LE FAIT

            if ($success_message && (strpos($action, 'delete_') === 0 || strpos($action, 'add_') === 0) ) { // Recharger après add_ aussi
                $personnel_list = [];// LISTE DU PERSONNEL
                $result_personnel = mysqli_query($conn, "SELECT u.ID, u.Nom, u.Prenom, up.Type FROM utilisateurs u JOIN utilisateurs_personnel up ON u.ID = up.ID ORDER BY u.Nom, u.Prenom");
                if ($result_personnel) { while ($row = mysqli_fetch_assoc($result_personnel)) { $personnel_list[] = $row; } mysqli_free_result($result_personnel); }

                $laboratories_list = [];//LISTE DS LABO
                $result_labs = mysqli_query($conn, "SELECT ID, Nom FROM laboratoire ORDER BY Nom");
                if ($result_labs) { while ($row = mysqli_fetch_assoc($result_labs)) { $laboratories_list[] = $row; } mysqli_free_result($result_labs); }

                $services_list = [];
                $result_services = mysqli_query($conn, "SELECT sl.ID, sl.NomService, l.Nom AS LaboNom FROM service_labo sl JOIN laboratoire l ON sl.ID_Laboratoire = l.ID ORDER BY l.Nom, sl.NomService");
                if ($result_services) { while ($row = mysqli_fetch_assoc($result_services)) { $services_list[] = $row; } mysqli_free_result($result_services); }

                $dispo_list = [];
                $sql_dispo_reload = "
                    SELECT d.ID, d.Date, d.HeureDebut, d.HeureFin, d.Prix,
                    CASE WHEN d.IdPersonnel != 0 THEN CONCAT(u.Prenom, ' ', u.Nom, ' (', up.Type, ')')
                         WHEN d.IdServiceLabo != 0 THEN CONCAT(l.Nom, ' - ', sl.NomService)
                         ELSE 'Inconnu'
                    END AS TargetName
                    FROM dispo d
                    LEFT JOIN utilisateurs u ON d.IdPersonnel = u.ID
                    LEFT JOIN utilisateurs_personnel up ON d.IdPersonnel = up.ID
                    LEFT JOIN service_labo sl ON d.IdServiceLabo = sl.ID
                    LEFT JOIN laboratoire l ON sl.ID_Laboratoire = l.ID
                    ORDER BY d.Date ASC, d.HeureDebut ASC";
                $result_dispo = mysqli_query($conn, $sql_dispo_reload);
                if ($result_dispo) { while ($row = mysqli_fetch_assoc($result_dispo)) { $dispo_list[] = $row; } mysqli_free_result($result_dispo); }

                $all_users_list = [];
                $result_all_users = mysqli_query($conn, "SELECT ID, Nom, Prenom, TypeCompte FROM utilisateurs ORDER BY Nom, Prenom");
                if ($result_all_users) { while ($row = mysqli_fetch_assoc($result_all_users)) { $all_users_list[] = $row; } mysqli_free_result($result_all_users); }

                $_POST = []; 
            }

        } catch (Exception $e) {
            mysqli_rollback($conn); 
            error_log($action . " a échoué: " . $e->getMessage() . " | MySQLi Error: " . mysqli_error($conn));
            $error_message = "Une erreur est survenue lors de l'opération '" . $action . "': " . $e->getMessage();//ON PREVIENT ERR4ERUES TROUVE
             if (mysqli_error($conn)) {
                $error_message .= " (Détail BD: " . mysqli_error($conn) .")";
            }
        }
    } 

?>

<!DOCTYPE html> 
<html lang="fr">
    <?php require 'includes/head.php'; ?>

<body>

    <?php require 'includes/header.php'; ?><!--grader le haut de la page-->

    <main class="admin-main">
        <div class="admin-container">
            <h1 class="admin-title">Panneau d'Administration</h1>
            <?php if (!empty($success_message)): ?>
                <div class="admin-alert success"><?= safe_html($success_message) ?></div>
            <?php endif; ?>
            <?php if (!empty($error_message)): ?>
                <div class="admin-alert error"><?= safe_html($error_message) ?></div>
            <?php endif; ?>


            <!--ajouter un compte personnel-->
            <section class="admin-section">
                <h2 class="section-title">Ajouter un compte personnel</h2>
                <form action="admin_panel.php" method="POST" class="admin-form">
                    <input type="hidden" name="action" value="add_personnel">

                    <div class="form-group">
                        <label for="nom">Nom :</label><!--pour indiquer le nom-->
                        <input type="text" id="nom" name="nom" value="<?= safe_html($_POST['nom'] ?? '') ?>" required>
                        <?php if (isset($form_errors['add_personnel']['nom'])) echo "<p class='error-message'>".safe_html($form_errors['add_personnel']['nom'])."</p>"; ?>
                    </div>
                    <div class="form-group">
                        <label for="prenom">Prénom :</label><!--pour indiquer le prenom-->
                        <input type="text" id="prenom" name="prenom" value="<?= safe_html($_POST['prenom'] ?? '') ?>" required>
                        <?php if (isset($form_errors['add_personnel']['prenom'])) echo "<p class='error-message'>".safe_html($form_errors['add_personnel']['prenom'])."</p>"; ?>
                    </div>
                    <div class="form-group">
                        <label for="email">Email :</label><!--pour indiquer le mail-->
                        <input type="email" id="email" name="email" value="<?= safe_html($_POST['email'] ?? '') ?>" required>
                        <?php if (isset($form_errors['add_personnel']['email'])) echo "<p class='error-message'>".safe_html($form_errors['add_personnel']['email'])."</p>"; ?>
                    </div>
                    <div class="form-group">
                        <label for="password">Mot de passe :</label><!--pour indiquer le mdp-->
                        <input type="password" id="password" name="password" required>
                        <?php if (isset($form_errors['add_personnel']['password'])) echo "<p class='error-message'>".safe_html($form_errors['add_personnel']['password'])."</p>"; ?>
                    </div>
                    <div class="form-group">
                        <label for="telephone">Téléphone :</label><!--pour indiquer le num de tel-->
                        <input type="tel" id="telephone" name="telephone" value="<?= safe_html($_POST['telephone'] ?? '') ?>" required pattern="^0[67]\d{8}$" title="Ex: 0612345678">
                        <?php if (isset($form_errors['add_personnel']['telephone'])) echo "<p class='error-message'>".safe_html($form_errors['add_personnel']['telephone'])."</p>"; ?>
                    </div>
                    <div class="form-group">
                        <label for="specialite">Spécialité (ex: Généraliste, Cardiologue) :</label><!--pour indiquer la specialite-->
                        <input type="text" id="specialite" name="specialite" value="<?= safe_html($_POST['specialite'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="description_personnel">Description (CV, etc.) :</label><!--pour indiquer la description-->
                        <textarea id="description_personnel" name="description"><?= safe_html($_POST['description'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="adresse_ligne_personnel">Adresse (N° et Rue) :</label><!--pour indiquer l adresse-->
                        <input type="text" id="adresse_ligne_personnel" name="adresse_ligne" value="<?= safe_html($_POST['adresse_ligne'] ?? '') ?>" required>
                        <?php if (isset($form_errors['add_personnel']['adresse_ligne'])) echo "<p class='error-message'>".safe_html($form_errors['add_personnel']['adresse_ligne'])."</p>"; ?>
                    </div>
                    <div class="form-group">
                        <label for="ville_personnel">Ville :</label>
                        <input type="text" id="ville_personnel" name="ville" value="<?= safe_html($_POST['ville'] ?? '') ?>" required>
                        <?php if (isset($form_errors['add_personnel']['ville'])) echo "<p class='error-message'>".safe_html($form_errors['add_personnel']['ville'])."</p>"; ?>
                    </div>
                    <div class="form-group">
                        <label for="code_postal_personnel">Code Postal :</label>
                        <input type="text" id="code_postal_personnel" name="code_postal" value="<?= safe_html($_POST['code_postal'] ?? '') ?>" required maxlength="5" pattern="\d{5}">
                        <?php if (isset($form_errors['add_personnel']['code_postal'])) echo "<p class='error-message'>".safe_html($form_errors['add_personnel']['code_postal'])."</p>"; ?>
                    </div>
                    <div class="form-group">
                        <label for="infos_complementaires_personnel">Infos Complémentaires (optionnel) :</label>
                        <textarea id="infos_complementaires_personnel" name="infos_complementaires"><?= safe_html($_POST['infos_complementaires'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="photo_path">Chemin Photo (ex: images/medecins/dr_dupont.jpg) :</label>
                        <input type="text" id="photo_path" name="photo_path" value="<?= safe_html($_POST['photo_path'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="video_path">Chemin Vidéo (ex: videos/dr_dupont.mp4) :</label>
                        <input type="text" id="video_path" name="video_path" value="<?= safe_html($_POST['video_path'] ?? '') ?>">
                    </div>
                    <button type="submit" class="btn-admin-action btn-add">Ajouter Personnel</button>
                </form>
            </section>


            <!-- ajouter un labo-->
            <section class="admin-section">
                <h2 class="section-title">Ajouter un laboratoire</h2>
                <form action="admin_panel.php" method="POST" class="admin-form">
                    <input type="hidden" name="action" value="add_laboratory">

                    <div class="form-group">
                        <label for="lab_nom">Nom du laboratoire :</label>
                        <input type="text" id="lab_nom" name="nom" value="<?= safe_html($_POST['nom'] ?? '') ?>" required>
                        <?php if (isset($form_errors['add_laboratory']['nom'])) echo "<p class='error-message'>".safe_html($form_errors['add_laboratory']['nom'])."</p>"; ?>
                    </div>
                    <div class="form-group">
                        <label for="lab_telephone">Téléphone :</label>
                        <input type="tel" id="lab_telephone" name="telephone" value="<?= safe_html($_POST['telephone'] ?? '') ?>" required pattern="^0[1-9]\d{8}$" title="Ex: 0123456789">
                        <?php if (isset($form_errors['add_laboratory']['telephone'])) echo "<p class='error-message'>".safe_html($form_errors['add_laboratory']['telephone'])."</p>"; ?>
                    </div>
                    <div class="form-group">
                        <label for="lab_email">Email :</label>
                        <input type="email" id="lab_email" name="email" value="<?= safe_html($_POST['email'] ?? '') ?>" required>
                        <?php if (isset($form_errors['add_laboratory']['email'])) echo "<p class='error-message'>".safe_html($form_errors['add_laboratory']['email'])."</p>"; ?>
                    </div>
                    <div class="form-group">
                        <label for="lab_description">Description :</label>
                        <textarea id="lab_description" name="description"><?= safe_html($_POST['description'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="lab_adresse_ligne">Adresse (N° et Rue) :</label>
                        <input type="text" id="lab_adresse_ligne" name="adresse_ligne" value="<?= safe_html($_POST['adresse_ligne'] ?? '') ?>" required>
                        <?php if (isset($form_errors['add_laboratory']['adresse_ligne'])) echo "<p class='error-message'>".safe_html($form_errors['add_laboratory']['adresse_ligne'])."</p>"; ?>
                    </div>
                    <div class="form-group">
                        <label for="lab_ville">Ville :</label>
                        <input type="text" id="lab_ville" name="ville" value="<?= safe_html($_POST['ville'] ?? '') ?>" required>
                        <?php if (isset($form_errors['add_laboratory']['ville'])) echo "<p class='error-message'>".safe_html($form_errors['add_laboratory']['ville'])."</p>"; ?>
                    </div>
                    <div class="form-group">
                        <label for="lab_code_postal">Code Postal :</label>
                        <input type="text" id="lab_code_postal" name="code_postal" value="<?= safe_html($_POST['code_postal'] ?? '') ?>" required maxlength="5" pattern="\d{5}">
                        <?php if (isset($form_errors['add_laboratory']['code_postal'])) echo "<p class='error-message'>".safe_html($form_errors['add_laboratory']['code_postal'])."</p>"; ?>
                    </div>
                    <div class="form-group">
                        <label for="lab_infos_complementaires">Infos Complémentaires (optionnel) :</label>
                        <textarea id="lab_infos_complementaires" name="infos_complementaires"><?= safe_html($_POST['infos_complementaires'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="lab_photo_path">Chemin Photo (ex: images/labos/labo_paris.png) :</label>
                        <input type="text" id="lab_photo_path" name="photo_path" value="<?= safe_html($_POST['photo_path'] ?? '') ?>">
                    </div>
                    <button type="submit" class="btn-admin-action btn-add">Ajouter Laboratoire</button>
                </form>
            </section>


            <!-- Ajouter un service à un laboratoire -->
            <section class="admin-section">
                <h2 class="section-title">Ajouter un service et l'associer à un laboratoire</h2>
                <form action="admin_panel.php" method="POST" class="admin-form">
                    <input type="hidden" name="action" value="add_service">

                    <div class="form-group">
                        <label for="labo_id">Sélectionner un laboratoire :</label>
                        <select id="labo_id" name="labo_id" required>
                            <option value="">-- Choisir un laboratoire --</option>
                            <?php foreach ($laboratories_list as $lab): ?>
                                <option value="<?= safe_html($lab['ID']) ?>" <?= (isset($_POST['labo_id']) && $_POST['labo_id'] == $lab['ID']) ? 'selected' : '' ?>>
                                    <?= safe_html($lab['Nom']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($form_errors['add_service']['labo_id'])) echo "<p class='error-message'>".safe_html($form_errors['add_service']['labo_id'])."</p>"; ?>
                    </div>
                    <div class="form-group">
                        <label for="nom_service">Nom du service :</label>
                        <input type="text" id="nom_service" name="nom_service" value="<?= safe_html($_POST['nom_service'] ?? '') ?>" required>
                        <?php if (isset($form_errors['add_service']['nom_service'])) echo "<p class='error-message'>".safe_html($form_errors['add_service']['nom_service'])."</p>"; ?>
                    </div>
                    <div class="form-group">
                        <label for="prix_service">Prix (€) :</label>
                        <input type="number" step="0.01" id="prix_service" name="prix" value="<?= safe_html($_POST['prix'] ?? '') ?>" required min="0.01">
                        <?php if (isset($form_errors['add_service']['prix'])) echo "<p class='error-message'>".safe_html($form_errors['add_service']['prix'])."</p>"; ?>
                    </div>
                    <div class="form-group">
                        <label for="service_description">Description du service (optionnel) :</label>
                        <textarea id="service_description" name="description"><?= safe_html($_POST['description'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn-admin-action btn-add">Ajouter Service</button>
                </form>
            </section>


            <section class="admin-section">
                <h2 class="section-title">Ajouter un créneau de disponibilité</h2>
                <form action="admin_panel.php" method="POST" class="admin-form">
                    <input type="hidden" name="action" value="add_dispo">

                    <div class="form-group">
                        <label>Type de créneau :</label><br>
                        <input type="radio" id="dispo_type_personnel" name="dispo_type" value="personnel" <?= (isset($_POST['dispo_type']) && $_POST['dispo_type'] === 'personnel') ? 'checked' : ((!isset($_POST['dispo_type'])) ? 'checked' : '') ?> onchange="toggleDispoTarget()">
                        <label for="dispo_type_personnel">Personnel / Médecin</label>
                        <input type="radio" id="dispo_type_laboratoire" name="dispo_type" value="laboratoire" <?= (isset($_POST['dispo_type']) && $_POST['dispo_type'] === 'laboratoire') ? 'checked' : '' ?> onchange="toggleDispoTarget()">
                        <label for="dispo_type_laboratoire">Service de Laboratoire</label>
                        <?php if (isset($form_errors['add_dispo']['dispo_type'])) echo "<p class='error-message'>".safe_html($form_errors['add_dispo']['dispo_type'])."</p>"; ?>
                    </div>

                    <div class="form-group" id="personnel_target_group" style="display:<?= (isset($_POST['dispo_type']) && $_POST['dispo_type'] === 'laboratoire') ? 'none' : 'block' ?>;">
                        <label for="personnel_id_dispo">Sélectionner le personnel :</label>
                        <select id="personnel_id_dispo" name="target_id_personnel"> <!-- Changed name -->
                            <option value="">-- Choisir un personnel --</option>
                            <?php foreach ($personnel_list as $personnel): ?>
                                <option value="<?= safe_html($personnel['ID']) ?>" <?= (isset($_POST['target_id_personnel']) && $_POST['dispo_type'] === 'personnel' && $_POST['target_id_personnel'] == $personnel['ID']) ? 'selected' : '' ?>>
                                    Dr. <?= safe_html($personnel['Prenom']) ?> <?= safe_html($personnel['Nom']) ?> (<?= safe_html($personnel['Type']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" id="labo_service_target_group" style="display:<?= (isset($_POST['dispo_type']) && $_POST['dispo_type'] === 'laboratoire') ? 'block' : 'none' ?>;">
                        <label for="labo_service_id_dispo">Sélectionner un service de laboratoire :</label>
                        <select id="labo_service_id_dispo" name="target_id_labo_service"> <!-- Changed name -->
                            <option value="">-- Choisir un service de laboratoire --</option>
                            <?php
                            $sql_lab_services_dispo = "SELECT sl.ID, sl.NomService, l.Nom AS LaboNom FROM service_labo sl JOIN laboratoire l ON sl.ID_Laboratoire = l.ID ORDER BY l.Nom, sl.NomService";
                            $result_lab_services_dispo = mysqli_query($conn, $sql_lab_services_dispo);
                            if ($result_lab_services_dispo) {
                                while ($row_service_dispo = mysqli_fetch_assoc($result_lab_services_dispo)): ?>
                                    <option value="<?= safe_html($row_service_dispo['ID']) ?>" <?= (isset($_POST['target_id_labo_service']) && $_POST['dispo_type'] === 'laboratoire' && $_POST['target_id_labo_service'] == $row_service_dispo['ID']) ? 'selected' : '' ?>>
                                        <?= safe_html($row_service_dispo['LaboNom']) ?> - <?= safe_html($row_service_dispo['NomService']) ?>
                                    </option>
                                <?php endwhile;
                                mysqli_free_result($result_lab_services_dispo);
                            } else {
                                echo "<option value=''>Erreur chargement services</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <?php if (isset($form_errors['add_dispo']['target_id'])) echo "<p class='error-message'>".safe_html($form_errors['add_dispo']['target_id'])."</p>"; ?>


                    <div class="form-group">
                        <label for="date_dispo">Date :</label>
                        <input type="date" id="date_dispo" name="date" value="<?= safe_html($_POST['date'] ?? date('Y-m-d')) ?>" required min="<?= date('Y-m-d') ?>">
                        <?php if (isset($form_errors['add_dispo']['date'])) echo "<p class='error-message'>".safe_html($form_errors['add_dispo']['date'])."</p>"; ?>
                    </div>
                    <div class="form-group">
                        <label for="heure_debut_dispo">Heure de début :</label>
                        <input type="time" id="heure_debut_dispo" name="heure_debut" value="<?= safe_html($_POST['heure_debut'] ?? '09:00') ?>" required>
                        <?php if (isset($form_errors['add_dispo']['heure_debut'])) echo "<p class='error-message'>".safe_html($form_errors['add_dispo']['heure_debut'])."</p>"; ?>
                    </div>
                    <div class="form-group">
                        <label for="heure_fin_dispo">Heure de fin :</label>
                        <input type="time" id="heure_fin_dispo" name="heure_fin" value="<?= safe_html($_POST['heure_fin'] ?? '10:00') ?>" required>
                        <?php if (isset($form_errors['add_dispo']['heure_fin'])) echo "<p class='error-message'>".safe_html($form_errors['add_dispo']['heure_fin'])."</p>"; ?>
                        <?php if (isset($form_errors['add_dispo']['time_order'])) echo "<p class='error-message'>".safe_html($form_errors['add_dispo']['time_order'])."</p>"; ?>
                    </div>
                    <div class="form-group">
                        <label for="prix_dispo">Prix (€) :</label>
                        <input type="number" step="0.01" id="prix_dispo" name="prix_dispo" value="<?= safe_html($_POST['prix_dispo'] ?? '') ?>" required min="0.01">
                        <?php if (isset($form_errors['add_dispo']['prix_dispo'])) echo "<p class='error-message'>".safe_html($form_errors['add_dispo']['prix_dispo'])."</p>"; ?>
                    </div>
                    <button type="submit" class="btn-admin-action btn-add">Ajouter Créneau</button>
                </form>
            </section>


            <section class="admin-section delete-section">
                <h2 class="section-title delete-title">Supprimer des entités</h2>

                <h3 class="subsection-title">Supprimer un Compte Utilisateur (Client ou Personnel)</h3><!--pour sup un utilisateur-->
                <form action="admin_panel.php" method="POST" class="admin-form" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce compte utilisateur et toutes ses données associées (RDV, créneaux, CV etc.)? Cette action est irréversible.');">
                    <input type="hidden" name="action" value="delete_account">
                    <div class="form-group">
                        <label for="user_id_to_delete">Sélectionner le compte à supprimer :</label>
                        <select id="user_id_to_delete" name="user_id_to_delete" required>
                            <option value="">-- Choisir un utilisateur --</option>
                            <?php foreach ($all_users_list as $user_item): ?>
                                <option value="<?= safe_html($user_item['ID']) ?>">
                                    <?= safe_html($user_item['Nom']) ?> <?= safe_html($user_item['Prenom']) ?> (<?= safe_html($user_item['TypeCompte']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($form_errors['delete_account']['user_id_to_delete'])) echo "<p class='error-message'>".safe_html($form_errors['delete_account']['user_id_to_delete'])."</p>"; ?>
                        <?php if (isset($form_errors['delete_account']['self_delete'])) echo "<p class='error-message'>".safe_html($form_errors['delete_account']['self_delete'])."</p>"; ?>
                    </div>
                    <button type="submit" class="btn-admin-action btn-delete">Supprimer Compte</button>
                </form>

                <hr class="form-separator">

                <h3 class="subsection-title">Supprimer un Professionnel de Santé</h3><!--pour sup un personnel-->
                <form action="admin_panel.php" method="POST" class="admin-form" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce professionnel de santé, son compte utilisateur, ses RDV et créneaux ? Cette action est irréversible.');">
                    <input type="hidden" name="action" value="delete_professional">
                    <div class="form-group">
                        <label for="personnel_id_to_delete">Sélectionner le professionnel à supprimer :</label>
                        <select id="personnel_id_to_delete" name="personnel_id_to_delete" required>
                            <option value="">-- Choisir un professionnel --</option>
                            <?php foreach ($personnel_list as $personnel): ?>
                                <option value="<?= safe_html($personnel['ID']) ?>">
                                    Dr. <?= safe_html($personnel['Prenom']) ?> <?= safe_html($personnel['Nom']) ?> (<?= safe_html($personnel['Type']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($form_errors['delete_professional']['personnel_id_to_delete'])) echo "<p class='error-message'>".safe_html($form_errors['delete_professional']['personnel_id_to_delete'])."</p>"; ?>
                        <?php if (isset($form_errors['delete_professional']['self_delete'])) echo "<p class='error-message'>".safe_html($form_errors['delete_professional']['self_delete'])."</p>"; ?>
                    </div>
                    <button type="submit" class="btn-admin-action btn-delete">Supprimer Professionnel</button>
                </form>

                <hr class="form-separator">

                <h3 class="subsection-title">Supprimer un Laboratoire</h3><!--pour sup un labo-->
                <form action="admin_panel.php" method="POST" class="admin-form" onsubmit="return confirm('ATTENTION : La suppression d\'un laboratoire supprimera TOUS les services et créneaux/RDV associés à ce laboratoire. Êtes-vous sûr ? Cette action est irréversible.');">
                    <input type="hidden" name="action" value="delete_laboratory">
                    <div class="form-group">
                        <label for="labo_id_to_delete">Sélectionner le laboratoire à supprimer :</label>
                        <select id="labo_id_to_delete" name="labo_id_to_delete" required>
                            <option value="">-- Choisir un laboratoire --</option>
                            <?php foreach ($laboratories_list as $lab): ?>
                                <option value="<?= safe_html($lab['ID']) ?>">
                                    <?= safe_html($lab['Nom']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($form_errors['delete_laboratory']['labo_id_to_delete'])) echo "<p class='error-message'>".safe_html($form_errors['delete_laboratory']['labo_id_to_delete'])."</p>"; ?>
                    </div>
                    <button type="submit" class="btn-admin-action btn-delete">Supprimer Laboratoire</button>
                </form>

                <hr class="form-separator">

                <h3 class="subsection-title">Supprimer un Service de Laboratoire</h3><!--pour sup un service de labo-->
                <form action="admin_panel.php" method="POST" class="admin-form" onsubmit="return confirm('ATTENTION : La suppression de ce service supprimera TOUS les créneaux et RDV associés à ce service. Êtes-vous sûr ? Cette action est irréversible.');">
                    <input type="hidden" name="action" value="delete_service">
                    <div class="form-group">
                        <label for="service_id_to_delete">Sélectionner le service à supprimer :</label>
                        <select id="service_id_to_delete" name="service_id_to_delete" required>
                            <option value="">-- Choisir un service --</option>
                            <?php foreach ($services_list as $service): ?>
                                <option value="<?= safe_html($service['ID']) ?>">
                                    <?= safe_html($service['LaboNom']) ?> - <?= safe_html($service['NomService']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($form_errors['delete_service']['service_id_to_delete'])) echo "<p class='error-message'>".safe_html($form_errors['delete_service']['service_id_to_delete'])."</p>"; ?>
                    </div>
                    <button type="submit" class="btn-admin-action btn-delete">Supprimer Service</button>
                </form>

                <hr class="form-separator">

                <h3 class="subsection-title">Supprimer un Créneau de Disponibilité</h3><!--pour sup un créneau-->
                <form action="admin_panel.php" method="POST" class="admin-form" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce créneau de disponibilité ?');">
                    <input type="hidden" name="action" value="delete_dispo">
                    <div class="form-group">
                        <label for="dispo_id_to_delete">Sélectionner le créneau à supprimer :</label>
                        <select id="dispo_id_to_delete" name="dispo_id_to_delete" required>
                            <option value="">-- Choisir un créneau --</option>
                            <?php foreach ($dispo_list as $dispo): ?>
                                <option value="<?= safe_html($dispo['ID']) ?>">
                                    ID: <?= safe_html($dispo['ID']) ?> | Date: <?= safe_html($dispo['Date']) ?> | Heure: <?= substr(safe_html($dispo['HeureDebut']), 0, 5) ?> - <?= substr(safe_html($dispo['HeureFin']), 0, 5) ?> | Pour: <?= safe_html($dispo['TargetName']) ?> (<?= safe_html(number_format((float)$dispo['Prix'], 2, ',', ' ')) ?> €)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($form_errors['delete_dispo']['dispo_id_to_delete'])) echo "<p class='error-message'>".safe_html($form_errors['delete_dispo']['dispo_id_to_delete'])."</p>"; ?>
                    </div>
                    <button type="submit" class="btn-admin-action btn-delete">Supprimer Créneau</button>
                </form>
            </section>

        </div>
    </main>

    <?php require 'includes/footer.php'; ?>

    <script>
        function toggleDispoTarget() {
            const dispoTypePersonnel = document.getElementById('dispo_type_personnel');
            const dispoTypeLaboratoire = document.getElementById('dispo_type_laboratoire');
            const personnelGroup = document.getElementById('personnel_target_group');
            const laboServiceGroup = document.getElementById('labo_service_target_group');
            const personnelSelect = document.getElementById('personnel_id_dispo');
            const laboServiceSelect = document.getElementById('labo_service_id_dispo');

            if (dispoTypePersonnel.checked) {
                personnelGroup.style.display = 'block';
                laboServiceGroup.style.display = 'none';
                personnelSelect.setAttribute('name', 'target_id'); 
                laboServiceSelect.removeAttribute('name');
                personnelSelect.required = true;
                laboServiceSelect.required = false;
            } else if (dispoTypeLaboratoire.checked) {
                personnelGroup.style.display = 'none';
                laboServiceGroup.style.display = 'block';
                laboServiceSelect.setAttribute('name', 'target_id'); 
                personnelSelect.removeAttribute('name');
                personnelSelect.required = false;
                laboServiceSelect.required = true;
            } else { 
                personnelGroup.style.display = 'none';
                laboServiceGroup.style.display = 'none';
                personnelSelect.removeAttribute('name');
                laboServiceSelect.removeAttribute('name');
                personnelSelect.required = false;
                laboServiceSelect.required = false;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            toggleDispoTarget(); 
        });
    </script>

    <style>
    .admin-main {
        padding: 2rem;
        background-color: #f2f2f2;
        display: flex;
        justify-content: center;
        align-items: flex-start;
        min-height: calc(100vh - 160px);
    }

    .admin-container {
        max-width: 900px;
        width: 100%;
        margin: auto;
        background: #fff;
        padding: 2.5rem;
        border-radius: 10px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        box-sizing: border-box;
    }

    .admin-title {
        text-align: center;
        color:rgb(10, 122, 191); 
        margin-bottom: 2.5rem;
        font-size: 2.5rem;
        font-weight: 700;
    }

    .admin-section {
        background-color:rgb(248, 249, 250);
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 2.5rem;
    }

    .section-title {
        color:rgb(10, 122, 191);
        font-size: 1.8rem;
        margin-top: 0;
        margin-bottom: 1.5rem;
        padding-bottom: 0.8rem;
        border-bottom: 2px solid #eaf5ff;
        text-align: center;
    }

    .admin-form .form-group {
        margin-bottom: 1rem;
    }

    .admin-form label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: #333;
    }

    .admin-form input[type="text"],
    .admin-form input[type="email"],
    .admin-form input[type="password"],
    .admin-form input[type="tel"],
    .admin-form input[type="date"],
    .admin-form input[type="time"],
    .admin-form input[type="number"],
    .admin-form select,
    .admin-form textarea {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #ccc;
        border-radius: 5px;
        font-size: 1rem;
        box-sizing: border-box;
        transition: border-color 0.2s ease;
    }

    .admin-form input:focus,
    .admin-form select:focus,
    .admin-form textarea:focus {
        outline: none;
        border-color: #0a7abf;
        box-shadow: 0 0 0 3px rgba(10, 122, 191, 0.2);
    }

    .admin-form textarea {
        min-height: 80px;
        resize: vertical;
    }

    .btn-admin-action {
        display: block;
        width: 100%;
        padding: 12px 25px;
        color: white;
        text-decoration: none;
        border-radius: 6px;
        font-weight: bold;
        transition: background-color 0.2s ease, transform 0.2s ease;
        margin-top: 1.5rem;
        text-align: center;
        border: none;
        cursor: pointer;
        font-size: 1.1rem;
    }

    .btn-add {
        background-color:rgb(40, 167, 69);
    }
    .btn-add:hover {
        background-color:rgb(33, 136, 56);
        transform: translateY(-2px);
    }

    .error-message {
        color:rgb(220, 53, 69);
        font-size: 0.85em;
        margin-top: 5px;
    }

    .admin-alert {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 5px;
        text-align: center;
        font-weight: 500;
    }
    .admin-alert.success {
        background-color:rgb(212, 237, 218); 
        color:rgb(21, 87, 36); 
        border: 1px solidrgb(195, 230, 203);
    }
    .admin-alert.error {
        background-color: #f8d7da; 
        color:rgb(114, 28, 36); 
        border: 1px solidrgb(245, 198, 203);
    }

    .admin-form input[type="radio"] {
        width: auto;
        margin-right: 0.5em;
    }
    .admin-form label[for="dispo_type_personnel"],
    .admin-form label[for="dispo_type_laboratoire"] {
        display: inline-block;
        margin-right: 1.5em;
        font-weight: normal; 
    }

    .delete-section {
        background-color: #ffebeb; 
        border-color:rgb(255, 194, 194); 
    }rgb(255, 194, 194)

    .section-title.delete-title {
        color:rgb(220, 53, 69); 
        border-bottom: 2px solidrgb(255, 194, 194);
    }

    .subsection-title {
        color:rgb(200, 35, 51);
        font-size: 1.25rem;
        margin-top: 1.5rem;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px dashed #ffc2c2;
    }

    .form-separator {
        border: 0;
        height: 1px;
        background-image: linear-gradient(to right, rgba(220, 53, 69, 0), rgba(220, 53, 69, 0.75), rgba(220, 53, 69, 0));
        margin: 2rem 0;
    }

    .btn-delete {
        background-color: #dc3545; 
    }

    .btn-delete:hover {
        background-color: #c82333;
        transform: translateY(-2px);
    }

    </style>
</body>
</html>
<?php
    if (isset($conn)) {
        mysqli_close($conn);
    }
?>