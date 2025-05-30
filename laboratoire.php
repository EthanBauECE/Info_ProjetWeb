<?php /////////////////////////////////////////////// PHP //////////////////////////////////////////

    // ______________/ Initialisation Session et Statut Connexion \_____________________

    /// NOUVEAU UTILISATEUR (Vérification et démarrage session si besoin)
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    /// Vérification si l'utilisateur est connecté
    $user_is_logged_in = isset($_SESSION["user_id"]);


    // ______________/ Connexion Base de Données \_____________________
    $db_host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'base_donne_web';
    $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

    /// Vérification de la connexion MySQLi
    if (!$conn) {
        die("ERREUR : Impossible de se connecter à la base de données. " . mysqli_connect_error());
    }
    mysqli_set_charset($conn, 'utf8');


    // ______________/ Fonctions Utiles \_____________________

    /// Fonction pour sécuriser l'affichage HTML
    function safe_html($value) {
        return $value !== null ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : '';
    }


    // ______________/ Récupération des Laboratoires \_____________________
    $laboratoires = [];
    $sqlLabos = "
        SELECT 
            l.ID, l.Nom, l.Photos, l.Email, l.Telephone, l.Description AS LaboDescription,
            ad.Adresse AS adresse_ligne, ad.Ville AS adresse_ville, ad.CodePostal AS adresse_code_postal, ad.InfosComplementaires AS adresse_infos_comp
        FROM 
            laboratoire l
        LEFT JOIN 
            adresse ad ON l.ID_Adresse = ad.ID
        ORDER BY 
            l.Nom ASC
    ";

    $resultLabos = mysqli_query($conn, $sqlLabos);

    if ($resultLabos) {
        while ($row = mysqli_fetch_assoc($resultLabos)) {
            $laboratoires[] = $row;
        }
        mysqli_free_result($resultLabos);
    } else {
        die("ERREUR : Impossible de récupérer la liste des laboratoires. " . mysqli_error($conn));
    }

?>

