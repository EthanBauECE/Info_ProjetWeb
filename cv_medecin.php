<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'includes/head.php';
require 'includes/header.php';

function safe_html($value) {
    return $value !== null ? htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') : '';
}

$personnel_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$cv_data = null;
$personnel_info = null;
$xml_content = null;
$error_message_cv = '';

if ($personnel_id === 0) {
    $error_message_cv = "Aucun identifiant de professionnel fourni.";
} else {
    $db_host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'base_donne_web';
    $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

    if (!$conn) {
        $error_message_cv = "Erreur de connexion à la base de données: " . mysqli_connect_error();
    } else {
        mysqli_set_charset($conn, 'utf8');

        // Récupérer les informations du personnel et le chemin du fichier CV
        $sql = "SELECT u.Nom, u.Prenom, up.Type AS Specialite, cv.ContenuXML
                FROM utilisateurs_personnel up
                JOIN utilisateurs u ON up.ID = u.ID
                LEFT JOIN cv ON up.ID = cv.ID_Personnel
                WHERE up.ID = ?";

        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $personnel_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $personnel_info = mysqli_fetch_assoc($result);
            mysqli_free_result($result);
            mysqli_stmt_close($stmt);

            if ($personnel_info) {
                if (!empty($personnel_info['ContenuXML'])) {
                    $xml_file_name = $personnel_info['ContenuXML'];
                    $xml_file_path = __DIR__ . '/cvs/' . $xml_file_name; // Assurez-vous que le dossier 'cvs' existe à la racine

                    if (file_exists($xml_file_path)) {
                        libxml_use_internal_errors(true); // Pour gérer les erreurs de parsing XML
                        $xml_content = simplexml_load_file($xml_file_path);
                        if ($xml_content === false) {
                            $error_message_cv = "Erreur lors de la lecture du fichier XML du CV :<br>";
                            foreach(libxml_get_errors() as $error) {
                                $error_message_cv .= safe_html($error->message) . "<br>";
                            }
                            libxml_clear_errors();
                        }
                    } else {
                        $error_message_cv = "Le fichier XML du CV ('" . safe_html($xml_file_name) . "') est introuvable.";
                    }
                } else {
                    $error_message_cv = "Aucun CV n'a été enregistré pour ce professionnel.";
                }
            } else {
                $error_message_cv = "Professionnel non trouvé.";
            }
        } else {
            $error_message_cv = "Erreur de préparation de la requête : " . mysqli_error($conn);
        }
        mysqli_close($conn);
    }
}
?>

<main class="cv-main">
    <div class="cv-container">
        <?php if ($personnel_info): ?>
            <h1>CV de Dr. <?php echo safe_html($personnel_info['Prenom']) . ' ' . safe_html($personnel_info['Nom']); ?></h1>
            <p class="cv-specialite"><?php echo safe_html($personnel_info['Specialite']); ?></p>
        <?php else: ?>
            <h1>Curriculum Vitae</h1>
        <?php endif; ?>

        <?php if (!empty($error_message_cv)): ?>
            <div class="cv-alert error"><?php echo $error_message_cv; ?></div>
        <?php elseif ($xml_content): ?>
            <div class="cv-content">
                <?php if (isset($xml_content->informationsPersonnelles)): ?>
                <section class="cv-section">
                    <h2>Informations Personnelles</h2>
                    <?php if (isset($xml_content->informationsPersonnelles->emailContact)): ?>
                        <p><strong>Email :</strong> <?php echo safe_html($xml_content->informationsPersonnelles->emailContact); ?></p>
                    <?php endif; ?>
                    <?php if (isset($xml_content->informationsPersonnelles->telephoneContact)): ?>
                        <p><strong>Téléphone :</strong> <?php echo safe_html($xml_content->informationsPersonnelles->telephoneContact); ?></p>
                    <?php endif; ?>
                    <?php if (isset($xml_content->informationsPersonnelles->adresseWeb)): ?>
                        <p><strong>Site Web :</strong> <a href="http://<?php echo safe_html($xml_content->informationsPersonnelles->adresseWeb); ?>" target="_blank"><?php echo safe_html($xml_content->informationsPersonnelles->adresseWeb); ?></a></p>
                    <?php endif; ?>
                </section>
                <?php endif; ?>

                <?php if (isset($xml_content->resume) && !empty(trim((string)$xml_content->resume))): ?>
                <section class="cv-section">
                    <h2>Résumé Professionnel</h2>
                    <p><?php echo nl2br(safe_html($xml_content->resume)); ?></p>
                </section>
                <?php endif; ?>

                <?php if (isset($xml_content->formation) && isset($xml_content->formation->diplome)): ?>
                <section class="cv-section">
                    <h2>Formation</h2>
                    <ul>
                        <?php foreach ($xml_content->formation->diplome as $diplome): ?>
                            <li>
                                <strong><?php echo safe_html($diplome->titre); ?></strong> - <?php echo safe_html($diplome->institution); ?>
                                (<?php echo safe_html($diplome->anneeObtention); ?>)
                                <?php if (isset($diplome->mention) && !empty(trim((string)$diplome->mention))): ?>
                                    <em> - Mention : <?php echo safe_html($diplome->mention); ?></em>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
                <?php endif; ?>

                <?php if (isset($xml_content->experienceProfessionnelle) && isset($xml_content->experienceProfessionnelle->poste)): ?>
                <section class="cv-section">
                    <h2>Expérience Professionnelle</h2>
                    <?php foreach ($xml_content->experienceProfessionnelle->poste as $poste): ?>
                        <div class="experience-item">
                            <h3><?php echo safe_html($poste->titrePoste); ?></h3>
                            <p class="employeur-periode">
                                <?php echo safe_html($poste->employeur); ?> (<?php echo safe_html($poste->ville); ?>) - <em><?php echo safe_html($poste->periode); ?></em>
                            </p>
                            <?php if (isset($poste->responsabilites) && isset($poste->responsabilites->responsabilite)): ?>
                            <ul>
                                <?php foreach ($poste->responsabilites->responsabilite as $resp): ?>
                                    <li><?php echo safe_html($resp); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </section>
                <?php endif; ?>

                <?php if (isset($xml_content->competences) && isset($xml_content->competences->categorie)): ?>
                <section class="cv-section">
                    <h2>Compétences</h2>
                    <?php foreach ($xml_content->competences->categorie as $categorie): ?>
                        <div class="competences-categorie">
                            <h4><?php echo safe_html($categorie['nom']); ?></h4>
                            <ul>
                                <?php foreach ($categorie->competence as $comp): ?>
                                    <li><?php echo safe_html($comp); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                </section>
                <?php endif; ?>

                <?php if (isset($xml_content->publicationsEtRecherches) && isset($xml_content->publicationsEtRecherches->item)): ?>
                <section class="cv-section">
                    <h2>Publications et Recherches</h2>
                     <ul>
                        <?php foreach ($xml_content->publicationsEtRecherches->item as $item): ?>
                            <li>
                                <strong><?php echo safe_html($item->titre); ?></strong> (<?php echo safe_html($item['type']); ?>)
                                <br /><em><?php echo safe_html($item->support); ?> - <?php echo safe_html($item->annee); ?></em>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
                <?php endif; ?>

                <?php if (isset($xml_content->referencesPro) && isset($xml_content->referencesPro->reference)): ?>
                <section class="cv-section">
                    <h2>Références</h2>
                     <?php foreach ($xml_content->referencesPro->reference as $ref): ?>
                        <p>
                        <?php if (isset($ref['surDemande']) && $ref['surDemande'] == 'true'): ?>
                            <?php echo safe_html($ref); ?>
                        <?php else: ?>
                            <!-- Structure pour une référence détaillée si besoin -->
                            <?php echo safe_html($ref); ?>
                        <?php endif; ?>
                        </p>
                    <?php endforeach; ?>
                </section>
                <?php endif; ?>

            </div>
        <?php endif; ?>
        <div class="cv-actions">
             <a href="javascript:history.back()" class="btn-cv-action">Retour</a>
        </div>
    </div>
