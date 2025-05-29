<?php
// On place les includes qui pourraient démarrer une session AVANT tout code HTML.
require 'includes/head.php';
require 'includes/header.php';

$user_is_logged_in = isset($_SESSION["user_id"]);

function safe_html($value) {
    return $value !== null ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : '';
}

$conn = new mysqli("localhost", "root", "", "base_donne_web");
if ($conn->connect_error) {
    die("Erreur de connexion: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// 1. Récupérer la liste unique des spécialités pour le filtre
$sqlSpecialitesUniques = "SELECT DISTINCT p.Type 
                          FROM utilisateurs_personnel p 
                          WHERE p.Type IS NOT NULL AND LOWER(p.Type) != 'generaliste' 
                          ORDER BY p.Type ASC";
$resultSpecialitesUniques = $conn->query($sqlSpecialitesUniques);
$specialites_disponibles = [];
if ($resultSpecialitesUniques && $resultSpecialitesUniques->num_rows > 0) {
    while ($row = $resultSpecialitesUniques->fetch_assoc()) {
        $specialites_disponibles[] = $row['Type'];
    }
}

// 2. Récupérer tous les médecins spécialistes
$sqlMedecins = "SELECT u.ID, u.Nom, u.Prenom, u.Email,
                       p.Photo, p.Telephone, p.Type AS Specialite,
                       a.Adresse, a.Ville, a.CodePostal
                FROM utilisateurs_personnel p
                LEFT JOIN utilisateurs u ON p.ID = u.ID
                LEFT JOIN adresse a ON p.ID_Adresse = a.ID
                WHERE p.Type IS NOT NULL AND LOWER(p.Type) != 'generaliste'";

$resultMedecins = $conn->query($sqlMedecins);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php // Le head.php est déjà inclus plus haut ?>
    <style>
        /* ========================================================= */
        /* STYLES SPÉCIFIQUES POUR LA PAGE DES MÉDECINS (v4 de ton fichier original)      */
        /* ========================================================= */

        .main-specialistes { /* J'utilise le nom de classe original .main-specialistes */
            padding: 2rem;
            background-color: #f2f2f2;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2rem;
        }
        
        .main-specialistes h1 {
            color: #333;
            margin-bottom: 1rem; /* Un peu moins pour le filtre */
        }

        /* NOUVEAUX STYLES POUR LE FILTRE DE SPÉCIALITÉS (conçus pour être moins invasifs) */
        .specialty-filter-section {
            width: 100%;
            max-width: 900px; /* Cohérent avec doctor-card */
            margin-bottom: 2rem; /* Espace avant la liste des médecins */
            background-color: #fff;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
        }
        .specialty-filter-section h3 {
            margin-top: 0;
            margin-bottom: 1rem;
            color: #0a7abf;
            font-size: 1.2rem;
            text-align: center;
        }
        .specialty-options-grid { /* Utilisation d'une grille pour les options */
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); /* Colonnes responsives */
            gap: 0.75rem;
        }
        .specialty-options-grid label {
            display: flex;
            align-items: center;
            cursor: pointer;
            font-size: 0.9rem;
            color: #333;
            background-color: #f8f9fa;
            padding: 8px 12px;
            border-radius: 5px; /* Moins arrondi que "pilule" */
            border: 1px solid #dee2e6;
            transition: background-color 0.2s, border-color 0.2s;
            box-sizing: border-box;
        }
        .specialty-options-grid input[type="checkbox"] {
            margin-right: 0.5rem;
            accent-color: #0a7abf;
            width: 15px;
            height: 15px;
        }
        .specialty-options-grid label:hover {
            background-color: #e9ecef;
            border-color: #adb5bd;
        }
        .specialty-options-grid label.checked-label {
            background-color: #d1e7ff; /* Bleu plus clair pour sélection */
            color: #004085; /* Texte plus foncé */
            border-color: #b8daff;
            font-weight: 500;
        }

        /* STYLES ORIGINAUX POUR DOCTOR-CARD et ses enfants */
        .doctor-card {
            background-color: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 900px;
            padding: 1.5rem;
            display: flex; /* Affiché par défaut */
            flex-direction: column;
            gap: 1.5rem;
        }
        .doctor-card.hidden-by-filter {
            display: none !important; 
        }

        .doctor-header {
            display: flex;
            gap: 2rem;
            align-items: center; 
        }
        .doctor-photo {
            width: 170px;
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
        .doctor-details .specialite-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
            background-color: #eaf5ff;
            padding: 12px;
            border-radius: 6px;
            margin: 0; /* Reset marge du h3 ici */
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem; 
            padding-top: 1rem; 
        }
        .info-cell { font-size: 1.1rem; }
        .full-width { grid-column: 1 / -1; }
        .info-cell strong { font-weight: 500; color: #333; }
        
        /* STYLES CALENDRIER (inchangés par rapport à la version précédente) */
        .calendar-container { margin-top: 1rem; } /* Pour espacer du bloc info */
        .calendar-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .calendar-controls button {
            background-color: #0a7abf;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
        }
        .calendar-controls button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        .week-display {
            font-weight: bold;
            color: #0a7abf;
        }
        .availability-grid {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
            table-layout: fixed; 
            /* margin-top: 1rem; Déjà géré par .calendar-container */
        }
        .availability-grid th {
            background-color: #4a6fa5;
            color: white;
            padding: 10px 5px; /* Padding réduit */
            font-weight: 500;
            font-size: 0.85rem; /* Taille police réduite */
        }
        .availability-grid td {
            border: 1px solid #e0e0e0;
            padding: 2px; /* Léger padding pour espacer les boutons */
            color: #555;
            height: auto; /* Hauteur auto pour s'adapter au contenu */
            vertical-align: top; /* Aligner les boutons en haut */
        }
        .availability-grid td:empty { /* Cache les cellules vides si aucun créneau à cette heure ce jour-là */
            border: 1px solid transparent; /* Rend la bordure invisible mais garde l'espace */
            background-color: #f9f9f9; /* Un fond discret */
        }
        .time-slot-button {
            display: block;
            width: 100%;
            padding: 6px 4px;
            margin-bottom: 2px; /* Espace entre boutons si plusieurs dans une cellule (ne devrait pas arriver ici) */
            border: 1px solid #bde0fe;
            border-radius: 4px;
            background-color: #e6ffed; /* Vert clair pour dispo */
            color: #155724; /* Vert foncé pour le texte */
            cursor: pointer;
            font-size: 0.75rem;
            font-weight: bold;
            box-sizing: border-box;
            line-height: 1.2;
        }
        .time-slot-button .slot-price {
            display: block;
            font-size: 0.65rem;
            font-weight: normal;
            color: #555;
        }
        .time-slot-button.selected {
            background-color: #28a745 !important; /* Vert pour sélectionné */
            color: white !important;
            border-color: #1c7430;
        }
        .time-slot-button.selected .slot-price {
            color: #f0f0f0;
        }
        .time-slot-button:hover:not(.selected) {
            background-color: #d4f8e0;
            border-color: #a3e9b9;
        }
        .time-slot-button:disabled { 
             background-color: #ccc;
             cursor: not-allowed;
             border-color: #bbb;
             color: #666;
        }
        
        /* ACTIONS CONTAINER (inchangés) */
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
        .btn-rdv.active { background-color: #28a745; }
        .btn-rdv:disabled {
            background-color: #ccc;
            cursor: not-allowed;
            opacity: 0.7;
        }
        .btn-communiquer { background-color: #5dade2; }
        .btn-cv { background-color: #4a6fa5; }
    </style>
</head>
<body>

<main class="main-specialistes"> <!-- Classe principale originale -->
    <h1>Nos Médecins Spécialistes</h1>

    <!-- SECTION DU FILTRE -->
    <?php if (!empty($specialites_disponibles)): ?>
    <section class="specialty-filter-section"> <!-- Utilisation de <section> pour le bloc filtre -->
        <h3>Filtrer par spécialité :</h3>
        <div class="specialty-options-grid" id="specialty-filter-options-container">
            <?php foreach ($specialites_disponibles as $spec): ?>
                <label>
                    <input type="checkbox" name="specialite_filtre[]" value="<?php echo safe_html($spec); ?>" class="specialty-checkbox-input">
                    <span><?php echo safe_html($spec); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
    <!-- FIN SECTION DU FILTRE -->

    <!-- Les cartes de médecins sont générées ici, sans conteneur supplémentaire pour ne pas casser le layout -->
    <?php
    if ($resultMedecins && $resultMedecins->num_rows > 0) {
        while ($medecin = $resultMedecins->fetch_assoc()) {
            $idMedecin = safe_html($medecin['ID']);
            $specialite = safe_html($medecin['Specialite']);
            $adresse_complete = safe_html($medecin['Adresse']) . ', ' . safe_html($medecin['CodePostal']) . ' ' . safe_html($medecin['Ville']);
            ?>
            <div class="doctor-card" id="doctor-<?php echo $idMedecin; ?>" data-specialite="<?php echo $specialite; ?>">
                <div class="doctor-header">
                    <div class="doctor-photo">
                        <?php if (!empty($medecin['Photo'])): ?>
                            <img src="<?php echo safe_html($medecin['Photo']); ?>" alt="Photo de <?php echo safe_html($medecin['Prenom']); ?>">
                        <?php else: ?>
                            <span>Photo</span>
                        <?php endif; ?>
                    </div>
                    <div class="doctor-details">
                        <h3 class="specialite-title">Spécialiste - <?php echo $specialite; ?></h3>
                        <div class="info-grid">
                            <div class="info-cell"><strong>Nom :</strong> <?php echo safe_html($medecin['Nom']); ?></div>
                            <div class="info-cell"><strong>Prénom :</strong> <?php echo safe_html($medecin['Prenom']); ?></div>
                            <div class="info-cell full-width"><strong>Adresse :</strong> <?php echo $adresse_complete; ?></div>
                            <div class="info-cell full-width"><strong>Email :</strong> <?php echo safe_html($medecin['Email']); ?></div>
                            <div class="info-cell full-width"><strong>Téléphone :</strong> <?php echo safe_html($medecin['Telephone']); ?></div>
                        </div>
                    </div>
                </div>

                <div class="calendar-container"> <!-- Conteneur pour le calendrier et ses contrôles -->
                    <div class="calendar-controls">
                        <button class="prev-week" data-medecin-id="<?php echo $idMedecin; ?>">< Sem. Prec.</button>
                        <span class="week-display" id="week-display-<?php echo $idMedecin; ?>">Chargement...</span>
                        <button class="next-week" data-medecin-id="<?php echo $idMedecin; ?>">Sem. Suiv. ></button>
                    </div>
                    <table class="availability-grid" id="calendar-<?php echo $idMedecin; ?>">
                        <thead>
                            <tr>
                                <th style="width: 15%;">Heure</th>
                                <th style="width: 12.14%;">Lun</th><th style="width: 12.14%;">Mar</th><th style="width: 12.14%;">Mer</th>
                                <th style="width: 12.14%;">Jeu</th><th style="width: 12.14%;">Ven</th><th style="width: 12.14%;">Sam</th>
                                <th style="width: 12.14%;">Dim</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <form action="confirmation_paiement.php" method="POST" class="rdv-form" id="form-<?php echo $idMedecin; ?>">
                    <input type="hidden" name="medecin_id" value="<?php echo $idMedecin; ?>">
                    <input type="hidden" name="medecin_nom" value="<?php echo safe_html($medecin['Nom'] . ' ' . $medecin['Prenom']); ?>">
                    <input type="hidden" name="medecin_specialite" value="<?php echo $specialite; ?>">
                    <input type="hidden" name="selected_date_db" id="selected-date-db-<?php echo $idMedecin; ?>">
                    <input type="hidden" name="selected_heure_debut_db" id="selected-heure-debut-db-<?php echo $idMedecin; ?>">
                    <input type="hidden" name="selected_heure_fin_db" id="selected-heure-fin-db-<?php echo $idMedecin; ?>">
                    <input type="hidden" name="selected_prix" id="selected-prix-<?php echo $idMedecin; ?>">
                    <input type="hidden" name="id_service_labo" id="id-service-labo-<?php echo $idMedecin; ?>">
                    
                    <div class="actions-container">
                        <?php if ($user_is_logged_in): ?>
                            <button type="submit" class="btn-action btn-rdv" id="btn-rdv-<?php echo $idMedecin; ?>" disabled>Choisir un créneau</button>
                        <?php else: ?>
                            <a href="login.php?redirect=medecins_special.php" class="btn-action btn-rdv" style="background-color:#007bff;">Se connecter pour prendre RDV</a>
                        <?php endif; ?>
                        <a href="communiquer.php?id=<?php echo $idMedecin; ?>" class="btn-action btn-communiquer">Communiquer</a>
                        <a href="cv_medecin.php?id=<?php echo $idMedecin; ?>" class="btn-action btn-cv">Voir CV</a>
                    </div>
                </form>
            </div>
            <?php
        }
    } else {
        echo "<p>Aucun médecin spécialiste trouvé.</p>";
    }
    $conn->close();
    ?>
</main>

<?php require 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- GESTION DU FILTRE DE SPÉCIALITÉS ---
    const filterCheckboxesContainer = document.getElementById('specialty-filter-options-container');
    // Cibler les cartes directement sous .main-specialistes, car on a enlevé le conteneur intermédiaire.
    // Ou, si tu veux être plus précis, les cartes qui ne sont pas le bloc de filtre lui-même.
    const allDoctorCards = Array.from(document.querySelectorAll('.main-specialistes > .doctor-card'));


    function applySpecialtyFilter() {
        if (!filterCheckboxesContainer) return; // Si pas de filtre, ne rien faire

        const selectedSpecialties = Array.from(filterCheckboxesContainer.querySelectorAll('.specialty-checkbox-input:checked'))
            .map(cb => cb.value);

        // Mettre à jour le style des labels
        filterCheckboxesContainer.querySelectorAll('label').forEach(label => {
            const checkbox = label.querySelector('.specialty-checkbox-input');
            if (checkbox && checkbox.checked) {
                label.classList.add('checked-label');
            } else {
                label.classList.remove('checked-label');
            }
        });
        
        allDoctorCards.forEach(card => {
            const cardSpecialty = card.dataset.specialite;
            if (selectedSpecialties.length === 0 || selectedSpecialties.includes(cardSpecialty)) {
                card.classList.remove('hidden-by-filter');
            } else {
                card.classList.add('hidden-by-filter');
            }
        });
    }

    if (filterCheckboxesContainer) {
        filterCheckboxesContainer.querySelectorAll('.specialty-checkbox-input').forEach(checkbox => {
            checkbox.addEventListener('change', applySpecialtyFilter);
        });
        applySpecialtyFilter(); // Appliquer au chargement
    }


    // --- GESTION DU CALENDRIER (Code inchangé par rapport à la version précédente) ---
    const daysOfWeekHeaders = ["Dim", "Lun", "Mar", "Mer", "Jeu", "Ven", "Sam"];

    function getWeekDates(date) {
        const startOfWeek = new Date(date);
        const dayOfWeek = startOfWeek.getDay();
        const diff = startOfWeek.getDate() - dayOfWeek + (dayOfWeek === 0 ? -6 : 1);
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

    function formatDateToYYYYMMDD(date) {
        const d = new Date(date);
        let month = '' + (d.getMonth() + 1);
        let day = '' + d.getDate();
        const year = d.getFullYear();
        if (month.length < 2) month = '0' + month;
        if (day.length < 2) day = '0' + day;
        return [year, month, day].join('-');
    }
    
    function formatTimeHHMM(timeStr) {
        return timeStr.substring(0, 5);
    }

    let currentSelectedSlots = {};

    function renderCalendar(medecinId, weekDates, availabilities) {
        const calendarBody = document.querySelector(`#calendar-${medecinId} tbody`);
        const weekDisplay = document.getElementById(`week-display-${medecinId}`);
        const btnRdv = document.getElementById(`btn-rdv-${medecinId}`);
        
        if (!calendarBody) return;
        calendarBody.innerHTML = ''; 

        weekDisplay.textContent = `${formatDateToYYYYMMDD(weekDates[0]).substring(5)} au ${formatDateToYYYYMMDD(weekDates[6]).substring(5)}`;

        const slotsByTime = {};
        availabilities.forEach(dispo => {
            const heureDebut = formatTimeHHMM(dispo.HeureDebut);
            if (!slotsByTime[heureDebut]) {
                slotsByTime[heureDebut] = Array(7).fill(null);
            }
            const dispoDate = new Date(dispo.Date + 'T00:00:00');
            const dayIndex = weekDates.findIndex(weekDate => weekDate.getTime() === dispoDate.getTime());

            if (dayIndex !== -1) {
                 if (!slotsByTime[heureDebut][dayIndex]) {
                    slotsByTime[heureDebut][dayIndex] = [];
                }
                slotsByTime[heureDebut][dayIndex].push({
                    heureDebut: dispo.HeureDebut,
                    heureFin: dispo.HeureFin,
                    prix: dispo.Prix,
                    idServiceLabo: dispo.IdServiceLabo,
                    date: dispo.Date
                });
            }
        });

        const sortedTimes = Object.keys(slotsByTime).sort();

        if (sortedTimes.length === 0) {
            const tr = calendarBody.insertRow();
            const td = tr.insertCell();
            td.colSpan = 8;
            td.textContent = "Aucun créneau disponible pour cette semaine.";
            td.style.textAlign = "center";
            td.style.padding = "10px";
        } else {
            sortedTimes.forEach(heureDebutAffichage => {
                const tr = calendarBody.insertRow();
                const th = document.createElement('th');
                th.textContent = heureDebutAffichage;
                tr.appendChild(th);

                slotsByTime[heureDebutAffichage].forEach((daySlots, dayIndex) => {
                    const td = tr.insertCell();
                    if (daySlots && daySlots.length > 0) {
                        const slotData = daySlots[0]; 
                        const slotButton = document.createElement('button');
                        slotButton.classList.add('time-slot-button');
                        slotButton.innerHTML = `${formatTimeHHMM(slotData.heureDebut)}<span class="slot-price">${parseFloat(slotData.prix).toFixed(2)} €</span>`;
                        
                        slotButton.dataset.dateDb = slotData.date;
                        slotButton.dataset.heureDebutDb = slotData.heureDebut;
                        slotButton.dataset.heureFinDb = slotData.heureFin;
                        slotButton.dataset.prix = slotData.prix;
                        slotButton.dataset.idServiceLabo = slotData.idServiceLabo;

                        slotButton.onclick = function() {
                            selectSlot(medecinId, this);
                        };
                        td.appendChild(slotButton);
                    } else {
                        td.innerHTML = ' ';
                    }
                });
            });
        }
        
        if (btnRdv) {
            btnRdv.disabled = true;
            btnRdv.textContent = 'Choisir un créneau';
            btnRdv.classList.remove('active');
        }
        document.getElementById(`selected-date-db-${medecinId}`).value = '';
        document.getElementById(`selected-heure-debut-db-${medecinId}`).value = '';
        document.getElementById(`selected-heure-fin-db-${medecinId}`).value = '';
        document.getElementById(`selected-prix-${medecinId}`).value = '';
        document.getElementById(`id-service-labo-${medecinId}`).value = '';
    }

    function fetchAvailabilitiesAndRender(medecinId, currentDateRef) {
        const weekDates = getWeekDates(currentDateRef);
        const startDateStr = formatDateToYYYYMMDD(weekDates[0]);
        const endDateStr = formatDateToYYYYMMDD(weekDates[6]);

        fetch(`get_disponibilites.php?medecin_id=${medecinId}&start_date=${startDateStr}&end_date=${endDateStr}`)
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok: ' + response.statusText);
                return response.json();
            })
            .then(data => {
                if (data.error) throw new Error(data.error);
                renderCalendar(medecinId, weekDates, data);
            })
            .catch(error => {
                console.error('Error fetching/rendering availabilities for medecin ' + medecinId + ':', error);
                const calendarBody = document.querySelector(`#calendar-${medecinId} tbody`);
                if (calendarBody) {
                    calendarBody.innerHTML = `<tr><td colspan="8" style="color:red;text-align:center;padding:10px;">Erreur: ${error.message}</td></tr>`;
                }
            });
    }
    
    function selectSlot(medecinId, slotButtonElement) {
        if (currentSelectedSlots[medecinId]) {
            currentSelectedSlots[medecinId].classList.remove('selected');
        }

        slotButtonElement.classList.add('selected');
        currentSelectedSlots[medecinId] = slotButtonElement;

        document.getElementById(`selected-date-db-${medecinId}`).value = slotButtonElement.dataset.dateDb;
        document.getElementById(`selected-heure-debut-db-${medecinId}`).value = slotButtonElement.dataset.heureDebutDb;
        document.getElementById(`selected-heure-fin-db-${medecinId}`).value = slotButtonElement.dataset.heureFinDb;
        document.getElementById(`selected-prix-${medecinId}`).value = slotButtonElement.dataset.prix;
        document.getElementById(`id-service-labo-${medecinId}`).value = slotButtonElement.dataset.idServiceLabo;

        const btnRdv = document.getElementById(`btn-rdv-${medecinId}`);
        if (btnRdv) {
            btnRdv.disabled = false;
            btnRdv.textContent = 'Valider ce créneau';
            btnRdv.classList.add('active');
        }
    }
    
    // Initialiser les calendriers pour toutes les cartes de médecin.
    // Le filtre JS s'occupera de les cacher/montrer.
    allDoctorCards.forEach(card => {
        const medecinId = card.id.split('-')[1];
        let currentDateForCalendar = new Date();
        
        fetchAvailabilitiesAndRender(medecinId, currentDateForCalendar);

        card.querySelector('.prev-week').addEventListener('click', function() {
            currentDateForCalendar.setDate(currentDateForCalendar.getDate() - 7);
            fetchAvailabilitiesAndRender(medecinId, currentDateForCalendar);
        });

        card.querySelector('.next-week').addEventListener('click', function() {
            currentDateForCalendar.setDate(currentDateForCalendar.getDate() + 7);
            fetchAvailabilitiesAndRender(medecinId, currentDateForCalendar);
        });
    });
});
</script>

</body>
</html>