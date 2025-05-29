<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Redirect if not logged in or not an admin
if (!isset($_SESSION["user_id"]) || $_SESSION["user_type"] !== "Admin") {
    header("Location: login.php"); // Or to index.php with an error
    exit();
}

// Database connection
try {
    $pdo = new PDO("mysql:host=localhost;dbname=base_donne_web;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // Ajouté pour faciliter la récupération
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

$success_message = '';
$error_message = '';
$form_errors = []; // To store validation errors for each form

// Function to safely display HTML
function safe_html($value) {
    return $value !== null ? htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') : '';
}

// --- Fetch data for dropdowns (Personnel, Labs, Services, Dispos, All Users) ---
$personnel_list = [];
$stmt_personnel = $pdo->query("SELECT u.ID, u.Nom, u.Prenom, up.Type FROM utilisateurs u JOIN utilisateurs_personnel up ON u.ID = up.ID ORDER BY u.Nom, u.Prenom");
while ($row = $stmt_personnel->fetch(PDO::FETCH_ASSOC)) {
    $personnel_list[] = $row;
}

$laboratories_list = [];
$stmt_labs = $pdo->query("SELECT ID, Nom FROM laboratoire ORDER BY Nom");
while ($row = $stmt_labs->fetch(PDO::FETCH_ASSOC)) {
    $laboratories_list[] = $row;
}

// NOUVEAU : Récupérer la liste des services de laboratoire pour la suppression
$services_list = [];
$stmt_services = $pdo->query("SELECT sl.ID, sl.NomService, l.Nom AS LaboNom FROM service_labo sl JOIN laboratoire l ON sl.ID_Laboratoire = l.ID ORDER BY l.Nom, sl.NomService");
while ($row = $stmt_services->fetch(PDO::FETCH_ASSOC)) {
    $services_list[] = $row;
}

// NOUVEAU : Récupérer la liste des créneaux de disponibilité pour la suppression
$dispo_list = [];
$stmt_dispo = $pdo->query("
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
    ORDER BY d.Date ASC, d.HeureDebut ASC
");
while ($row = $stmt_dispo->fetch(PDO::FETCH_ASSOC)) {
    $dispo_list[] = $row;
}

// NOUVEAU : Récupérer la liste de TOUS les utilisateurs pour la suppression de compte
$all_users_list = [];
$stmt_all_users = $pdo->query("SELECT ID, Nom, Prenom, TypeCompte FROM utilisateurs ORDER BY Nom, Prenom");
while ($row = $stmt_all_users->fetch(PDO::FETCH_ASSOC)) {
    $all_users_list[] = $row;
}


// --- Handle Form Submissions ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        // ... (Tes cas existants : add_personnel, add_laboratory, add_service, add_dispo) ...
        // Voici le bloc à insérer après les cas existants (add_)

        case 'delete_account':
            $user_id_to_delete = intval($_POST['user_id_to_delete'] ?? 0);
            if ($user_id_to_delete === 0) {
                $form_errors['delete_account']['user_id_to_delete'] = "Veuillez sélectionner un compte à supprimer.";
            } elseif ($user_id_to_delete == $_SESSION['user_id']) { // Empêcher l'admin de se supprimer lui-même
                $form_errors['delete_account']['self_delete'] = "Vous ne pouvez pas supprimer votre propre compte.";
            }

            if (empty($form_errors['delete_account'])) {
                $pdo->beginTransaction();
                try {
                    // 1. Récupérer le type de compte de l'utilisateur
                    $stmt_type = $pdo->prepare("SELECT TypeCompte FROM utilisateurs WHERE ID = ?");
                    $stmt_type->execute([$user_id_to_delete]);
                    $user_type_to_delete = $stmt_type->fetchColumn();

                    // 2. Supprimer les entrées dépendantes
                    if ($user_type_to_delete === 'client') {
                        // Supprimer les RDV associés à ce client
                        $stmt_del_rdv_client = $pdo->prepare("DELETE FROM rdv WHERE ID_Client = ?");
                        $stmt_del_rdv_client->execute([$user_id_to_delete]);
                        // Supprimer l'entrée dans utilisateurs_client
                        $stmt_del_client = $pdo->prepare("DELETE FROM utilisateurs_client WHERE ID = ?");
                        $stmt_del_client->execute([$user_id_to_delete]);
                    } elseif ($user_type_to_delete === 'Personnel') { // 'Personnel' avec P majuscule
                        // Supprimer les RDV associés à ce personnel
                        $stmt_del_rdv_personnel = $pdo->prepare("DELETE FROM rdv WHERE ID_Personnel = ?");
                        $stmt_del_rdv_personnel->execute([$user_id_to_delete]);
                        // Supprimer les créneaux de dispo associés
                        $stmt_del_dispo_personnel = $pdo->prepare("DELETE FROM dispo WHERE IdPersonnel = ?");
                        $stmt_del_dispo_personnel->execute([$user_id_to_delete]);
                        // Supprimer le CV
                        $stmt_del_cv = $pdo->prepare("DELETE FROM cv WHERE ID_Personnel = ?");
                        $stmt_del_cv->execute([$user_id_to_delete]);
                        // Supprimer l'entrée dans utilisateurs_personnel
                        $stmt_del_personnel = $pdo->prepare("DELETE FROM utilisateurs_personnel WHERE ID = ?");
                        $stmt_del_personnel->execute([$user_id_to_delete]);
                    } elseif ($user_type_to_delete === 'Admin') {
                        // Empêcher la suppression d'un administrateur (sauf si c'est une fonctionnalité voulue)
                        $pdo->rollBack();
                        $error_message = "Impossible de supprimer un compte Administrateur via ce formulaire.";
                        break; // Sortir du switch
                    }

                    // 3. Supprimer l'entrée principale dans utilisateurs
                    $stmt_del_user = $pdo->prepare("DELETE FROM utilisateurs WHERE ID = ?");
                    $stmt_del_user->execute([$user_id_to_delete]);

                    $pdo->commit();
                    $success_message = "Compte utilisateur supprimé avec succès.";
                    // Recharger les listes pour les dropdowns
                    // Recharger toutes les listes après suppression
                    $all_users_list = $pdo->query("SELECT ID, Nom, Prenom, TypeCompte FROM utilisateurs ORDER BY Nom, Prenom")->fetchAll();
                    $personnel_list = $pdo->query("SELECT u.ID, u.Nom, u.Prenom, up.Type FROM utilisateurs u JOIN utilisateurs_personnel up ON u.ID = up.ID ORDER BY u.Nom, u.Prenom")->fetchAll();
                    $dispo_list = $pdo->query("SELECT d.ID, d.Date, d.HeureDebut, d.HeureFin, d.Prix, CASE WHEN d.IdPersonnel != 0 THEN CONCAT(u.Prenom, ' ', u.Nom, ' (', up.Type, ')') WHEN d.IdServiceLabo != 0 THEN CONCAT(l.Nom, ' - ', sl.NomService) ELSE 'Inconnu' END AS TargetName FROM dispo d LEFT JOIN utilisateurs u ON d.IdPersonnel = u.ID LEFT JOIN utilisateurs_personnel up ON d.IdPersonnel = up.ID LEFT JOIN service_labo sl ON d.IdServiceLabo = sl.ID LEFT JOIN laboratoire l ON sl.ID_Laboratoire = l.ID ORDER BY d.Date ASC, d.HeureDebut ASC")->fetchAll();

                    $_POST = []; // Clear POST data
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    error_log("Delete account failed: " . $e->getMessage());
                    $error_message = "Erreur lors de la suppression du compte : " . $e->getMessage();
                }
            } else {
                $error_message = "Veuillez corriger les erreurs dans le formulaire 'Supprimer un compte'.";
            }
            break;

        case 'delete_professional':
            $personnel_id_to_delete = intval($_POST['personnel_id_to_delete'] ?? 0);
            if ($personnel_id_to_delete === 0) {
                $form_errors['delete_professional']['personnel_id_to_delete'] = "Veuillez sélectionner un professionnel à supprimer.";
            } elseif ($personnel_id_to_delete == $_SESSION['user_id'] && $_SESSION['user_type'] === 'Personnel') {
                $form_errors['delete_professional']['self_delete'] = "Vous ne pouvez pas supprimer votre propre compte professionnel.";
            }

            if (empty($form_errors['delete_professional'])) {
                $pdo->beginTransaction();
                try {
                    // Comme un personnel est aussi un utilisateur, on va utiliser la logique de suppression de compte
                    // pour s'assurer que toutes les dépendances utilisateur sont gérées.
                    // On pourrait appeler la logique du 'delete_account' mais pour éviter la complexité de fonction,
                    // on duplique la logique ici mais ciblée sur le personnel.

                    // 1. Supprimer les RDV associés à ce personnel
                    $stmt_del_rdv = $pdo->prepare("DELETE FROM rdv WHERE ID_Personnel = ?");
                    $stmt_del_rdv->execute([$personnel_id_to_delete]);
                    // 2. Supprimer les créneaux de dispo associés
                    $stmt_del_dispo = $pdo->prepare("DELETE FROM dispo WHERE IdPersonnel = ?");
                    $stmt_del_dispo->execute([$personnel_id_to_delete]);
                    // 3. Supprimer le CV
                    $stmt_del_cv = $pdo->prepare("DELETE FROM cv WHERE ID_Personnel = ?");
                    $stmt_del_cv->execute([$personnel_id_to_delete]);
                    // 4. Supprimer l'entrée dans utilisateurs_personnel
                    $stmt_del_personnel_detail = $pdo->prepare("DELETE FROM utilisateurs_personnel WHERE ID = ?");
                    $stmt_del_personnel_detail->execute([$personnel_id_to_delete]);
                    // 5. Supprimer l'entrée principale dans utilisateurs
                    $stmt_del_user_main = $pdo->prepare("DELETE FROM utilisateurs WHERE ID = ?");
                    $stmt_del_user_main->execute([$personnel_id_to_delete]);

                    $pdo->commit();
                    $success_message = "Compte professionnel supprimé avec succès.";
                    // Recharger les listes pour les dropdowns
                    $personnel_list = $pdo->query("SELECT u.ID, u.Nom, u.Prenom, up.Type FROM utilisateurs u JOIN utilisateurs_personnel up ON u.ID = up.ID ORDER BY u.Nom, u.Prenom")->fetchAll();
                    $all_users_list = $pdo->query("SELECT ID, Nom, Prenom, TypeCompte FROM utilisateurs ORDER BY Nom, Prenom")->fetchAll();
                    $dispo_list = $pdo->query("SELECT d.ID, d.Date, d.HeureDebut, d.HeureFin, d.Prix, CASE WHEN d.IdPersonnel != 0 THEN CONCAT(u.Prenom, ' ', u.Nom, ' (', up.Type, ')') WHEN d.IdServiceLabo != 0 THEN CONCAT(l.Nom, ' - ', sl.NomService) ELSE 'Inconnu' END AS TargetName FROM dispo d LEFT JOIN utilisateurs u ON d.IdPersonnel = u.ID LEFT JOIN utilisateurs_personnel up ON d.IdPersonnel = up.ID LEFT JOIN service_labo sl ON d.IdServiceLabo = sl.ID LEFT JOIN laboratoire l ON sl.ID_Laboratoire = l.ID ORDER BY d.Date ASC, d.HeureDebut ASC")->fetchAll();

                    $_POST = [];
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    error_log("Delete professional failed: " . $e->getMessage());
                    $error_message = "Erreur lors de la suppression du professionnel : " . $e->getMessage();
                }
            } else {
                 $error_message = "Veuillez corriger les erreurs dans le formulaire 'Supprimer un professionnel'.";
            }
            break;

        case 'delete_laboratory':
            $labo_id_to_delete = intval($_POST['labo_id_to_delete'] ?? 0);
            if ($labo_id_to_delete === 0) {
                $form_errors['delete_laboratory']['labo_id_to_delete'] = "Veuillez sélectionner un laboratoire à supprimer.";
            }

            if (empty($form_errors['delete_laboratory'])) {
                $pdo->beginTransaction();
                try {
                    // 1. Récupérer tous les services associés à ce laboratoire
                    $stmt_get_services = $pdo->prepare("SELECT ID FROM service_labo WHERE ID_Laboratoire = ?");
                    $stmt_get_services->execute([$labo_id_to_delete]);
                    $service_ids = $stmt_get_services->fetchAll(PDO::FETCH_COLUMN);

                    // 2. Pour chaque service, supprimer les RDV et les créneaux de dispo associés
                    if (!empty($service_ids)) {
                        $placeholders = implode(',', array_fill(0, count($service_ids), '?'));
                        $stmt_del_rdv_services = $pdo->prepare("DELETE FROM rdv WHERE ID_ServiceLabo IN ($placeholders)");
                        $stmt_del_rdv_services->execute($service_ids);

                        $stmt_del_dispo_services = $pdo->prepare("DELETE FROM dispo WHERE IdServiceLabo IN ($placeholders)");
                        $stmt_del_dispo_services->execute($service_ids);
                    }

                    // 3. Supprimer les services du laboratoire
                    $stmt_del_services_labo = $pdo->prepare("DELETE FROM service_labo WHERE ID_Laboratoire = ?");
                    $stmt_del_services_labo->execute([$labo_id_to_delete]);

                    // 4. Supprimer le laboratoire lui-même
                    $stmt_del_labo = $pdo->prepare("DELETE FROM laboratoire WHERE ID = ?");
                    $stmt_del_labo->execute([$labo_id_to_delete]);

                    $pdo->commit();
                    $success_message = "Laboratoire et tous ses services associés supprimés avec succès.";
                    // Recharger les listes pour les dropdowns
                    $laboratories_list = $pdo->query("SELECT ID, Nom FROM laboratoire ORDER BY Nom")->fetchAll();
                    $services_list = $pdo->query("SELECT sl.ID, sl.NomService, l.Nom AS LaboNom FROM service_labo sl JOIN laboratoire l ON sl.ID_Laboratoire = l.ID ORDER BY l.Nom, sl.NomService")->fetchAll();
                    $dispo_list = $pdo->query("SELECT d.ID, d.Date, d.HeureDebut, d.HeureFin, d.Prix, CASE WHEN d.IdPersonnel != 0 THEN CONCAT(u.Prenom, ' ', u.Nom, ' (', up.Type, ')') WHEN d.IdServiceLabo != 0 THEN CONCAT(l.Nom, ' - ', sl.NomService) ELSE 'Inconnu' END AS TargetName FROM dispo d LEFT JOIN utilisateurs u ON d.IdPersonnel = u.ID LEFT JOIN utilisateurs_personnel up ON d.IdPersonnel = up.ID LEFT JOIN service_labo sl ON d.IdServiceLabo = sl.ID LEFT JOIN laboratoire l ON sl.ID_Laboratoire = l.ID ORDER BY d.Date ASC, d.HeureDebut ASC")->fetchAll();

                    $_POST = [];
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    error_log("Delete laboratory failed: " . $e->getMessage());
                    $error_message = "Erreur lors de la suppression du laboratoire : " . $e->getMessage();
                }
            } else {
                 $error_message = "Veuillez corriger les erreurs dans le formulaire 'Supprimer un laboratoire'.";
            }
            break;

        case 'delete_service':
            $service_id_to_delete = intval($_POST['service_id_to_delete'] ?? 0);
            if ($service_id_to_delete === 0) {
                $form_errors['delete_service']['service_id_to_delete'] = "Veuillez sélectionner un service à supprimer.";
            }

            if (empty($form_errors['delete_service'])) {
                $pdo->beginTransaction();
                try {
                    // 1. Supprimer les RDV associés à ce service
                    $stmt_del_rdv_service = $pdo->prepare("DELETE FROM rdv WHERE ID_ServiceLabo = ?");
                    $stmt_del_rdv_service->execute([$service_id_to_delete]);

                    // 2. Supprimer les créneaux de dispo associés à ce service
                    $stmt_del_dispo_service = $pdo->prepare("DELETE FROM dispo WHERE IdServiceLabo = ?");
                    $stmt_del_dispo_service->execute([$service_id_to_delete]);

                    // 3. Supprimer le service lui-même
                    $stmt_del_service = $pdo->prepare("DELETE FROM service_labo WHERE ID = ?");
                    $stmt_del_service->execute([$service_id_to_delete]);

                    $pdo->commit();
                    $success_message = "Service et tous ses créneaux/RDV associés supprimés avec succès.";
                    // Recharger les listes pour les dropdowns
                    $services_list = $pdo->query("SELECT sl.ID, sl.NomService, l.Nom AS LaboNom FROM service_labo sl JOIN laboratoire l ON sl.ID_Laboratoire = l.ID ORDER BY l.Nom, sl.NomService")->fetchAll();
                    $dispo_list = $pdo->query("SELECT d.ID, d.Date, d.HeureDebut, d.HeureFin, d.Prix, CASE WHEN d.IdPersonnel != 0 THEN CONCAT(u.Prenom, ' ', u.Nom, ' (', up.Type, ')') WHEN d.IdServiceLabo != 0 THEN CONCAT(l.Nom, ' - ', sl.NomService) ELSE 'Inconnu' END AS TargetName FROM dispo d LEFT JOIN utilisateurs u ON d.IdPersonnel = u.ID LEFT JOIN utilisateurs_personnel up ON d.IdPersonnel = up.ID LEFT JOIN service_labo sl ON d.IdServiceLabo = sl.ID LEFT JOIN laboratoire l ON sl.ID_Laboratoire = l.ID ORDER BY d.Date ASC, d.HeureDebut ASC")->fetchAll();

                    $_POST = [];
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    error_log("Delete service failed: " . $e->getMessage());
                    $error_message = "Erreur lors de la suppression du service : " . $e->getMessage();
                }
            } else {
                 $error_message = "Veuillez corriger les erreurs dans le formulaire 'Supprimer un service'.";
            }
            break;

        case 'delete_dispo':
            $dispo_id_to_delete = intval($_POST['dispo_id_to_delete'] ?? 0);
            if ($dispo_id_to_delete === 0) {
                $form_errors['delete_dispo']['dispo_id_to_delete'] = "Veuillez sélectionner un créneau de disponibilité à supprimer.";
            }

            if (empty($form_errors['delete_dispo'])) {
                $pdo->beginTransaction();
                try {
                    // 1. Supprimer les RDV associés à ce créneau de dispo (vérifier si un RDV a été pris pour ce créneau qui n'existe plus)
                    // Note: Selon ta logique actuelle, un créneau dispo est supprimé une fois réservé.
                    // Donc, si on supprime un créneau dispo manuellement, il ne devrait pas y avoir de RDV lié.
                    // Cependant, pour la robustesse, on peut ajouter une vérification ou une suppression.
                    // Ici, je vais juste supprimer le créneau. Si un RDV était lié (ce qui est une anomalie si la logique est bonne),
                    // il deviendrait orphelin. Idéalement, la table rdv devrait avoir une FK vers dispo.

                    $stmt_del_dispo_single = $pdo->prepare("DELETE FROM dispo WHERE ID = ?");
                    $stmt_del_dispo_single->execute([$dispo_id_to_delete]);

                    $pdo->commit();
                    $success_message = "Créneau de disponibilité supprimé avec succès.";
                    // Recharger la liste des dispo
                    $dispo_list = $pdo->query("SELECT d.ID, d.Date, d.HeureDebut, d.HeureFin, d.Prix, CASE WHEN d.IdPersonnel != 0 THEN CONCAT(u.Prenom, ' ', u.Nom, ' (', up.Type, ')') WHEN d.IdServiceLabo != 0 THEN CONCAT(l.Nom, ' - ', sl.NomService) ELSE 'Inconnu' END AS TargetName FROM dispo d LEFT JOIN utilisateurs u ON d.IdPersonnel = u.ID LEFT JOIN utilisateurs_personnel up ON d.IdPersonnel = up.ID LEFT JOIN service_labo sl ON d.IdServiceLabo = sl.ID LEFT JOIN laboratoire l ON sl.ID_Laboratoire = l.ID ORDER BY d.Date ASC, d.HeureDebut ASC")->fetchAll();

                    $_POST = [];
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    error_log("Delete dispo failed: " . $e->getMessage());
                    $error_message = "Erreur lors de la suppression du créneau de disponibilité : " . $e->getMessage();
                }
            } else {
                 $error_message = "Veuillez corriger les erreurs dans le formulaire 'Supprimer un créneau de disponibilité'.";
            }
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<?php require 'includes/head.php'; ?>
<body>
<?php require 'includes/header.php'; ?>

<main class="admin-main">
    <div class="admin-container">
        <h1 class="admin-title">Panneau d'Administration</h1>

        <?php if (!empty($success_message)): ?>
            <div class="admin-alert success"><?= safe_html($success_message) ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="admin-alert error"><?= safe_html($error_message) ?></div>
        <?php endif; ?>

        <!-- Section Ajouter un compte personnel -->
        <section class="admin-section">
            <h2 class="section-title">Ajouter un compte personnel</h2>
            <form action="admin_panel.php" method="POST" class="admin-form">
                <input type="hidden" name="action" value="add_personnel">

                <div class="form-group">
                    <label for="nom">Nom :</label>
                    <input type="text" id="nom" name="nom" value="<?= safe_html($_POST['nom'] ?? '') ?>" required>
                    <?php if (isset($form_errors['add_personnel']['nom'])) echo "<p class='error-message'>".$form_errors['add_personnel']['nom']."</p>"; ?>
                </div>
                <div class="form-group">
                    <label for="prenom">Prénom :</label>
                    <input type="text" id="prenom" name="prenom" value="<?= safe_html($_POST['prenom'] ?? '') ?>" required>
                    <?php if (isset($form_errors['add_personnel']['prenom'])) echo "<p class='error-message'>".$form_errors['add_personnel']['prenom']."</p>"; ?>
                </div>
                <div class="form-group">
                    <label for="email">Email :</label>
                    <input type="email" id="email" name="email" value="<?= safe_html($_POST['email'] ?? '') ?>" required>
                    <?php if (isset($form_errors['add_personnel']['email'])) echo "<p class='error-message'>".$form_errors['add_personnel']['email']."</p>"; ?>
                </div>
                <div class="form-group">
                    <label for="password">Mot de passe :</label>
                    <input type="password" id="password" name="password" required>
                    <?php if (isset($form_errors['add_personnel']['password'])) echo "<p class='error-message'>".$form_errors['add_personnel']['password']."</p>"; ?>
                </div>
                <div class="form-group">
                    <label for="telephone">Téléphone :</label>
                    <input type="tel" id="telephone" name="telephone" value="<?= safe_html($_POST['telephone'] ?? '') ?>" required pattern="^0[67]\d{8}$" title="Ex: 0612345678">
                    <?php if (isset($form_errors['add_personnel']['telephone'])) echo "<p class='error-message'>".$form_errors['add_personnel']['telephone']."</p>"; ?>
                </div>
                <div class="form-group">
                    <label for="specialite">Spécialité/Type (ex: Généraliste, Cardiologue) :</label>
                    <input type="text" id="specialite" name="specialite" value="<?= safe_html($_POST['specialite'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="description">Description :</label>
                    <textarea id="description" name="description"><?= safe_html($_POST['description'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label for="adresse_ligne_personnel">Adresse (N° et Rue) :</label>
                    <input type="text" id="adresse_ligne_personnel" name="adresse_ligne" value="<?= safe_html($_POST['adresse_ligne'] ?? '') ?>" required>
                    <?php if (isset($form_errors['add_personnel']['adresse_ligne'])) echo "<p class='error-message'>".$form_errors['add_personnel']['adresse_ligne']."</p>"; ?>
                </div>
                <div class="form-group">
                    <label for="ville_personnel">Ville :</label>
                    <input type="text" id="ville_personnel" name="ville" value="<?= safe_html($_POST['ville'] ?? '') ?>" required>
                    <?php if (isset($form_errors['add_personnel']['ville'])) echo "<p class='error-message'>".$form_errors['add_personnel']['ville']."</p>"; ?>
                </div>
                <div class="form-group">
                    <label for="code_postal_personnel">Code Postal :</label>
                    <input type="text" id="code_postal_personnel" name="code_postal" value="<?= safe_html($_POST['code_postal'] ?? '') ?>" required maxlength="5" pattern="\d{5}">
                    <?php if (isset($form_errors['add_personnel']['code_postal'])) echo "<p class='error-message'>".$form_errors['add_personnel']['code_postal']."</p>"; ?>
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

        <!-- Section Ajouter un laboratoire -->
        <section class="admin-section">
            <h2 class="section-title">Ajouter un laboratoire</h2>
            <form action="admin_panel.php" method="POST" class="admin-form">
                <input type="hidden" name="action" value="add_laboratory">

                <div class="form-group">
                    <label for="lab_nom">Nom du laboratoire :</label>
                    <input type="text" id="lab_nom" name="nom" value="<?= safe_html($_POST['nom'] ?? '') ?>" required>
                    <?php if (isset($form_errors['add_laboratory']['nom'])) echo "<p class='error-message'>".$form_errors['add_laboratory']['nom']."</p>"; ?>
                </div>
                <div class="form-group">
                    <label for="lab_telephone">Téléphone :</label>
                    <input type="tel" id="lab_telephone" name="telephone" value="<?= safe_html($_POST['telephone'] ?? '') ?>" required pattern="^0[1-9]\d{8}$" title="Ex: 0123456789">
                    <?php if (isset($form_errors['add_laboratory']['telephone'])) echo "<p class='error-message'>".$form_errors['add_laboratory']['telephone']."</p>"; ?>
                </div>
                <div class="form-group">
                    <label for="lab_email">Email :</label>
                    <input type="email" id="lab_email" name="email" value="<?= safe_html($_POST['email'] ?? '') ?>" required>
                    <?php if (isset($form_errors['add_laboratory']['email'])) echo "<p class='error-message'>".$form_errors['add_laboratory']['email']."</p>"; ?>
                </div>
                <div class="form-group">
                    <label for="lab_description">Description :</label>
                    <textarea id="lab_description" name="description"><?= safe_html($_POST['description'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label for="lab_adresse_ligne">Adresse (N° et Rue) :</label>
                    <input type="text" id="lab_adresse_ligne" name="adresse_ligne" value="<?= safe_html($_POST['adresse_ligne'] ?? '') ?>" required>
                    <?php if (isset($form_errors['add_laboratory']['adresse_ligne'])) echo "<p class='error-message'>".$form_errors['add_laboratory']['adresse_ligne']."</p>"; ?>
                </div>
                <div class="form-group">
                    <label for="lab_ville">Ville :</label>
                    <input type="text" id="lab_ville" name="ville" value="<?= safe_html($_POST['ville'] ?? '') ?>" required>
                    <?php if (isset($form_errors['add_laboratory']['ville'])) echo "<p class='error-message'>".$form_errors['add_laboratory']['ville']."</p>"; ?>
                </div>
                <div class="form-group">
                    <label for="lab_code_postal">Code Postal :</label>
                    <input type="text" id="lab_code_postal" name="code_postal" value="<?= safe_html($_POST['code_postal'] ?? '') ?>" required maxlength="5" pattern="\d{5}">
                    <?php if (isset($form_errors['add_laboratory']['code_postal'])) echo "<p class='error-message'>".$form_errors['add_laboratory']['code_postal']."</p>"; ?>
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

        <!-- Section Ajouter un service et l'associer à un laboratoire -->
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
                    <?php if (isset($form_errors['add_service']['labo_id'])) echo "<p class='error-message'>".$form_errors['add_service']['labo_id']."</p>"; ?>
                </div>
                <div class="form-group">
                    <label for="nom_service">Nom du service :</label>
                    <input type="text" id="nom_service" name="nom_service" value="<?= safe_html($_POST['nom_service'] ?? '') ?>" required>
                    <?php if (isset($form_errors['add_service']['nom_service'])) echo "<p class='error-message'>".$form_errors['add_service']['nom_service']."</p>"; ?>
                </div>
                <div class="form-group">
                    <label for="prix_service">Prix (€) :</label>
                    <input type="number" step="0.01" id="prix_service" name="prix" value="<?= safe_html($_POST['prix'] ?? '') ?>" required min="0.01">
                    <?php if (isset($form_errors['add_service']['prix'])) echo "<p class='error-message'>".$form_errors['add_service']['prix']."</p>"; ?>
                </div>
                <div class="form-group">
                    <label for="service_description">Description du service (optionnel) :</label>
                    <textarea id="service_description" name="description"><?= safe_html($_POST['description'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn-admin-action btn-add">Ajouter Service</button>
            </form>
        </section>

        <!-- Section Créer un créneau de disponibilité -->
        <section class="admin-section">
            <h2 class="section-title">Ajouter un créneau de disponibilité</h2>
            <form action="admin_panel.php" method="POST" class="admin-form">
                <input type="hidden" name="action" value="add_dispo">

                <div class="form-group">
                    <label>Type de créneau :</label><br>
                    <input type="radio" id="dispo_type_personnel" name="dispo_type" value="personnel" <?= (isset($_POST['dispo_type']) && $_POST['dispo_type'] === 'personnel') ? 'checked' : '' ?> onchange="toggleDispoTarget()">
                    <label for="dispo_type_personnel">Personnel / Médecin</label>
                    <input type="radio" id="dispo_type_laboratoire" name="dispo_type" value="laboratoire" <?= (isset($_POST['dispo_type']) && $_POST['dispo_type'] === 'laboratoire') ? 'checked' : '' ?> onchange="toggleDispoTarget()">
                    <label for="dispo_type_laboratoire">Service de Laboratoire</label>
                    <?php if (isset($form_errors['add_dispo']['dispo_type'])) echo "<p class='error-message'>".$form_errors['add_dispo']['dispo_type']."</p>"; ?>
                </div>

                <div class="form-group" id="personnel_target_group" style="display:none;">
                    <label for="personnel_id">Sélectionner le personnel :</label>
                    <select id="personnel_id" name="target_id">
                        <option value="">-- Choisir un personnel --</option>
                        <?php foreach ($personnel_list as $personnel): ?>
                            <option value="<?= safe_html($personnel['ID']) ?>" <?= (isset($_POST['target_id']) && $_POST['dispo_type'] === 'personnel' && $_POST['target_id'] == $personnel['ID']) ? 'selected' : '' ?>>
                                Dr. <?= safe_html($personnel['Prenom']) ?> <?= safe_html($personnel['Nom']) ?> (<?= safe_html($personnel['Type']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" id="labo_service_target_group" style="display:none;">
                    <label for="labo_service_id">Sélectionner un service de laboratoire :</label>
                    <select id="labo_service_id" name="target_id">
                        <option value="">-- Choisir un service de laboratoire --</option>
                        <?php
                        $stmt_lab_services = $pdo->query("SELECT sl.ID, sl.NomService, l.Nom AS LaboNom FROM service_labo sl JOIN laboratoire l ON sl.ID_Laboratoire = l.ID ORDER BY l.Nom, sl.NomService");
                        while ($row = $stmt_lab_services->fetch(PDO::FETCH_ASSOC)): ?>
                            <option value="<?= safe_html($row['ID']) ?>" <?= (isset($_POST['target_id']) && $_POST['dispo_type'] === 'laboratoire' && $_POST['target_id'] == $row['ID']) ? 'selected' : '' ?>>
                                <?= safe_html($row['LaboNom']) ?> - <?= safe_html($row['NomService']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <?php if (isset($form_errors['add_dispo']['target_id'])) echo "<p class='error-message'>".$form_errors['add_dispo']['target_id']."</p>"; ?>

                <div class="form-group">
                    <label for="date_dispo">Date :</label>
                    <input type="date" id="date_dispo" name="date" value="<?= safe_html($_POST['date'] ?? date('Y-m-d')) ?>" required>
                    <?php if (isset($form_errors['add_dispo']['date'])) echo "<p class='error-message'>".$form_errors['add_dispo']['date']."</p>"; ?>
                </div>
                <div class="form-group">
                    <label for="heure_debut_dispo">Heure de début :</label>
                    <input type="time" id="heure_debut_dispo" name="heure_debut" value="<?= safe_html($_POST['heure_debut'] ?? '09:00') ?>" required>
                    <?php if (isset($form_errors['add_dispo']['heure_debut'])) echo "<p class='error-message'>".$form_errors['add_dispo']['heure_debut']."</p>"; ?>
                </div>
                <div class="form-group">
                    <label for="heure_fin_dispo">Heure de fin :</label>
                    <input type="time" id="heure_fin_dispo" name="heure_fin" value="<?= safe_html($_POST['heure_fin'] ?? '10:00') ?>" required>
                    <?php if (isset($form_errors['add_dispo']['heure_fin'])) echo "<p class='error-message'>".$form_errors['add_dispo']['heure_fin']."</p>"; ?>
                    <?php if (isset($form_errors['add_dispo']['time_order'])) echo "<p class='error-message'>".$form_errors['add_dispo']['time_order']."</p>"; ?>
                </div>
                <div class="form-group">
                    <label for="prix_dispo">Prix (€) :</label>
                    <input type="number" step="0.01" id="prix_dispo" name="prix_dispo" value="<?= safe_html($_POST['prix_dispo'] ?? '') ?>" required min="0.01">
                    <?php if (isset($form_errors['add_dispo']['prix_dispo'])) echo "<p class='error-message'>".$form_errors['add_dispo']['prix_dispo']."</p>"; ?>
                </div>
                <button type="submit" class="btn-admin-action btn-add">Ajouter Créneau</button>
            </form>
        </section>

        <!-- NOUVEAU : Section Supprimer des entités -->
        <section class="admin-section delete-section">
            <h2 class="section-title delete-title">Supprimer des entités</h2>

            <!-- Formulaire de suppression de Compte Utilisateur -->
            <h3 class="subsection-title">Supprimer un Compte Utilisateur (Client ou Personnel)</h3>
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
                    <?php if (isset($form_errors['delete_account']['user_id_to_delete'])) echo "<p class='error-message'>".$form_errors['delete_account']['user_id_to_delete']."</p>"; ?>
                    <?php if (isset($form_errors['delete_account']['self_delete'])) echo "<p class='error-message'>".$form_errors['delete_account']['self_delete']."</p>"; ?>
                </div>
                <button type="submit" class="btn-admin-action btn-delete">Supprimer Compte</button>
            </form>

            <hr class="form-separator">

            <!-- Formulaire de suppression de Personnel (spécialiste ou généraliste) -->
            <h3 class="subsection-title">Supprimer un Professionnel de Santé</h3>
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
                    <?php if (isset($form_errors['delete_professional']['personnel_id_to_delete'])) echo "<p class='error-message'>".$form_errors['delete_professional']['personnel_id_to_delete']."</p>"; ?>
                    <?php if (isset($form_errors['delete_professional']['self_delete'])) echo "<p class='error-message'>".$form_errors['delete_professional']['self_delete']."</p>"; ?>
                </div>
                <button type="submit" class="btn-admin-action btn-delete">Supprimer Professionnel</button>
            </form>

            <hr class="form-separator">

            <!-- Formulaire de suppression de Laboratoire -->
            <h3 class="subsection-title">Supprimer un Laboratoire</h3>
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
                    <?php if (isset($form_errors['delete_laboratory']['labo_id_to_delete'])) echo "<p class='error-message'>".$form_errors['delete_laboratory']['labo_id_to_delete']."</p>"; ?>
                </div>
                <button type="submit" class="btn-admin-action btn-delete">Supprimer Laboratoire</button>
            </form>

            <hr class="form-separator">

            <!-- Formulaire de suppression de Service de Laboratoire -->
            <h3 class="subsection-title">Supprimer un Service de Laboratoire</h3>
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
                    <?php if (isset($form_errors['delete_service']['service_id_to_delete'])) echo "<p class='error-message'>".$form_errors['delete_service']['service_id_to_delete']."</p>"; ?>
                </div>
                <button type="submit" class="btn-admin-action btn-delete">Supprimer Service</button>
            </form>

            <hr class="form-separator">

            <!-- Formulaire de suppression de Créneau de Disponibilité -->
            <h3 class="subsection-title">Supprimer un Créneau de Disponibilité</h3>
            <form action="admin_panel.php" method="POST" class="admin-form" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce créneau de disponibilité ?');">
                <input type="hidden" name="action" value="delete_dispo">
                <div class="form-group">
                    <label for="dispo_id_to_delete">Sélectionner le créneau à supprimer :</label>
                    <select id="dispo_id_to_delete" name="dispo_id_to_delete" required>
                        <option value="">-- Choisir un créneau --</option>
                        <?php foreach ($dispo_list as $dispo): ?>
                            <option value="<?= safe_html($dispo['ID']) ?>">
                                ID: <?= safe_html($dispo['ID']) ?> | Date: <?= safe_html($dispo['Date']) ?> | Heure: <?= substr(safe_html($dispo['HeureDebut']), 0, 5) ?> - <?= substr(safe_html($dispo['HeureFin']), 0, 5) ?> | Pour: <?= safe_html($dispo['TargetName']) ?> (<?= safe_html(number_format($dispo['Prix'], 2, ',', ' ')) ?> €)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($form_errors['delete_dispo']['dispo_id_to_delete'])) echo "<p class='error-message'>".$form_errors['delete_dispo']['dispo_id_to_delete']."</p>"; ?>
                </div>
                <button type="submit" class="btn-admin-action btn-delete">Supprimer Créneau</button>
            </form>
        </section>

    </div>
</main>

<?php require 'includes/footer.php'; ?>

<script>
    // JavaScript to toggle visibility of personnel/lab service dropdowns
    function toggleDispoTarget() {
        const dispoTypePersonnel = document.getElementById('dispo_type_personnel');
        const dispoTypeLaboratoire = document.getElementById('dispo_type_laboratoire');
        const personnelGroup = document.getElementById('personnel_target_group');
        const laboServiceGroup = document.getElementById('labo_service_target_group');

        if (dispoTypePersonnel.checked) {
            personnelGroup.style.display = 'block';
            laboServiceGroup.style.display = 'none';
            laboServiceGroup.querySelector('select').removeAttribute('name'); // Remove name to prevent submission
            personnelGroup.querySelector('select').setAttribute('name', 'target_id'); // Add name to be submitted
        } else if (dispoTypeLaboratoire.checked) {
            personnelGroup.style.display = 'none';
            laboServiceGroup.style.display = 'block';
            personnelGroup.querySelector('select').removeAttribute('name');
            laboServiceGroup.querySelector('select').setAttribute('name', 'target_id');
        } else {
            personnelGroup.style.display = 'none';
            laboServiceGroup.style.display = 'none';
            personnelGroup.querySelector('select').removeAttribute('name');
            laboServiceGroup.querySelector('select').removeAttribute('name');
        }
    }

    // Call on page load to set initial state based on POST data or default
    document.addEventListener('DOMContentLoaded', toggleDispoTarget);
</script>

<style>
/* Base styles similar to profil.php and modifier_profil.php */
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
    color: #0a7abf;
    margin-bottom: 2.5rem;
    font-size: 2.5rem;
    font-weight: 700;
}

.admin-section {
    background-color: #f8f9fa;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2.5rem;
}

.section-title {
    color: #0a7abf;
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
    background-color: #28a745;
}
.btn-add:hover {
    background-color: #218838;
    transform: translateY(-2px);
}

.error-message {
    color: #dc3545;
    font-size: 0.85em;
    margin-top: 5px;
}

/* Alert styles */
.admin-alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 5px;
    text-align: center;
    font-weight: 500;
}
.admin-alert.success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}
.admin-alert.error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* Radio button specific style */
.admin-form input[type="radio"] {
    width: auto; /* Override 100% width */
    margin-right: 0.5em;
}
.admin-form label[for="dispo_type_personnel"],
.admin-form label[for="dispo_type_laboratoire"] {
    display: inline-block;
    margin-right: 1.5em;
    font-weight: normal; /* Labels for radio buttons are less bold */
}

/* Styles pour la section de suppression */
.delete-section {
    background-color: #ffebeb; /* Couleur de fond plus douce pour les suppressions */
    border-color: #ffc2c2; /* Bordure rouge clair */
}

.section-title.delete-title {
    color: #dc3545; /* Rouge pour le titre de la section de suppression */
    border-bottom: 2px solid #ffc2c2;
}

.subsection-title {
    color: #dc3545; /* Rouge pour les sous-titres */
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
    background-color: #dc3545; /* Bouton rouge pour la suppression */
}

.btn-delete:hover {
    background-color: #c82333;
    transform: translateY(-2px);
}

</style>
</body>
</html>