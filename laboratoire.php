<?php
require 'includes/head.php';
require 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <style>
        .main-container {
            padding: 2rem;
            background-color: #f2f2f2;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2rem;
        }
        .main-container h1 {
            color: #333;
            margin-bottom: 1rem;
        }
        .lab-card {
            background-color: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 900px;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        .lab-header {
            display: flex;
            gap: 2rem;
            align-items: center;
        }
        .lab-photo {
            width: 170px;
            height: 170px;
            border: 1px solid #e0e0e0;
            background-color: #f8f8f8;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #aaa;
            border-radius: 4px;
            flex-shrink: 0;
            font-size: 1.2rem;
        }
        .lab-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 4px;
        }
        .lab-details {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .lab-details .lab-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
            background-color: #eaf5ff;
            padding: 12px;
            border-radius: 6px;
            margin: 0;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            padding-top: 1.0rem;
        }
        .info-cell {
            font-size: 1.1rem;
        }
        .full-width {
            grid-column: 1 / -1;
        }
        .info-cell strong {
            font-weight: 500;
            color: #333;
        }
        .actions-container {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            padding-top: 1rem;
        }
        .btn-action {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            color: white;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            transition: opacity 0.3s ease;
        }
        .btn-action:hover { opacity: 0.85; }
        .btn-rdv { background-color: #6c757d; }
        .btn-communiquer { background-color: #5dade2; }
    </style>
</head>
<body>
<main class="main-container">
    <h1>Laboratoires</h1>
    <?php
    function safe_html($value) {
        return $value !== null ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : '';
    }

    $conn = new mysqli("localhost", "root", "", "base_donne_web");
    if ($conn->connect_error) {
        die("Erreur de connexion: " . $conn->connect_error);
    }
    $conn->set_charset("utf8");

    // Adapte la requête selon ta structure de base de données
    $sql = "SELECT l.ID, l.Nom, l.Description, l.Telephone, l.Email, l.Photo, a.Adresse, a.Ville, a.CodePostal
            FROM laboratoire l
            LEFT JOIN adresse a ON l.ID_Adresse = a.ID";

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $id = safe_html($row['ID']);
            $adresse_complete = safe_html($row['Adresse']) . ', ' . safe_html($row['CodePostal']) . ' ' . safe_html($row['Ville']);
            ?>
            <div class="lab-card">
                <div class="lab-header">
                    <div class="lab-photo">
                        <?php if (!empty($row['Photo'])): ?>
                            <img src="<?= safe_html($row['Photo']) ?>" alt="Photo du laboratoire">
                        <?php else: ?>
                            <span>Photo</span>
                        <?php endif; ?>
                    </div>
                    <div class="lab-details">
                        <h3 class="lab-title"><?= safe_html($row['Nom']) ?></h3>
                        <div class="info-grid">
                            <div class="info-cell full-width"><strong>Adresse :</strong> <?= $adresse_complete ?></div>
                            <div class="info-cell full-width"><strong>Description :</strong> <?= safe_html($row['Description']) ?></div>
                            <div class="info-cell"><strong>Téléphone :</strong> <?= safe_html($row['Telephone']) ?></div>
                            <div class="info-cell"><strong>Email :</strong> <?= safe_html($row['Email']) ?></div>
                        </div>
                    </div>
                </div>
                <div class="actions-container">
                    <a href="communiquer.php?lab_id=<?= $id ?>" class="btn-action btn-communiquer">Communiquer</a>
                    <a href="prendre_rdv_lab.php?lab_id=<?= $id ?>" class="btn-action btn-rdv">Prendre le RDV</a>
                </div>
            </div>
            <?php
        }
    } else {
        echo "<p>Aucun laboratoire trouvé.</p>";
    }
    $conn->close();
    ?>
</main>
<?php require 'includes/footer.php'; ?>
</body>
</html>
