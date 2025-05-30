<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php?redirect=" . urlencode('profil.php'));
    exit();
}

if (isset($_SESSION["user_type"]) && $_SESSION["user_type"] === "admin") {
    header("Location: admin_panel.php");
    exit();
}

// --- DÉBUT DE LA PARTIE AVEC MySQLi en mode procédural ---

$db_host = "localhost";
$db_user = "root";
$db_pass = ""; // Votre mot de passe root, si vous en avez un
$db_name = "base_donne_web";

// 1. Connexion à la base de données
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Vérifier la connexion
if (!$conn) {
    // Ne jamais afficher mysqli_connect_error() directement en production.
    // Loggez l'erreur et affichez un message générique.
    error_log("Erreur de connexion BDD (mysqli_connect): " . mysqli_connect_error() . " (Code: " . mysqli_connect_errno() . ")");
    die("Erreur de connexion à la base de données. Veuillez réessayer plus tard.");
}

// 2. Définir le charset (très important)
if (!mysqli_set_charset($conn, "utf8")) {
    error_log("Erreur lors du chargement du jeu de caractères utf8 (mysqli_set_charset): " . mysqli_error($conn));
    mysqli_close($conn);
    die("Erreur de configuration de la base de données.");
}

$user_id = $_SESSION["user_id"];
// 3. ÉCHAPPEMENT OBLIGATOIRE pour éviter les injections SQL
//    Puisque $user_id vient de la session et est un entier, un cast (int) est plus sûr.
$user_id_safe = (int) $user_id;


$sql = "SELECT ID, Nom, Prenom, Email, TypeCompte FROM utilisateurs WHERE ID = $user_id_safe";
$result_user = mysqli_query($conn, $sql);

if (!$result_user) {
    error_log("Erreur requête utilisateur (mysqli_query): " . mysqli_error($conn) . " SQL: " . $sql);
    mysqli_close($conn);
    die("Une erreur s'est produite lors de la récupération de vos informations.");
}

$user = mysqli_fetch_assoc($result_user);
mysqli_free_result($result_user);

if (!$user) {
    session_destroy();
    mysqli_close($conn);
    header("Location: login.php");
    exit();
}

$type = $user["TypeCompte"];
$detailed_info = [];
$stats = [];

