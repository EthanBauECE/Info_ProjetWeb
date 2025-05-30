<?php
session_start();

if (!isset($_SESSION["user_id"])) {//VERIFIER QUE QUAND LA PERSONNE SE CO?NCETE SON COMPTE EXISTE DANS LA BASE DE DONNE DES CLEINT
    header("Location: login.php?redirect=" . urlencode('profil.php'));//ON LE REDIRIGE SUR LA PAGE DE LA CONNEXION SI CE N EST PAS LE CAS
    exit();
}

if (isset($_SESSION["user_type"]) && $_SESSION["user_type"] === "admin") {// SI LA PERSONNE SE CONNCETE ET A UN COMPTE ASDMIN
    header("Location: admin_panel.php");//ON LE DIRIGE SUR SA PAGE SPECIALISE ADMIN
    exit();
}


$db_host = "localhost";//CONNECTION base de donnee
$db_user = "root";
$db_pass = ""; 
$db_name = "base_donne_web";

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);//ICI ON VA SE CONNECTER A NOTRE BASE DE DONNEE POUR POUVOIR L EXPLOITER


if (!$conn) {//SI LA PERSONNE N ARRIVE PAS A SE CONNECTER
    error_log("Erreur de connexion BDD" . mysqli_connect_error() . " (Code: " . mysqli_connect_errno() . ")");//ON LUI INDIQUE QU IL A UN PB CAR IL N EST PAS ENREGISTRER SUR NOTRE SITE
    die("Erreur de connexion à la BDD");
}

if (!mysqli_set_charset($conn, "utf8")) {//SI UTF8 MARCHE PAS 
    error_log("Erreur : " . mysqli_error($conn));//ON L INDIQUE A L UTILISATEUR POUR QU IL SACHE
    mysqli_close($conn);//ON FERME
    die("Erreur de configuration de la base de données.");
}

$user_id = $_SESSION["user_id"];//ICI C EST L ID DE L UTILISATEUR
$user_id_safe = (int) $user_id;//ON BLOCK

$sql = "SELECT ID, Nom, Prenom, Email, TypeCompte FROM utilisateurs WHERE ID = $user_id_safe";//ON PREND LES INFORMATIONS DE LA PERSONNE
$result_user = mysqli_query($conn, $sql);//ON LE STOCK DANS LA BASE DE DONNE

if (!$result_user) {//ON GARDE UNE TRACE DE L ERRREJR
    error_log("Erreur requête utilisateur " . mysqli_error($conn) . " SQL: " . $sql);//ON INQUIE A LA PERSONNE ERREUR
    mysqli_close($conn);
    die("Une erreur s'est produite lors de la récupération de vos informations.");//ON ARRETE LA SAISIE ON ABANDONNE
}

$user = mysqli_fetch_assoc($result_user);//ON RECUPER ELES INFO SUR L UTILISATEURS QUI ONT ETE DONNE
mysqli_free_result($result_user);

if (!$user) {//SI L ID DE LA PERSONNE DE L APPLI EST PAS COMME CELLE DE LA BASE
    session_destroy();//ON ANNULE TOUT
    mysqli_close($conn);
    header("Location: login.php");//ON DEMANDE A LA PERSONNE DE SE RECONNECTER
    exit();
}

$type = $user["TypeCompte"];//ON REGARDE LE TYPE DU COMPTE DE LA PERSONNE
$detailed_info = [];//ICI LES INFO
$stats = [];//ET LES STAATS DE LA PERSONNES

