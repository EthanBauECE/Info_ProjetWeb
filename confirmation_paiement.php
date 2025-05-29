<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

function safe_html($value) {
    return $value !== null ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : '';
}

if (!isset($_SESSION["user_id"])) {
    $redirect_page = 'index.php'; 
    if (isset($_SESSION['pending_rdv']['type'])) {
        $redirect_page = ($_SESSION['pending_rdv']['type'] === 'laboratoire') ? 'laboratoire.php' : 'medecine_general.php';
    }
    header("Location: login.php?redirect=" . urlencode($redirect_page));
    exit();
}

$idClient = $_SESSION["user_id"];
$message = '';
$pending_rdv_details = null;
$form_errors = []; // Pour stocker les erreurs de validation du formulaire de paiement

// NOUVEAU BLOC DE VÉRIFICATION DU TYPE DE COMPTE
$is_personnel_account = false;
if (isset($_SESSION["user_type"]) && $_SESSION["user_type"] === "Personnel") { // Assurez-vous que 'Personnel' correspond à la valeur stockée dans votre DB
    $is_personnel_account = true;
    $message = "<p style='color:red;'>Vous ne pouvez pas réserver de rendez-vous avec un compte professionnel. Veuillez vous connecter avec un compte client ou en créer un.</p>";
    unset($_SESSION['pending_rdv']); // Empêche l'affichage du résumé et du formulaire de paiement
}
// FIN NOUVEAU BLOC

$conn = new mysqli("localhost", "root", "", "base_donne_web");
if ($conn->connect_error) {
    die("Erreur de connexion BDD : " . $conn->connect_error); 
}
$conn->set_charset("utf8");

// Étape 1: Traiter le POST initial pour charger les détails du RDV en session
// Ajoute la condition `!$is_personnel_account` ici
if (!$is_personnel_account && $_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['action'])) { 
    unset($_SESSION['pending_rdv']); 
    if (isset($_POST['type_rdv']) && $_POST['type_rdv'] === 'laboratoire') {
        $_SESSION['pending_rdv'] = [
            'type' => 'laboratoire', /* ... (autres champs comme avant) ... */
            'labo_id' => intval($_POST['labo_id']),
            'labo_nom' => safe_html($_POST['labo_nom']),
            'service_id' => intval($_POST['selected_service_id']),
            'service_nom' => safe_html($_POST['selected_service_nom']),
            'date' => safe_html($_POST['selected_date_db']),
            'heure_debut' => safe_html($_POST['selected_heure_debut_db']),
            'heure_fin' => safe_html($_POST['selected_heure_fin_db']),
            'prix' => floatval($_POST['selected_prix'])
        ];
    } elseif (isset($_POST['medecin_id'])) { 
        $_SESSION['pending_rdv'] = [
            'type' => 'medecin', /* ... (autres champs comme avant) ... */
            'medecin_id' => intval($_POST['medecin_id']),
            'medecin_nom' => safe_html($_POST['medecin_nom']),
            'medecin_specialite' => isset($_POST['medecin_specialite']) ? safe_html($_POST['medecin_specialite']) : 'Généraliste',
            'date' => safe_html($_POST['selected_date_db']),
            'heure_debut' => safe_html($_POST['selected_heure_debut_db']),
            'heure_fin' => safe_html($_POST['selected_heure_fin_db']),
            'prix' => floatval($_POST['selected_prix']),
            'id_service_labo' => intval($_POST['id_service_labo'])
        ];
    } else {
        $message = "<p style='color:red;'>Données de rendez-vous manquantes ou invalides.</p>";
    }
}

