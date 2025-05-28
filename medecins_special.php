<?php
require 'includes/head.php';
require 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <style>
        /* ========================================================= */
        /* STYLES SPÉCIFIQUES POUR LA PAGE DES MÉDECINS (v4)      */
        /* ========================================================= */

        .main-specialistes {
            padding: 2rem;
            background-color: #f2f2f2;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2rem;
        }
        
        .main-specialistes h1 {
            color: #333;
            margin-bottom: 1rem;
        }

        .doctor-card {
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

        /* --- Section supérieure (Photo + Infos) --- */
        .doctor-header {
            display: flex;
            gap: 2rem;
            /* MODIFIÉ : Centre la photo verticalement avec le bloc de détails */
            align-items: center; 
        }

        .doctor-photo {
            width: 170px;
            /* MODIFIÉ : Taille ajustée pour mieux correspondre au visuel */
            height: 220px; 
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
        
        .doctor-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 4px;
        }

        .doctor-details {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        /* MODIFIÉ : Style du titre de spécialité */
        .doctor-details .specialite-title {
            font-size: 1.5rem;
            font-weight: 600; /* Police un peu plus grasse */
            color: #333;
            background-color: #eaf5ff; /* Fond bleu clair */
            padding: 12px;
            border-radius: 6px;
            
            margin: 0;
        }
        
        /* MODIFIÉ : Conteneur en grille pour les informations */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr; /* Deux colonnes de largeur égale */
            gap: 1rem; /* Espace entre les éléments */
            padding-top: 1rem; /* Espace sous le titre */
        }
        
        .info-cell {
            font-size: 1.1rem;
        }

        /* Fait en sorte qu'un élément prenne toute la largeur de la grille */
        .full-width {
            grid-column: 1 / -1; /* Prend l'espace de la colonne 1 à la dernière */
        }

        .info-cell strong {
            font-weight: 500;
            color: #333;
        }
        

        /* --- Tableau des disponibilités --- */
        .availability-grid {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
            table-layout: fixed; 
        }
        
        .availability-grid th {
            background-color: #4a6fa5;
            color: white;
            padding: 12px;
            font-weight: 500;
        }
        
        .availability-grid td {
            border: 1px solid #e0e0e0;
            padding: 12px;
            color: #555;
        }
        
        .availability-grid th:first-child { border-top-left-radius: 6px; }
        .availability-grid th:last-child { border-top-right-radius: 6px; }
        .availability-grid tr:last-child td:first-child { border-bottom-left-radius: 6px; }
        .availability-grid tr:last-child td:last-child { border-bottom-right-radius: 6px; }

        /* --- Section des boutons --- */
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
        .btn-cv { background-color: #4a6fa5; }
    </style>
</head>
<body>

    <main class="main-specialistes">
        <h1>Nos Médecins et Spécialistes</h1>

        <?php
        
        function safe_html($value) {
            return $value !== null ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : '';
        }

        $conn = new mysqli("localhost", "root", "", "base_donne_web");
        if ($conn->connect_error) { die("Erreur de connexion: " . $conn->connect_error); }
        $conn->set_charset("utf8");

        $sql = "SELECT u.ID, u.Nom, u.Prenom, u.Email,
                       p.Photo, p.Telephone, p.Type,
                       a.Adresse, a.Ville, a.CodePostal
                FROM utilisateurs_personnel p
                LEFT JOIN utilisateurs u ON p.ID = u.ID
                LEFT JOIN adresse a ON p.ID_Adresse = a.ID
                WHERE p.Type IS NOT NULL";

        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $id = safe_html($row['ID']);

                $type_medecin = safe_html($row['Type']);
                $titre_formate = (strtolower($type_medecin) === 'généraliste') ? 'Généraliste' : 'Spécialiste - ' . $type_medecin;
                
                $adresse_complete = safe_html($row['Adresse']) . ', ' . safe_html($row['CodePostal']) . ' ' . safe_html($row['Ville']);
                ?>

                <div class="doctor-card">
                    <div class="doctor-header">
                        <div class="doctor-photo">
                            <?php if (!empty($row['Photo'])): ?>
                                <img src="<?= safe_html($row['Photo']) ?>" alt="Photo de <?= safe_html($row['Prenom']) ?>">
                            <?php else: ?>
                                <span>Photo</span>
                            <?php endif; ?>
                        </div>
                        <div class="doctor-details">
                            <h3 class="specialite-title">Spécialiste - <?= $type_medecin ?></h3>
                            
                            <div class="info-grid">
                                <div class="info-cell"><strong>Nom :</strong> <?= safe_html($row['Nom']) ?></div>
                                <div class="info-cell"><strong>Prénom :</strong> <?= safe_html($row['Prenom']) ?></div>
                                <div class="info-cell full-width"><strong>Adresse :</strong> <?= $adresse_complete ?></div>
                                <div class="info-cell full-width"><strong>Email :</strong> <?= safe_html($row['Email']) ?></div>
                                <div class="info-cell full-width"><strong>Téléphone :</strong> <?= safe_html($row['Telephone']) ?></div>
                            </div>
                        </div>
                    </div>

                    <table class="availability-grid">
                        <thead>
                            <tr>
                                <th>Lundi</th><th>Mardi</th><th>Mercredi</th><th>Jeudi</th><th>Vendredi</th><th>Samedi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td></tr>
                            <tr><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td></tr>
                            <tr><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td></tr>
                            <tr><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td></tr>
                        </tbody>
                    </table>

                    <div class="actions-container">
                        <a href="prendre_rdv.php?id=<?= $id ?>" class="btn-action btn-rdv">Prendre le RDV</a>
                        <a href="communiquer.php?id=<?= $id ?>" class="btn-action btn-communiquer">Communiquer</a>
                        <a href="cv_medecin.php?id=<?= $id ?>" class="btn-action btn-cv">Voir CV</a>
                    </div>
                </div>

                <?php
            }
        } else {
            echo "<p>Aucun médecin trouvé.</p>";
        }

        $conn->close();
        ?>
    </main>

    <?php require 'includes/footer.php'; ?>
</body>
</html>