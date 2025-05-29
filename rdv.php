<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

function safe_html($value) {
    return $value !== null ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : '';
}

// Redirection si l'utilisateur n'est pas connecté
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php?redirect=" . urlencode('rdv.php'));
    exit();
}

$idClient = $_SESSION["user_id"];
$success_message = '';
$error_message = '';

// --- Connexion à la base de données ---
$conn = new mysqli("localhost", "root", "", "base_donne_web");
if ($conn->connect_error) {
    die("Erreur de connexion: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// --- Traitement de l'annulation du rendez-vous ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_rdv_to_cancel'])) {
    $rdv_id_to_cancel = intval($_POST['id_rdv_to_cancel']);

    $conn->begin_transaction();
    try {
        $sql_get_rdv = "SELECT DateRDV, HeureDebut, HeureFin, ID_Personnel, ID_ServiceLabo, ID_Paiement
                        FROM rdv
                        WHERE ID = ? AND ID_Client = ?";
        $stmt_get_rdv = $conn->prepare($sql_get_rdv);
        if (!$stmt_get_rdv) throw new Exception("Erreur préparation requête get_rdv: " . $conn->error);
        $stmt_get_rdv->bind_param("ii", $rdv_id_to_cancel, $idClient);
        $stmt_get_rdv->execute();
        $result_get_rdv = $stmt_get_rdv->get_result();

        if ($result_get_rdv->num_rows === 0) {
            throw new Exception("Rendez-vous non trouvé ou vous n'êtes pas autorisé à l'annuler.");
        }

        $rdv_info = $result_get_rdv->fetch_assoc();
        $stmt_get_rdv->close();

        $date_rdv = $rdv_info['DateRDV'];
        $heure_debut_rdv = $rdv_info['HeureDebut'];
        $heure_fin_rdv = $rdv_info['HeureFin'];
        $id_personnel_rdv = $rdv_info['ID_Personnel'];
        $id_service_labo_rdv = $rdv_info['ID_ServiceLabo'];
        $id_paiement_rdv = $rdv_info['ID_Paiement'];

        $prix_to_reinsert = 0.0;
        if ($id_service_labo_rdv != 0) {
            $stmt_get_price = $conn->prepare("SELECT Prix FROM service_labo WHERE ID = ?");
            if (!$stmt_get_price) throw new Exception("Erreur préparation requête get_price: " . $conn->error);
            $stmt_get_price->bind_param("i", $id_service_labo_rdv);
            $stmt_get_price->execute();
            $result_get_price = $stmt_get_price->get_result();
            if ($price_row = $result_get_price->fetch_assoc()) {
                $prix_to_reinsert = $price_row['Prix'];
            }
            $stmt_get_price->close();
        } elseif ($id_personnel_rdv != 0) {
            $prix_to_reinsert = 28.00;
        }

        $sql_insert_dispo = "INSERT INTO dispo (Date, HeureDebut, HeureFin, IdPersonnel, IdServiceLabo, Prix)
                             VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_insert_dispo = $conn->prepare($sql_insert_dispo);
        if (!$stmt_insert_dispo) throw new Exception("Erreur préparation requête insert_dispo: " . $conn->error);
        $stmt_insert_dispo->bind_param("sssiid", $date_rdv, $heure_debut_rdv, $heure_fin_rdv, $id_personnel_rdv, $id_service_labo_rdv, $prix_to_reinsert);
        $stmt_insert_dispo->execute();
        $stmt_insert_dispo->close();

        $sql_delete_rdv = "DELETE FROM rdv WHERE ID = ?";
        $stmt_delete_rdv = $conn->prepare($sql_delete_rdv);
        if (!$stmt_delete_rdv) throw new Exception("Erreur préparation requête delete_rdv: " . $conn->error);
        $stmt_delete_rdv->bind_param("i", $rdv_id_to_cancel);
        $stmt_delete_rdv->execute();
        $stmt_delete_rdv->close();

        if ($id_paiement_rdv !== null) {
            $sql_delete_paiement = "DELETE FROM id_paiement WHERE ID = ?";
            $stmt_delete_paiement = $conn->prepare($sql_delete_paiement);
            if ($stmt_delete_paiement) {
                $stmt_delete_paiement->bind_param("i", $id_paiement_rdv);
                $stmt_delete_paiement->execute();
                $stmt_delete_paiement->close();
            } else {
                 error_log("Erreur préparation requête delete_paiement: " . $conn->error);
            }
        }
        $conn->commit();
        $success_message = "Rendez-vous annulé avec succès. Le créneau est de nouveau disponible.";
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Erreur lors de l'annulation: " . $e->getMessage();
    }
}

date_default_timezone_set('Europe/Paris');

// --- Récupération des rendez-vous pour l'affichage ---
$sql_rdv = "
    SELECT
        R.ID AS rdv_id,
        R.DateRDV,
        R.HeureDebut,
        R.HeureFin,
        R.Statut,
        R.InfoComplementaire,
        R.ID_Personnel,
        R.ID_ServiceLabo,

        -- Informations pour les rendez-vous médecin
        U_PROF.Nom AS prof_nom,
        U_PROF.Prenom AS prof_prenom,
        U_PROF.Email AS prof_email,       -- Email du professionnel
        UP.Photo AS prof_photo,
        UP.Telephone AS prof_telephone,   -- Téléphone du professionnel
        UP.Type AS prof_specialite,
        UP.ID_Adresse AS prof_id_adresse, -- ID Adresse du professionnel

        -- Adresse du professionnel
        AD_PROF.Adresse AS prof_adresse_ligne,
        AD_PROF.Ville AS prof_ville,
        AD_PROF.CodePostal AS prof_code_postal,
        AD_PROF.InfosComplementaires AS prof_adresse_infos_comp,

        -- Informations pour les rendez-vous laboratoire
        L.Nom AS labo_nom,
        L.Email AS labo_email,            -- Email du laboratoire
        L.Telephone AS labo_telephone,    -- Téléphone du laboratoire
        L.ID_Adresse AS labo_id_adresse,
        SL.NomService AS service_labo_nom,
        SL.Description AS service_labo_description, -- Description du service de laboratoire

        -- Adresse du laboratoire
        AD_LABO.Adresse AS labo_adresse_ligne,
        AD_LABO.Ville AS labo_ville,
        AD_LABO.CodePostal AS labo_code_postal,
        AD_LABO.InfosComplementaires AS labo_adresse_infos_comp
    FROM
        rdv R
    LEFT JOIN -- Pour les médecins (personnel)
        utilisateurs U_PROF ON R.ID_Personnel = U_PROF.ID AND R.ID_Personnel != 0 -- Assurer que c'est un personnel
    LEFT JOIN
        utilisateurs_personnel UP ON R.ID_Personnel = UP.ID
    LEFT JOIN
        adresse AD_PROF ON UP.ID_Adresse = AD_PROF.ID -- Adresse du personnel

    LEFT JOIN -- Pour les services (labo ou type de consultation médecin)
        service_labo SL ON R.ID_ServiceLabo = SL.ID
    LEFT JOIN -- Pour les laboratoires (si c'est un service de labo)
        laboratoire L ON SL.ID_Laboratoire = L.ID AND R.ID_Personnel = 0 -- Assurer que c'est un labo (ID_Personnel = 0)
    LEFT JOIN -- Pour l'adresse du laboratoire
        adresse AD_LABO ON L.ID_Adresse = AD_LABO.ID
    WHERE
        R.ID_Client = ?
    ORDER BY
        R.DateRDV ASC, R.HeureDebut ASC;
";


$stmt_rdv = $conn->prepare($sql_rdv);
if (!$stmt_rdv) {
    die("Erreur de préparation de la requête des RDV: " . $conn->error . "<br><pre>" . $sql_rdv . "</pre>");
}
$stmt_rdv->bind_param("i", $idClient);
$stmt_rdv->execute();
$result_rdv = $stmt_rdv->get_result();

$upcoming_rdv = [];
$past_rdv = [];
$now = new DateTime();

while ($rdv_data = $result_rdv->fetch_assoc()) {
    $rdv_datetime_str = $rdv_data['DateRDV'] . ' ' . $rdv_data['HeureDebut'];
    try {
        $rdv_datetime = new DateTime($rdv_datetime_str);
        if ($rdv_datetime < $now) {
            $past_rdv[] = $rdv_data;
        } else {
            $upcoming_rdv[] = $rdv_data;
        }
    } catch (Exception $e) {
        error_log("Invalid date format for RDV ID " . $rdv_data['rdv_id'] . ": " . $rdv_datetime_str . " - " . $e->getMessage());
    }
}

$stmt_rdv->close();
$conn->close();

if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
?>

<!DOCTYPE html>
<html lang="fr">
<?php require 'includes/head.php'; ?>
<body>
<?php require 'includes/header.php'; ?>

<main class="main-content-page">
    <h1 style="text-align: center; margin-bottom: 2rem;">Mes Rendez-vous</h1>

    <?php
    if (!empty($success_message)) {
        echo '<div class="alert success">' . safe_html($success_message) . '</div>';
    }
    if (!empty($error_message)) {
        echo '<div class="alert error">' . safe_html($error_message) . '</div>';
    }
    ?>

    <section class="rdv-section">
        <?php if (empty($upcoming_rdv) && empty($past_rdv)): ?>
            <p style="text-align: center; font-size: 1.1em; color: #6c757d; margin-top: 30px;">Vous n'avez aucun rendez-vous pour le moment.</p>
            <p style="text-align: center; margin-top: 15px;">
                <a href="medecine_general.php" class="btn-action-link">Prendre un rendez-vous médecin</a>
                <a href="laboratoire.php" class="btn-action-link btn-labo">Prendre un rendez-vous laboratoire</a>
            </p>
        <?php endif; ?>

        <?php if (!empty($upcoming_rdv)): ?>
            <h2 class="rdv-section-title">Rendez-vous à venir</h2>
            <div class="rdv-list">
                <?php foreach ($upcoming_rdv as $rdv): ?>
                    <div class="rdv-card upcoming">
                        <div class="rdv-header">
                            <?php
                            $is_doctor_rdv = (isset($rdv['ID_Personnel']) && $rdv['ID_Personnel'] != 0 && $rdv['ID_Personnel'] !== null);
                            ?>
                            <?php if ($is_doctor_rdv): ?>
                                <div class="rdv-photo-container">
                                    <img src="<?php echo safe_html($rdv['prof_photo'] ?: './images/default_doctor.png'); ?>" alt="Photo Dr. <?php echo safe_html($rdv['prof_nom']); ?>" class="rdv-photo">
                                </div>
                                <div class="rdv-title-details">
                                    <h3>Dr. <?php echo safe_html($rdv['prof_prenom']) . ' ' . safe_html($rdv['prof_nom']); ?></h3>
                                    <p class="rdv-type"><?php echo safe_html($rdv['prof_specialite']); ?></p>
                                </div>
                            <?php else: // Rendez-vous laboratoire ?>
                                <div class="rdv-photo-container">
                                    <img src="./images/default_labo.jpg" alt="Laboratoire" class="rdv-photo">
                                </div>
                                <div class="rdv-title-details">
                                    <h3><?php echo safe_html($rdv['labo_nom']); ?></h3>
                                    <p class="rdv-type">Service : <?php echo safe_html($rdv['service_labo_nom']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="rdv-body">
                            <p><strong>Date :</strong> <?php echo date("d/m/Y", strtotime(safe_html($rdv['DateRDV']))); ?></p>
                            <p><strong>Heure :</strong> <?php echo substr(safe_html($rdv['HeureDebut']), 0, 5) . ' - ' . substr(safe_html($rdv['HeureFin']), 0, 5); ?></p>

                            <?php if ($is_doctor_rdv): ?>
                                <?php if (!empty($rdv['prof_adresse_ligne'])): ?>
                                    <p><strong>Adresse Cabinet :</strong>
                                        <?php
                                            echo safe_html($rdv['prof_adresse_ligne']);
                                            if (!empty($rdv['prof_code_postal'])) echo ', ' . safe_html($rdv['prof_code_postal']);
                                            if (!empty($rdv['prof_ville'])) echo ' ' . safe_html($rdv['prof_ville']);
                                            if (!empty($rdv['prof_adresse_infos_comp'])) {
                                                echo '<br><em class="address-details">' . safe_html($rdv['prof_adresse_infos_comp']) . '</em>';
                                            }
                                        ?>
                                    </p>
                                <?php endif; ?>
                                <?php if (!empty($rdv['prof_telephone'])): ?>
                                    <p><strong>Téléphone Cabinet :</strong> <?php echo safe_html($rdv['prof_telephone']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($rdv['prof_email'])): ?>
                                    <p><strong>Email Cabinet :</strong> <a href="mailto:<?php echo safe_html($rdv['prof_email']); ?>"><?php echo safe_html($rdv['prof_email']); ?></a></p>
                                <?php endif; ?>
                            <?php else: // Laboratoire ?>
                                <?php if (!empty($rdv['labo_adresse_ligne'])): ?>
                                    <p><strong>Adresse Labo :</strong>
                                        <?php
                                            echo safe_html($rdv['labo_adresse_ligne']);
                                            if (!empty($rdv['labo_code_postal'])) echo ', ' . safe_html($rdv['labo_code_postal']);
                                            if (!empty($rdv['labo_ville'])) echo ' ' . safe_html($rdv['labo_ville']);
                                            if (!empty($rdv['labo_adresse_infos_comp'])) {
                                                echo '<br><em class="address-details">' . safe_html($rdv['labo_adresse_infos_comp']) . '</em>';
                                            }
                                        ?>
                                    </p>
                                <?php endif; ?>
                                <?php if (!empty($rdv['labo_telephone'])): ?>
                                    <p><strong>Téléphone Labo :</strong> <?php echo safe_html($rdv['labo_telephone']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($rdv['labo_email'])): ?>
                                    <p><strong>Email Labo :</strong> <a href="mailto:<?php echo safe_html($rdv['labo_email']); ?>"><?php echo safe_html($rdv['labo_email']); ?></a></p>
                                <?php endif; ?>
                                <?php if (!empty($rdv['service_labo_description'])): ?>
                                    <p><strong>Description du Service :</strong> <?php echo nl2br(safe_html($rdv['service_labo_description'])); ?></p>
                                <?php endif; ?>
                            <?php endif; ?>

                            <p><strong>Statut :</strong> <span class="status-upcoming"><?php echo safe_html($rdv['Statut']); ?></span></p>
                            <?php if (!empty($rdv['InfoComplementaire'])): ?>
                                <p><strong>Infos Complémentaires RDV :</strong> <?php echo safe_html($rdv['InfoComplementaire']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="rdv-actions">
                            <form method="POST" action="rdv.php" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler ce rendez-vous ?');">
                                <input type="hidden" name="id_rdv_to_cancel" value="<?php echo safe_html($rdv['rdv_id']); ?>">
                                <button type="submit" class="btn-cancel">Annuler le RDV</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($past_rdv)): ?>
            <h2 class="rdv-section-title past-title">Rendez-vous passés</h2>
            <div class="rdv-list">
                <?php foreach ($past_rdv as $rdv): ?>
                    <div class="rdv-card past">
                        <div class="rdv-header">
                            <?php
                            $is_doctor_rdv = (isset($rdv['ID_Personnel']) && $rdv['ID_Personnel'] != 0 && $rdv['ID_Personnel'] !== null);
                            ?>
                            <?php if ($is_doctor_rdv): ?>
                                <div class="rdv-photo-container">
                                    <img src="<?php echo safe_html($rdv['prof_photo'] ?: './images/default_doctor.png'); ?>" alt="Photo Dr. <?php echo safe_html($rdv['prof_nom']); ?>" class="rdv-photo grayscale">
                                </div>
                                <div class="rdv-title-details">
                                    <h3>Dr. <?php echo safe_html($rdv['prof_prenom']) . ' ' . safe_html($rdv['prof_nom']); ?></h3>
                                    <p class="rdv-type"><?php echo safe_html($rdv['prof_specialite']); ?></p>
                                </div>
                            <?php else: // Rendez-vous laboratoire ?>
                                <div class="rdv-photo-container">
                                    <img src="./images/default_labo.jpg" alt="Laboratoire" class="rdv-photo grayscale">
                                </div>
                                <div class="rdv-title-details">
                                    <h3><?php echo safe_html($rdv['labo_nom']); ?></h3>
                                    <p class="rdv-type">Service : <?php echo safe_html($rdv['service_labo_nom']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="rdv-body">
                            <p><strong>Date :</strong> <?php echo date("d/m/Y", strtotime(safe_html($rdv['DateRDV']))); ?></p>
                            <p><strong>Heure :</strong> <?php echo substr(safe_html($rdv['HeureDebut']), 0, 5) . ' - ' . substr(safe_html($rdv['HeureFin']), 0, 5); ?></p>

                            <?php if ($is_doctor_rdv): ?>
                                <?php if (!empty($rdv['prof_adresse_ligne'])): ?>
                                    <p><strong>Adresse Cabinet :</strong>
                                        <?php
                                            echo safe_html($rdv['prof_adresse_ligne']);
                                            if (!empty($rdv['prof_code_postal'])) echo ', ' . safe_html($rdv['prof_code_postal']);
                                            if (!empty($rdv['prof_ville'])) echo ' ' . safe_html($rdv['prof_ville']);
                                            if (!empty($rdv['prof_adresse_infos_comp'])) {
                                                echo '<br><em class="address-details">' . safe_html($rdv['prof_adresse_infos_comp']) . '</em>';
                                            }
                                        ?>
                                    </p>
                                <?php endif; ?>
                                <?php if (!empty($rdv['prof_telephone'])): ?>
                                    <p><strong>Téléphone Cabinet :</strong> <?php echo safe_html($rdv['prof_telephone']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($rdv['prof_email'])): ?>
                                    <p><strong>Email Cabinet :</strong> <a href="mailto:<?php echo safe_html($rdv['prof_email']); ?>"><?php echo safe_html($rdv['prof_email']); ?></a></p>
                                <?php endif; ?>
                            <?php else: // Laboratoire ?>
                                <?php if (!empty($rdv['labo_adresse_ligne'])): ?>
                                    <p><strong>Adresse Labo :</strong>
                                        <?php
                                            echo safe_html($rdv['labo_adresse_ligne']);
                                            if (!empty($rdv['labo_code_postal'])) echo ', ' . safe_html($rdv['labo_code_postal']);
                                            if (!empty($rdv['labo_ville'])) echo ' ' . safe_html($rdv['labo_ville']);
                                            if (!empty($rdv['labo_adresse_infos_comp'])) {
                                                echo '<br><em class="address-details">' . safe_html($rdv['labo_adresse_infos_comp']) . '</em>';
                                            }
                                        ?>
                                    </p>
                                <?php endif; ?>
                                <?php if (!empty($rdv['labo_telephone'])): ?>
                                    <p><strong>Téléphone Labo :</strong> <?php echo safe_html($rdv['labo_telephone']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($rdv['labo_email'])): ?>
                                    <p><strong>Email Labo :</strong> <a href="mailto:<?php echo safe_html($rdv['labo_email']); ?>"><?php echo safe_html($rdv['labo_email']); ?></a></p>
                                <?php endif; ?>
                                <?php if (!empty($rdv['service_labo_description'])): ?>
                                    <p><strong>Description du Service :</strong> <?php echo nl2br(safe_html($rdv['service_labo_description'])); ?></p>
                                <?php endif; ?>
                            <?php endif; ?>

                            <p><strong>Statut :</strong> <span class="status-past">Terminé</span></p>
                            <?php if (!empty($rdv['InfoComplementaire'])): ?>
                                <p><strong>Infos Complémentaires RDV :</strong> <?php echo safe_html($rdv['InfoComplementaire']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="rdv-actions">
                            <span class="past-label">Rendez-vous passé</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php require 'includes/footer.php'; ?>

<style>
/* Styles spécifiques pour la page rdv.php (la plupart existent déjà) */
.main-content-page {
    padding: 2rem;
    background-color: #f2f2f2;
    display: flex;
    flex-direction: column;
    align-items: center;
    min-height: calc(100vh - 160px);
}

.rdv-section {
    width: 100%;
    max-width: 900px;
    margin-bottom: 2rem;
}

.rdv-section-title {
    color: #0a7abf;
    margin-top: 30px;
    margin-bottom: 20px;
    text-align: center;
    font-size: 1.8rem;
}
.rdv-section-title.past-title {
    color: #6c757d;
    margin-top: 50px;
}

.rdv-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.rdv-card {
    background-color: #ffffff;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    padding: 20px;
    display: flex;
    flex-direction: column;
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.rdv-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
}

.rdv-card.past {
    background-color: #f8f9fa;
    border: 1px solid #e9ecef;
    opacity: 0.85;
}

.rdv-card.past:hover {
    transform: none;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.rdv-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}

.rdv-photo-container {
    flex-shrink: 0;
    width: 80px;
    height: 80px;
    border-radius: 50%;
    overflow: hidden;
    border: 2px solid #0a7abf;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f0f7ff;
}
.rdv-card.past .rdv-photo-container {
    border-color: #adb5bd;
}

.rdv-photo {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.rdv-photo.grayscale {
    filter: grayscale(100%);
}

.rdv-title-details h3 {
    margin: 0;
    font-size: 1.4rem;
    color: #0a7abf;
}

.rdv-title-details .rdv-type {
    margin: 5px 0 0;
    font-size: 0.95rem;
    color: #555;
}

.rdv-card.past .rdv-title-details h3,
.rdv-card.past .rdv-title-details .rdv-type {
    color: #6c757d;
}

.rdv-body p {
    margin: 8px 0;
    font-size: 1rem;
    line-height: 1.5;
    color: #333;
}
.rdv-body p a {
    color: #007bff;
    text-decoration: none;
}
.rdv-body p a:hover {
    text-decoration: underline;
}


.rdv-body strong {
    color: #0a7abf;
    font-weight: 600;
}
.rdv-body .address-details {
    font-size:0.9em;
    color: #6c757d;
}

.rdv-card.past .rdv-body strong,
.rdv-card.past .rdv-body p {
    color: #5a6268;
}
.rdv-card.past .rdv-body .address-details {
    color: #777;
}
.rdv-card.past .rdv-body p a {
    color: #0056b3; /* Darker link for past appointments */
}


.status-upcoming {
    font-weight: bold;
    color: #28a745;
    background-color: #e9f7ef;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 0.9em;
}

.status-past {
    font-weight: bold;
    color: #6c757d;
    background-color: #f1f3f5;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 0.9em;
}

.rdv-actions {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px dashed #eee;
    display: flex;
    justify-content: flex-end;
}

.btn-cancel {
    background-color: #dc3545;
    color: white;
    padding: 10px 18px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 0.95rem;
    font-weight: 500;
    transition: background-color 0.3s ease;
}

.btn-cancel:hover {
    background-color: #c82333;
}

.past-label {
    background-color: #e9ecef;
    color: #495057;
    padding: 8px 15px;
    border-radius: 5px;
    font-weight: 500;
    font-size: 0.9rem;
}

.alert {
    width: 100%;
    max-width: 860px;
    box-sizing: border-box;
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 5px;
    text-align: center;
    font-weight: 500;
}
.alert.success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}
.alert.error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.btn-action-link {
    display:inline-block;
    background-color:#007bff;
    color:white;
    padding:12px 20px;
    border-radius:5px;
    text-decoration:none;
    font-weight: 500;
    transition: background-color 0.2s ease;
    margin: 5px;
}
.btn-action-link:hover {
    background-color: #0056b3;
}
.btn-action-link.btn-labo {
    background-color: #17a2b8;
}
.btn-action-link.btn-labo:hover {
    background-color: #117a8b;
}

</style>
</body>
</html>