if ($type === "client") {
    $sql_client = "
        SELECT uc.Telephone, uc.CarteVitale,
               a.Adresse AS AdresseLigne, a.Ville, a.CodePostal, a.InfosComplementaires
        FROM utilisateurs_client uc
        LEFT JOIN adresse a ON uc.ID_Adresse = a.ID
        WHERE uc.ID = $user_id_safe
    ";
    $result_client = mysqli_query($conn, $sql_client);
    if ($result_client) {
        $detailed_info = mysqli_fetch_assoc($result_client) ?: [];
        mysqli_free_result($result_client);
    } else {
        error_log("Erreur requête client détails (mysqli_query): " . mysqli_error($conn) . " SQL: " . $sql_client);
    }

    // Client stats
    $sql_rdv_past = "SELECT COUNT(*) as count FROM rdv WHERE ID_Client = $user_id_safe AND CONCAT(DateRDV, ' ', HeureFin) < NOW()";
    $result_rdv_past = mysqli_query($conn, $sql_rdv_past);
    if ($result_rdv_past) {
        $row_rdv_past = mysqli_fetch_assoc($result_rdv_past);
        $stats['rdv_past_count'] = $row_rdv_past['count'] ?? 0;
        mysqli_free_result($result_rdv_past);
    } else {
        error_log("Erreur requête rdv_past_count (mysqli_query): " . mysqli_error($conn) . " SQL: " . $sql_rdv_past);
        $stats['rdv_past_count'] = 0;
    }

    $sql_rdv_upcoming = "SELECT COUNT(*) as count FROM rdv WHERE ID_Client = $user_id_safe AND CONCAT(DateRDV, ' ', HeureDebut) >= NOW()";
    $result_rdv_upcoming = mysqli_query($conn, $sql_rdv_upcoming);
     if ($result_rdv_upcoming) {
        $row_rdv_upcoming = mysqli_fetch_assoc($result_rdv_upcoming);
        $stats['rdv_upcoming_count'] = $row_rdv_upcoming['count'] ?? 0;
        mysqli_free_result($result_rdv_upcoming);
    } else {
        error_log("Erreur requête rdv_upcoming_count (mysqli_query): " . mysqli_error($conn) . " SQL: " . $sql_rdv_upcoming);
        $stats['rdv_upcoming_count'] = 0;
    }

} elseif ($type === "Personnel") {
    $sql_personnel = "
        SELECT up.Telephone, up.Description, up.Type AS Specialite, up.Photo, up.Video, cv.ContenuXML AS CV_Content,
               a.Adresse AS AdresseLigne, a.Ville, a.CodePostal, a.InfosComplementaires
        FROM utilisateurs_personnel up
        LEFT JOIN adresse a ON up.ID_Adresse = a.ID
        LEFT JOIN cv ON up.ID = cv.ID_Personnel
        WHERE up.ID = $user_id_safe
    ";
    $result_personnel = mysqli_query($conn, $sql_personnel);
    if ($result_personnel) {
        $detailed_info = mysqli_fetch_assoc($result_personnel) ?: [];
        mysqli_free_result($result_personnel);
    } else {
         error_log("Erreur requête personnel détails (mysqli_query): " . mysqli_error($conn) . " SQL: " . $sql_personnel);
    }

    // Personnel stats
    $sql_rdv_pers_up = "SELECT COUNT(*) as count FROM rdv WHERE ID_Personnel = $user_id_safe AND CONCAT(DateRDV, ' ', HeureDebut) >= NOW()";
    $result_rdv_pers_up = mysqli_query($conn, $sql_rdv_pers_up);
    if ($result_rdv_pers_up) {
        $row_rdv_pers_up = mysqli_fetch_assoc($result_rdv_pers_up);
        $stats['personnel_rdv_upcoming_count'] = $row_rdv_pers_up['count'] ?? 0;
        mysqli_free_result($result_rdv_pers_up);
    } else {
        error_log("Erreur requête personnel_rdv_upcoming_count (mysqli_query): " . mysqli_error($conn) . " SQL: " . $sql_rdv_pers_up);
        $stats['personnel_rdv_upcoming_count'] = 0;
    }

    $sql_dispo_pers_up = "SELECT COUNT(*) as count FROM dispo WHERE IdPersonnel = $user_id_safe AND CONCAT(Date, ' ', HeureDebut) >= NOW()";
    $result_dispo_pers_up = mysqli_query($conn, $sql_dispo_pers_up);
    if ($result_dispo_pers_up) {
        $row_dispo_pers_up = mysqli_fetch_assoc($result_dispo_pers_up);
        $stats['personnel_dispo_upcoming_count'] = $row_dispo_pers_up['count'] ?? 0;
        mysqli_free_result($result_dispo_pers_up);
    } else {
        error_log("Erreur requête personnel_dispo_upcoming_count (mysqli_query): " . mysqli_error($conn) . " SQL: " . $sql_dispo_pers_up);
        $stats['personnel_dispo_upcoming_count'] = 0;
    }
}
// Le bloc admin est omis car la redirection initiale l'empêche d'être atteint.
// Et si on l'atteignait, il faudrait aussi échapper les variables ou utiliser des requêtes préparées.

// --- FIN DE LA PARTIE AVEC MySQLi en mode procédural ---

$success_message = isset($_SESSION['profile_success']) ? $_SESSION['profile_success'] : '';
$error_message = isset($_SESSION['profile_error']) ? $_SESSION['profile_error'] : '';
unset($_SESSION['profile_success']);
unset($_SESSION['profile_error']);

function safe_html($value) {
    return $value !== null ? htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') : '';
}
?>

<!DOCTYPE html>
<html lang="fr">
<?php require 'includes/head.php'; ?>
<body>
<?php require 'includes/header.php'; ?>

