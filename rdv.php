<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

function safe_html($value) {
    return $value !== null ? htmlspecialchars($value) : '';
}

// Redirection si non connecté
if (!isset($_SESSION["user_id"])) {
    header("Location: #");
    exit();
}

$user_id = $_SESSION["user_id"];

// Traitement de l'annulation du rendez-vous
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_rdv'])) {
    $rdv_id = $_POST['id_rdv'];
    
    $conn = new mysqli("localhost", "root", "", "base_donne_web");
    if ($conn->connect_error) die("Connexion échouée: " . $conn->connect_error);
    $conn->set_charset("utf8");

    // Récupération des infos du RDV
    $sql = "SELECT ID_Personnel, HeureDebut, HeureFin, ID_ServiceLabo FROM rdv WHERE ID = ? AND ID_Client = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $rdv_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        die("Rendez-vous non trouvé ou vous n'êtes pas autorisé à l'annuler.");
    }

    $rdv = $result->fetch_assoc();
    
    // Conversion des DATETIME en DATE et TIME
    $date = date('Y-m-d', strtotime($rdv['HeureDebut']));
    $heure_debut = date('H:i:s', strtotime($rdv['HeureDebut']));
    $heure_fin = date('H:i:s', strtotime($rdv['HeureFin']));

    // Transaction pour l'annulation
    $conn->begin_transaction();

    try {
        // 1. Copie dans la table dispo
        $insert_sql = "INSERT INTO dispo (IDPersonnel, Date, HeureDebut, HeureFin, IdServiceLabo) 
                       VALUES (?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("isssi", 
            $rdv['ID_Personnel'],
            $date,
            $heure_debut,
            $heure_fin,
            $rdv['ID_ServiceLabo']
        );
        $insert_stmt->execute();
        
        // 2. Suppression du RDV
        $delete_sql = "DELETE FROM rdv WHERE ID = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $rdv_id);
        $delete_stmt->execute();
        
        $conn->commit();
        $success_message = "Rendez-vous annulé avec succès! La plage horaire est de nouveau disponible.";
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Erreur lors de l'annulation: " . $e->getMessage();
    } finally {
        $stmt->close();
        if (isset($delete_stmt)) $delete_stmt->close();
        if (isset($insert_stmt)) $insert_stmt->close();
        $conn->close();
    }
}

// Connexion pour l'affichage des rendez-vous
$conn = new mysqli("localhost", "root", "", "base_donne_web");
if ($conn->connect_error) {
    die("Erreur de connexion: " . $conn->connect_error);
}
$conn->set_charset("utf8");
?>

<!DOCTYPE html>
<?php require 'includes/head.php'; ?>



<body>
<?php require 'includes/header.php'; ?>

<section class="hero">
    <div class="hero-content">
        <h1>Mes Rendez-vous</h1>
    </div>
</section>

<section class="rdv-section">
    <div class="rdv-container">
        <?php
        // Affichage des messages
        if (isset($success_message)) {
            echo '<div class="alert success">' . safe_html($success_message) . '</div>';
        }
        if (isset($error_message)) {
            echo '<div class="alert error">' . safe_html($error_message) . '</div>';
        }

        date_default_timezone_set('Europe/Paris');

        $sql = "
            SELECT rdv.ID, rdv.HeureDebut, rdv.HeureFin, rdv.Statut, rdv.ID_Personnel, rdv.ID_ServiceLabo,
                   utilisateurs.Nom AS nom_medecin,
                   utilisateurs_personnel.Photo AS photo_medecin
            FROM rdv
            JOIN utilisateurs_personnel ON rdv.ID_Personnel = utilisateurs_personnel.ID
            JOIN utilisateurs ON utilisateurs_personnel.ID = utilisateurs.ID
            WHERE rdv.ID_Client = ?
            ORDER BY rdv.HeureDebut ASC
        ";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Erreur dans la requête préparée : " . $conn->error);
        }

        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            echo "<p>Aucun rendez-vous trouvé.</p>";
        }

        while ($rdv = $result->fetch_assoc()) {
            $datetime_rdv = strtotime($rdv['HeureDebut']);
            $now = time();
            $is_past = $datetime_rdv < $now;

            echo '<div class="rdv-box ' . ($is_past ? 'rdv-past' : '') . '">';
            echo   '<div class="rdv-photo">';
            echo     '<img src="' . safe_html($rdv['photo_medecin']) . '" alt="Photo du médecin">';
            echo   '</div>';
            echo   '<div class="rdv-details">';
            echo     '<h3>Dr. ' . safe_html($rdv['nom_medecin']) . '</h3>';
            echo     '<p><strong>Début :</strong> ' . date('d/m/Y H:i', strtotime($rdv['HeureDebut'])) . '</p>';
            echo     '<p><strong>Fin :</strong> ' . date('H:i', strtotime($rdv['HeureFin'])) . '</p>';
            echo     '<p><strong>Statut :</strong> ' . safe_html($rdv['Statut']) . '</p>';

            if ($is_past) {
                echo '<div class="past-label">Passé</div>';
            } else {
                echo '<form method="POST" action="">';
                echo   '<input type="hidden" name="id_rdv" value="' . safe_html($rdv['ID']) . '">';
                echo   '<button type="submit" class="btn-annuler">Annuler le RDV</button>';
                echo '</form>';
            }

            echo   '</div>';
            echo '</div>';
        }

        $stmt->close();
        $conn->close();
        ?>
    </div>
</section>

<?php require 'includes/footer.php'; ?>

</body>
</html>