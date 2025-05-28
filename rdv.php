<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

function safe_html($value) {
    return $value !== null ? htmlspecialchars($value) : '';
}

$conn = new mysqli("localhost", "root", "", "base_donne_web");
if ($conn->connect_error) {
    die("Erreur de connexion: " . $conn->connect_error);
}
$conn->set_charset("utf8");

if (!isset($_SESSION["user_id"])) {
    echo "Utilisateur non connecté.";
    exit();
}

$user_id = $_SESSION["user_id"];
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
        date_default_timezone_set('Europe/Paris');

        $sql = "
            SELECT rdv.ID, rdv.HeureDebut, rdv.HeureFin, rdv.Statut,
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

            echo '<div class="rdv-box ' . ($is_past ? 'rdv-past' : 'rdv-upcoming') . '">';
            echo   '<div class="rdv-photo">';
            echo     '<img src="' . safe_html($rdv['photo_medecin']) . '" alt="photo médecin">';
            echo   '</div>';
            echo   '<div class="rdv-details">';
            echo     '<h3>Dr. ' . safe_html($rdv['nom_medecin']) . '</h3>';
            echo     '<p><strong>Début :</strong> ' . date('d/m/Y H:i', strtotime($rdv['HeureDebut'])) . '</p>';
            echo     '<p><strong>Fin :</strong> ' . date('H:i', strtotime($rdv['HeureFin'])) . '</p>';
            echo     '<p><strong>Statut :</strong> ' . safe_html($rdv['Statut']) . '</p>';

            if ($is_past) {
                echo '<div class="past-label">Passé</div>';
            } else {
                echo '<form method="POST" action="annuler_rdv.php">';
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