if ($type==="client") {//SI C EST UN CLIENT
    $sql_client = "
        SELECT uc.Telephone, uc.CarteVitale,
               a.Adresse AS AdresseLigne, a.Ville, a.CodePostal, a.InfosComplementaires
        FROM utilisateurs_client uc
        LEFT JOIN adresse a ON uc.ID_Adresse = a.ID
        WHERE uc.ID = $user_id_safe
    ";//ON PRENDS TOUTES LES INFOS DU CLIENT QU ON VA STOCKER
    $result_client = mysqli_query($conn, $sql_client);//ON STOCK
    if ($result_client) {//SI C EST BIEN UN CLIENT LA PERSONNE
        $detailed_info = mysqli_fetch_assoc($result_client) ?: [];//ON GARDE TOUTES SES INFOS 
        mysqli_free_result($result_client);
    } else {
        error_log("Erreur requête clienT: " . mysqli_error($conn) . " SQL: " . $sql_client);//SINON LUI DIRE QU IL Y A UN PB
    }

    $sql_rdv_past = "SELECT COUNT(*) as count FROM rdv WHERE ID_Client = $user_id_safe AND CONCAT(DateRDV, ' ', HeureFin) < NOW()";//CONCERCNE POUR LES RDV PASSE DONC POUR HISTORIQUE DE LA PERSONNE
    $result_rdv_past = mysqli_query($conn, $sql_rdv_past);
    if ($result_rdv_past) {//LES RENDEZ VOUS PAASEE
        $row_rdv_past = mysqli_fetch_assoc($result_rdv_past);
        $stats['rdv_past_count'] = $row_rdv_past['count'] ?? 0;//ON LES AJOUTE A HISTORIQYE
        mysqli_free_result($result_rdv_past);
    } else {
        error_log("Erreur requête rdv passe " . mysqli_error($conn) . " SQL: " . $sql_rdv_past);//INDIQUER UTILISATUERS LE PB
        $stats['rdv_past_count'] = 0;//ON LE MET PAS
    }

    $sql_rdv_upcoming = "SELECT COUNT(*) as count FROM rdv WHERE ID_Client = $user_id_safe AND CONCAT(DateRDV, ' ', HeureDebut) >= NOW()";//POUR LES RDV A VENIR DE LA PERSONNE CONNECTE
    $result_rdv_upcoming = mysqli_query($conn, $sql_rdv_upcoming);//ON AJOUTE A SON EDT
     if ($result_rdv_upcoming) {
        $row_rdv_upcoming = mysqli_fetch_assoc($result_rdv_upcoming);
        $stats['rdv_upcoming_count'] = $row_rdv_upcoming['count'] ?? 0;//ON AJOUTE
        mysqli_free_result($result_rdv_upcoming);
    } else {
        error_log("Erreur requête rdv futurs" . mysqli_error($conn) . " SQL: " . $sql_rdv_upcoming);//PREVENIR QUE Y A UN PB AVEC SON RDV A VE?IT
        $stats['rdv_upcoming_count'] = 0;//ON COMPTABUILISE PAS
    }

} elseif ($type === "Personnel") {//SI L UTILISATEUR QUI VEUT EST UN PERSONNEL DONC MEDECIN
    $sql_personnel = "
        SELECT up.Telephone, up.Description, up.Type AS Specialite, up.Photo, up.Video, cv.ContenuXML AS CV_Content,
               a.Adresse AS AdresseLigne, a.Ville, a.CodePostal, a.InfosComplementaires
        FROM utilisateurs_personnel up
        LEFT JOIN adresse a ON up.ID_Adresse = a.ID
        LEFT JOIN cv ON up.ID = cv.ID_Personnel
        WHERE up.ID = $user_id_safe
    ";//ON PREND TOUTES SE SINFO
    $result_personnel = mysqli_query($conn, $sql_personnel);//ON ENREGISTRE LES INFO QUE LE PERSONNEL A DONNE SUR LUI
    if ($result_personnel) {// SI C EST BON
        $detailed_info = mysqli_fetch_assoc($result_personnel) ?: [];//ON GARDE SES INFO ET ON L INTEGRE
        mysqli_free_result($result_personnel);
    } else {
         error_log("Erreur requête personnel" . mysqli_error($conn) . " SQL: " . $sql_personnel);//ON PREVIENT LA PERSONNE QU IL Y A UN PB ET ON PEUT PAS
    }

    $sql_rdv_pers_up = "SELECT COUNT(*) as count FROM rdv WHERE ID_Personnel = $user_id_safe AND CONCAT(DateRDV, ' ', HeureDebut) >= NOW()";//ON REGARDE LES RENDEZ VOUS QUI VONT ARRIVER POUR LUI
    $result_rdv_pers_up = mysqli_query($conn, $sql_rdv_pers_up);//ICI ON VA AFFICHER SES DIFFERENTES DISPO POUR TENIR INFORME LES CLIENTS FUTURS
    if ($result_rdv_pers_up) {
        $row_rdv_pers_up = mysqli_fetch_assoc($result_rdv_pers_up);
        $stats['personnel_rdv_upcoming_count'] = $row_rdv_pers_up['count'] ?? 0;//ON PREND EN COMPTE LA DEMANDE
        mysqli_free_result($result_rdv_pers_up);//ON AJOUTE
    } else {
        error_log("Erreur requête " . mysqli_error($conn) . " SQL: " . $sql_rdv_pers_up);//ON PREVIENT QU IL Y A UN PB
        $stats['personnel_rdv_upcoming_count'] = 0;//ON A NNULE LA DEMANDE
    }

    $sql_dispo_pers_up = "SELECT COUNT(*) as count FROM dispo WHERE IdPersonnel = $user_id_safe AND CONCAT(Date, ' ', HeureDebut) >= NOW()";//ICI ON VA REGARDER SES DISPONIBILITES
    $result_dispo_pers_up = mysqli_query($conn, $sql_dispo_pers_up);
    if ($result_dispo_pers_up) {//SI IL A DES DISPO
        $row_dispo_pers_up = mysqli_fetch_assoc($result_dispo_pers_up);
        $stats['personnel_dispo_upcoming_count'] = $row_dispo_pers_up['count'] ?? 0;//IL EST DISPO
        mysqli_free_result($result_dispo_pers_up);//ON AFFICHE AU PUBLIC SES DISPO
    } else {
        error_log("Erreur requête" . mysqli_error($conn) . " SQL: " . $sql_dispo_pers_up);//INDIQUEER QUE PAS POQQIBLE CAR UN PB ET PREVENIR
        $stats['personnel_dispo_upcoming_count'] = 0;//ANNULE LA REQUETE COMMMENCE
    }
}

