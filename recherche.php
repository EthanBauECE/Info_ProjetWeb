<?php
    if (session_status() === PHP_SESSION_NONE) { //ON COMMENCE LA PAGE
        session_start();
    }
    error_reporting(E_ALL);//REGARDER SI ERREURS
    ini_set('display_errors', 1); //ON LES AFFICHE

 
    function safe_html($value) {
        return $value !== 0 ? htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') : '';//ON PROTEGE VOIR SOUCE 
    }

    $db_host = 'localhost';//ON SE CONNECTE A LA BDD
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'base_donne_web';
    $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);//AVEC LES DIFFERENTES COLONNES A REMPLIR D INFO

    if (!$conn) {//SI ON PEUT PAS SE CONNECTER
        die("Erreur de connexion BDD: " . mysqli_connect_error());//ON AFFICHE KL ERREUR
    }
    mysqli_set_charset($conn, 'utf8');

    $search_query = '';//POUVOIR RECHERCHER
    $results = [];//AFFICHER LE RESULTAT
    $message = '';//ET MESSAGE


    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['recherche'])) {//SI ON EST POUR LA RECHERCHE
        $search_query = trim($_POST['recherche']);//RECHERCHE
        header("Location: recherche.php?recherche=" . urlencode($search_query));//ON OUVRE LA PAGE RECHERCHE
        exit();

    }
    elseif (isset($_GET['recherche'])) {//SI Y A RECHERHCE
        $search_query = trim($_GET['recherche']);//ALORS RECHERCHE

        if (empty($search_query)) {//SI Y A RIEN QUI EST ECRIT
            $message = "Veuillez entrer un terme de recherche.";//ON INFORME QU IL FAUT INDIQUER A L I+UTILISATEUR
        } else {
            $search_param = '%' . $search_query . '%';
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
            ";//LA C EST POUR CHERCHER DES MEDECIN DES LABO OU DES MEDECINS SPECIALISES

            $stmt_search = mysqli_prepare($conn, $sql_search);//ON SE CONNECTE A LA BASE DE DONNEE

            if ($stmt_search) {
                mysqli_stmt_bind_param($stmt_search, "ssss", $search_param, $search_param, $search_param, $search_param);//ON RELIE LES INFOS
                if (mysqli_stmt_execute($stmt_search)) {//POUR POUVOIR CONNECTER A LA BDD
                    $result_set = mysqli_stmt_get_result($stmt_search);//RESULTAT DE LA RECHERCHE
                    $results = mysqli_fetch_all($result_set, MYSQLI_ASSOC);
                    mysqli_free_result($result_set);

                    if (empty($results)) {//SI RIEN DANS LA RCHCERHCE
                        $message = "Aucun résultat trouvé pour \"" . safe_html($search_query) . "\".";//INDIQUE QU ON A PAS TROUVE DE REPONSE A LA RECHERCHE
                    } else {
                        $message = count($results) . " résultat(s) trouvé(s) pour \"" . safe_html($search_query) . "\".";//INDIQUER CE QU ON A TROUVE POUR LA RECHERCHE
                    }
                } else {
                    $message = "Erreur : " . mysqli_stmt_error($stmt_search);//PREVENIR UTILISATEUR DE L ERREUR
                    error_log("Search query execution failed: " . mysqli_stmt_error($stmt_search));// LES ERREURS
                }
                mysqli_stmt_close($stmt_search);
            } else {
                $message = "Erreur " . mysqli_error($conn);//INDIQUER L ERREUR POUR UTILISATEUR
                error_log("Search query preparation failed: " . mysqli_error($conn));
            }
        }
    }
?>

