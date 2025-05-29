<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

function safe_html($value) {
    return $value !== null ? htmlspecialchars($value) : '';
}

// Redirection vers login.php si non connecté
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$is_personnel = false;
$user_type = 'client';

// Détermination du type d'utilisateur
$conn = new mysqli("localhost", "root", "", "base_donne_web");
if ($conn->connect_error) die("Connexion échouée: " . $conn->connect_error);
$conn->set_charset("utf8");

// Vérification si l'utilisateur est du personnel
$sql_check = "SELECT ID FROM utilisateurs_personnel WHERE ID = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("i", $user_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    $is_personnel = true;
    $user_type = 'personnel';
}
$stmt_check->close();

// Traitement de l'annulation du rendez-vous
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_rdv'])) {
    $rdv_id = $_POST['id_rdv'];
    
    // Construction de la requête selon le type d'utilisateur
    if ($is_personnel) {
        $sql = "SELECT ID_Personnel, HeureDebut, HeureFin, ID_ServiceLabo 
                FROM rdv 
                WHERE ID = ? AND ID_Personnel = ?";
    } else {
        $sql = "SELECT ID_Personnel, HeureDebut, HeureFin, ID_ServiceLabo 
                FROM rdv 
                WHERE ID = ? AND ID_Client = ?";
    }
    
    $stmt = $conn->prepare($sql);
    $user_id_param = $user_id;
    $stmt->bind_param("ii", $rdv_id, $user_id_param);
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
        // Copie dans la table dispo
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
        
        // Mise à jour du statut au lieu de suppression
        $update_sql = "UPDATE rdv SET Statut = 'Annulé' WHERE ID = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $rdv_id);
        $update_stmt->execute();
        
        $conn->commit();
        $success_message = "Rendez-vous annulé avec succès!";
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Erreur lors de l'annulation: " . $e->getMessage();
    } finally {
        $stmt->close();
        if (isset($update_stmt)) $update_stmt->close();
        if (isset($insert_stmt)) $insert_stmt->close();
    }
}
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

        // Construction de la requête selon le type d'utilisateur
        if ($is_personnel) {
            $sql = "
                SELECT rdv.ID, rdv.HeureDebut, rdv.HeureFin, rdv.Statut, rdv.ID_Client,
                       utilisateurs.Nom AS nom_client,
                       utilisateurs.Prenom AS prenom_client
                FROM rdv
                JOIN utilisateurs_client ON rdv.ID_Client = utilisateurs_client.ID
                JOIN utilisateurs ON utilisateurs_client.ID = utilisateurs.ID
                WHERE rdv.ID_Personnel = ?
                ORDER BY rdv.HeureDebut ASC
            ";
            $title = " (Personnel)";
        } else {
            $sql = "
                SELECT rdv.ID, rdv.HeureDebut, rdv.HeureFin, rdv.Statut, rdv.ID_Personnel,
                       utilisateurs.Nom AS nom_medecin,
                       utilisateurs.Prenom AS prenom_medecin,
                       utilisateurs_personnel.Photo AS photo_medecin
                FROM rdv
                JOIN utilisateurs_personnel ON rdv.ID_Personnel = utilisateurs_personnel.ID
                JOIN utilisateurs ON utilisateurs_personnel.ID = utilisateurs.ID
                WHERE rdv.ID_Client = ?
                ORDER BY rdv.HeureDebut ASC
            ";
            $title = " (Client)";
        }

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
            $is_cancelled = ($rdv['Statut'] === 'Annulé');

            // Classes CSS conditionnelles
            $box_class = 'rdv-box';
            if ($is_cancelled) {
                $box_class .= ' rdv-cancelled';
            } elseif ($is_past) {
                $box_class .= ' rdv-past';
            }

            echo '<div class="' . $box_class . '">';
            
            // Affichage de la photo uniquement pour les clients
            if (!$is_personnel) {
                echo   '<div class="rdv-photo">';
                echo     '<img src="' . safe_html($rdv['photo_medecin']) . '" alt="Photo du médecin">';
                echo   '</div>';
            } else {
                // Espace vide pour maintenir la mise en page
                echo   '<div class="rdv-photo">';
                echo     '&nbsp;';
                echo   '</div>';
            }
            
            echo   '<div class="rdv-details">';
            
            // Affichage du nom selon le type d'utilisateur
            if ($is_personnel) {
                echo     '<h3>Client: ' . safe_html($rdv['prenom_client']) . ' ' . safe_html($rdv['nom_client']) . '</h3>';
            } else {
                echo     '<h3>Dr. ' . safe_html($rdv['prenom_medecin']) . ' ' . safe_html($rdv['nom_medecin']) . '</h3>';
            }
            
            echo     '<p><strong>Début :</strong> ' . date('d/m/Y H:i', strtotime($rdv['HeureDebut'])) . '</p>';
            echo     '<p><strong>Fin :</strong> ' . date('H:i', strtotime($rdv['HeureFin'])) . '</p>';
            echo     '<p><strong>Statut :</strong> ' . safe_html($rdv['Statut']) . '</p>';

            // Affichage conditionnel des états
            if ($is_cancelled) {
                echo '<div class="cancelled-label">Annulé</div>';
            } elseif ($is_past) {
                echo '<div class="past-label">Passé</div>';
            } else {
                // Bouton d'annulation uniquement pour les RDV futurs non annulés
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