$success_message = isset($_SESSION['profile_success']) ? $_SESSION['profile_success'] : '';//ON VA METTRE UN MESSAGE PAS LOGTEMPS POUR PREVENIR DE LA REUSSITE
$error_message = isset($_SESSION['profile_error']) ? $_SESSION['profile_error'] : '';//ON VA METTRE UN MESSAGE PAS LOGTEMPS POUR PREVENIR DE PB
unset($_SESSION['profile_success']);//ON ENLEVE LE MESS
unset($_SESSION['profile_error']);//ON ENLEVE AUSSI LE MESS

function safe_html($value) {//POUR LA SECURITE 
    return $value !== 0 ? htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') : '';
}
?>

<!DOCTYPE html><!--DEBUT DU HTML-->
<html lang="fr">
<?php require 'includes/head.php'; ?><!--pour garder haurt de la page-->
<body>
<?php require 'includes/header.php'; ?><!--pour garder le footer de la page-->

<main class="profile-main"><!--on cree la page avec les petites parties -->
    <div class="profile-container">
        <div class="profile-page-header">
            <h2 class="profile-title">Mon Profil</h2>
            <div class="account-type-badge-container">
                 <span class="account-type-tag type-<?= strtolower(safe_html($type)) ?>"><?= safe_html(ucfirst($type)) ?></span><!--on indique le type de compte et on me la premiere lette en majuscule pour faire propre-->
            </div>
        </div>

        <?php if (!empty($success_message)): ?><!--SI CA A BIEN MARCH2-->
            <div class="profile-alert success"><?= safe_html($success_message) ?></div><!--on indique a l utilisateur que ca a marche-->
        <?php endif; ?>
        <?php if (!empty($error_message)): ?><!--on fait pareil mais pour les erreurs-->
            <div class="profile-alert error"><?= safe_html($error_message) ?></div><!--on previent la personne-->
        <?php endif; ?>

        <section class="profile-section"><!--endroit ou la personne aura ses informations personnlelles-->
            <h3 class="section-title">Informations Générales du Compte</h3><!--titre indicatiosn-->
            <div class="profile-details-grid">
                <p class="profile-detail-item"><strong>Nom :</strong> <?= safe_html($user["Nom"] ?? '') ?></p><!--patie pour indiquer le nom de la personne-->
                <p class="profile-detail-item"><strong>Prénom :</strong> <?= safe_html($user["Prenom"] ?? '') ?></p><!--patie pour indiquer le prenom de la personne-->
                <p class="profile-detail-item full-width"><strong>Email :</strong> <?= safe_html($user["Email"] ?? '') ?></p><!--patie pour indiquer le mail de la personne-->
            </div>
        </section>

        <?php if ($type === "Personnel") : ?><!--mainetenant pour le personnel-->
            <section class="profile-section">
                <h3 class="section-title">Détails Professionnels</h3><!--titre indicatiosn premier bloc-->
                <?php if (!empty($detailed_info["Photo"])): ?>
                    <div class="profile-photo-wrapper">
                        <img src="uploads/<?= safe_html($detailed_info["Photo"]) ?>" alt="Photo de profil" class="profile-photo-display"><!--photo de profil du personnel a afficher-->
                    </div>
                <?php endif; ?>
                <div class="profile-details-grid"><!--dans un autre bloc en dessous pour etrebeai-->
                    <p class="profile-detail-item"><strong>Téléphone :</strong> <?= safe_html($detailed_info["Telephone"] ?? "Non renseigné") ?></p><!--telephone du personnel-->
                    <p class="profile-detail-item"><strong>Spécialité / Type :</strong> <?= safe_html($detailed_info["Specialite"] ?? "Non renseignée") ?></p><!--sa spécialité-->

                    <?php if (!empty($detailed_info["Description"])): ?><!--autre partie pour la description du personnel-->
                        <p class="profile-detail-item full-width"><strong>Description :</strong><br><span class="description-text"><?= nl2br(safe_html($detailed_info["Description"])) ?></span></p>
                    <?php endif; ?>

                    <?php if (!empty($detailed_info["AdresseLigne"])): ?>
                        <p class="profile-detail-item full-width"><strong>Adresse Cabinet :</strong><!--tindiquer son adresse precise pour client apres-->
                            <span class="address-block">
                                <?= safe_html($detailed_info["AdresseLigne"]) ?><br><!--son numero adresse et tout-->
                                <?= safe_html($detailed_info["CodePostal"] ?? '') ?> <?= safe_html($detailed_info["Ville"] ?? '') ?><!--la ville de la ou il estn-->
                                <?php if (!empty($detailed_info["InfosComplementaires"])): ?><!--si des info en plus-->
                                    <br><em class="address-complement"><?= safe_html($detailed_info["InfosComplementaires"]) ?></em>
                                <?php endif; ?>
                            </span>
                        </p>
                    <?php else: ?>
                        <p class="profile-detail-item full-width"><strong>Adresse Cabinet :</strong> Non renseignée</p><!--marque qu il ne l a pas dit -->
                    <?php endif; ?>

                     <?php if (!empty($detailed_info["Video"])): ?>
                        <p class="profile-detail-item"><strong>Vidéo :</strong><!--pour la video de la personne-->
                            <a href="uploads/<?= safe_html($detailed_info["Video"]) ?>" target="_blank" class="video-link">Voir la vidéo</a><!--pouvoir voir la video du professionnel su rl eprofil-->
                        </p>
                    <?php endif; ?>
                     <?php if (!empty($detailed_info["CV_Content"])): ?><!--pour son CV-->
                        <p class="profile-detail-item"><strong>CV :</strong>
                            CV Disponible<!--indiquer que c est disponible-->
                        </p>
                    <?php else: ?>
                        <p class="profile-detail-item"><strong>CV :</strong> Non renseigné</p><!--Sinon inquiquer a la perosnne que le docteru  n en a pas mis-->
                    <?php endif; ?>
                </div>
            </section>
            <section class="profile-section"><!--une autre partie du profil-->
                <h3 class="section-title">Activité Professionnelle</h3><!--indiquer le titre-->
                <div class="profile-details-grid">
                    <p class="profile-detail-item"><strong>Vos RDV à venir :</strong> <?= safe_html($stats['personnel_rdv_upcoming_count'] ?? '0'); ?> </p><!--indiquer des creneau qui sont deja pris par des gens-->
                    <p class="profile-detail-item"><strong>Vos créneaux libres à venir :</strong> <?= safe_html($stats['personnel_dispo_upcoming_count'] ?? '0'); ?></p><!--indiquer des creneau qui sont possible de prendre-->
                </div>
            </section>

        <?php elseif ($type === "client") : ?><!--si la personne est un client-->
            <section class="profile-section">
                <h3 class="section-title">Détails Personnels</h3><!--iindiquer le titre de la premiere partie de la page-->
                <div class="profile-details-grid">
                    <p class="profile-detail-item"><strong>Téléphone :</strong> <?= safe_html($detailed_info["Telephone"] ?? "Non renseigné") ?></p><!--idonner l info sur son numero de telephone pour verifier-->
                    <p class="profile-detail-item"><strong>Carte Vitale :</strong> <?= safe_html($detailed_info["CarteVitale"] ?? "Non renseignée") ?></p><!--Le numero de sa carte vitale-->
                    <?php if (!empty($detailed_info["AdresseLigne"])): ?>
                        <p class="profile-detail-item full-width"><strong>Adresse :</strong><!--on peut aussi voir son adresse affichéee-->
                             <span class="address-block">
                                <?= safe_html($detailed_info["AdresseLigne"]) ?><br>
                                <?= safe_html($detailed_info["CodePostal"] ?? '') ?> <?= safe_html($detailed_info["Ville"] ?? '') ?><!--son code postale-->
                                <?php if (!empty($detailed_info["InfosComplementaires"])): ?><!--les infos en plus au cas ou-->
                                    <br><em class="address-complement"><?= safe_html($detailed_info["InfosComplementaires"]) ?></em>
                                <?php endif; ?>
                            </span>
                        </p>
                    <?php else: ?>
                        <p class="profile-detail-item full-width"><strong>Adresse :</strong> Non renseignée</p><!--indiquer a la personne que ce champp est pas indique dans le profil-->
                    <?php endif; ?>
                </div>
            </section>
            <section class="profile-section"><!--deuxieme partie de la page-->
                <h3 class="section-title">Vos Rendez-vous</h3><!--ion met le titre-->
                <div class="profile-details-grid">
                    <p class="profile-detail-item"><strong>Rendez-vous passés :</strong> <?= safe_html($stats['rdv_past_count'] ?? '0'); ?></p><!--voir historique des ses rdv-->
                    <p class="profile-detail-item"><strong>Rendez-vous à venir :</strong> <?= safe_html($stats['rdv_upcoming_count'] ?? '0'); ?></p><!--voir les futurs qui vont arriver-->
                </div>
            </section>
        <?php endif; ?>

        <div class="profile-actions"><!--derniere partie de la page-->
             <a href="modifier_profil.php" class="btn-profile-action btn-edit-profile">Modifier mon compte</a><!--un bouton qui permet a la personne de pouvoir changer ses informations-->
            <a href="logout.php" class="btn-profile-action btn-logout">Se déconnecter</a><!--pouvoir changer de compte en cliqaunt sur ce bouton-->
        </div>
    </div>