<html lang="fr">

    <?php require 'includes/head.php'; ?>

    <body>
        <?php require 'includes/header.php'; ?><!--GARDER LE HAUT D EPAGE-->
        <main class="search-main">
            <div class="search-container">

                <h1 class="search-page-title">Résultats de recherche</h1><!--TITRE POUR AFFICHER LE RESULTAT DE LA RECHERHCE-->
                <?php if (!empty($message)): ?><!--SI PAS DE MESSAGE-->
                    <div class="search-alert <?= empty($results) ? 'info' : 'success' ?>"><!--CREER UNE ALERTE-->
                        <?= safe_html($message) ?>
                    </div>
                <?php endif; ?>

                <div class="search-results-list">
                    <?php if (!empty($results)): ?><!--SI ON A DES RESULTAT -->
                        <?php foreach ($results as $result): ?><!--ON PARCOURS TOUS LES RESULTATS POSSIBLES-->
                            <div class="result-card type-<?= safe_html($result['type_result']) ?>">

                                <div class="result-header"> <!-- ENTETE CARTE RESULTAT -->
                                    <?php if ($result['type_result'] === 'personnel'): ?><!-- SI CEST UN PERSONNEL-->
                                        <div class="result-photo-container">
                                            <img src="<?= safe_html($result['photo_path'] ?: './images/default_doctor.png') ?>" alt="Photo de <?= safe_html($result['nom_complet']) ?>" class="result-photo"><!--CA AFFICHE LA PHOTO OU ALORS LE FOND BLANC PAR DEFAUT-->
                                        </div>
                                        <div class="result-title-details">
                                            <h3>Dr. <?= safe_html($result['nom_complet']) ?></h3><!--POUR AFFICHER LE NIOM DU DOCTEUR--> 
                                            <p class="result-type"><?= safe_html($result['categorie']) ?></p><!-- ET AUSSI SA CATEGORIE-->
                                        </div>
                                    <?php elseif ($result['type_result'] === 'laboratoire'): ?><!--SI C EST UN LABORATOIRE-->
                                        <div class="result-photo-container"> <!-- EN CE QUI CONCERNE LA PHOTO-->
                                            <img src="<?= safe_html($result['photo_path'] ?: './images/default_labo.jpg') ?>" alt="Photo de <?= safe_html($result['nom_complet']) ?>" class="result-photo"><!--ON AFFICHE L IMAGE ET LE NOM-->
                                        </div>
                                        <div class="result-title-details">
                                            <h3>Laboratoire : <?= safe_html($result['nom_complet']) ?></h3><!--AFFICHER LE NOM DU LABO-->
                                            <p class="result-type"><?= safe_html($result['categorie']) ?></p><!--POUR LA CATEGORIE DU LABO-->
                                        </div>
                                    <?php elseif ($result['type_result'] === 'service'): ?><!--CA C EST POUR LES SERVIES QU IL PROPOSE-->
                                        <div class="result-photo-container">
                                            <img src="<?= safe_html($result['photo_path'] ?: './images/default_labo.jpg') ?>" alt="Photo du laboratoire de service" class="result-photo"><!--AFFICHER LA PHOTO DU LABO AVEC LES SERVIVES-->
                                        </div>
                                        <div class="result-title-details">
                                            <h3>Service : <?= safe_html($result['nom_complet']) ?></h3><!-- POUR LE NOM-->
                                            <p class="result-type">Du laboratoire : <?= safe_html($result['labo_nom_associe']) ?></p><!--AFFICHER LE NOM DU LABO-->
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="result-body">
                                    <?php if ($result['type_result'] === 'personnel'): ?><!-- SI CEST UN PERSONNEL-->
                                        <p><strong>Email :</strong> <a href="mailto:<?= safe_html($result['email']) ?>"><?= safe_html($result['email']) ?></a></p><!--ON FAIT LE LIEN AVEC L EMAIL ASSOCIEE ET AFFICHE-->
                                        <p><strong>Téléphone :</strong> <?= safe_html($result['telephone']) ?></p><!-- IDEM POUR LE TEL-->
                                        <p><strong>Adresse :</strong>
                                            <?php
                                                echo safe_html($result['adresse_ligne']);
                                                if (!empty($result['adresse_code_postal'])) echo ', ' . safe_html($result['adresse_code_postal']);// LA ON FAIT PAREIL POUR LE CODE POSTAL DU MEDECIN ON L AJOUTE
                                                if (!empty($result['adresse_ville'])) echo ' ' . safe_html($result['adresse_ville']);//ON AJOUTE AUSSI ICI L ADRESSE DU PERSONNEL
                                                if (!empty($result['adresse_infos_comp'])) {//LES INFO S EN PLUS SI BESOIN
                                                    echo '<br><em class="address-details">' . safe_html($result['adresse_infos_comp']) . '</em>';//DOCN LES INFOS COMPLEMENBTAIRES
                                                }
                                            ?>
                                        </p>
                                        <?php
                                        $personnel_desc_row = 0;//ON INTITIALISEE
                                        $sql_desc = "SELECT Description FROM utilisateurs_personnel WHERE ID = ?";//ON PREND LA DESCRIPTION DE L UTILISATEUR PERSONNEL
                                        $stmt_desc = mysqli_prepare($conn, $sql_desc);//ON RELIE A LA TABLE
                                        if ($stmt_desc) {
                                            mysqli_stmt_bind_param($stmt_desc, "i", $result['id']); //REGARDER L ID DE L UTILISATEUR
                                            if (mysqli_stmt_execute($stmt_desc)) {//ON FAIT CE QUI EST DEMANDEEE
                                                $desc_result_set = mysqli_stmt_get_result($stmt_desc);//ON REGARDE LE RESULTAT
                                                $personnel_desc_row = mysqli_fetch_assoc($desc_result_set);//ON PEUT MAINTENANT LE RELIER
                                                mysqli_free_result($desc_result_set);
                                            }
                                            mysqli_stmt_close($stmt_desc);//ON REFERMEE
                                        }

                                        if ($personnel_desc_row && !empty($personnel_desc_row['Description'])): ?><!--est ce qu il ya une descriptionn-->
                                            <p><strong>Description :</strong> <?= nl2br(safe_html($personnel_desc_row['Description'])) ?></p><!--on affiche cette description-->
                                        <?php endif; ?>
                                    <?php elseif ($result['type_result'] === 'laboratoire'): ?><!--si cest un laboratoire-->
                                        <p><strong>Email :</strong> <a href="mailto:<?= safe_html($result['email']) ?>"><?= safe_html($result['email']) ?></a></p><!--on affiche l email-->
                                        <p><strong>Téléphone :</strong> <?= safe_html($result['telephone']) ?></p><!--on affiche le numero de telephoen aussi-->
                                        <p><strong>Adresse :</strong><!--idem pour l adresse-->
                                            <?php
                                                echo safe_html($result['adresse_ligne']);
                                                if (!empty($result['adresse_code_postal'])) echo ', ' . safe_html($result['adresse_code_postal']);// AFFICHER D ABORD LE CODE POSTALE ASSOCIEE
                                                if (!empty($result['adresse_ville'])) echo ' ' . safe_html($result['adresse_ville']);//PUIS L ADRESSE
                                                if (!empty($result['adresse_infos_comp'])) {//LES INFOS COMPLEMENTIARE SI Y EN A 
                                                    echo '<br><em class="address-details">' . safe_html($result['adresse_infos_comp']) . '</em>';
                                                }
                                            ?>
                                        </p>
                                        <?php if (!empty($result['description_complementaire'])): ?><!--verif si y a une descripton en plus-->
                                            <p><strong>Description :</strong> <?= nl2br(safe_html($result['description_complementaire'])) ?></p><!--afficher la dercription complementaire-->
                                        <?php endif; ?>
                                    <?php elseif ($result['type_result'] === 'service'): ?><!--si c est un service-->
                                        <p><strong>Prix :</strong> <?= safe_html(number_format((float)$result['service_prix'], 2, ',', ' ')) ?> €</p><!--afficher le prix-->
                                        <?php if (!empty($result['description_complementaire'])): ?><!--si y a laors on affiche-->
                                            <p><strong>Description du service :</strong> <?= nl2br(safe_html($result['description_complementaire'])) ?></p><!--source: https://www.php.net/manual/fr/function.htmlspecialchars.php-->
                                        <?php endif; ?>
                                        <p><strong>Contact Laboratoire :</strong> <a href="mailto:<?= safe_html($result['email']) ?>"><?= safe_html($result['email']) ?></a></p><!--on affiche l email asoociee-->
                                        <p><strong>Téléphone Laboratoire :</strong> <?= safe_html($result['telephone']) ?></p><!--pareil pour le numero de tel-->
                                        <p><strong>Adresse Laboratoire :</strong><!-- pour indicztion de l adresse-->
                                            <?php
                                                echo safe_html($result['adresse_ligne']);//AFFICHE ADRESSE-->
                                                if (!empty($result['adresse_code_postal'])) echo ', ' . safe_html($result['adresse_code_postal']);// A LA SUITE METTRE CODE ¨POSTAL
                                                if (!empty($result['adresse_ville'])) echo ' ' . safe_html($result['adresse_ville']);//PUIS AJOUTER EN PLUS L ADRESSE
                                                if (!empty($result['adresse_infos_comp'])) {//SI IL Y A DES INFO COMPLEMENTAIRES
                                                    echo '<br><em class="address-details">' . safe_html($result['adresse_infos_comp']) . '</em>';//ON LES AJOUTERE AUSSI A LA SUITE
                                                }
                                            ?>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <div class="result-actions">
                                    <?php if ($result['type_result'] === 'personnel'): ?><!-- SI C EST UN PERSONNEL -->
                                        <a href="medecine_general.php#doctor-<?= safe_html($result['id']) ?>" class="btn-action">Voir les disponibilités</a><!-- BOUTON POIUR POUVOIR VOIR LES DISPO-->
                                        <a href="chat.php?target_id=<?= safe_html($result['id']) ?>" class="btn-action btn-communiquer">Communiquer</a><!--BOUTON POUR DISCUTER AVCE LUI -->
                                        <a href="cv_medecin.php?id=<?= safe_html($result['id']) ?>" class="btn-action btn-cv">Voir CV</a><!-- BOUTON POUR VOIR LE CV DU DOCTEUR -->
                                    <?php elseif ($result['type_result'] === 'laboratoire'): ?><!-- SI C EST UN LABORATOIRE -->
                                        <a href="laboratoire.php#labo-<?= safe_html($result['id']) ?>" class="btn-action">Voir les services et disponibilités</a><!--BOUTON POUUR POUVOIRR VOIR LE S SERVICES ET DISPO DU LABO -->
                                    <?php elseif ($result['type_result'] === 'service'): ?><!-- SI C EST UN SERVICE -->
                                        <a href="laboratoire.php#labo-<?= safe_html($result['labo_id']) ?>" class="btn-action">Voir le laboratoire</a><!-- BOUTON POUR POUVOIR VOIR LE LABORATOIRE -->
                                    <?php endif; ?>
                                </div>

                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div> 

            </div> 
        </main>

        <?php require 'includes/footer.php'; ?><!--ON GARDE FOOTER-->
        <?php mysqli_close($conn); ?> 

    </body>
