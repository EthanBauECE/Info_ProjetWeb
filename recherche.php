<?php /////////////////////////////////////////////// PHP //////////////////////////////////////////

    // ______________/ Initialisation Session et Erreurs \_____________________
    if (session_status() === PHP_SESSION_NONE) { // Démarre la session si elle n'est pas déjà active
        session_start();
    }
    error_reporting(E_ALL);      // Affiche toutes les erreurs pour le débogage
    ini_set('display_errors', 1); // Active l'affichage des erreurs

    // ______________/ Fonction Utilitaire \_____________________
    /// Fonction pour échapper les caractères HTML
    function safe_html($value) {
        return $value !== null ? htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') : '';
    }

    // ______________/ Connexion à la base de données (MySQLi) \_____________________
    $db_host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'base_donne_web';
    $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

    /// Vérification de la connexion MySQLi
    if (!$conn) {
        die("Erreur de connexion BDD: " . mysqli_connect_error());
    }
    mysqli_set_charset($conn, 'utf8');

    // ______________/ Variables de recherche \_____________________
    $search_query = '';
    $results = [];
    $message = '';

    // ______________/ Gestion de la recherche \_____________________

    /// Si le formulaire est soumis en POST
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['recherche'])) {
        $search_query = trim($_POST['recherche']);

        /// Optionnel : stocker le terme de recherche en session pour qu'il soit pré-rempli dans le header
        // $_SESSION['last_search_query'] = $search_query;

        /// Rediriger vers la page elle-même mais avec le terme de recherche en GET
        /// C'est une méthode courante pour éviter le re-soumission de formulaire au rafraîchissement.
        header("Location: recherche.php?recherche=" . urlencode($search_query));
        exit();

    }
    /// Si la page est accédée via GET (après redirection POST ou directement)
    elseif (isset($_GET['recherche'])) {
        $search_query = trim($_GET['recherche']);

        if (empty($search_query)) {
            $message = "Veuillez entrer un terme de recherche.";
        } else {
            /// Préparer le terme de recherche pour la clause LIKE
            $search_param = '%' . $search_query . '%';

            // ______________/ Requête SQL unifiée avec UNION \_____________________
            /// On sélectionne des colonnes communes ou NULL pour maintenir la compatibilité des UNION
            $sql_search = "
                (SELECT
                    'personnel' AS type_result,
                    u.ID AS id,
                    CONCAT(u.Prenom, ' ', u.Nom) AS nom_complet,
                    up.Type AS categorie, -- Spécialité
                    u.Email AS email,
                    up.Telephone AS telephone,
                    a.Adresse AS adresse_ligne, a.Ville AS adresse_ville, a.CodePostal AS adresse_code_postal, a.InfosComplementaires AS adresse_infos_comp,
                    up.Photo AS photo_path,
                    NULL AS description_complementaire,
                    NULL AS service_prix,
                    NULL AS labo_id,
                    NULL AS labo_nom_associe
                FROM utilisateurs u
                JOIN utilisateurs_personnel up ON u.ID = up.ID
                LEFT JOIN adresse a ON up.ID_Adresse = a.ID
                WHERE CONCAT(u.Nom, ' ', u.Prenom) LIKE ? OR up.Type LIKE ?)

                UNION ALL

                (SELECT
                    'laboratoire' AS type_result,
                    l.ID AS id,
                    l.Nom AS nom_complet,
                    'Laboratoire' AS categorie,
                    l.Email AS email,
                    l.Telephone AS telephone,
                    a.Adresse AS adresse_ligne, a.Ville AS adresse_ville, a.CodePostal AS adresse_code_postal, a.InfosComplementaires AS adresse_infos_comp,
                    l.Photos AS photo_path,
                    l.Description AS description_complementaire,
                    NULL AS service_prix,
                    NULL AS labo_id,
                    NULL AS labo_nom_associe
                FROM laboratoire l
                LEFT JOIN adresse a ON l.ID_Adresse = a.ID
                WHERE l.Nom LIKE ?)

                UNION ALL

                (SELECT
                    'service' AS type_result,
                    sl.ID AS id,
                    sl.NomService AS nom_complet,
                    'Service de Laboratoire' AS categorie,
                    la.Email AS email, -- Email du laboratoire associé
                    la.Telephone AS telephone, -- Téléphone du laboratoire associé
                    ad.Adresse AS adresse_ligne, ad.Ville AS adresse_ville, ad.CodePostal AS adresse_code_postal, ad.InfosComplementaires AS adresse_infos_comp,
                    la.Photos AS photo_path, -- Photo du laboratoire associé
                    sl.Description AS description_complementaire,
                    sl.Prix AS service_prix,
                    la.ID AS labo_id,
                    la.Nom AS labo_nom_associe
                FROM service_labo sl
                JOIN laboratoire la ON sl.ID_Laboratoire = la.ID
                LEFT JOIN adresse ad ON la.ID_Adresse = ad.ID
                WHERE sl.NomService LIKE ?)
                ORDER BY nom_complet;
            ";

            /// Préparation de la requête
            $stmt_search = mysqli_prepare($conn, $sql_search);

            if ($stmt_search) {
                /// Liaison des paramètres (tous de type string 's')
                mysqli_stmt_bind_param($stmt_search, "ssss", $search_param, $search_param, $search_param, $search_param);

                /// Exécution de la requête
                if (mysqli_stmt_execute($stmt_search)) {
                    $result_set = mysqli_stmt_get_result($stmt_search);
                    $results = mysqli_fetch_all($result_set, MYSQLI_ASSOC);
                    mysqli_free_result($result_set);

                    if (empty($results)) {
                        $message = "Aucun résultat trouvé pour \"" . safe_html($search_query) . "\".";
                    } else {
                        $message = count($results) . " résultat(s) trouvé(s) pour \"" . safe_html($search_query) . "\".";
                    }
                } else {
                    $message = "Erreur lors de l'exécution de la recherche : " . mysqli_stmt_error($stmt_search);
                    error_log("Search query execution failed: " . mysqli_stmt_error($stmt_search));
                }
                mysqli_stmt_close($stmt_search);
            } else {
                $message = "Erreur lors de la préparation de la recherche : " . mysqli_error($conn);
                error_log("Search query preparation failed: " . mysqli_error($conn));
            }
        }
    }
