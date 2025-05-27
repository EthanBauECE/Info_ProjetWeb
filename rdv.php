<!DOCTYPE html>

<!-- Importation du head -->
<?php require 'includes/head.php'; ?>

<body>

    <!-- Importation du header -->
    <?php require 'includes/header.php'; ?>


    <div class="rdv-container">
        <?php
    // (Connexion à la base de données)
    $pdo = new PDO('mysql:host=localhost;dbname=medicare;charset=utf8', 'root', 'root');
    date_default_timezone_set('Europe/Paris');
    
    // (Supposons que l'utilisateur est connecté avec user_id = 5)
    $stmt = $pdo->prepare("SELECT * FROM rendezvous WHERE user_id = ? ORDER BY date ASC, heure ASC");
    $stmt->execute([5]);
    
    while ($rdv = $stmt->fetch()) {
        // Fusionne date et heure pour comparer à maintenant
        $datetime_rdv = strtotime($rdv['date'] . ' ' . $rdv['heure']);
        $now = time();
        $is_past = $datetime_rdv < $now;
    
        echo '<div class="rdv-box">';
        echo   '<div class="rdv-photo">';
        echo     '<img src="' . $rdv['photo_medecin'] . '" alt="photo médecin">';
        echo     '<p>photo<br>médecin<br>demandé</p>';
        echo   '</div>';
        echo   '<div class="rdv-details">';
        echo     '<h2>Détails rendez-vous</h2>';
        echo     '<p><strong>Médecin :</strong> ' . htmlspecialchars($rdv['nom_medecin']) . '</p>';
        echo     '<p><strong>Date :</strong> ' . htmlspecialchars($rdv['date']) . '</p>';
        echo     '<p><strong>Heure :</strong> ' . htmlspecialchars($rdv['heure']) . '</p>';
    
        if ($is_past) {
            echo '<div class="past-label">Passé</div>';
        } else {
            echo '<form method="POST" action="annuler_rdv.php">';
            echo   '<input type="hidden" name="id_rdv" value="' . $rdv['id'] . '">';
            echo   '<button type="submit" class="btn-annuler">Annuler le rdv</button>';
            echo '</form>';
        }
    
        echo   '</div>';
        echo '</div>';
    }
    ?>
    </div>

    </main>

    <!-- Importation du footer -->
    <?php require 'includes/footer.php'; ?>

</body>

</html>