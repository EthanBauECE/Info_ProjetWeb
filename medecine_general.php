<?php
// On place les includes qui pourraient démarrer une session AVANT tout code HTML.
require 'includes/head.php'; // Assure-toi que jQuery est inclus ici si tu l'utilises
require 'includes/header.php';

// S'assurer que l'utilisateur est connecté pour prendre RDV
$user_is_logged_in = isset($_SESSION["user_id"]);

// --- Fonctions Utilitaires ---
function safe_html($value) {
    return $value !== null ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : '';
}

// --- Connexion à la BDD ---
$conn = new mysqli("localhost", "root", "", "base_donne_web");
if ($conn->connect_error) {
    die("Erreur de connexion: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// Requête SQL pour les médecins généralistes uniquement
$sqlMedecins = "SELECT u.ID, u.Nom, u.Prenom, u.Email,
                       p.Photo, p.Telephone, p.Type,
                       a.Adresse, a.Ville, a.CodePostal
                FROM utilisateurs_personnel p
                LEFT JOIN utilisateurs u ON p.ID = u.ID
                LEFT JOIN adresse a ON p.ID_Adresse = a.ID
                WHERE LOWER(p.Type) = 'generaliste'";

$resultMedecins = $conn->query($sqlMedecins);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php // Le head.php est déjà inclus plus haut ?>
    <style>
        /* Styles existants de medecine_general.txt ... */
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
            margin: 0;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            padding-top: 1.0rem;
        }
        .info-cell { font-size: 1.1rem; }
        .full-width { grid-column: 1 / -1; }
        .info-cell strong { font-weight: 500; color: #333; }
        
        /* STYLES POUR LE CALENDRIER INTERACTIF */
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
            margin-top: 1rem;
        }
        .availability-grid th {
            background-color: #4a6fa5;
            color: white;
            padding: 10px 5px;
            font-weight: 500;
            font-size: 0.85rem;
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
        .time-slot-button:disabled { /* Style pour le bouton RDV désactivé */
             background-color: #ccc;
             cursor: not-allowed;
             border-color: #bbb;
             color: #666;
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

<main class="main-container">
    <h1>Médecins Généralistes</h1>

    <?php
    if ($resultMedecins && $resultMedecins->num_rows > 0) {
        while ($medecin = $resultMedecins->fetch_assoc()) {
            $idMedecin = safe_html($medecin['ID']);
            $adresse_complete = safe_html($medecin['Adresse']) . ', ' . safe_html($medecin['CodePostal']) . ' ' . safe_html($medecin['Ville']);
            ?>
            <div class="doctor-card" id="doctor-<?php echo $idMedecin; ?>">
                <div class="doctor-header">
                    <div class="doctor-photo">
                        <?php if (!empty($medecin['Photo'])): ?>
                            <img src="<?php echo safe_html($medecin['Photo']); ?>" alt="Photo de <?php echo safe_html($medecin['Prenom']); ?>">
                        <?php else: ?>
                            <span>Photo</span>
                        <?php endif; ?>
                    </div>
                    <div class="doctor-details">
                        <h3 class="specialite-title">Généraliste</h3>
                        <div class="info-grid">
                            <div class="info-cell"><strong>Nom :</strong> <?php echo safe_html($medecin['Nom']); ?></div>
                            <div class="info-cell"><strong>Prénom :</strong> <?php echo safe_html($medecin['Prenom']); ?></div>
                            <div class="info-cell full-width"><strong>Adresse :</strong> <?php echo $adresse_complete; ?></div>
                            <div class="info-cell full-width"><strong>Email :</strong> <?php echo safe_html($medecin['Email']); ?></div>
                            <div class="info-cell full-width"><strong>Téléphone :</strong> <?php echo safe_html($medecin['Telephone']); ?></div>
                        </div>
                    </div>
                </div>

                <div class="calendar-container">
                    <div class="calendar-controls">
                        <button class="prev-week" data-medecin-id="<?php echo $idMedecin; ?>">< Sem. Prec.</button>
                        <span class="week-display" id="week-display-<?php echo $idMedecin; ?>">Chargement...</span>
                        <button class="next-week" data-medecin-id="<?php echo $idMedecin; ?>">Sem. Suiv. ></button>
                    </div>
                    <table class="availability-grid" id="calendar-<?php echo $idMedecin; ?>">
                        <thead>
                            <tr>
                                <th style="width: 15%;">Heure</th>
                                <th style="width: 12.14%;">Lun</th>
                                <th style="width: 12.14%;">Mar</th>
                                <th style="width: 12.14%;">Mer</th>
                                <th style="width: 12.14%;">Jeu</th>
                                <th style="width: 12.14%;">Ven</th>
                                <th style="width: 12.14%;">Sam</th>
                                <th style="width: 12.14%;">Dim</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Les lignes de créneaux seront générées par JS dynamiquement -->
                        </tbody>
                    </table>
                </div>

                <form action="confirmation_paiement.php" method="POST" class="rdv-form" id="form-<?php echo $idMedecin; ?>">
                    <input type="hidden" name="medecin_id" value="<?php echo $idMedecin; ?>">
                    <input type="hidden" name="medecin_nom" value="<?php echo safe_html($medecin['Nom'] . ' ' . $medecin['Prenom']); ?>">
                    <input type="hidden" name="selected_date_db" id="selected-date-db-<?php echo $idMedecin; ?>"> <!-- YYYY-MM-DD -->
                    <input type="hidden" name="selected_heure_debut_db" id="selected-heure-debut-db-<?php echo $idMedecin; ?>"> <!-- HH:MM:SS -->
                    <input type="hidden" name="selected_heure_fin_db" id="selected-heure-fin-db-<?php echo $idMedecin; ?>"> <!-- HH:MM:SS -->
                    <input type="hidden" name="selected_prix" id="selected-prix-<?php echo $idMedecin; ?>">
                    <input type="hidden" name="id_service_labo" id="id-service-labo-<?php echo $idMedecin; ?>">
                    
                    <div class="actions-container">
                         <?php if ($user_is_logged_in): ?>
                            <button type="submit" class="btn-action btn-rdv" id="btn-rdv-<?php echo $idMedecin; ?>" disabled>Choisir un créneau</button>
                        <?php else: ?>
                            <a href="login.php?redirect=medecine_general.php" class="btn-action btn-rdv" style="background-color:#007bff;">Se connecter pour prendre RDV</a>
                        <?php endif; ?>
                        <a href="communiquer.php?id=<?php echo $idMedecin; ?>" class="btn-action btn-communiquer">Communiquer</a>
                        <a href="cv_medecin.php?id=<?php echo $idMedecin; ?>" class="btn-action btn-cv">Voir CV</a>
                    </div>
                </form>
            </div>
            <?php
        }
    } else {
        echo "<p>Aucun médecin généraliste trouvé.</p>";
    }
    $conn->close();
    ?>
</main>

<?php require 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const daysOfWeekHeaders = ["Dim", "Lun", "Mar", "Mer", "Jeu", "Ven", "Sam"]; // JS Day: 0=Dim, 1=Lun...

    function getWeekDates(date) {
        const startOfWeek = new Date(date);
        // Lundi comme premier jour de la semaine (ISO 8601)
        const dayOfWeek = startOfWeek.getDay(); // 0 (Dimanche) à 6 (Samedi)
        const diff = startOfWeek.getDate() - dayOfWeek + (dayOfWeek === 0 ? -6 : 1); // ajustement pour Lundi
        startOfWeek.setDate(diff);
        startOfWeek.setHours(0, 0, 0, 0); // Normaliser à minuit
        
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
    
    function formatTimeHHMM(timeStr) { // 'HH:MM:SS' -> 'HH:MM'
        return timeStr.substring(0, 5);
    }

    // Garde une trace du slot sélectionné par médecin
    let currentSelectedSlots = {};

    function renderCalendar(medecinId, weekDates, availabilities) {
        const calendarBody = document.querySelector(`#calendar-${medecinId} tbody`);
        const weekDisplay = document.getElementById(`week-display-${medecinId}`);
        const btnRdv = document.getElementById(`btn-rdv-${medecinId}`);
        
        if (!calendarBody) return;
        calendarBody.innerHTML = ''; // Vider le calendrier

        // Affichage de la période
        weekDisplay.textContent = `${formatDateToYYYYMMDD(weekDates[0]).substring(5)} au ${formatDateToYYYYMMDD(weekDates[6]).substring(5)}`;

        // Grouper les disponibilités par heure de début pour créer les lignes du tableau
        const slotsByTime = {};
        availabilities.forEach(dispo => {
            const heureDebut = formatTimeHHMM(dispo.HeureDebut); // ex: "09:00"
            if (!slotsByTime[heureDebut]) {
                slotsByTime[heureDebut] = Array(7).fill(null); // Initialiser une ligne pour les 7 jours
            }
            const dispoDate = new Date(dispo.Date + 'T00:00:00'); // S'assurer que c'est traité comme date locale
            const dayIndex = weekDates.findIndex(weekDate => weekDate.getTime() === dispoDate.getTime());

            if (dayIndex !== -1) {
                 if (!slotsByTime[heureDebut][dayIndex]) {
                    slotsByTime[heureDebut][dayIndex] = [];
                }
                // Stocker toutes les infos du créneau
                slotsByTime[heureDebut][dayIndex].push({
                    heureDebut: dispo.HeureDebut, // HH:MM:SS
                    heureFin: dispo.HeureFin,     // HH:MM:SS
                    prix: dispo.Prix,
                    idServiceLabo: dispo.IdServiceLabo,
                    date: dispo.Date // YYYY-MM-DD
                });
            }
        });

        // Trier les heures de début
        const sortedTimes = Object.keys(slotsByTime).sort();

        if (sortedTimes.length === 0) {
            const tr = calendarBody.insertRow();
            const td = tr.insertCell();
            td.colSpan = 8; // 1 pour l'heure + 7 jours
            td.textContent = "Aucun créneau disponible pour cette semaine.";
            td.style.textAlign = "center";
            td.style.padding = "10px";
        } else {
            sortedTimes.forEach(heureDebutAffichage => { // ex: "09:00"
                const tr = calendarBody.insertRow();
                const th = document.createElement('th');
                th.textContent = heureDebutAffichage;
                tr.appendChild(th);

                slotsByTime[heureDebutAffichage].forEach((daySlots, dayIndex) => {
                    const td = tr.insertCell();
                    if (daySlots && daySlots.length > 0) {
                        // Pour cette version, on ne prend que le premier slot s'il y en a plusieurs (ne devrait pas arriver si les créneaux ne se chevauchent pas)
                        const slotData = daySlots[0]; 
                        const slotButton = document.createElement('button');
                        slotButton.classList.add('time-slot-button');
                        slotButton.innerHTML = `${formatTimeHHMM(slotData.heureDebut)}<span class="slot-price">${parseFloat(slotData.prix).toFixed(2)} €</span>`;
                        
                        // Stocker les données complètes pour le formulaire
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
                        td.innerHTML = ' '; // Laisser vide si pas de créneau
                    }
                });
            });
        }
        
        // Réinitialiser le bouton RDV si l'utilisateur est connecté
        if (btnRdv) {
            btnRdv.disabled = true;
            btnRdv.textContent = 'Choisir un créneau';
            btnRdv.classList.remove('active');
        }
        // Vider les champs cachés du formulaire
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
        // Désélectionner le précédent slot pour ce médecin
        if (currentSelectedSlots[medecinId]) {
            currentSelectedSlots[medecinId].classList.remove('selected');
        }

        slotButtonElement.classList.add('selected');
        currentSelectedSlots[medecinId] = slotButtonElement;

        // Mettre à jour les champs cachés du formulaire
        document.getElementById(`selected-date-db-${medecinId}`).value = slotButtonElement.dataset.dateDb;
        document.getElementById(`selected-heure-debut-db-${medecinId}`).value = slotButtonElement.dataset.heureDebutDb;
        document.getElementById(`selected-heure-fin-db-${medecinId}`).value = slotButtonElement.dataset.heureFinDb;
        document.getElementById(`selected-prix-${medecinId}`).value = slotButtonElement.dataset.prix;
        document.getElementById(`id-service-labo-${medecinId}`).value = slotButtonElement.dataset.idServiceLabo;

        const btnRdv = document.getElementById(`btn-rdv-${medecinId}`);
        if (btnRdv) { // Vérifier si le bouton existe (cas où l'utilisateur n'est pas connecté)
            btnRdv.disabled = false;
            btnRdv.textContent = 'Valider ce créneau';
            btnRdv.classList.add('active');
        }
    }
    
    document.querySelectorAll('.doctor-card').forEach(card => {
        const medecinId = card.id.split('-')[1];
        let currentDateForCalendar = new Date(); // Date de référence pour la semaine, propre à chaque médecin
        
        // Initial load pour chaque médecin
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