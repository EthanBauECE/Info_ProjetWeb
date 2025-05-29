<?php
header('Content-Type: application/json');
// Pour le développement, il est utile de voir les erreurs. 
// En production, vous voudrez peut-être error_reporting(0);
error_reporting(E_ALL);
ini_set('display_errors', 1);

// session_start(); // Décommentez si vous avez besoin d'infos de session ici

$response_data = []; // Pour s'assurer qu'on retourne toujours un tableau JSON

$conn = new mysqli("localhost", "root", "", "base_donne_web");

if ($conn->connect_error) {
    echo json_encode(['error' => 'Database connection failed: ' . $conn->connect_error, 'data' => $response_data]);
    exit;
}
$conn->set_charset("utf8");

// labo_id n'est pas directement utilisé pour filtrer 'dispo' si 'service_id' et un IdPersonnel générique le font.
// $laboId = isset($_GET['labo_id']) ? intval($_GET['labo_id']) : 0; 
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
        $row['status'] = 'available'; 
        $response_data[] = $row; // On ajoute à $response_data
    }
    $stmtDispo->close();

} catch (Exception $e) {
    echo json_encode(['error' => 'Exception: ' . $e->getMessage(), 'data' => $response_data]);
    $conn->close(); // S'assurer que la connexion est fermée
    exit;
}

$conn->close();
// C'est la seule sortie attendue si tout se passe bien.
echo json_encode($response_data); 
?>