<?php
// On s'assure que la session est bien démarrée avant tout.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Connexion à la base de données ---
try {
    $pdo = new PDO('mysql:host=localhost;dbname=base_donne_web;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("ERREUR : Impossible de se connecter à la base de données. " . $e->getMessage());
}

// --- Récupérer la liste des laboratoires AVEC LEURS ADRESSES ---
try {
    // Modification de la requête pour inclure les informations de la table 'adresse'
    $stmtLabos = $pdo->query("
        SELECT 
            l.ID, l.Nom, l.Photos, l.Email, l.Telephone, l.Description,
            ad.Adresse AS adresse_ligne, ad.Ville AS adresse_ville, ad.CodePostal AS adresse_code_postal, ad.InfosComplementaires AS adresse_infos_comp
        FROM 
            laboratoire l
        LEFT JOIN 
            adresse ad ON l.ID_Adresse = ad.ID
        ORDER BY 
            l.Nom ASC
    ");
    $laboratoires = $stmtLabos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("ERREUR : Impossible de récupérer la liste des laboratoires. " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Nos Laboratoires - Medicare</title>
    <link rel="icon" type="image/png" href="./images/medicare_logo.png" />
    <link rel="stylesheet" href="./css/style.css" />

    <style>
        .main-labo-content {
            background-color: #f4f7f6;
            padding: 2rem 1rem;
        }
        .labo-container {
            max-width: 900px;
            margin: auto;
        }
        .page-labo-title {
            text-align: center;
            color: #0a7abf;
            margin-bottom: 2.5rem;
            font-size: 2.2rem;
        }
        .labo-bricks-list {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        .labo-brick {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
        }
        .labo-header {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .labo-photo {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 6px;
            flex-shrink: 0;
        }
        .labo-title-section h3 {
            margin: 0 0 0.5rem 0;
            color: #0a7abf;
            font-size: 1.4rem;
        }
        .labo-info {
            font-size: 0.9rem;
            color: #555;
            margin-bottom: 1rem;
        }
        .labo-info p {
            margin: 0.3rem 0;
        }
        .labo-info strong {
            color: #333;
        }
        .labo-description {
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 1.5rem;
            color: #444;
        }
        /* Style pour le nouveau bouton "Communiquer" */
        .btn-communiquer {
            display: inline-block;
            background-color: #5bc0de; /* Couleur différente pour le distinguer */
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            text-align: center;
            align-self: flex-start; /* Aligne le bouton à gauche */
            margin-bottom: 1.5rem; /* Espace avant la section des services */
            transition: background-color 0.3s;
        }
        .btn-communiquer:hover {
            background-color: #31b0d5;
        }

        .labo-services-title {
            font-size: 1.25rem; /* Un peu plus gros */
            color: #0a7abf;   /* En bleu */
            margin-top: 1rem;
            margin-bottom: 0.75rem;
            border-top: 1px solid #eee;
            padding-top: 1rem;
        }
        .labo-services-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .service-button {
            display: block;
            width: 100%;
            background-color: #e8f4fd;
            color: #0a7abf;
            border: 1px solid #bde0fe;
            padding: 0.75rem 1rem;
            border-radius: 5px;
            text-decoration: none;
            text-align: center;
            font-weight: 500;
            transition: background-color 0.3s, color 0.3s;
        }
        .service-button:hover {
            background-color: #0a7abf;
            color: white;
        }
        .service-price {
            font-size: 0.85rem;
            color: #777;
        }
    </style>
</head>
<body>
    <?php require 'includes/header.php'; ?>

    <main class="main-labo-content">
        <div class="labo-container">
            <h1 class="page-labo-title">Nos Laboratoires Partenaires</h1>

            <div class="labo-bricks-list">
                <?php if (empty($laboratoires)): ?>
                    <p style="text-align: center;">Aucun laboratoire disponible pour le moment.</p>
                <?php else: ?>
                    <?php foreach ($laboratoires as $labo): ?>
                        <div class="labo-brick">
                            <div class="labo-header">
                                <img src="<?php echo htmlspecialchars($labo['Photos'] ?: './images/default_labo.jpg'); ?>" alt="Photo de <?php echo htmlspecialchars($labo['Nom']); ?>" class="labo-photo">
                                <div class="labo-title-section">
                                    <h3><?php echo htmlspecialchars($labo['Nom']); ?></h3>
                                    <div class="labo-info">
                                        <?php if (!empty($labo['adresse_ligne'])): ?>
                                            <p><strong>Adresse :</strong> 
                                                <?php echo htmlspecialchars($labo['adresse_ligne']); ?>
                                                <?php if (!empty($labo['adresse_code_postal']) && !empty($labo['adresse_ville'])): ?>
                                                    , <?php echo htmlspecialchars($labo['adresse_code_postal']) . ' ' . htmlspecialchars($labo['adresse_ville']); ?>
                                                <?php endif; ?>
                                            </p>
                                            <?php if (!empty($labo['adresse_infos_comp'])): ?>
                                                <p style="font-size:0.85em; color: #666;"><em><?php echo htmlspecialchars($labo['adresse_infos_comp']); ?></em></p>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        
                                        <p><strong>Email :</strong> <?php echo htmlspecialchars($labo['Email']); ?></p>
                                        <p><strong>Téléphone :</strong> <?php echo htmlspecialchars($labo['Telephone']); ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <p class="labo-description"><?php echo nl2br(htmlspecialchars($labo['Description'])); ?></p>

                            <a href="mailto:<?php echo htmlspecialchars($labo['Email']); ?>" class="btn-communiquer">Communiquer avec ce laboratoire</a>
                            
                            <h4 class="labo-services-title">Prendre un rendez-vous pour :</h4>
                            <div class="labo-services-list">
                                <?php
                                try {
                                    $stmtServices = $pdo->prepare("SELECT NomService, Prix FROM service_labo WHERE ID_Laboratoire = ? ORDER BY NomService ASC");
                                    $stmtServices->execute([$labo['ID']]);
                                    $services = $stmtServices->fetchAll(PDO::FETCH_ASSOC);

                                    if (empty($services)) {
                                        echo "<p style='font-size:0.9em; color:#777;'>Aucun service spécifique listé pour ce laboratoire.</p>";
                                    } else {
                                        foreach ($services as $service) {
                                            echo '<a href="prendre_rdv_labo.php?labo_id=' . $labo['ID'] . '&service=' . urlencode($service['NomService']) . '" class="service-button">';
                                            echo htmlspecialchars($service['NomService']);
                                            if (isset($service['Prix'])) {
                                                echo ' <span class="service-price">(' . htmlspecialchars($service['Prix']) . ' €)</span>';
                                            }
                                            echo '</a>';
                                        }
                                    }
                                } catch (PDOException $e) {
                                    echo "<p style='color:red;'>Erreur de chargement des services : " . $e->getMessage() . "</p>";
                                }
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php require 'includes/footer.php'; ?>
</body>
</html>