<main class="profile-main">
    <div class="profile-container">
        <div class="profile-page-header">
            <h2 class="profile-title">Mon Profil</h2>
            <div class="account-type-badge-container">
                 <span class="account-type-tag type-<?= strtolower(safe_html($type)) ?>"><?= safe_html(ucfirst($type)) ?></span>
            </div>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="profile-alert success"><?= safe_html($success_message) ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="profile-alert error"><?= safe_html($error_message) ?></div>
        <?php endif; ?>

        <section class="profile-section">
            <h3 class="section-title">Informations Générales du Compte</h3>
            <div class="profile-details-grid">
                <p class="profile-detail-item"><strong>Nom :</strong> <?= safe_html($user["Nom"] ?? '') ?></p>
                <p class="profile-detail-item"><strong>Prénom :</strong> <?= safe_html($user["Prenom"] ?? '') ?></p>
                <p class="profile-detail-item full-width"><strong>Email :</strong> <?= safe_html($user["Email"] ?? '') ?></p>
            </div>
        </section>

        <?php if ($type === "Personnel") : ?>
            <section class="profile-section">
                <h3 class="section-title">Détails Professionnels</h3>
                <?php if (!empty($detailed_info["Photo"])): ?>
                    <div class="profile-photo-wrapper">
                        <img src="uploads/<?= safe_html($detailed_info["Photo"]) ?>" alt="Photo de profil" class="profile-photo-display">
                    </div>
                <?php endif; ?>
                <div class="profile-details-grid">
                    <p class="profile-detail-item"><strong>Téléphone :</strong> <?= safe_html($detailed_info["Telephone"] ?? "Non renseigné") ?></p>
                    <p class="profile-detail-item"><strong>Spécialité / Type :</strong> <?= safe_html($detailed_info["Specialite"] ?? "Non renseignée") ?></p>

                    <?php if (!empty($detailed_info["Description"])): ?>
                        <p class="profile-detail-item full-width"><strong>Description :</strong><br><span class="description-text"><?= nl2br(safe_html($detailed_info["Description"])) ?></span></p>
                    <?php endif; ?>

                    <?php if (!empty($detailed_info["AdresseLigne"])): ?>
                        <p class="profile-detail-item full-width"><strong>Adresse Cabinet :</strong>
                            <span class="address-block">
                                <?= safe_html($detailed_info["AdresseLigne"]) ?><br>
                                <?= safe_html($detailed_info["CodePostal"] ?? '') ?> <?= safe_html($detailed_info["Ville"] ?? '') ?>
                                <?php if (!empty($detailed_info["InfosComplementaires"])): ?>
                                    <br><em class="address-complement"><?= safe_html($detailed_info["InfosComplementaires"]) ?></em>
                                <?php endif; ?>
                            </span>
                        </p>
                    <?php else: ?>
                        <p class="profile-detail-item full-width"><strong>Adresse Cabinet :</strong> Non renseignée</p>
                    <?php endif; ?>

                     <?php if (!empty($detailed_info["Video"])): ?>
                        <p class="profile-detail-item"><strong>Vidéo :</strong>
                            <a href="uploads/<?= safe_html($detailed_info["Video"]) ?>" target="_blank" class="video-link">Voir la vidéo</a>
                        </p>
                    <?php endif; ?>
                     <?php if (!empty($detailed_info["CV_Content"])): ?>
                        <p class="profile-detail-item"><strong>CV :</strong>
                            CV Disponible
                        </p>
                    <?php else: ?>
                        <p class="profile-detail-item"><strong>CV :</strong> Non renseigné</p>
                    <?php endif; ?>
                </div>
            </section>
            <section class="profile-section">
                <h3 class="section-title">Activité Professionnelle</h3>
                <div class="profile-details-grid">
                    <p class="profile-detail-item"><strong>Vos RDV à venir :</strong> <?= safe_html($stats['personnel_rdv_upcoming_count'] ?? '0'); ?> </p>
                    <p class="profile-detail-item"><strong>Vos créneaux libres à venir :</strong> <?= safe_html($stats['personnel_dispo_upcoming_count'] ?? '0'); ?></p>
                </div>
            </section>

        <?php elseif ($type === "client") : ?>
            <section class="profile-section">
                <h3 class="section-title">Détails Personnels</h3>
                <div class="profile-details-grid">
                    <p class="profile-detail-item"><strong>Téléphone :</strong> <?= safe_html($detailed_info["Telephone"] ?? "Non renseigné") ?></p>
                    <p class="profile-detail-item"><strong>Carte Vitale :</strong> <?= safe_html($detailed_info["CarteVitale"] ?? "Non renseignée") ?></p>
                    <?php if (!empty($detailed_info["AdresseLigne"])): ?>
                        <p class="profile-detail-item full-width"><strong>Adresse :</strong>
                             <span class="address-block">
                                <?= safe_html($detailed_info["AdresseLigne"]) ?><br>
                                <?= safe_html($detailed_info["CodePostal"] ?? '') ?> <?= safe_html($detailed_info["Ville"] ?? '') ?>
                                <?php if (!empty($detailed_info["InfosComplementaires"])): ?>
                                    <br><em class="address-complement"><?= safe_html($detailed_info["InfosComplementaires"]) ?></em>
                                <?php endif; ?>
                            </span>
                        </p>
                    <?php else: ?>
                        <p class="profile-detail-item full-width"><strong>Adresse :</strong> Non renseignée</p>
                    <?php endif; ?>
                </div>
            </section>
            <section class="profile-section">
                <h3 class="section-title">Vos Rendez-vous</h3>
                <div class="profile-details-grid">
                    <p class="profile-detail-item"><strong>Rendez-vous passés :</strong> <?= safe_html($stats['rdv_past_count'] ?? '0'); ?></p>
                    <p class="profile-detail-item"><strong>Rendez-vous à venir :</strong> <?= safe_html($stats['rdv_upcoming_count'] ?? '0'); ?></p>
                </div>
            </section>
        <?php endif; ?>

        <div class="profile-actions">
             <a href="modifier_profil.php" class="btn-profile-action btn-edit-profile">Modifier mon compte</a>
            <a href="logout.php" class="btn-profile-action btn-logout">Se déconnecter</a>
        </div>
    </div>
