<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$response_data = []; 

$conn = new mysqli("localhost", "root", "", "base_donne_web");

if ($conn->connect_error) {
    echo json_encode(['error' => 'Database connection failed: ' . $conn->connect_error, 'data' => $response_data]);
    exit;
}
$conn->set_charset("utf8");

// Définir le fuseau horaire pour des comparaisons précises
date_default_timezone_set('Europe/Paris'); // Ou votre fuseau horaire

$serviceId = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;
$startDateStr = isset($_GET['start_date']) ? $_GET['start_date'] : ''; 
$endDateStr = isset($_GET['end_date']) ? $_GET['end_date'] : '';     

if ($serviceId === 0 || empty($startDateStr) || empty($endDateStr)) {
    echo json_encode(['error' => 'Paramètres manquants (service_id et/ou dates)', 'data' => $response_data]);
    exit;
}

if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $startDateStr) ||
    !preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $endDateStr)) {
    echo json_encode(['error' => 'Format de date invalide. Attendu YYYY-MM-DD.', 'data' => $response_data]);
    exit;
}

$idPersonnelLaboGenerique = 0; // CONVENTION: IdPersonnel=0 pour les disponibilités de laboratoire
$now = new DateTime(); // Heure actuelle

try {
    // On filtre sur IdServiceLabo ET sur IdPersonnel (avec la valeur générique pour labo)
    $sqlDispo = "SELECT ID, Date, HeureDebut, HeureFin, IdPersonnel, IdServiceLabo, Prix 
                 FROM dispo 
                 WHERE IdServiceLabo = ?
                   AND IdPersonnel = ?  -- Filtre pour les disponibilités du laboratoire
                   AND Date >= ? 
                   AND Date <= ?
                 ORDER BY Date, HeureDebut";
    
    $stmtDispo = $conn->prepare($sqlDispo);
    if (!$stmtDispo) {
        echo json_encode(['error' => 'Erreur préparation requête: ' . $conn->error, 'data' => $response_data]);
        $conn->close();
        exit;
    }
    
    $stmtDispo->bind_param("iiss", $serviceId, $idPersonnelLaboGenerique, $startDateStr, $endDateStr); 
    
    if (!$stmtDispo->execute()) {
        echo json_encode(['error' => 'Erreur exécution requête: ' . $stmtDispo->error, 'data' => $response_data]);
        $stmtDispo->close();
        $conn->close();
        exit;
    }
    
    $resultDispo = $stmtDispo->get_result();
    
    while ($row = $resultDispo->fetch_assoc()) {
        $slotEndTime = new DateTime($row['Date'] . ' ' . $row['HeureFin']);
        
        // Déterminer le statut du créneau: 'past' ou 'available'
        $row['status'] = ($slotEndTime < $now) ? 'past' : 'available'; 

        $response_data[] = $row; 
    }
    $stmtDispo->close();

} catch (Exception $e) {
    echo json_encode(['error' => 'Exception: ' . $e->getMessage(), 'data' => $response_data]);
    $conn->close(); 
    exit;
}

$conn->close();
echo json_encode($response_data); 
?>