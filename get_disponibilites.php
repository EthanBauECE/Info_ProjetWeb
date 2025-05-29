<?php
header('Content-Type: application/json');
session_start(); 

// --- Connexion à la BDD ---
$conn = new mysqli("localhost", "root", "", "base_donne_web");
if ($conn->connect_error) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}
$conn->set_charset("utf8");

// Définir le fuseau horaire pour des comparaisons précises
date_default_timezone_set('Europe/Paris'); // Ou votre fuseau horaire

// --- Récupération des paramètres ---
$medecinId = isset($_GET['medecin_id']) ? intval($_GET['medecin_id']) : 0;
$startDateStr = isset($_GET['start_date']) ? $_GET['start_date'] : ''; // Format YYYY-MM-DD
$endDateStr = isset($_GET['end_date']) ? $_GET['end_date'] : '';     // Format YYYY-MM-DD

if ($medecinId === 0 || empty($startDateStr) || empty($endDateStr)) {
    echo json_encode(['error' => 'Paramètres manquants']);
    exit;
}

// Validation simple des formats de date (peut être plus robuste)
if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $startDateStr) ||
    !preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $endDateStr)) {
    echo json_encode(['error' => 'Format de date invalide. Attendu YYYY-MM-DD.']);
    exit;
}


$disponibilites = [];
$now = new DateTime(); // Heure actuelle

try {
    // 1. Récupérer les disponibilités de base de la table `dispo` pour la période donnée
    $sqlDispo = "SELECT ID, Date, HeureDebut, HeureFin, IdServiceLabo, Prix 
                 FROM dispo 
                 WHERE IdPersonnel = ? 
                   AND Date >= ? 
                   AND Date <= ?
                 ORDER BY Date, HeureDebut";
    
    $stmtDispo = $conn->prepare($sqlDispo);
    if (!$stmtDispo) {
        echo json_encode(['error' => 'Erreur préparation requête dispo: ' . $conn->error]);
        exit;
    }
    $stmtDispo->bind_param("iss", $medecinId, $startDateStr, $endDateStr);
    $stmtDispo->execute();
    $resultDispo = $stmtDispo->get_result();
    
    while ($row = $resultDispo->fetch_assoc()) {
        $slotEndTime = new DateTime($row['Date'] . ' ' . $row['HeureFin']);
        
        // Déterminer le statut du créneau: 'past' ou 'available'
        $row['status'] = ($slotEndTime < $now) ? 'past' : 'available'; 
        
        $disponibilites[] = $row;
    }
    $stmtDispo->close();

} catch (Exception $e) {
    echo json_encode(['error' => 'Exception: ' . $e->getMessage()]);
    exit;
}

$conn->close();
echo json_encode($disponibilites);
?>