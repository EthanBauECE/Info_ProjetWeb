<?php
header('Content-Type: application/json');
session_start(); //ON COMMENCE UNE NOUVELLE SESSION 
$conn = new mysqli("localhost", "root", "", "base_donne_web");//ON SE CONNECTE A NOTRE BASE DE DONEE
if ($conn->connect_error) {//SI ON ARRIVE PAS A S E CO?NECTER ON INDIQUE
    echo json_encode(['error' => 'Database connection failed']);//INDIQUER A UTILISATEUR
    exit;
}
$conn->set_charset("utf8");

date_default_timezone_set('Europe/Paris'); //ON DEFINIT ICI LES HORAIRES EN SE BASANT SUR HORAIRE PARIS NE FRANCE

$medecinId = isset($_GET['medecin_id']) ? intval($_GET['medecin_id']) : 0;
$startDateStr = isset($_GET['start_date']) ? $_GET['start_date'] : ''; //ON GARDE UN FORMAT DE DAT6E EN MODE ANNEE MOIS ET JOUR
$endDateStr = isset($_GET['end_date']) ? $_GET['end_date'] : '';     //ON GARDE UN FORMAT DE DAT6E EN MODE ANNEE MOIS ET JOUR

if ($medecinId === 0 || empty($startDateStr) || empty($endDateStr)) {//SI MEDECIN A PAS D IDI BAH PAS DE DATE ALORS
    echo json_encode(['error' => 'Paramètres manquants']);//INDIQUER A LA PERSONNE QU IL MANQUE DES INFO POUR CONTINUER
    exit;
}

if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $startDateStr) ||
    !preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $endDateStr)) {//VERIFIER QUE CA RESSPECTE BIEN LE FORMAT IMPOSER POUR LA DDATE SOURCE: https://www.php.net/manual/fr/function.preg-match.php
    echo json_encode(['error' => 'Format de date invalide. Attendu YYYY-MM-DD.']);// SI PAS BON ALORS ON LE DIT
    exit;
}

$disponibilites = [];//ON VEIRIFIE LES DISPO
$now = new DateTime(); //ON SE REFERE A L HEURE ACTUELLE SUR ORDI

try {

    $sqlDispo = "SELECT ID, Date, HeureDebut, HeureFin, IdServiceLabo, Prix 
                 FROM dispo 
                 WHERE IdPersonnel = ? 
                   AND Date >= ? 
                   AND Date <= ?
                 ORDER BY Date, HeureDebut";// ON VERIFIE EN FONCTION DE CE QU IL Y A DANS LA BASE DE NOS RDV SI C EST POSSIBLE A LA DATE DEMANDEEE
    
    $stmtDispo = $conn->prepare($sqlDispo);//ON SE PREPAPRE ICI POUR LE SQL
    if (!$stmtDispo) {
        echo json_encode(['error' => 'Erreur préparation requête dispo: ' . $conn->error]);//SI PAS DISPO ON INDIQUE QEU IL Y A UN PB PAS DISPO
        exit;
    }
    $stmtDispo->bind_param("iss", $medecinId, $startDateStr, $endDateStr);
    $stmtDispo->execute();
    $resultDispo = $stmtDispo->get_result();//ON RECUPER LE RESULTAT DE LA REQUETE
    
    while ($row = $resultDispo->fetch_assoc()) {
        $slotEndTime = new DateTime($row['Date'] . ' ' . $row['HeureFin']);//ON REGARDE AVEC LES HORAIRE QUE LA PERSONNE VEUT SI OSSIBLE
        $row['status'] = ($slotEndTime < $now) ? 'past' : 'available';//SAVOIR SI LE CRENEAU EST LIBRE OOU NON 
        
        $disponibilites[] = $row;//ON AJOUTE LA DISPO AU CALENDRIER
    }
    $stmtDispo->close();//ON FERME LA REQUERETE

} catch (Exception $e) {
    echo json_encode(['error' => 'Exception: ' . $e->getMessage()]);// SI Y A UN PB ON PREVIENT
    exit;//ON FERME
}

$conn->close();
echo json_encode($disponibilites);//PERMET DE BIEN METTRE A JOUR LES DIFFERENTES DISPONIBILITE
?>