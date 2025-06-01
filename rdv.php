<?php
session_start(); //démarre session php-->accès aux var
error_reporting(E_ALL); //affiche ttes les erreurs si besoin pour corriger
ini_set('display_errors', 1);

function safe_html($value) {//empeche attaques XSS en échaappant les caracteres speciaux
    return $value !== null ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : '';
}

if (!isset($_SESSION["user_id"])) {//vérifie utilisateur conneté
    header("Location: login.php?redirect=" . urlencode('rdv.php'));//sinon redirect sur login.php
    exit();
}

$idClient = $_SESSION["user_id"]; //id utilisateur actuel
$success_message = '';//pas de message mais verif
$error_message = '';

$conn = new mysqli("localhost", "root", "", "base_donne_web");//connexion à base de donnees avec utilisateur root
if ($conn->connect_error) {//verif connexion fonctionne
    die("Erreur de connexion: " . $conn->connect_error);
}
$conn->set_charset("utf8");//pour accents fr etc

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_rdv_to_cancel'])) {//detecte si annulation rdv
    $rdv_id_to_cancel = intval($_POST['id_rdv_to_cancel']);//transforme id en entier securise

    $conn->begin_transaction(); //commence transact pour assurer que ttes les op se font ensemble
    try {
        $sql_get_rdv = "SELECT DateRDV, HeureDebut, HeureFin, ID_Personnel, ID_ServiceLabo, ID_Paiement
                        FROM rdv
                        WHERE ID = ? AND ID_Client = ?";//verif que le rdv existe et est à l'uitlisateur+ recup id du service, labo, med, horaire et paiement
        $stmt_get_rdv = $conn->prepare($sql_get_rdv);
        if (!$stmt_get_rdv) throw new Exception("Erreur préparation requête get_rdv: " . $conn->error); //reverif sur w3schools, verif erreurs
        $stmt_get_rdv->bind_param("ii", $rdv_id_to_cancel, $idClient); //https://www.php.net/manual/en/mysqli-stmt.bind-param.php, fais en sorte que l'annulation soit appliquee à l'utilisateur
        $stmt_get_rdv->execute(); 
        $result_get_rdv = $stmt_get_rdv->get_result();

        if ($result_get_rdv->num_rows === 0) { //pour gérer les erreurss
            throw new Exception("Rendez-vous non trouvé ou vous n'êtes pas autorisé à l'annuler.");//message d'erreur
        }

        $rdv_info = $result_get_rdv->fetch_assoc();//recup les lignes du rdv en tableau + lit la prochaine ligne de requete-->acces des chps daterdv, HeureDebut...
        $stmt_get_rdv->close(); //fermer le statement prep apres utilisation+libere ressources memoires

        $date_rdv = $rdv_info['DateRDV'];//stocke la date du rdv 
        $heure_debut_rdv = $rdv_info['HeureDebut']; //stocke heure du rdv
        $heure_fin_rdv = $rdv_info['HeureFin']; //stocke herue fin de rdv (utile pour dispo)
        $id_personnel_rdv = $rdv_info['ID_Personnel'];//stocke id medecin, si 0-> labo
        $id_service_labo_rdv = $rdv_info['ID_ServiceLabo']; //stocke le labo, si 0-->medecin
        $id_paiement_rdv = $rdv_info['ID_Paiement']; //id de l'entree ds table des paiement pour le rdv, facilite la suppression pour annulation

        $prix_to_reinsert = 0.0;//init prix 
        if ($id_service_labo_rdv != 0) {//verif si c'est un rdv en labo
            $stmt_get_price = $conn->prepare("SELECT Prix FROM service_labo WHERE ID = ?");//prep requete sql pour prix du service de labo
            if (!$stmt_get_price) throw new Exception("Erreur préparation requête get_price: " . $conn->error);//si echec, exception (debug)
            $stmt_get_price->bind_param("i", $id_service_labo_rdv);//lie id du labo à requete (type i=integer)
            $stmt_get_price->execute();//fais la requete
            $result_get_price = $stmt_get_price->get_result(); //recup resultat (prix)
            if ($price_row = $result_get_price->fetch_assoc()) {//si ligne retournée, on recup le champ prix 
                $prix_to_reinsert = $price_row['Prix'];//et stocke dans$prix_to_reinsert 
            }
            $stmt_get_price->close();//fermeture requete
        } elseif ($id_personnel_rdv != 0) {//si ID_personnel non nul--> medecin
            $prix_to_reinsert = 28.00;//prix fixe:28€
        }

        $sql_insert_dispo = "INSERT INTO dispo (Date, HeureDebut, HeureFin, IdPersonnel, IdServiceLabo, Prix)
                             VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_insert_dispo = $conn->prepare($sql_insert_dispo);//on prep un requete d'insertion pour remmettre dans dispo
        if (!$stmt_insert_dispo) throw new Exception("Erreur préparation requête insert_dispo: " . $conn->error);//verif requete prep correctement
        $stmt_insert_dispo->bind_param("sssiid", $date_rdv, $heure_debut_rdv, $heure_fin_rdv, $id_personnel_rdv, $id_service_labo_rdv, $prix_to_reinsert);//on lie les val aux ? de la requete
        $stmt_insert_dispo->execute(); //execution
        $stmt_insert_dispo->close(); //fermeture

        $sql_delete_rdv = "DELETE FROM rdv WHERE ID = ?";//prepare la requete sql pour supprimer un rdv de la table rdv
        $stmt_delete_rdv = $conn->prepare($sql_delete_rdv); //creation requete preparee, https://www.php.net/manual/fr/pdo.prepared-statements.php
        if (!$stmt_delete_rdv) throw new Exception("Erreur préparation requête delete_rdv: " . $conn->error); //si requete echoue, exception
        $stmt_delete_rdv->bind_param("i", $rdv_id_to_cancel);//lie id du rdv a annuler a la requete preparee
        $stmt_delete_rdv->execute();
        $stmt_delete_rdv->close();

        if ($id_paiement_rdv !== null) {//verif si paiement associe au rdv
            $sql_delete_paiement = "DELETE FROM id_paiement WHERE ID = ?";// prep une requete pour supp paiement
            $stmt_delete_paiement = $conn->prepare($sql_delete_paiement);
            if ($stmt_delete_paiement) {// si requete bien prep, elle est executée et fermee
                $stmt_delete_paiement->bind_param("i", $id_paiement_rdv);
                $stmt_delete_paiement->execute();
                $stmt_delete_paiement->close();
            } else {
                 error_log("Erreur préparation requête delete_paiement: " . $conn->error);//si erreur, on l'ecrit dans log
            }
        }
        $conn->commit(); //si tt va bien, on valide la transaction
        $success_message = "Rendez-vous annulé avec succès. Le créneau est de nouveau disponible.";//message de succes
    } catch (Exception $e) {//si erreur -->tout est annule
        $conn->rollback();
        $error_message = "Erreur lors de l'annulation: " . $e->getMessage();//message erreur
    }
}