</main>

<?php require 'includes/footer.php'; ?>

<style>
/* ... Vos styles CSS restent inchangés ... */
.profile-main {
    padding: 2rem;
    background-color: #f2f2f2;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    min-height: calc(100vh - 160px);
}

.profile-container {
    max-width: 750px;
    width: 100%;
    margin: 1rem auto;
    background: #fff;
    padding: 2.5rem;
    border-radius: 10px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    box-sizing: border-box;
}

.profile-page-header { /* New wrapper for title and badge */
    text-align: center;
    margin-bottom: 2.5rem;
}

.profile-title {
    color: #0a7abf;
    margin-bottom: 0.5rem; /* Reduced margin as badge is below */
    font-size: 2.2rem;
    font-weight: 600;
}
.account-type-badge-container {
    margin-top: 0.5rem; /* Space between title and badge */
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

.profile-details-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.8rem 1.5rem; /* Adjusted gap */
}

@media (min-width: 600px) {
    .profile-details-grid {
        grid-template-columns: 1fr 1fr;
    }
    .profile-detail-item.full-width {
        grid-column: 1 / -1;
    }
}

.profile-detail-item {
    margin: 0;
    font-size: 1.05rem;
    color: #495057;
    padding-bottom: 0.5rem;
    line-height: 1.6; /* Improved line height */
}

.profile-detail-item strong {
    color: #0a7abf;
    font-weight: 600;
    margin-right: 0.5rem;
    display: inline-block;
    min-width: 120px;
}
.description-text, .address-block {
    display: block; /* Ensure these start on a new line after the strong tag */
    margin-left: 10px; /* Indent the actual content slightly */
    margin-top: 4px;
}


.account-type-tag {
    display: inline-block;
    padding: 0.4em 0.9em; /* Slightly more padding */
    border-radius: 15px; /* More pill-like */
    font-weight: 500; /* Adjusted weight */
    font-size: 0.9em;
    color: white;
    text-transform: capitalize;
}
.account-type-tag.type-client { background-color: #28a745; }
.account-type-tag.type-personnel { background-color: #17a2b8; }
.account-type-tag.type-admin { background-color: #6c757d; } /* Grey for admin */

.profile-photo-wrapper {
    text-align: center; /* Center the photo */
    margin-bottom: 1rem;
}
.profile-photo-display {
    max-width: 150px; /* Slightly larger */
    height: 150px;
    object-fit: cover; /* Ensure photo covers the area well */
    border-radius: 50%; /* Circular photo */
    border: 3px solid #0a7abf; /* Border matching theme */
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.video-link, .cv-link { /* Combined styles for links */
    display: inline-block;
    background-color: #007bff;
    color: white !important; /* Ensure white text */
    padding: 8px 15px;
    border-radius: 5px;
    text-decoration: none;
    font-weight: 500;
    transition: background-color 0.2s ease;
    margin-top: 5px;
}
.video-link:hover, .cv-link:hover {
    background-color: #0056b3;
    color: white !important;
}
.cv-link { /* Specific if needed */
    background-color: #5a6268; /* Different color for CV maybe */
}
.cv-link:hover {
    background-color: #474c51;
}


.address-complement {
    font-size: 0.9em;
    color: #6c757d;
    display: block;
    margin-top: 5px;
    font-style: italic;
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
    border: none;
    cursor: pointer;
}

.btn-edit-profile {
    background-color: #0a7abf;
}
.btn-edit-profile:hover {
    background-color: #075c92;
    transform: translateY(-2px);
}

.btn-logout {
    background-color: #dc3545;
}
.btn-logout:hover {
    background-color: #c82333;
    transform: translateY(-2px);
}

.profile-alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 5px;
    text-align: center;
    font-weight: 500;
}
.profile-alert.success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}
.profile-alert.error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
</style>
</body>
</html>
<?php
// 4. Fermer la connexion
if (isset($conn) && $conn) {
    mysqli_close($conn);
}
?>