?>

<!DOCTYPE html> <!-- ////////////////////////////////////////// HTML ///////////////////////////////////////////-->
<html lang="fr">

    <!-- Importation head -->
    <?php require 'includes/head.php'; ?>

    <body>

        <!-- Importation header -->
        <?php require 'includes/header.php'; ?>

        <main class="search-main">
            <div class="search-container">

                <h1 class="search-page-title">Résultats de recherche</h1>

                <!-- Message d'information/erreur -->
                <?php if (!empty($message)): ?>
                    <div class="search-alert <?= empty($results) ? 'info' : 'success' ?>">
                        <?= safe_html($message) ?>
                    </div>
                <?php endif; ?>

                <!-- Liste des résultats -->
                <div class="search-results-list">
                    <?php if (!empty($results)): ?>
                        <?php foreach ($results as $result): ?>
                            <div class="result-card type-<?= safe_html($result['type_result']) ?>">

                                <div class="result-header"> <!-- ENTETE CARTE RESULTAT -->
                                    <?php if ($result['type_result'] === 'personnel'): ?>
                                        <div class="result-photo-container">
                                            <img src="<?= safe_html($result['photo_path'] ?: './images/default_doctor.png') ?>" alt="Photo de <?= safe_html($result['nom_complet']) ?>" class="result-photo">
                                        </div>
                                        <div class="result-title-details">
                                            <h3>Dr. <?= safe_html($result['nom_complet']) ?></h3>
                                            <p class="result-type"><?= safe_html($result['categorie']) ?></p>
                                        </div>
                                    <?php elseif ($result['type_result'] === 'laboratoire'): ?>
                                        <div class="result-photo-container">
                                            <img src="<?= safe_html($result['photo_path'] ?: './images/default_labo.jpg') ?>" alt="Photo de <?= safe_html($result['nom_complet']) ?>" class="result-photo">
                                        </div>
                                        <div class="result-title-details">
                                            <h3>Laboratoire : <?= safe_html($result['nom_complet']) ?></h3>
                                            <p class="result-type"><?= safe_html($result['categorie']) ?></p>
                                        </div>
                                    <?php elseif ($result['type_result'] === 'service'): ?>
                                        <div class="result-photo-container">
                                            <img src="<?= safe_html($result['photo_path'] ?: './images/default_labo.jpg') ?>" alt="Photo du laboratoire de service" class="result-photo">
                                        </div>
                                        <div class="result-title-details">
                                            <h3>Service : <?= safe_html($result['nom_complet']) ?></h3>
                                            <p class="result-type">Du laboratoire : <?= safe_html($result['labo_nom_associe']) ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="result-body"> <!-- CORPS CARTE RESULTAT -->
                                    <?php if ($result['type_result'] === 'personnel'): ?>
                                        <p><strong>Email :</strong> <a href="mailto:<?= safe_html($result['email']) ?>"><?= safe_html($result['email']) ?></a></p>
                                        <p><strong>Téléphone :</strong> <?= safe_html($result['telephone']) ?></p>
                                        <p><strong>Adresse :</strong>
                                            <?php
                                                echo safe_html($result['adresse_ligne']);
                                                if (!empty($result['adresse_code_postal'])) echo ', ' . safe_html($result['adresse_code_postal']);
                                                if (!empty($result['adresse_ville'])) echo ' ' . safe_html($result['adresse_ville']);
                                                if (!empty($result['adresse_infos_comp'])) {
                                                    echo '<br><em class="address-details">' . safe_html($result['adresse_infos_comp']) . '</em>';
                                                }
                                            ?>
                                        </p>
                                        <?php
                                        /// Récupérer la description spécifique du personnel
                                        $personnel_desc_row = null;
                                        $sql_desc = "SELECT Description FROM utilisateurs_personnel WHERE ID = ?";
                                        $stmt_desc = mysqli_prepare($conn, $sql_desc);
                                        if ($stmt_desc) {
                                            mysqli_stmt_bind_param($stmt_desc, "i", $result['id']); // 'i' pour integer
                                            if (mysqli_stmt_execute($stmt_desc)) {
                                                $desc_result_set = mysqli_stmt_get_result($stmt_desc);
                                                $personnel_desc_row = mysqli_fetch_assoc($desc_result_set);
                                                mysqli_free_result($desc_result_set);
                                            }
                                            mysqli_stmt_close($stmt_desc);
                                        }

                                        if ($personnel_desc_row && !empty($personnel_desc_row['Description'])): ?>
                                            <p><strong>Description :</strong> <?= nl2br(safe_html($personnel_desc_row['Description'])) ?></p>
                                        <?php endif; ?>
                                    <?php elseif ($result['type_result'] === 'laboratoire'): ?>
                                        <p><strong>Email :</strong> <a href="mailto:<?= safe_html($result['email']) ?>"><?= safe_html($result['email']) ?></a></p>
                                        <p><strong>Téléphone :</strong> <?= safe_html($result['telephone']) ?></p>
                                        <p><strong>Adresse :</strong>
                                            <?php
                                                echo safe_html($result['adresse_ligne']);
                                                if (!empty($result['adresse_code_postal'])) echo ', ' . safe_html($result['adresse_code_postal']);
                                                if (!empty($result['adresse_ville'])) echo ' ' . safe_html($result['adresse_ville']);
                                                if (!empty($result['adresse_infos_comp'])) {
                                                    echo '<br><em class="address-details">' . safe_html($result['adresse_infos_comp']) . '</em>';
                                                }
                                            ?>
                                        </p>
                                        <?php if (!empty($result['description_complementaire'])): ?>
                                            <p><strong>Description :</strong> <?= nl2br(safe_html($result['description_complementaire'])) ?></p>
                                        <?php endif; ?>
                                    <?php elseif ($result['type_result'] === 'service'): ?>
                                        <p><strong>Prix :</strong> <?= safe_html(number_format((float)$result['service_prix'], 2, ',', ' ')) ?> €</p>
                                        <?php if (!empty($result['description_complementaire'])): ?>
                                            <p><strong>Description du service :</strong> <?= nl2br(safe_html($result['description_complementaire'])) ?></p>
                                        <?php endif; ?>
                                        <p><strong>Contact Laboratoire :</strong> <a href="mailto:<?= safe_html($result['email']) ?>"><?= safe_html($result['email']) ?></a></p>
                                        <p><strong>Téléphone Laboratoire :</strong> <?= safe_html($result['telephone']) ?></p>
                                        <p><strong>Adresse Laboratoire :</strong>
                                            <?php
                                                echo safe_html($result['adresse_ligne']);
                                                if (!empty($result['adresse_code_postal'])) echo ', ' . safe_html($result['adresse_code_postal']);
                                                if (!empty($result['adresse_ville'])) echo ' ' . safe_html($result['adresse_ville']);
                                                if (!empty($result['adresse_infos_comp'])) {
                                                    echo '<br><em class="address-details">' . safe_html($result['adresse_infos_comp']) . '</em>';
                                                }
                                            ?>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <div class="result-actions"> <!-- ACTIONS CARTE RESULTAT -->
                                    <?php if ($result['type_result'] === 'personnel'): ?>
                                        <a href="medecine_general.php#doctor-<?= safe_html($result['id']) ?>" class="btn-action">Voir les disponibilités</a>
                                        <a href="chat.php?target_id=<?= safe_html($result['id']) ?>" class="btn-action btn-communiquer">Communiquer</a>
                                        <a href="cv_medecin.php?id=<?= safe_html($result['id']) ?>" class="btn-action btn-cv">Voir CV</a>
                                    <?php elseif ($result['type_result'] === 'laboratoire'): ?>
                                        <a href="laboratoire.php#labo-<?= safe_html($result['id']) ?>" class="btn-action">Voir les services et disponibilités</a>
                                    <?php elseif ($result['type_result'] === 'service'): ?>
                                        <a href="laboratoire.php#labo-<?= safe_html($result['labo_id']) ?>" class="btn-action">Voir le laboratoire</a>
                                    <?php endif; ?>
                                </div>

                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div> <!-- FIN .search-results-list -->

            </div> <!-- FIN .search-container -->
        </main>

        <!-- Importation footer -->
        <?php require 'includes/footer.php'; ?>

        <!-- // Fermeture de la connexion a la BD
        <?php mysqli_close($conn); ?> -->

    </body>