date_default_timezone_set('Europe/Paris');//pour les rdv

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
"; // requete recup les infos utiles pour chaque rdv (selon le type) LEFT JOIN-->lier tables sans perdre de lignes, AS --> renom colones


$stmt_rdv = $conn->prepare($sql_rdv);// si requete echoue
if (!$stmt_rdv) {
    die("Erreur : " . $conn->error . "<br><pre>" . $sql_rdv . "</pre>");//affiche erreur 
}
$stmt_rdv->bind_param("i", $idClient);//lie id client a la requete sql
$stmt_rdv->execute();
$result_rdv = $stmt_rdv->get_result();//recup le resultat 

$upcoming_rdv = [];//init tableau rdv (futur)
$past_rdv = [];//init tableau rdv passe
$now = new DateTime(); //recup date/heure 

while ($rdv_data = $result_rdv->fetch_assoc()) {//on boucle sur chaque ligne du resultatt sql
    $rdv_datetime_str = $rdv_data['DateRDV'] . ' ' . $rdv_data['HeureDebut'];//prep chaine lisible par DateTime
    try {//convertion la chaine en obj DateTime pour comparer
        $rdv_datetime = new DateTime($rdv_datetime_str);
        if ($rdv_datetime < $now) {
            $past_rdv[] = $rdv_data;//si date avant mtn--> $past_rdv
        } else {
            $upcoming_rdv[] = $rdv_data;//sinon dans $upcoming_rdv
        }
    } catch (Exception $e) {// si la date est mal formee, on log l'erreur
        error_log("Invalid date format for RDV ID " . $rdv_data['rdv_id'] . ": " . $rdv_datetime_str . " - " . $e->getMessage());
    }
}

$stmt_rdv->close();//feermeture
$conn->close();//fermeture

