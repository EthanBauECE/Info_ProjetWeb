<!DOCTYPE html>
<?php require 'includes/head.php'; ?>

<body>
<?php require 'includes/header.php'; ?>

<main style="padding: 2rem; background-color: #f9f9f9;">
    <h1 style="margin-bottom: 2rem;">Médecins généralistes</h1>

    <?php
    // Fonction helper pour échapper les caractères spéciaux HTML
    function safe_html($value) {
        return $value !== null ? htmlspecialchars($value) : '';
    }

    // Connexion MySQLi
    $conn = new mysqli("localhost", "root", "", "base_donne_web");

    if ($conn->connect_error) {
        die("Erreur de connexion: " . $conn->connect_error);
    }

    // Requête SQL pour les médecins généralistes
    $sql = "SELECT u.ID, u.Nom, u.Prenom, u.Email,
                   p.Photo, p.Video, p.Telephone, p.Description, p.Type,
                   a.Adresse, a.Ville, a.CodePostal, a.InfosComplementaires
            FROM utilisateurs_personnel p
            LEFT JOIN utilisateurs u ON p.ID = u.ID
            LEFT JOIN adresse a ON p.ID_Adresse = a.ID
            WHERE LOWER(p.Type) != 'généraliste'OR p.Type IS NULL";

    $result = $conn->query($sql);

    if ($result === false) {
        echo "<p style='color: red;'>Erreur SQL : " . safe_html($conn->error) . "</p>";
    } elseif ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $id = safe_html($row['ID']);

            echo '<section class="info-card" style="background: #fff; padding: 1.5rem; margin-bottom: 2rem; border-radius: 10px;">';

            echo '<h2>Dr. ' . safe_html($row['Prenom']) . ' ' . safe_html($row['Nom']) . '</h2>';

            // Affiche la photo si présente
            if (!empty($row['Photo'])) {
                echo '<img src="' . safe_html($row['Photo']) . '" style="width: 200px; border-radius: 8px;" alt="Photo">';
            }

            // Affiche la vidéo si présente
            if (!empty($row['Video'])) {
                echo '<video width="320" height="240" controls style="margin-top: 1rem;">
                        <source src="' . safe_html($row['Video']) . '" type="video/mp4">
                        Votre navigateur ne supporte pas la vidéo.
                      </video>';
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

            // ➤ Exemple de calendrier de disponibilités statique (à adapter si tu veux rendre dynamique)
            echo '<table style="margin-top: 1rem; border-collapse: collapse; width: 100%; text-align: center;">
                    <thead>
                        <tr style="background-color: #e0e0e0;">
                            <th>Spécialité</th>
                            <th>Médecin</th>
                            <th>Lundi</th>
                            <th>Mardi</th>
                            <th>Mercredi</th>
                            <th>Jeudi</th>
                            <th>Vendredi</th>
                            <th>Samedi</th>
                            <th>Dimanche</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Médecin généraliste</td>
                            <td>' . safe_html($row['Nom']) . ', ' . safe_html($row['Prenom']) . '</td>
                            <td>AM / PM</td>
                            <td>AM / PM</td>
                            <td>PM</td>
                            <td>AM</td>
                            <td>AM / PM</td>
                            <td>AM</td>
                            <td>—</td>
                        </tr>
                    </tbody>
                  </table>';

            // Boutons liés aux pages dynamiques
            echo '<div style="margin-top: 1.5rem; display: flex; gap: 1rem;">
                    <a href="prendre_rdv.php?id=' . $id . '" style="background-color: #8bc34a; padding: 0.8rem 1.2rem; border-radius: 8px; color: white; font-weight: bold; text-decoration: none;">Prendre un RDV</a>
                    <a href="communiquer.php?id=' . $id . '" style="background-color: #03a9f4; padding: 0.8rem 1.2rem; border-radius: 8px; color: white; font-weight: bold; text-decoration: none;">Communiquer avec le médecin</a>
                    <a href="cv_medecin.php?id=' . $id . '" style="background-color: #e0e0e0; padding: 0.8rem 1.2rem; border-radius: 8px; font-weight: bold; text-decoration: none;">Voir son CV</a>
                  </div>';

            echo '</section>';
        }
    } else {
        echo "<p style='color: red;'>Aucun médecin généraliste trouvé.</p>";
    }

    $conn->close();
    ?>
</main>

<?php require 'includes/footer.php'; ?>
</body>
</html>