</main>

<style>
.cv-main {
    padding: 2rem;
    background-color: #f9f9f9; /* Un fond légèrement différent pour la page CV */
    display: flex;
    justify-content: center;
    align-items: flex-start;
    min-height: calc(100vh - 160px); /* Ajuster si header/footer ont des hauteurs différentes */
}

.cv-container {
    max-width: 900px;
    width: 100%;
    margin: auto;
    background: #fff;
    padding: 2.5rem;
    border-radius: 10px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    color: #333;
}

.cv-container h1 {
    text-align: center;
    color: #0a7abf; /* Bleu Medicare */
    margin-bottom: 0.5rem;
    font-size: 2.4rem;
    font-weight: 700;
}
.cv-specialite {
    text-align: center;
    color: #555;
    font-size: 1.2rem;
    margin-bottom: 2.5rem;
    font-style: italic;
}

.cv-alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 5px;
    text-align: center;
    font-weight: 500;
}
.cv-alert.error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.cv-section {
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #eee;
}
.cv-section:last-of-type {
    border-bottom: none;
    margin-bottom: 0;
}

.cv-section h2 {
    color: #0056b3; /* Un bleu plus foncé pour les titres de section */
    font-size: 1.6rem;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e0f2ff; /* Soulignement léger */
}
.cv-section h3 { /* Pour les sous-titres comme les postes */
    color: #007bff;
    font-size: 1.3rem;
    margin-top: 1.5rem;
    margin-bottom: 0.5rem;
}
.cv-section h4 { /* Pour les catégories de compétences */
    color: #17a2b8; /* Un cyan pour varier */
    font-size: 1.1rem;
    margin-top: 1rem;
    margin-bottom: 0.5rem;
}

.cv-section p {
    line-height: 1.7;
    margin-bottom: 0.8rem;
    font-size: 1rem;
}
.cv-section ul {
    list-style-type: disc;
    margin-left: 25px;
    padding-left: 0;
}
.cv-section ul li {
    margin-bottom: 0.6rem;
    line-height: 1.6;
}

.experience-item {
    margin-bottom: 1.5rem;
    padding-left: 10px;
    border-left: 3px solid #b8daff; /* Bordure discrète pour les items d'expérience */
}
.experience-item .employeur-periode {
    font-style: italic;
    color: #555;
    margin-bottom: 0.5rem;
}
.competences-categorie {
    margin-bottom: 1rem;
}

.cv-actions {
    text-align: center;
    margin-top: 2.5rem;
}
.btn-cv-action {
    display: inline-block;
    padding: 10px 25px;
    background-color: #6c757d; /* Gris pour le bouton retour */
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-weight: 500;
    transition: background-color 0.2s ease;
}
.btn-cv-action:hover {
    background-color: #5a6268;
}

</style>

<?php require 'includes/footer.php'; ?>