if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];//recup message de succes 
    unset($_SESSION['success_message']);//suppr le message
}
?>

<!DOCTYPE html>
<?php require 'includes/head.php'; ?> <!--inclus l'interface du haut de page-->
<body>
<?php require 'includes/header.php'; ?><!--inclus l'interface de nav de la page-->

<main class="main-content-page"> <!--corps de la page-->
    <h1 style="text-align: center; margin-bottom: 2rem;">Mes Rendez-vous</h1> <!--nom de la page-->

    <?php
    if (!empty($success_message)) {
        echo '<div class="alert success">' . safe_html($success_message) . '</div>'; //message de succes 
    }
    if (!empty($error_message)) {
        echo '<div class="alert error">' . safe_html($error_message) . '</div>';//message d'erreru
    }
    ?>

    <section class="rdv-section"> <!--affichage des rdv-->
        <?php if (empty($upcoming_rdv) && empty($past_rdv)): ?> <!-- si pas de rdv-->
            <p style="text-align: center; font-size: 1.1em; color: #6c757d; margin-top: 30px;">Vous n'avez aucun rendez-vous pour le moment.</p><!--cas ou ya aucun rdv-->
            <p style="text-align: center; margin-top: 15px;">
                <a href="medecine_general.php" class="btn-action-link">Prendre un rendez-vous médecin</a> <!--lien pour prendre rdv avec med-->
                <a href="laboratoire.php" class="btn-action-link btn-labo">Prendre un rendez-vous laboratoire</a><!--lien pour prendre rdv avec labo-->
            </p>
        <?php endif; ?>

        <?php if (!empty($upcoming_rdv)): ?> <!--s'il y a des rdv-->
            <h2 class="rdv-section-title">Rendez-vous à venir</h2><!-- titre-->
            <div class="rdv-list">
                <?php foreach ($upcoming_rdv as $rdv): ?> <!--on set les rdv-->
                    <div class="rdv-card upcoming">
                        <div class="rdv-header">
                            <?php
                            $is_doctor_rdv = (isset($rdv['ID_Personnel']) && $rdv['ID_Personnel'] != 0 && $rdv['ID_Personnel'] !== null); //verif si le rdv est avec med
                            ?>
                            <?php if ($is_doctor_rdv): ?> <!--si rdv bien avec med-->
                                <div class="rdv-photo-container">
                                    <img src="<?php echo safe_html($rdv['prof_photo'] ?: './images/default_doctor.png'); ?>" alt="Photo Dr. <?php echo safe_html($rdv['prof_nom']); ?>" class="rdv-photo"> <!--affichage photo-->
                                </div>
                                <div class="rdv-title-details"> <!--info med-->
                                    <h3>Dr. <?php echo safe_html($rdv['prof_prenom']) . ' ' . safe_html($rdv['prof_nom']); ?></h3>
                                    <p class="rdv-type"><?php echo safe_html($rdv['prof_specialite']); ?></p> <!--spécialité-->
                                </div>
                            <?php else:?> <!--sinon labo-->
                                <div class="rdv-photo-container">
                                    <img src="./images/default_labo.jpg" alt="Laboratoire" class="rdv-photo"> <!--photo labo-->
                                </div>
                                <div class="rdv-title-details">
                                    <h3><?php echo safe_html($rdv['labo_nom']); ?></h3>
                                    <p class="rdv-type">Service : <?php echo safe_html($rdv['service_labo_nom']); ?></p> <!--service demande-->
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="rdv-body"> <!--carte + details-->
                            <p><strong>Date :</strong> <?php echo date("d/m/Y", strtotime(safe_html($rdv['DateRDV']))); ?></p>
                            <p><strong>Heure :</strong> <?php echo substr(safe_html($rdv['HeureDebut']), 0, 5) . ' - ' . substr(safe_html($rdv['HeureFin']), 0, 5); ?></p> <!--date du rdv-->

                            <?php if ($is_doctor_rdv): ?><!--si c'est un rdv avec un médecin-->
                                <?php if (!empty($rdv['prof_adresse_ligne'])): ?><!--si il y a adresse-->
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
                                <?php if (!empty($rdv['prof_telephone'])): ?> <!--affiche tel cabinet-->
                                    <p><strong>Téléphone Cabinet :</strong> <?php echo safe_html($rdv['prof_telephone']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($rdv['prof_email'])): ?><!--email cab-->
                                    <p><strong>Email Cabinet :</strong> <a href="mailto:<?php echo safe_html($rdv['prof_email']); ?>"><?php echo safe_html($rdv['prof_email']); ?></a></p>
                                <?php endif; ?>
                            <?php else: ?> <!--rdv en labo ?-->
                                <?php if (!empty($rdv['labo_adresse_ligne'])): ?> <!--meme trame que med-->
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

                            <p><strong>Statut :</strong> <span class="status-upcoming"><?php echo safe_html($rdv['Statut']); ?></span></p><!--on affiche le statuut du rdv-->
                            <?php if (!empty($rdv['InfoComplementaire'])): ?>
                                <p><strong>Infos Complémentaires RDV :</strong> <?php echo safe_html($rdv['InfoComplementaire']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="rdv-actions"> <!--bouton annul-->
                            <form method="POST" action="rdv.php" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler ce rendez-vous ?');">
                                <input type="hidden" name="id_rdv_to_cancel" value="<?php echo safe_html($rdv['rdv_id']); ?>">
                                <button type="submit" class="btn-cancel">Annuler le RDV</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?> <!-- fin du parcour de tableau-->
            </div>
        <?php endif; ?>

        <?php if (!empty($past_rdv)): ?> <!--verif rdv passe a afficher-->
            <h2 class="rdv-section-title past-title">Rendez-vous passés</h2>
            <div class="rdv-list">
                <?php foreach ($past_rdv as $rdv): ?> <!--pour chaque rdv de rdv passe on fait:-->
                    <div class="rdv-card past">
                        <div class="rdv-header">
                            <?php
                            $is_doctor_rdv = (isset($rdv['ID_Personnel']) && $rdv['ID_Personnel'] != 0 && $rdv['ID_Personnel'] !== null);
                            ?><!--rdv avec med ou labo?-->
                            <?php if ($is_doctor_rdv): ?> <!--si c'est un med-->
                                <div class="rdv-photo-container">
                                    <img src="<?php echo safe_html($rdv['prof_photo'] ?: './images/default_doctor.png'); ?>" alt="Photo Dr. <?php echo safe_html($rdv['prof_nom']); ?>" class="rdv-photo grayscale">
                                </div>
                                <div class="rdv-title-details">
                                    <h3>Dr. <?php echo safe_html($rdv['prof_prenom']) . ' ' . safe_html($rdv['prof_nom']); ?></h3>
                                    <p class="rdv-type"><?php echo safe_html($rdv['prof_specialite']); ?></p>
                                </div><!-- on affiche les details du medecin-->
                            <?php else: ?> <!--labo-->
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
                            <p><strong>Heure :</strong> <?php echo substr(safe_html($rdv['HeureDebut']), 0, 5) . ' - ' . substr(safe_html($rdv['HeureFin']), 0, 5); ?></p> <!--affcihe les details du rdv-->

                            <?php if ($is_doctor_rdv): ?> <!--affiche adresse cabinet et autres infos-->
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
                                <?php endif; ?> <!--affiche les moyen de communiquer-->
                                <?php if (!empty($rdv['prof_telephone'])): ?>
                                    <p><strong>Téléphone Cabinet :</strong> <?php echo safe_html($rdv['prof_telephone']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($rdv['prof_email'])): ?>
                                    <p><strong>Email Cabinet :</strong> <a href="mailto:<?php echo safe_html($rdv['prof_email']); ?>"><?php echo safe_html($rdv['prof_email']); ?></a></p>
                                <?php endif; ?>
                            <?php else: // Labo ?>
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
                                <?php endif; ?><!--meme struct pour le labo-->
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

                            <p><strong>Statut :</strong> <span class="status-past">Terminé</span></p> <!--rdv passés donc tjrs termine-->
                            <?php if (!empty($rdv['InfoComplementaire'])): ?>
                                <p><strong>Infos Complémentaires RDV :</strong> <?php echo safe_html($rdv['InfoComplementaire']); ?></p>
                            <?php endif; ?> <!-- infos complementaires pr le rdv-->
                        </div>
                        <div class="rdv-actions">
                            <span class="past-label">Rendez-vous passé</span> <!--pas de bouton caar passe-->
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php require 'includes/footer.php'; ?> <!--inclus presentation bas de page-->

<style>
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
    padding: 20px;
    display: flex;
    flex-direction: column;
    transition: transform 0.2s ease-in-out;
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
    width: 80px;
    height: 80px;
    border-radius: 50%;
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
    color: #0056b3; 
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