</html>

<style>
    .search-main {
        padding: 2rem;
        background-color:rgb(242, 242, 242);
        display: flex;
        flex-direction: column;
        align-items: center;
        min-height: calc(100vh - 160px); 
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
        color:rgb(10, 122, 191);
        margin-bottom: 2.5rem;
        font-size: 2.2rem;
        font-weight: 600;
    }

    .search-alert {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 5px;
        text-align: center;
        font-weight: 500;
    }
    .search-alert.success {
        background-color:rgb(212, 237, 218);
        color:rgb(21, 87, 36);
        border: 1px solidrgb(190, 231, 200);
    }
    .search-alert.info {
        background-color: white;
        color:rgb(10, 122, 191);
        border: 1px solidrgb(204, 224, 255);
    }

    .search-results-list {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .result-card {
        background-color:rgb(248, 249, 250);
        border: 1px solidrgb(224, 224, 224);
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
        border: 2px solidrgb(10, 122, 191);
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: white;
    }

    .result-photo {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .result-title-details h3 {
        margin: 0;
        font-size: 1.3rem;
        color:rgb(10, 122, 191); 
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
        color:rgb(10, 122, 191); 
        font-weight: 600;
    }
    .result-body p a {
        color:rgb(0, 123, 255);
        text-decoration: none;
    }
    .result-body p a:hover {
        text-decoration: underline;
    }
    .result-body .address-details {
        font-size: 0.85em;
        color:rgb(108, 117, 125);
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
        background-color:rgb(0, 123, 255); 
    }

    .btn-action:hover {
        background-color:rgb(0, 86, 179); 
        transform: translateY(-1px);
    }
    .btn-action.btn-communiquer { background-color: #5dade2; }
    .btn-action.btn-communiquer:hover { background-color: #4499cc; }
    .btn-action.btn-cv { background-color: #4a6fa5; }
    .btn-action.btn-cv:hover { background-color: #3b5a86; }

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
            display: block; 
        }
    }
</style>