<!DOCTYPE html> <!-- ////////////////////////////////////////// HTML ///////////////////////////////////////////-->
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Nos Laboratoires - Medicare</title>
    <link rel="icon" type="image/png" href="./images/medicare_logo.png" />
    <link rel="stylesheet" href="./css/style.css" />

    <style>
        /* Styles communs (similaires à ceux des pages médecins) */
        .main-content-page { /* Classe générique pour les pages de contenu */
            padding: 2rem;
            background-color: #f2f2f2;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2.5rem; /* Espace entre les blocs labo */
        }
        .main-content-page h1 {
            color: #333;
            margin-bottom: 2rem;
            font-size: 2.2rem;
            text-align: center;
        }

        .labo-card { /* Similaire à .doctor-card */
            background-color: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 900px;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .labo-header-section { /* Similaire à .doctor-header */
            display: flex;
            gap: 2rem;
            align-items: center; /* Centrer verticalement photo et infos */
        }
        .labo-photo-display { /* Similaire à .doctor-photo */
            width: 150px; /* Taille ajustée pour labo */
            height: 150px;
            object-fit: cover;
            border: 1px solid #e0e0e0;
            background-color: #f8f8f8;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #aaa;
            border-radius: 8px;
            flex-shrink: 0;
            font-size: 1.2rem;
        }
        .labo-photo-display img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
        }
        .labo-details-info { /* Similaire à .doctor-details */
            flex-grow: 1;
        }
        .labo-details-info .labo-name-title { /* Similaire à .specialite-title */
            font-size: 1.8rem; /* Plus grand pour le nom du labo */
            font-weight: 600;
            color: #0a7abf; /* Couleur principale */
            background-color: #eaf5ff;
            padding: 12px 18px;
            border-radius: 8px;
            margin: 0 0 1rem 0; 
            display: inline-block; /* Pour que le fond ne prenne pas toute la largeur */
        }
        .labo-contact-grid { /* Similaire à .info-grid */
            display: grid;
            grid-template-columns: 1fr; /* Une seule colonne pour les contacts labo */
            gap: 0.5rem; /* Espace réduit */
        }
        .labo-contact-grid p {
            font-size: 0.95rem;
            margin: 0.3rem 0;
            color: #454545;
        }
        .labo-contact-grid strong { font-weight: 500; color: #333; }
        .labo-contact-grid a { color: #007bff; text-decoration: none; }
        .labo-contact-grid a:hover { text-decoration: underline; }

        .labo-description-display { /* Style pour la description du labo */
            font-size: 0.95rem;
            line-height: 1.6;
            margin: 1rem 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-left: 4px solid #17a2b8; /* Couleur accent */
            border-radius: 0 6px 6px 0;
            color: #495057;
        }
        
        .labo-services-section-title { /* Titre pour la section des services */
            font-size: 1.3rem;
            color: #0a7abf;
            margin-top: 1.5rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #0a7abf;
            display: inline-block;
        }
        .labo-services-list { display: flex; flex-wrap: wrap; gap: 0.8rem; }
        .service-select-button { /* Boutons pour sélectionner un service */
            background-color: #f0f7ff; color: #0056b3; border: 1px solid #cce0ff;
            padding: 0.7rem 1.2rem; border-radius: 25px; text-decoration: none;
            text-align: center; font-weight: 500; transition: all 0.2s ease-in-out;
            cursor: pointer; font-size: 0.9rem;
        }
        .service-select-button:hover { background-color: #d1e7ff; border-color: #a8cfff; transform: translateY(-1px); box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .service-select-button.active-service { background-color: #0a7abf; color: white; border-color: #005c99; transform: translateY(0); box-shadow: inset 0 1px 3px rgba(0,0,0,0.1); }
        .service-price { font-size: 0.85em; color: #6c757d; margin-left: 6px; }
        .service-select-button:hover .service-price, .service-select-button.active-service .service-price { color: #e0f0ff; }
        .labo-bricks-list {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }


        /* STYLES CALENDRIER (identiques aux versions précédentes) */
        .lab-calendar-container { margin-top: 2rem; padding-top: 1.5rem; border-top: 2px dashed #d4eaff; display: none; }
        .lab-calendar-container h5 { font-size: 1.2rem; color: #0056b3; margin-bottom: 1.2rem; font-weight: 500; }
        .calendar-controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .calendar-controls button { background-color: #007bff; color: white; border: none; padding: 9px 16px; border-radius: 5px; cursor: pointer; font-size:0.9rem; transition: background-color 0.2s; }
        .calendar-controls button:hover { background-color: #0056b3; }
        .calendar-controls button:disabled { background-color: #ccc; cursor: not-allowed; }
        .week-display { font-weight: 600; font-size: 1.1rem; color: #0a7abf; }
        .availability-grid { width: 100%; border-collapse: collapse; text-align: center; table-layout: fixed; margin-top: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border-radius: 6px; overflow: hidden; /* Pour les coins arrondis du tableau */ }
        .availability-grid th { background-color: #0a7abf; color: white; padding: 12px 5px; font-weight: 500; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;}
        .availability-grid td { border: 1px solid #dee2e6; padding: 4px; /* Plus d'espace */ color: #495057; height: auto; vertical-align: top; background-color: #fff; }
        .availability-grid td:empty { border: 1px solid #f8f9fa; background-color: #f8f9fa; }
        .time-slot-button { display: block; width: 100%; padding: 9px 5px; margin-bottom: 3px; border: 1px solid #b8daff; border-radius: 4px; background-color: #e7f3ff; color: #004085; cursor: pointer; font-size: 0.85rem; font-weight: 500; box-sizing: border-box; line-height: 1.3; transition: all 0.2s; }
        .time-slot-button .slot-price { display: block; font-size: 0.75rem; font-weight: normal; color: #5a6268; margin-top: 3px;}
        .time-slot-button.selected { background-color: #28a745 !important; color: white !important; border-color: #1e7e34 !important; transform: scale(1.02); box-shadow: 0 3px 7px rgba(40,167,69,0.35); }
        .time-slot-button.selected .slot-price { color: #f0f8ff; }
        .time-slot-button:hover:not(.selected) { background-color: #cfe2ff; border-color: #9fceff; transform: translateY(-1px); }
        
        /* Styles pour les créneaux passés (NON CLICABLES) */
        .time-slot-button.past-slot {
            background-color: #e9ecef !important; /* Gris très clair */
            color: #6c757d !important; /* Texte gris foncé */
            border-color: #ced4da !important; /* Bordure grise */
            cursor: not-allowed !important; /* Curseur "interdit" */
            opacity: 0.7; /* Légèrement transparent */
            text-decoration: line-through; /* Optionnel: barre le texte pour plus de clarté */
            pointer-events: none; /* Empêche tout événement de souris (clic, survol) */
            box-shadow: none; /* Pas d'ombre portée */
        }
        /* Assurez-vous que le prix dans le créneau passé est aussi grisé */
        .time-slot-button.past-slot .slot-price {
            color: #888 !important; /* Une nuance de gris plus foncée pour le prix */
        }
        /* Ajustement pour les boutons désactivés en général, si non déjà fait */
        .time-slot-button:disabled { background-color: #ccc; cursor: not-allowed; border-color: #bbb; color: #666; opacity:0.7; }
        
        .lab-actions-container { display: flex; justify-content: center; gap: 1rem; padding-top: 1.5rem; margin-top: 1.5rem; border-top: 1px solid #e9ecef; }
        .btn-action.btn-rdv { background-image: linear-gradient(to right, #0062E6 0%, #33AEFF 100%); color: white; padding: 12px 30px; font-size: 1.05rem; font-weight: 600; border: none; border-radius: 25px; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); text-transform: uppercase; letter-spacing: 1px; }
        .btn-action.btn-rdv:disabled { background-image: none; background-color: #adb5bd; cursor: not-allowed; opacity: 0.6; box-shadow: none; }
        .btn-action.btn-rdv.active { background-image: linear-gradient(to right, #28a745 0%, #218838 100%); box-shadow: 0 4px 15px rgba(40, 167, 69, 0.2); }
        .btn-action.btn-rdv:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15); }

    </style>
</head>
<body>
    <!-- Importation header -->
    <?php require 'includes/header.php'; ?>

    <main class="main-content-page">
        <h1>Laboratoires</h1>
        <div class="labo-bricks-list">

            <?php if (empty($laboratoires)): ?>
                <p style="text-align: center; font-size: 1.1em; color: #6c757d;">Aucun laboratoire disponible pour le moment.</p>
            <?php else: ?>
                <?php foreach ($laboratoires as $labo): ?>
                    <?php $laboId = safe_html($labo['ID']); ?>
                    <div class="labo-card" id="labo-<?php echo $laboId; ?>">
                        <div class="labo-header-section"> <!-- ENTETE DU LABO (PHOTO + INFOS) -->
                            <div class="labo-photo-display">
                                <img src="<?php echo safe_html($labo['Photos'] ?: './images/default_labo.jpg'); ?>" alt="Photo de <?php echo safe_html($labo['Nom']); ?>">
                            </div>
                            <div class="labo-details-info">
                                <h3 class="labo-name-title"><?php echo safe_html($labo['Nom']); ?></h3>
                                <div class="labo-contact-grid">
                                    <?php if (!empty($labo['adresse_ligne'])): ?>
                                        <p><strong>Adresse :</strong> <?php echo safe_html($labo['adresse_ligne']); ?>, <?php echo safe_html($labo['adresse_code_postal']) . ' ' . safe_html($labo['adresse_ville']); ?>
                                            <?php if (!empty($labo['adresse_infos_comp'])): ?>
                                                <br><em style="font-size:0.9em; color: #6c757d;"><?php echo safe_html($labo['adresse_infos_comp']); ?></em>
                                            <?php endif; ?>
                                        </p>
                                    <?php endif; ?>
                                    <p><strong>Email :</strong> <a href="mailto:<?php echo safe_html($labo['Email']); ?>"><?php echo safe_html($labo['Email']); ?></a></p>
                                    <p><strong>Téléphone :</strong> <?php echo safe_html($labo['Telephone']); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <?php if(!empty($labo['LaboDescription'])): ?>
                            <div class="labo-description-display"><?php echo nl2br(safe_html($labo['LaboDescription'])); ?></div>
                        <?php endif; ?>
                        
                        <h4 class="labo-services-section-title">Services disponibles</h4>
                        <div class="labo-services-list" id="services-list-<?php echo $laboId; ?>">
                            <?php // ______________/ Récupération des Services par Laboratoire \_____________________
                                $sqlServices = "SELECT ID, NomService, Prix FROM service_labo WHERE ID_Laboratoire = ? ORDER BY NomService ASC";
                                $stmtServices = mysqli_prepare($conn, $sqlServices);

                                if ($stmtServices) {
                                    mysqli_stmt_bind_param($stmtServices, "i", $labo['ID']);
                                    mysqli_stmt_execute($stmtServices);
                                    $resultServices = mysqli_stmt_get_result($stmtServices);
                                    $services = [];
                                    while ($service_row = mysqli_fetch_assoc($resultServices)) {
                                        $services[] = $service_row;
                                    }
                                    mysqli_free_result($resultServices);
                                    mysqli_stmt_close($stmtServices);

                                    if (empty($services)) {
                                        echo "<p style='font-size:0.9em; color:#777; width:100%;'>Aucun service spécifique proposé par ce laboratoire.</p>";
                                    } else {
                                        foreach ($services as $service) {
                                            echo '<button type="button" class="service-select-button" data-labo-id="'.$laboId.'" data-service-id="'.safe_html($service['ID']).'" data-service-nom="'.safe_html($service['NomService']).'" data-service-prix="'.safe_html($service['Prix']).'">';
                                            echo safe_html($service['NomService']) . ' <span class="service-price">('.safe_html(number_format($service['Prix'], 2, ',', ' ')).' €)</span>';
                                            echo '</button>';
                                        }
                                    }
                                } else {
                                    echo "<p style='color:red;'>Erreur lors de la préparation de la requête des services: " . mysqli_error($conn) . "</p>";
                                }
                            ?>
                        </div>

                        <div class="lab-calendar-container" id="lab-calendar-container-<?php echo $laboId; ?>"> <!-- CALENDRIER (initiallement caché) -->
                            <h5>Disponibilités pour : <strong class="selected-service-name" style="color:#007bff;"></strong></h5>
                            <div class="calendar-controls">
                                <button class="prev-week" data-labo-id="<?php echo $laboId; ?>">< Sem. Prec.</button>
                                <span class="week-display" id="week-display-lab-<?php echo $laboId; ?>"></span>
                                <button class="next-week" data-labo-id="<?php echo $laboId; ?>">Sem. Suiv. ></button>
                            </div>
                            <table class="availability-grid" id="calendar-lab-<?php echo $laboId; ?>">
                                <thead><tr>
                                    <th style="width: 15%;">Heure</th> <th style="width: 12.14%;">Lun</th> <th style="width: 12.14%;">Mar</th> <th style="width: 12.14%;">Mer</th>
                                    <th style="width: 12.14%;">Jeu</th> <th style="width: 12.14%;">Ven</th> <th style="width: 12.14%;">Sam</th> <th style="width: 12.14%;">Dim</th>
                                </tr></thead>
                                <tbody></tbody>
                            </table>
                            <form action="confirmation_paiement.php" method="POST" class="lab-rdv-form" id="form-lab-<?php echo $laboId; ?>">
                                <input type="hidden" name="type_rdv" value="laboratoire"> <!-- Important pour la page de paiement -->
                                <input type="hidden" name="labo_id" value="<?php echo $laboId; ?>">
                                <input type="hidden" name="labo_nom" value="<?php echo safe_html($labo['Nom']); ?>">
                                <input type="hidden" name="selected_service_id" id="selected-service-id-<?php echo $laboId; ?>">
                                <input type="hidden" name="selected_service_nom" id="selected-service-nom-<?php echo $laboId; ?>">
                                <input type="hidden" name="selected_date_db" id="selected-date-db-lab-<?php echo $laboId; ?>">
                                <input type="hidden" name="selected_heure_debut_db" id="selected-heure-debut-db-lab-<?php echo $laboId; ?>">
                                <input type="hidden" name="selected_heure_fin_db" id="selected-heure-fin-db-lab-<?php echo $laboId; ?>">
                                <input type="hidden" name="selected_prix" id="selected-prix-lab-<?php echo $laboId; ?>">
                                <div class="lab-actions-container"> <!-- BOUTONS D'ACTION (Prendre RDV / Se connecter) -->
                                    <?php if ($user_is_logged_in): ?>
                                        <button type="submit" class="btn-action btn-rdv" id="btn-rdv-lab-<?php echo $laboId; ?>" disabled>Choisir un créneau</button>
                                    <?php else: ?>
                                        <a href="login.php?redirect=laboratoire.php" class="btn-action btn-rdv">Se connecter pour prendre RDV</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Importation footer -->
    <?php require 'includes/footer.php'; ?>

<?php // ______________/ Fermeture connexion BDD \_____________________
    if ($conn) {
        mysqli_close($conn);
    }
?>

<script>
// ______________/ GESTION DYNAMIQUE DU CALENDRIER ET DES SERVICES (JavaScript) \_____________________
document.addEventListener('DOMContentLoaded', function() {
    // ______________/ Fonctions Utilitaires JavaScript \_____________________

    /// Calcule les dates de la semaine pour une date donnée (Lundi à Dimanche)
    function getWeekDates(date) {
        const startOfWeek = new Date(date);
        const dayOfWeek = startOfWeek.getDay();
        const diff = startOfWeek.getDate() - dayOfWeek + (dayOfWeek === 0 ? -6 : 1); // Lundi = 1, Dimanche = 0
        startOfWeek.setDate(diff);
        startOfWeek.setHours(0, 0, 0, 0);
        const week = [];
        for (let i = 0; i < 7; i++) {
            const day = new Date(startOfWeek);
            day.setDate(startOfWeek.getDate() + i);
            week.push(day);
        }
        return week;
    }

    /// Formate une date en YYYY-MM-DD
    function formatDateToYYYYMMDD(date) {
        const d = new Date(date);
        let month = '' + (d.getMonth() + 1);
        let day = '' + d.getDate();
        const year = d.getFullYear();
        if (month.length < 2) month = '0' + month;
        if (day.length < 2) day = '0' + day;
        return [year, month, day].join('-');
    }

    /// Formate une heure (HH:MM:SS) en HH:MM
    function formatTimeHHMM(timeStr) { return timeStr.substring(0, 5); }


    // ______________/ Variables Globales pour le Calendrier \_____________________
    let currentLabSelectedSlots = {};       // Stocke le créneau actuellement sélectionné pour chaque labo
    let currentLabCalendarDate = {};      // Stocke la date de référence (premier jour de la semaine affichée) pour chaque labo
    let currentLabSelectedService = {};   // Stocke le service actuellement sélectionné pour chaque labo


    // ______________/ Rendu du Calendrier \_____________________

    /// Affiche le calendrier pour un laboratoire, un service et une semaine donnés
    function renderLabCalendar(laboId, serviceId, serviceNom, servicePrixBase, weekDates, availabilities) {
        const calendarContainer = document.getElementById(`lab-calendar-container-${laboId}`);
        const calendarBody = calendarContainer.querySelector(`#calendar-lab-${laboId} tbody`);
        const weekDisplay = calendarContainer.querySelector(`#week-display-lab-${laboId}`);
        const btnRdv = calendarContainer.querySelector(`#btn-rdv-lab-${laboId}`);
        const serviceNameDisplay = calendarContainer.querySelector('.selected-service-name');
        
        calendarBody.innerHTML = ''; 
        calendarContainer.style.display = 'block';
        serviceNameDisplay.textContent = serviceNom; 
        weekDisplay.textContent = `${formatDateToYYYYMMDD(weekDates[0]).substring(8,10)}/${formatDateToYYYYMMDD(weekDates[0]).substring(5,7)} au ${formatDateToYYYYMMDD(weekDates[6]).substring(8,10)}/${formatDateToYYYYMMDD(weekDates[6]).substring(5,7)}`;

        const slotsByTime = {}; // Organise les disponibilités par heure de début
        if (Array.isArray(availabilities)) {
            availabilities.forEach(dispo => { 
                const heureDebut = formatTimeHHMM(dispo.HeureDebut);
                if (!slotsByTime[heureDebut]) slotsByTime[heureDebut] = Array(7).fill(null); // Initialise pour les 7 jours
                const dispoDate = new Date(dispo.Date + 'T00:00:00'); // Assure la comparaison correcte des dates
                const dayIndex = weekDates.findIndex(weekDate => weekDate.getTime() === dispoDate.getTime());
                if (dayIndex !== -1) { // Si la date de dispo est dans la semaine actuelle
                    if (!slotsByTime[heureDebut][dayIndex]) slotsByTime[heureDebut][dayIndex] = [];
                    slotsByTime[heureDebut][dayIndex].push({
                        heureDebut: dispo.HeureDebut, heureFin: dispo.HeureFin,
                        prix: dispo.Prix, idServiceLabo: dispo.IdServiceLabo, date: dispo.Date,
                        status: dispo.status // Statut du créneau (past, available)
                    });
                }
            });
        }

        const sortedTimes = Object.keys(slotsByTime).sort(); // Trie les heures
        if (sortedTimes.length === 0) {
            calendarBody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:15px; font-style:italic; color:#6c757d;">Aucun créneau disponible pour ce service cette semaine.</td></tr>`;
        } else {
            sortedTimes.forEach(heureDebutAffichage => { 
                const tr = calendarBody.insertRow();
                const th = document.createElement('th');
                th.textContent = heureDebutAffichage; // Affiche l'heure de début dans la première colonne
                tr.appendChild(th);
                slotsByTime[heureDebutAffichage].forEach(daySlots => { // Pour chaque jour de la semaine
                    const td = tr.insertCell();
                    if (daySlots && daySlots.length > 0) { // Si des créneaux existent pour cette heure et ce jour
                        const slotData = daySlots[0]; // On prend le premier (normalement un seul par heure/jour pour un service)
                        const slotButton = document.createElement('button');
                        slotButton.classList.add('time-slot-button');

                        if (slotData.status === 'past') { /// CRENEAU PASSE
                            slotButton.classList.add('past-slot');
                            slotButton.disabled = true;
                            slotButton.innerHTML = `Passé<span class="slot-price">${parseFloat(slotData.prix).toFixed(2)} €</span>`;
                        } else { /// CRENEAU DISPONIBLE
                            slotButton.innerHTML = `${formatTimeHHMM(slotData.heureDebut)}<span class="slot-price">(${parseFloat(slotData.prix).toFixed(2)} €)</span>`;
                            slotButton.dataset.dateDb = slotData.date;
                            slotButton.dataset.heureDebutDb = slotData.heureDebut;
                            slotButton.dataset.heureFinDb = slotData.heureFin;
                            slotButton.dataset.prix = slotData.prix; 
                            slotButton.dataset.idServiceLabo = slotData.idServiceLabo;
                            slotButton.onclick = function() { selectLabSlot(laboId, this); };
                        }
                        td.appendChild(slotButton);
                    } else { td.innerHTML = ' '; } // Case vide si pas de créneau
                });
            });
        }
        
        if (btnRdv) { // Réinitialise le bouton RDV
            btnRdv.disabled = true;
            btnRdv.textContent = 'Choisir un créneau';
            btnRdv.classList.remove('active');
        }
        // Met à jour les champs cachés du formulaire
        const form = document.getElementById(`form-lab-${laboId}`);
        form.querySelector(`#selected-service-id-${laboId}`).value = serviceId;
        form.querySelector(`#selected-service-nom-${laboId}`).value = serviceNom;
        form.querySelector(`#selected-prix-lab-${laboId}`).value = ''; 
        form.querySelector(`#selected-date-db-lab-${laboId}`).value = '';
        form.querySelector(`#selected-heure-debut-db-lab-${laboId}`).value = '';
        form.querySelector(`#selected-heure-fin-db-lab-${laboId}`).value = ''; 
    }


    // ______________/ Récupération des Disponibilités (AJAX) \_____________________

    /// Récupère les disponibilités pour un service et une période via AJAX, puis appelle renderLabCalendar
    function fetchLabAvailabilitiesAndRender(laboId, serviceId, serviceNom, servicePrixBase, dateRef) {
        if (!currentLabCalendarDate[laboId]) {
            currentLabCalendarDate[laboId] = new Date(dateRef);
        }
        const weekDates = getWeekDates(currentLabCalendarDate[laboId]);
        const startDateStr = formatDateToYYYYMMDD(weekDates[0]);
        const endDateStr = formatDateToYYYYMMDD(weekDates[6]);
        currentLabSelectedService[laboId] = { id: serviceId, nom: serviceNom, prixBase: servicePrixBase };

        const calendarContainer = document.getElementById(`lab-calendar-container-${laboId}`);
        const serviceNameDisp = calendarContainer.querySelector('.selected-service-name');
        const calendarBody = calendarContainer.querySelector(`#calendar-lab-${laboId} tbody`);
        const weekDisplay = calendarContainer.querySelector(`#week-display-lab-${laboId}`);

        serviceNameDisp.textContent = serviceNom; 
        weekDisplay.textContent = "Chargement..."; 
        calendarBody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:15px; font-style:italic; color:#6c757d;">Chargement des disponibilités...</td></tr>`;
        calendarContainer.style.display = 'block'; // Affiche le conteneur du calendrier

        fetch(`get_disponibilites_labo.php?service_id=${serviceId}&start_date=${startDateStr}&end_date=${endDateStr}`)
            .then(response => {
                if (!response.ok) { // Gère les erreurs HTTP
                    return response.text().then(text => { 
                        throw new Error(`Erreur réseau: ${response.status} - ${response.statusText}. Réponse: ${text}`);
                    });
                }
                return response.json(); // Tente de parser la réponse en JSON
            })
            .then(data => {
                if (data.error) { // Gère les erreurs applicatives renvoyées en JSON
                    throw new Error(data.error); 
                }
                // S'assure que les données sont un tableau, même si PHP renvoie un objet avec une propriété 'data'
                const availabilitiesData = Array.isArray(data) ? data : (data.data || []);
                renderLabCalendar(laboId, serviceId, serviceNom, servicePrixBase, weekDates, availabilitiesData); 
            })
            .catch(error => { // Gère les erreurs (réseau, JSON parse, applicatives)
                console.error(`Erreur chargement dispos labo ${laboId}, service ${serviceId}:`, error);
                serviceNameDisp.textContent = serviceNom; // Maintient le nom du service affiché
                // Affiche quand même les dates de la semaine
                weekDisplay.textContent = `${formatDateToYYYYMMDD(weekDates[0]).substring(8,10)}/${formatDateToYYYYMMDD(weekDates[0]).substring(5,7)} au ${formatDateToYYYYMMDD(weekDates[6]).substring(8,10)}/${formatDateToYYYYMMDD(weekDates[6]).substring(5,7)}`;
                calendarBody.innerHTML = `<tr><td colspan="8" style="color:red;text-align:center;padding:10px;">Erreur : ${error.message}</td></tr>`;
                calendarContainer.style.display = 'block';
            });
    }


    // ______________/ Sélection d'un Créneau \_____________________

    /// Gère la sélection d'un créneau horaire
    function selectLabSlot(laboId, slotButtonElement) { 
        if (currentLabSelectedSlots[laboId]) { // Désélectionne le précédent s'il y en a un
            currentLabSelectedSlots[laboId].classList.remove('selected');
        }
        slotButtonElement.classList.add('selected'); // Sélectionne le nouveau
        currentLabSelectedSlots[laboId] = slotButtonElement;

        // Met à jour les champs cachés du formulaire avec les données du créneau
        const form = document.getElementById(`form-lab-${laboId}`);
        form.querySelector(`#selected-date-db-lab-${laboId}`).value = slotButtonElement.dataset.dateDb;
        form.querySelector(`#selected-heure-debut-db-lab-${laboId}`).value = slotButtonElement.dataset.heureDebutDb;
        form.querySelector(`#selected-heure-fin-db-lab-${laboId}`).value = slotButtonElement.dataset.heureFinDb; 
        form.querySelector(`#selected-prix-lab-${laboId}`).value = slotButtonElement.dataset.prix; 
        
        const btnRdv = form.querySelector(`#btn-rdv-lab-${laboId}`);
        if (btnRdv) { // Active le bouton RDV
            btnRdv.disabled = false;
            btnRdv.textContent = 'Valider le créneau';
            btnRdv.classList.add('active');
        }
    }


    // ______________/ Gestion des Événements \_____________________

    /// Écouteurs pour les boutons de sélection de service
    document.querySelectorAll('.service-select-button').forEach(button => {
        button.addEventListener('click', function() {
            const laboId = this.dataset.laboId;
            const serviceId = this.dataset.serviceId;
            const serviceNom = this.dataset.serviceNom;
            const servicePrix = this.dataset.servicePrix; 

            // Gère le style 'active' pour les boutons de service
            document.querySelectorAll(`#services-list-${laboId} .service-select-button.active-service`).forEach(activeBtn => {
                activeBtn.classList.remove('active-service');
            });
            this.classList.add('active-service');

            currentLabCalendarDate[laboId] = new Date(); // Réinitialise à la semaine actuelle
            fetchLabAvailabilitiesAndRender(laboId, serviceId, serviceNom, servicePrix, currentLabCalendarDate[laboId]);
        });
    });

    /// Écouteurs pour les boutons de navigation du calendrier (semaine précédente/suivante)
    document.querySelectorAll('.lab-calendar-container').forEach(container => { 
        const laboId = container.id.split('-')[3]; // Extrait l'ID du labo depuis l'ID du conteneur

        container.querySelector('.prev-week').addEventListener('click', function() {
            if (currentLabCalendarDate[laboId] && currentLabSelectedService[laboId]) {
                currentLabCalendarDate[laboId].setDate(currentLabCalendarDate[laboId].getDate() - 7); // Semaine précédente
                const service = currentLabSelectedService[laboId];
                fetchLabAvailabilitiesAndRender(laboId, service.id, service.nom, service.prixBase, currentLabCalendarDate[laboId]);
            }
        });

        container.querySelector('.next-week').addEventListener('click', function() {
             if (currentLabCalendarDate[laboId] && currentLabSelectedService[laboId]) {
                currentLabCalendarDate[laboId].setDate(currentLabCalendarDate[laboId].getDate() + 7); // Semaine suivante
                const service = currentLabSelectedService[laboId];
                fetchLabAvailabilitiesAndRender(laboId, service.id, service.nom, service.prixBase, currentLabCalendarDate[laboId]);
            }
        });
    });
});
</script>
</body>
</html>