// Étape 2: Traiter l'action de "paiement"
// Ajoute la condition `!$is_personnel_account` ici
if (!$is_personnel_account && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'payer') {
    if (isset($_SESSION['pending_rdv'])) {
        $rdvDetails = $_SESSION['pending_rdv'];
        
        // Récupération et validation des données de paiement
        $typeCarte = isset($_POST['type_carte']) ? trim($_POST['type_carte']) : '';
        $numeroCarteStr = isset($_POST['numero_carte']) ? preg_replace('/\s+/', '', $_POST['numero_carte']) : ''; // Enlever les espaces
        $nomCarte = isset($_POST['nom_carte']) ? trim($_POST['nom_carte']) : '';
        $moisExpiration = isset($_POST['mois_expiration']) ? intval($_POST['mois_expiration']) : 0;
        $anneeExpiration = isset($_POST['annee_expiration']) ? intval($_POST['annee_expiration']) : 0;
        $ccvStr = isset($_POST['ccv']) ? trim($_POST['ccv']) : '';

        // Validation basique (à renforcer considérablement pour un vrai système)
        if (empty($typeCarte)) $form_errors['type_carte'] = "Type de carte requis.";
        if (!ctype_digit($numeroCarteStr) || strlen($numeroCarteStr) < 13 || strlen($numeroCarteStr) > 19) $form_errors['numero_carte'] = "Numéro de carte invalide.";
        if (empty($nomCarte)) $form_errors['nom_carte'] = "Nom sur la carte requis.";
        if ($moisExpiration < 1 || $moisExpiration > 12) $form_errors['mois_expiration'] = "Mois d'expiration invalide.";
        if ($anneeExpiration < date("Y") || $anneeExpiration > date("Y") + 20) $form_errors['annee_expiration'] = "Année d'expiration invalide.";
        // Vérifier si la date d'expiration n'est pas passée
        if (empty($form_errors['mois_expiration']) && empty($form_errors['annee_expiration'])) {
            if ($anneeExpiration == date("Y") && $moisExpiration < date("m")) {
                $form_errors['date_expiration'] = "La carte est expirée.";
            }
        }
        if (!ctype_digit($ccvStr) || strlen($ccvStr) < 3 || strlen($ccvStr) > 4) $form_errors['ccv'] = "CCV invalide.";

        if (empty($form_errors)) {
            // Formatage de la date d'expiration pour la BDD (premier jour du mois)
            $dateExpirationDB = sprintf('%04d-%02d-01', $anneeExpiration, $moisExpiration);
            $numeroCarte = (string) $numeroCarteStr; // S'assurer que c'est une chaîne pour BIGINT si besoin, ou directement BIGINT
            $ccv = intval($ccvStr);


            $selectedDate = $rdvDetails['date'];
            $selectedHeureDebut = $rdvDetails['heure_debut'];
            $selectedHeureFin = $rdvDetails['heure_fin'];

            $conn->begin_transaction();
            try {
                $idDispo = null;
                $idPersonnelPourDispoCheck = null;
                $idServiceLaboPourDispoCheck = null;

                if ($rdvDetails['type'] === 'laboratoire') {
                    $idPersonnelPourDispoCheck = 0; 
                    $idServiceLaboPourDispoCheck = $rdvDetails['service_id'];
                } elseif ($rdvDetails['type'] === 'medecin') {
                    $idPersonnelPourDispoCheck = $rdvDetails['medecin_id'];
                    $idServiceLaboPourDispoCheck = $rdvDetails['id_service_labo'];
                }

                $sqlCheck = "SELECT ID, Prix FROM dispo WHERE IdPersonnel = ? AND IdServiceLabo = ? AND Date = ? AND HeureDebut = ? AND HeureFin = ?";
                $stmtCheck = $conn->prepare($sqlCheck);
                $stmtCheck->bind_param("iisss", $idPersonnelPourDispoCheck, $idServiceLaboPourDispoCheck, $selectedDate, $selectedHeureDebut, $selectedHeureFin);
                $stmtCheck->execute();
                $resultCheck = $stmtCheck->get_result();
                
                if ($resultCheck->num_rows > 0) {
                    $dispoRow = $resultCheck->fetch_assoc();
                    $idDispo = $dispoRow['ID'];
                    $prixFinalRDV = $dispoRow['Prix']; 

                    // 2. Insertion du paiement RÉEL (avec les données saisies)
                    $sqlPaiement = "INSERT INTO id_paiement (TypeCarte, NumeroCarte, NomCarte, DateExpiration, CCV) VALUES (?, ?, ?, ?, ?)";
                    $stmtPaiement = $conn->prepare($sqlPaiement);
                    // NumeroCarte est BIGINT, mais bind_param attend des int (i) ou string (s). On va utiliser string.
                    $stmtPaiement->bind_param("ssssi", $typeCarte, $numeroCarte, $nomCarte, $dateExpirationDB, $ccv);
                    $stmtPaiement->execute();
                    $idPaiement = $conn->insert_id;
                    $stmtPaiement->close();

                    // 3. Insertion du RDV
                    // ... (logique d'insertion du RDV identique à la version précédente)
                    $statut = "A venir";
                    $idPersonnelPourRdv = null;
                    $idServiceLaboPourRdv = null;
                    $infoComplementaire = "";

                    if ($rdvDetails['type'] === 'laboratoire') {
                        $idPersonnelPourRdv = $idPersonnelPourDispoCheck;
                        $idServiceLaboPourRdv = $rdvDetails['service_id'];
                        $infoComplementaire = "RDV Labo: " . $rdvDetails['labo_nom'] . " - Service: " . $rdvDetails['service_nom'];
                    } elseif ($rdvDetails['type'] === 'medecin') {
                        $idPersonnelPourRdv = $rdvDetails['medecin_id'];
                        $idServiceLaboPourRdv = $rdvDetails['id_service_labo'];
                        $infoComplementaire = "RDV Médecin: Dr. " . $rdvDetails['medecin_nom'] . ($rdvDetails['medecin_specialite'] !== 'Généraliste' ? " (" . $rdvDetails['medecin_specialite'] . ")" : "");
                    }
                    
                    $sqlRdv = "INSERT INTO rdv (DateRDV, HeureDebut, HeureFin, Statut, InfoComplementaire, ID_Client, ID_Personnel, ID_ServiceLabo, ID_Paiement) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmtRdv = $conn->prepare($sqlRdv);
                    $stmtRdv->bind_param("sssssiiii", $selectedDate, $selectedHeureDebut, $selectedHeureFin, $statut, $infoComplementaire, $idClient, $idPersonnelPourRdv, $idServiceLaboPourRdv, $idPaiement);
                    $stmtRdv->execute();
                    $stmtRdv->close();
                    
                    // 4. Suppression de la disponibilité
                    $sqlDelete = "DELETE FROM dispo WHERE ID = ?";
                    $stmtDelete = $conn->prepare($sqlDelete);
                    $stmtDelete->bind_param("i", $idDispo);
                    $stmtDelete->execute();
                    $stmtDelete->close();

                    $conn->commit();
                    unset($_SESSION['pending_rdv']);
                    $_SESSION['success_message'] = "Rendez-vous confirmé avec succès pour " . $infoComplementaire . " le " . date("d/m/Y", strtotime($selectedDate)) . " de " . substr($selectedHeureDebut, 0, 5) . " à " . substr($selectedHeureFin, 0, 5) . ".";
                    header("Location: rdv.php");
                    exit();

                } else { /* ... (créneau non dispo) ... */ 
                    $conn->rollback();
                    $message = "<p style='color:red;'>Désolé, ce créneau n'est plus disponible. Veuillez en choisir un autre.</p>";
                    unset($_SESSION['pending_rdv']);
                }
                $stmtCheck->close();
            } catch (Exception $e) { /* ... (erreur technique) ... */
                $conn->rollback();
                $message = "<p style='color:red;'>Une erreur technique est survenue : " . $e->getMessage() . "</p>";
                unset($_SESSION['pending_rdv']);
            }
        } // Fin if empty($form_errors)
        // Si $form_errors n'est pas vide, la page se réaffiche avec les erreurs et les détails du RDV en session.
    } elseif (!isset($_SESSION['pending_rdv']) && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'payer' ) {
        $message = "<p style='color:orange;'>Votre session de réservation a expiré. Veuillez recommencer.</p>";
    }
}