</main>

<?php require 'includes/footer.php'; ?><!--footer de la page pour esthétiuqe-->

<style>

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

.profile-page-header { 
    text-align: center;
    margin-bottom: 2.5rem;
}

.profile-title {
    color: #0a7abf;
    margin-bottom: 0.5rem; 
    font-size: 2.2rem;
    font-weight: 600;
}
.account-type-badge-container {
    margin-top: 0.5rem; 
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
    gap: 0.8rem 1.5rem; 
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
    line-height: 1.6; 
}

.profile-detail-item strong {
    color: #0a7abf;
    font-weight: 600;
    margin-right: 0.5rem;
    display: inline-block;
    min-width: 120px;
}
.description-text, .address-block {
    display: block; 
    margin-left: 10px; 
    margin-top: 4px;
}


.account-type-tag {
    display: inline-block;
    padding: 0.4em 0.9em;
    border-radius: 15px; 
    font-weight: 500; 
    font-size: 0.9em;
    color: white;
    text-transform: capitalize;
}
.account-type-tag.type-client { background-color: #28a745; }
.account-type-tag.type-personnel { background-color: #17a2b8; }
.account-type-tag.type-admin { background-color: #6c757d; } 

.profile-photo-wrapper {
    text-align: center; 
    margin-bottom: 1rem;
}
.profile-photo-display {
    max-width: 150px; 
    height: 150px;
    object-fit: cover; 
    border-radius: 50%; 
    border: 3px solid #0a7abf; 
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.video-link, .cv-link {
    display: inline-block;
    background-color: #007bff;
    color: white !important; 
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
.cv-link { 
    background-color: #5a6268; 
    
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

if (isset($conn) && $conn) {
    mysqli_close($conn);
}
?>