</html>

<!-- ////////////////////////////////////////// CSS ///////////////////////////////////////////-->
<style>
    /* Styles généraux pour la page de recherche */
    .search-main {
        padding: 2rem;
        background-color: #f2f2f2;
        display: flex;
        flex-direction: column;
        align-items: center;
        min-height: calc(100vh - 160px); /* Ajuster si hauteur header/footer différente */
    }

    .search-container {
        max-width: 900px;
        width: 100%;
        margin: auto;
        background: #fff;
        padding: 2.5rem;
        border-radius: 10px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        box-sizing: border-box;
    }

    .search-page-title {
        text-align: center;
        color: #0a7abf; /* Couleur Omnes Education */
        margin-bottom: 2.5rem;
        font-size: 2.2rem;
        font-weight: 600;
    }

    /* Alert messages */
    .search-alert {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 5px;
        text-align: center;
        font-weight: 500;
    }
    .search-alert.success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .search-alert.info {
        background-color: #eaf5ff; /* Bleu clair */
        color: #0a7abf;
        border: 1px solid #cce0ff;
    }

    /* Result cards */
    .search-results-list {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .result-card {
        background-color: #f8f9fa;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.07);
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .result-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #eee;
    }

    .result-photo-container {
        flex-shrink: 0;
        width: 80px;
        height: 80px;
        border-radius: 50%;
        overflow: hidden;
        border: 2px solid #0a7abf; /* Couleur Omnes */
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f0f7ff; /* Fond bleu très clair */
    }

    .result-photo {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .result-title-details h3 {
        margin: 0;
        font-size: 1.3rem;
        color: #0a7abf; /* Couleur Omnes */
    }

    .result-title-details .result-type {
        margin: 5px 0 0;
        font-size: 0.9rem;
        color: #555;
    }

    .result-body p {
        margin: 0.5rem 0;
        font-size: 0.95rem;
        line-height: 1.5;
        color: #333;
    }

    .result-body strong {
        color: #0a7abf; /* Couleur Omnes */
        font-weight: 600;
    }
    .result-body p a {
        color: #007bff; /* Bleu lien standard */
        text-decoration: none;
    }
    .result-body p a:hover {
        text-decoration: underline;
    }
    .result-body .address-details {
        font-size: 0.85em;
        color: #6c757d; /* Gris standard pour détails */
    }

    .result-actions {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px dashed #eee;
        display: flex;
        flex-wrap: wrap;
        gap: 0.8rem;
        justify-content: flex-end;
    }

    .btn-action {
        display: inline-block;
        padding: 8px 15px;
        border: none;
        border-radius: 5px;
        color: white;
        font-size: 0.9rem;
        font-weight: 500;
        cursor: pointer;
        text-decoration: none;
        transition: background-color 0.2s ease, transform 0.2s ease;
        background-color: #007bff; /* Bleu bouton standard */
    }

    .btn-action:hover {
        background-color: #0056b3; /* Bleu plus foncé au survol */
        transform: translateY(-1px);
    }
    .btn-action.btn-communiquer { background-color: #5dade2; /* Bleu ciel */ }
    .btn-action.btn-communiquer:hover { background-color: #4499cc; }
    .btn-action.btn-cv { background-color: #4a6fa5; /* Bleu gris */ }
    .btn-action.btn-cv:hover { background-color: #3b5a86; }


    /* Media queries pour responsivité */
    @media (max-width: 768px) {
        .search-container {
            padding: 1.5rem;
        }
        .result-header {
            flex-direction: column;
            text-align: center;
        }
        .result-title-details {
            text-align: center;
        }
        .result-actions {
            justify-content: center;
        }
    }

    @media (max-width: 480px) {
        .search-page-title {
            font-size: 1.8rem;
        }
        .result-body strong {
            display: block; /* Force strong à la nouvelle ligne sur petits écrans pour lisibilité */
        }
    }
</style>