<!DOCTYPE html>
<?php require 'includes/head.php'; ?>

<body>
<?php require 'includes/header.php'; ?>

<main style="padding: 2rem; background-color: #f9f9f9;">
    <h1 style="margin-bottom: 2rem;">Médecins spécialisés</h1>

    <?php
    // Fonction helper pour gérer les valeurs nulles
    function safe_html($value) {
        return $value !== null ? htmlspecialchars($value) : '';
    }

    // Connexion MySQLi
    $conn = new mysqli("localhost", "root", "", "base_donne_web");

    if ($conn->connect_error) {
        die("Erreur de connexion: " . $conn->connect_error);
    }

    // Requête SQL pour tous les types SAUF généraliste
    $sql = "SELECT u.Nom, u.Prenom, u.Email,
                   p.Photo, p.Video, p.Telephone, p.Description, p.Type,
                   a.Adresse, a.Ville, a.CodePostal, a.InfosComplementaires
            FROM utilisateurs_personnel p
            LEFT JOIN utilisateurs u ON p.ID = u.ID
            LEFT JOIN adresse a ON p.ID_Adresse = a.ID
            WHERE LOWER(p.Type) != 'généraliste' OR p.Type IS NULL";

    $result = $conn->query($sql);

    if ($result === false) {
        echo "<p style='color: red;'>Erreur SQL : " . safe_html($conn->error) . "</p>";
    } elseif ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo '<section class="info-card" style="background: #fff; padding: 1.5rem; margin-bottom: 2rem; border-radius: 10px;">';

            echo '<h2>Dr. ' . safe_html($row['Prenom']) . ' ' . safe_html($row['Nom']) . '</h2>';

            if (!empty($row['Photo'])) {
                echo '<img src="' . safe_html($row['Photo']) . '" style="width: 200px; border-radius: 8px;" alt="Photo">';
            }

            if (!empty($row['Video'])) {
                echo '<video width="320" height="240" controls style="margin-top: 1rem;">';
                echo '<source src="' . safe_html($row['Video']) . '" type="video/mp4">';
                echo 'Votre navigateur ne supporte pas la vidéo.';
                echo '</video>';
            }

            echo '<p><strong>Email :</strong> ' . safe_html($row['Email']) . '</p>';
            echo '<p><strong>Téléphone :</strong> ' . safe_html($row['Telephone']) . '</p>';
            echo '<p><strong>Spécialité :</strong> ' . safe_html($row['Type']) . '</p>';
            echo '<p><strong>Adresse :</strong> ' . 
                 safe_html($row['Adresse']) . ', ' . 
                 safe_html($row['CodePostal']) . ' ' . 
                 safe_html($row['Ville']) . '</p>';
            echo '<p><strong>Complément :</strong> ' . safe_html($row['InfosComplementaires']) . '</p>';
            echo '<p><strong>Description :</strong> ' . safe_html($row['Description']) . '</p>';

            echo '</section>';
        }
    } else {
        echo "<p style='color: red;'>Aucun spécialiste trouvé.</p>";
    }

    $conn->close();
    ?>
</main>

<?php require 'includes/footer.php'; ?>
</body>
</html>