if (isset($_SESSION['pending_rdv'])) {
    $pending_rdv_details = $_SESSION['pending_rdv'];
}

$conn->close();
require 'includes/head.php';
?>
<body>
<?php require 'includes/header.php'; ?>
<main class="login-main"> 
    <div class="login-container" style="max-width: 600px; text-align:left;">
        <h2 style="text-align:center;">Confirmation et Paiement</h2>
        
        <?php if (!empty($message)) echo $message; ?>

        <?php 
        // NOUVEAU BLOC D'AFFICHAGE CONDITIONNEL
        if ($is_personnel_account): // Si c'est un compte personnel, affiche UNIQUEMENT le message d'erreur et le bouton
        ?>
            <div style="text-align:center; margin-top: 20px;">
                <a href="index.php" class="login-button" style="display:inline-block;">Retour à l'accueil</a>
            </div>
        <?php 
        // FIN NOUVEAU BLOC
        elseif ($pending_rdv_details && (empty($message) || !empty($form_errors)) ): // Afficher si RDV en attente ET (pas de message d'erreur global OU il y a des erreurs de formulaire)
        ?>
            <h4 style="margin-top:1.5rem; margin-bottom:1rem; color:#007bff;">Résumé de votre réservation :</h4>
            <?php if ($pending_rdv_details['type'] === 'laboratoire'): ?>
                <p><strong>Laboratoire :</strong> <?php echo safe_html($pending_rdv_details['labo_nom']); ?></p>
                <p><strong>Service :</strong> <?php echo safe_html($pending_rdv_details['service_nom']); ?></p>
            <?php elseif ($pending_rdv_details['type'] === 'medecin'): ?>
                <p><strong>Professionnel :</strong> Dr. <?php echo safe_html($pending_rdv_details['medecin_nom']); ?>
                    <?php if ($pending_rdv_details['medecin_specialite'] !== 'Généraliste'): ?>
                        (<?php echo safe_html($pending_rdv_details['medecin_specialite']); ?>)
                    <?php endif; ?>
                </p>
            <?php endif; ?>
            <p><strong>Date :</strong> <?php echo date("d/m/Y", strtotime(safe_html($pending_rdv_details['date']))); ?></p>
            <p><strong>Heure :</strong> de <?php echo substr(safe_html($pending_rdv_details['heure_debut']), 0, 5); ?> 
                                  à <?php echo substr(safe_html($pending_rdv_details['heure_fin']), 0, 5); ?></p>
            <p><strong>Prix :</strong> <?php echo safe_html(number_format($pending_rdv_details['prix'], 2, ',', ' ')); ?> €</p>
            
            <hr style="margin: 20px 0;">
            <h4 style="text-align:center; margin-bottom:1rem;">Informations de paiement</h4>
            <form action="confirmation_paiement.php" method="POST">
                <input type="hidden" name="action" value="payer">

                <div class="form-group">
                    <label for="type_carte">Type de carte</label>
                    <select id="type_carte" name="type_carte" class="form-control" required>
                        <option value="">-- Choisir --</option>
                        <option value="Visa" <?php echo (isset($_POST['type_carte']) && $_POST['type_carte'] == 'Visa') ? 'selected' : ''; ?>>Visa</option>
                        <option value="Mastercard" <?php echo (isset($_POST['type_carte']) && $_POST['type_carte'] == 'Mastercard') ? 'selected' : ''; ?>>Mastercard</option>
                        <option value="American Express" <?php echo (isset($_POST['type_carte']) && $_POST['type_carte'] == 'American Express') ? 'selected' : ''; ?>>American Express</option>
                    </select>
                    <?php if(isset($form_errors['type_carte'])) echo "<p style='color:red;font-size:0.8em;'>".$form_errors['type_carte']."</p>"; ?>
                </div>

                <div class="form-group">
                    <label for="numero_carte">Numéro de carte</label>
                    <input type="text" id="numero_carte" name="numero_carte" placeholder="•••• •••• •••• ••••" required pattern="[\d\s]{13,19}" title="Numéro de carte de 13 à 19 chiffres" value="<?php echo isset($_POST['numero_carte']) ? safe_html($_POST['numero_carte']) : ''; ?>">
                    <?php if(isset($form_errors['numero_carte'])) echo "<p style='color:red;font-size:0.8em;'>".$form_errors['numero_carte']."</p>"; ?>
                </div>

                <div class="form-group">
                    <label for="nom_carte">Nom sur la carte</label>
                    <input type="text" id="nom_carte" name="nom_carte" placeholder="Jean Dupont" required value="<?php echo isset($_POST['nom_carte']) ? safe_html($_POST['nom_carte']) : ''; ?>">
                    <?php if(isset($form_errors['nom_carte'])) echo "<p style='color:red;font-size:0.8em;'>".$form_errors['nom_carte']."</p>"; ?>
                </div>

                <div class="form-group" style="display: flex; gap: 10px;">
                    <div style="flex: 1;">
                        <label for="mois_expiration">Mois d'exp.</label>
                        <select id="mois_expiration" name="mois_expiration" required>
                            <option value="">MM</option>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?php echo sprintf('%02d', $m); ?>" <?php echo (isset($_POST['mois_expiration']) && $_POST['mois_expiration'] == $m) ? 'selected' : ''; ?>>
                                    <?php echo sprintf('%02d', $m); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div style="flex: 1;">
                        <label for="annee_expiration">Année d'exp.</label>
                        <select id="annee_expiration" name="annee_expiration" required>
                            <option value="">AAAA</option>
                            <?php $currentYear = date("Y"); ?>
                            <?php for ($y = 0; $y <= 10; $y++): ?>
                                <option value="<?php echo $currentYear + $y; ?>" <?php echo (isset($_POST['annee_expiration']) && $_POST['annee_expiration'] == ($currentYear + $y)) ? 'selected' : ''; ?>>
                                    <?php echo $currentYear + $y; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <?php if(isset($form_errors['mois_expiration'])) echo "<p style='color:red;font-size:0.8em;'>".$form_errors['mois_expiration']."</p>"; ?>
                <?php if(isset($form_errors['annee_expiration'])) echo "<p style='color:red;font-size:0.8em;'>".$form_errors['annee_expiration']."</p>"; ?>
                 <?php if(isset($form_errors['date_expiration'])) echo "<p style='color:red;font-size:0.8em;'>".$form_errors['date_expiration']."</p>"; ?>


                <div class="form-group">
                    <label for="ccv">CCV</label>
                    <input type="password" id="ccv" name="ccv" placeholder="•••" required pattern="\d{3,4}" title="3 ou 4 chiffres" maxlength="4" style="width: 100px;">
                    <?php if(isset($form_errors['ccv'])) echo "<p style='color:red;font-size:0.8em;'>".$form_errors['ccv']."</p>"; ?>
                </div>
                
                <button type="submit" class="login-button" style="font-size: 1.1em; padding: 15px;">Payer et Confirmer le RDV</button>
            </form>
        <?php elseif (empty($message)): // Message si aucun RDV en attente (et pas d'erreur spécifique) ?>
             <p style="text-align:center;">Aucun rendez-vous en cours de confirmation. Veuillez sélectionner un créneau.</p>
             <p style="text-align:center; margin-top:1rem;">
                <a href="medecine_general.php" class="btn-action" style="background-color:#007bff; color:white; padding:10px 15px; border-radius:5px; text-decoration:none;">Voir Médecins</a>
                <a href="laboratoire.php" class="btn-action" style="background-color:#17a2b8; color:white; padding:10px 15px; border-radius:5px; text-decoration:none; margin-left:10px;">Voir Laboratoires</a>
             </p>
        <?php endif; ?>
    </div>
</main>
<?php require 'includes/footer.php'; ?>
</body>
</html>