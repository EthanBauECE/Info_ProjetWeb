<?php
require 'includes/head.php';
require 'includes/header.php';//ON GARDE LE HAUT DE PAGE

$user_is_logged_in = isset($_SESSION["user_id"]);//ON REGARDE SI LA PERSONNE A BIEN UN COMOPTE

function safe_html($value) {
    return $value !== 0 ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : '';//SI Y A RIEN DANS LA CHAINE ALORS ON MET VIDE
}

$conn = new mysqli("localhost", "root", "", "base_donne_web");//ON CONNCETE A NOTRE BASE DE DONNE
if ($conn->connect_error) {//SI CA NE MARCHE PASS
    die("Erreur de connexion: " . $conn->connect_error);//ON AFFICHE UN MESSAGE D ERREUR
}
$conn->set_charset("utf8");//ON SE CONNECTE

$sqlMedecins = "SELECT u.ID, u.Nom, u.Prenom, u.Email,
                       p.Photo, p.Telephone, p.Type,
                       a.Adresse, a.Ville, a.CodePostal
                FROM utilisateurs_personnel p
                LEFT JOIN utilisateurs u ON p.ID = u.ID
                LEFT JOIN adresse a ON p.ID_Adresse = a.ID
                WHERE LOWER(p.Type) = 'generaliste'";//INFO POUR LE MEDECIN GENERALISRE

$resultMedecins = $conn->query($sqlMedecins);//ON CONNECTE A LA BASE DE DONNE DU MEDECIN
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php // Le head.php est déjà inclus plus haut ?>
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
            padding: 2px;
            color: #555;
            height: auto;
            vertical-align: top;
        }
        .availability-grid td:empty { 
            border: 1px solid transparent; 
            background-color: #f9f9f9;
        }

        .time-slot-button {
            display: block;
            width: 100%;
            padding: 6px 4px;
            margin-bottom: 2px; 
            border: 1px solid #bde0fe;
            border-radius: 4px;
            background-color: #e6ffed; 
            color: #155724; 
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
            background-color: #28a745 !important; 
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

        .time-slot-button.past-slot {
            background-color: #e9ecef !important; 
            color: #6c757d !important; 
            border-color: #ced4da !important; 
            cursor: not-allowed !important; 
            opacity: 0.7; 
            text-decoration: line-through; 
            pointer-events: none; 
            box-shadow: none; 
        }
        .time-slot-button.past-slot .slot-price {
            color: #888 !important;
        }
        .time-slot-button:disabled {
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

<main class="main-container"> <!--on fait le premier bloc de la page-->
    <h1>Médecins Généralistes</h1><!--on indique le titre pour l utilisateur-->

    <?php
    if ($resultMedecins && $resultMedecins->num_rows > 0) {// SI CA A MARCHE ET QU IL Y A BIEN UN MEDECIN
        while ($medecin = $resultMedecins->fetch_assoc()) {//ON REGARDE TOUS LES MEDECINS
            $idMedecin = safe_html($medecin['ID']);//ON SAUVEGARDE SON ID
            $adresse_complete = safe_html($medecin['Adresse']) . ', ' . safe_html($medecin['CodePostal']) . ' ' . safe_html($medecin['Ville']);//ON RECUPERE TOUTES SES INFON DE SON ADRESSE
            ?>
            <div class="doctor-card" id="doctor-<?php echo $idMedecin; ?>"><!--on prepare sa feuille de presentation-->
                <div class="doctor-header">
                    <div class="doctor-photo"><!--artie dedié a la photo-->
                        <?php if (!empty($medecin['Photo'])): ?><!--si il a une photo-->
                            <img src="<?php echo safe_html($medecin['Photo']); ?>" alt="Photo de <?php echo safe_html($medecin['Prenom']); ?>"><!--on affiche sur son dossier-->
                        <?php else: ?><!--si il n en a pas alors-->
                            <span>Photo</span><!-- on en met pas-->
                        <?php endif; ?>
                    </div>
                    <div class="doctor-details"><!--pour les info perso-->
                        <h3 class="specialite-title">Généraliste</h3><!--precise que c 'et le generalisyte-->
                        <div class="info-grid">
                            <div class="info-cell"><strong>Nom :</strong> <?php echo safe_html($medecin['Nom']); ?></div><!--on a une partie pour le nom-->
                            <div class="info-cell"><strong>Prénom :</strong> <?php echo safe_html($medecin['Prenom']); ?></div><!--une patrtie pour le prenom avec son info-->
                            <div class="info-cell full-width"><strong>Adresse :</strong> <?php echo $adresse_complete; ?></div><!--une partie pour adressse avec reciup de ses info-->
                            <div class="info-cell full-width"><strong>Email :</strong> <?php echo safe_html($medecin['Email']); ?></div><!--idem pour email-->
                            <div class="info-cell full-width"><strong>Téléphone :</strong> <?php echo safe_html($medecin['Telephone']); ?></div><!--idem pour le num de tel-->
                        </div>
                    </div>
                </div>

                <div class="calendar-container"><!--LA PARTIE POUR SON CALENDRIER-->
                    <div class="calendar-controls">
                        <button class="prev-week" data-medecin-id="<?php echo $idMedecin; ?>">< Sem. Prec.</button><!--BOUTON POUR VOIR LES DISPO DE LA SEMLINE PRECEDENTE-->
                        <span class="week-display" id="week-display-<?php echo $idMedecin; ?>">Chargement...</span><!--MESSAGE POUR MONTRER LE CHAGGEMENT-->
                        <button class="next-week" data-medecin-id="<?php echo $idMedecin; ?>">Sem. Suiv. ></button><!--BOUTON POUR VOIR LA SEMAINE APRES-->
                    </div>
                    <table class="availability-grid" id="calendar-<?php echo $idMedecin; ?>">
                        <thead>
                            <tr>
                                <th style="width: 15%;">Heure</th> <!--POUR GERER LA TAILLE DE L AFFICHAGE-->
                                <th style="width: 12.14%;">Lun</th><!--POUR GERER LA TAILLE DE L AFFICHAGE-->
                                <th style="width: 12.14%;">Mar</th><!--POUR GERER LA TAILLE DE L AFFICHAGE-->
                                <th style="width: 12.14%;">Mer</th><!--POUR GERER LA TAILLE DE L AFFICHAGE-->
                                <th style="width: 12.14%;">Jeu</th><!--POUR GERER LA TAILLE DE L AFFICHAGE-->
                                <th style="width: 12.14%;">Ven</th><!--POUR GERER LA TAILLE DE L AFFICHAGE-->
                                <th style="width: 12.14%;">Sam</th><!--POUR GERER LA TAILLE DE L AFFICHAGE-->
                                <th style="width: 12.14%;">Dim</th><!--POUR GERER LA TAILLE DE L AFFICHAGE-->
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>

                <form action="confirmation_paiement.php" method="POST" class="rdv-form" id="form-<?php echo $idMedecin; ?>">
                    <input type="hidden" name="medecin_id" value="<?php echo $idMedecin; ?>">
                    <input type="hidden" name="medecin_nom" value="<?php echo safe_html($medecin['Nom'] . ' ' . $medecin['Prenom']); ?>">
                    <input type="hidden" name="selected_date_db" id="selected-date-db-<?php echo $idMedecin; ?>"> <!--INDIQUER LA FORME DE LA DATE A FIARE-->
                    <input type="hidden" name="selected_heure_debut_db" id="selected-heure-debut-db-<?php echo $idMedecin; ?>"> <!--IDEM POUR LA-->
                    <input type="hidden" name="selected_heure_fin_db" id="selected-heure-fin-db-<?php echo $idMedecin; ?>"> <!--IDEM POUR HEURE DE FIN-->
                    <input type="hidden" name="selected_prix" id="selected-prix-<?php echo $idMedecin; ?>"><!--PARTI POUR SELECTIONNER LE PRIX-->
                    <input type="hidden" name="id_service_labo" id="id-service-labo-<?php echo $idMedecin; ?>"><!--ET AUSSI CE QUE FAIT LE LABO COMME EXAMENS-->
                    
                    <div class="actions-container"><!--UNE AUTRES SOUS PARTIE-->
                         <?php if ($user_is_logged_in): ?><!--SI LA PERSONNE EST BIEN CONNECTEE-->
                            <button type="submit" class="btn-action btn-rdv" id="btn-rdv-<?php echo $idMedecin; ?>" disabled>Choisir un créneau</button><!--IL VA POUVOIR CHOISIR SON RDV AVCE LE BOUTON-->
                        <?php else: ?><!--SINON-->
                            <a href="login.php?redirect=medecine_general.php" class="btn-action btn-rdv" style="background-color:#007bff;">Se connecter pour prendre RDV</a><!--POSSIBILIE DE SE CONNCETER POUR LE FAIRE-->
                        <?php endif; ?>
                        <a href="chat.php?target_id=<?php echo $idMedecin; ?>" class="btn-action btn-communiquer">Communiquer</a><!--BOUTON POIUR POUVOIR COMMUNIQUER AVEC LE MEDECIN-->
                        <a href="cv_medecin.php?id=<?php echo $idMedecin; ?>" class="btn-action btn-cv">Voir CV</a><!--BOUTON POUR VOIR LE CV AUSSI-->
                    </div>
                </form>
            </div>
            <?php
        }
    } else {
        echo "<p>Aucun médecin généraliste trouvé.</p>";//SINON INDIQUER QU IL N Y A PAS DE GENERALISTE
    }
    $conn->close();//ON FERME
    ?>
</main>

<?php require 'includes/footer.php'; ?><!--ON GARDE LE FOOTER-->

<script>
document.addEventListener('DOMContentLoaded', function() {
    const daysOfWeekHeaders = ["Dim", "Lun", "Mar", "Mer", "Jeu", "Ven", "Sam"]; //POUR LES DIFFERNETS JOURS DE LA SEMAINE

    function getWeekDates(date) {
        const startOfWeek = new Date(date);//POUR CREER UNE NOUVELLE DATE
        const dayOfWeek = startOfWeek.getDay(); //LES DIFFERNETS JOURRS DE LA SEMAINE DE LUNDI A SAMEDI
        const diff = startOfWeek.getDate() - dayOfWeek + (dayOfWeek === 0 ? -6 : 1); //POUR EXPLIQUER LE NOMBRE DE JOUR POSSIBILE ABVEC PAS DE RDV LE DIAMNCHE
        startOfWeek.setDate(diff);
        startOfWeek.setHours(0, 0, 0, 0); //ON VA NORML LA NUIT IICI
        
        const week = [];//POUR SEMAINE
        for (let i = 0; i < 7; i++) {// POUR DEFILER DANS LES JOURZS
            const day = new Date(startOfWeek);
            day.setDate(startOfWeek.getDate() + i);//ON AJOUTE LES JOURS SUPPLEMENTAIRES
            week.push(day);//ON AJOUTE
        }
        return week;
    }

    function formatDateToYYYYMMDD(date) {//POUR LE FORMAT DES DIFFERENTES DATES
        const d = new Date(date);
        let month = '' + (d.getMonth() + 1);//ICI ON VA REGARDE RLE MOIS MAIS CA PEUT PAS COMMENCER  A 0 DONC ON AJOUTE 1
        let day = '' + d.getDate();//EN PLUS IL Y A LE JOUR
        const year = d.getFullYear();//ET L ANNEE
        if (month.length < 2) month = '0' + month;//POUR LE MOIS ON AJOUTE DES 0 POUR QUE LA NOMENCLATUURE SOIT BIEN RESPECTEE
        if (day.length < 2) day = '0' + day;//IDEM POUR LE JOUR
        return [year, month, day].join('-');//APRES ON MET DES TIRETS
    }
    
    function formatTimeHHMM(timeStr) { //ON MET ICI QUE LE HEURE ET MINS
        return timeStr.substring(0, 5);
    }

    let currentSelectedSlots = {};

    function renderCalendar(medecinId, weekDates, availabilities) {//ON RECUPERE LES DONNEES DU MEDECIN
        const calendarBody = document.querySelector(`#calendar-${medecinId} tbody`);//SON CELENDRIER AUSISS
        const weekDisplay = document.getElementById(`week-display-${medecinId}`);
        const btnRdv = document.getElementById(`btn-rdv-${medecinId}`);//SES RDV
        
        if (!calendarBody) return;
        calendarBody.innerHTML = ''; //ON VIDE LE CALENDRIER POUR FAIRE
        weekDisplay.textContent = `${formatDateToYYYYMMDD(weekDates[0]).substring(5)} au ${formatDateToYYYYMMDD(weekDates[6]).substring(5)}`;//ON PEUT MEEETTRE Z JOUR LE CZLENDDDIRE
        const slotsByTime = {};
        availabilities.forEach(dispo => {//POUR CHAQUE DISPO
            const heureDebut = formatTimeHHMM(dispo.HeureDebut); //L HEURE RESPECET SA FORME
            if (!slotsByTime[heureDebut]) {
                slotsByTime[heureDebut] = Array(7).fill(null); //ON INITIALISE
            }
            const dispoDate = new Date(dispo.Date + 'T00:00:00'); // S'assurer que c'est traité comme date locale
            const dayIndex = weekDates.findIndex(weekDate => weekDate.getTime() === dispoDate.getTime());

            if (dayIndex !== -1) {
                 if (!slotsByTime[heureDebut][dayIndex]) {
                    slotsByTime[heureDebut][dayIndex] = [];
                }
                slotsByTime[heureDebut][dayIndex].push({//ON GARDE LES INFOS DU CRENEAU
                    heureDebut: dispo.HeureDebut, //HEURE DU DEBUT
                    heureFin: dispo.HeureFin,     //HEURE DE LA FIN
                    prix: dispo.Prix,//LE PRIX
                    idServiceLabo: dispo.IdServiceLabo,//LE LABO CHOISIT
                    date: dispo.Date, // LA DATE DE DISPO
                    status: dispo.status //ET LE STATUT SI IL EW PRIS OU NON
                });
            }
        });

        const sortedTimes = Object.keys(slotsByTime).sort();//ON TRIE

        if (sortedTimes.length === 0) {///SI PAS DE CRENEAU
            const tr = calendarBody.insertRow();//ON VA INDIQUER QUE Y A RIEN
            const td = tr.insertCell();
            td.colSpan = 8; // 
            td.textContent = "Aucun créneau disponible pour cette semaine.";//ON PREVIENT UTILISATEUR
            td.style.textAlign = "center";//STYLE
            td.style.padding = "10px";//STYLE
            td.style.fontStyle = "italic";//STYLE
            td.style.color = "#6c757d";//STYLE
        } else {
            sortedTimes.forEach(heureDebutAffichage => { //SINON ON INDQIQUE LES INFO DU RDV
                const tr = calendarBody.insertRow();//ON AJOUTE LA LIGNE DU RDV
                const th = document.createElement('th');
                th.textContent = heureDebutAffichage;//AVEC HEURE AU DEBUT
                tr.appendChild(th);

                slotsByTime[heureDebutAffichage].forEach((daySlots, dayIndex) => {//ON AJOUTE POUR CHAQUE JOUR
                    const td = tr.insertCell();//ON INSERE DANS CALENDRIER
                    if (daySlots && daySlots.length > 0) {//SI Y A UN CRENEAU
                        const slotData = daySlots[0]; 
                        const slotButton = document.createElement('button');//ON PEUT APPUER SUR LE BOUTON
                        slotButton.classList.add('time-slot-button');//ET SELECTIONNER
                        
                        if (slotData.status === 'past') {//ANCIENS CRENEAU PAS DISPO
                            slotButton.classList.add('past-slot'); //ON MET UN STYLE PRECIS
                            slotButton.disabled = true; //PEUT PLUS FAIRE L ACTION
                            slotButton.innerHTML = `Passé<span class="slot-price">${parseFloat(slotData.prix).toFixed(2)} €</span>`;//ON INDIQUE QUE C EST PASSSE ET LE PRIX
                        } else {
                            slotButton.innerHTML = `${formatTimeHHMM(slotData.heureDebut)}<span class="slot-price">${parseFloat(slotData.prix).toFixed(2)} €</span>`;//POUR LES CRENEZUX A VNEIR
                            slotButton.dataset.dateDb = slotData.date;//LA DATE
                            slotButton.dataset.heureDebutDb = slotData.heureDebut;///HEURE DU DEBUT
                            slotButton.dataset.heureFinDb = slotData.heureFin;//HEURE DE FIN
                            slotButton.dataset.prix = slotData.prix;//LE PRIX
                            slotButton.dataset.idServiceLabo = slotData.idServiceLabo;//ET INFO DU LABO
                            slotButton.onclick = function() {//POSSIBILITE DE SELCTIONNER
                                selectSlot(medecinId, this);
                            };
                        }

                        td.appendChild(slotButton);
                    } else {
                        td.innerHTML = ' '; //ON MET RIEN
                    }
                });
            });
        }
        
        if (btnRdv) {
            btnRdv.disabled = true;//POSSIBILITE DE CHOISIR LE RDV
            btnRdv.textContent = 'Choisir un créneau';//AFFICHE MESS
            btnRdv.classList.remove('active');//ON ACTIVE
        }
        document.getElementById(`selected-date-db-${medecinId}`).value = '';//ON MET LA CASE VIDE
        document.getElementById(`selected-heure-debut-db-${medecinId}`).value = '';//ON MET VIDE
        document.getElementById(`selected-heure-fin-db-${medecinId}`).value = '';//ON MET VIDE
        document.getElementById(`selected-prix-${medecinId}`).value = '';//ON MET VIDE
        document.getElementById(`id-service-labo-${medecinId}`).value = '';//ON MET VIDE
    }

    function fetchAvailabilitiesAndRender(medecinId, currentDateRef) {
        const weekDates = getWeekDates(currentDateRef);///ON PEUT RECUPERE LES RDV PREEVU CETTE SEMAINE
        const startDateStr = formatDateToYYYYMMDD(weekDates[0]);//LE DEBUT
        const endDateStr = formatDateToYYYYMMDD(weekDates[6]);//LA FIN

        fetch(`get_disponibilites.php?medecin_id=${medecinId}&start_date=${startDateStr}&end_date=${endDateStr}`)//RETENIR INFO SUR MEDECIN AVEC DATE RDV
            .then(response => {
                if (!response.ok) throw new Error('ERREUR ' + response.statusText);//VERIFIER SI C EST OK
                return response.json();//RETOUR source: https://www.w3schools.com/Php/php_json.asp
            })
            .then(data => {
                if (data.error) throw new Error(data.error);//SI Y A UNE ERREUR
                renderCalendar(medecinId, weekDates, data);//FAIT REFERENCE AU CALENDRIER
            })
            .catch(error => {
                console.error('Error fetching/rendering availabilities for medecin ' + medecinId + ':', error);//WI Y A UNE ERREUR INDIQUER
                const calendarBody = document.querySelector(`#calendar-${medecinId} tbody`);//ON LE MET DANS LE CALENDRIER
                if (calendarBody) {//POUR LE STYLE DU CLAENDRIER
                    calendarBody.innerHTML = `<tr><td colspan="8" style="color:red;text-align:center;padding:10px;">Erreur: ${error.message}</td></tr>`;//ON MET EN STYLE PARTICULIER
                }
            });
    }
    
    function selectSlot(medecinId, slotButtonElement) {
        if (currentSelectedSlots[medecinId]) {
            currentSelectedSlots[medecinId].classList.remove('selected');
        }

        slotButtonElement.classList.add('selected');
        currentSelectedSlots[medecinId] = slotButtonElement;

        document.getElementById(`selected-date-db-${medecinId}`).value = slotButtonElement.dataset.dateDb;//ON MET A JOUR LES INFO CONCERNANT
        document.getElementById(`selected-heure-debut-db-${medecinId}`).value = slotButtonElement.dataset.heureDebutDb;//ON MET A JOUR LES INFO CONCERNANT
        document.getElementById(`selected-heure-fin-db-${medecinId}`).value = slotButtonElement.dataset.heureFinDb;//ON MET A JOUR LES INFO CONCERNANT
        document.getElementById(`selected-prix-${medecinId}`).value = slotButtonElement.dataset.prix;//ON MET A JOUR LES INFO CONCERNANT
        document.getElementById(`id-service-labo-${medecinId}`).value = slotButtonElement.dataset.idServiceLabo;//ON MET A JOUR LES INFO CONCERNANT

        const btnRdv = document.getElementById(`btn-rdv-${medecinId}`);
        if (btnRdv) { //SI Y A BIEN LE BTN RDV DONC SI CONNECTE
            btnRdv.disabled = false;
            btnRdv.textContent = 'Valider ce créneau';//IL PEUT VALIDER SON CRENEU
            btnRdv.classList.add('active');//IL PEUT CHOISIR
        }
    }
    
    document.querySelectorAll('.doctor-card').forEach(card => {//POUR CHAQQUZ MEDECIN
        const medecinId = card.id.split('-')[1];//ON RECUP SON ID
        let currentDateForCalendar = new Date(); //ON SE REFERE A LA DATE
        
        fetchAvailabilitiesAndRender(medecinId, currentDateForCalendar);//ON INIT POUR CHAUE MEDECIN

        card.querySelector('.prev-week').addEventListener('click', function() {//QUAN D ON VA POUR LA SEMIANE PRECEDENTE
            currentDateForCalendar.setDate(currentDateForCalendar.getDate() - 7);//ON  NELEVE 7J
            fetchAvailabilitiesAndRender(medecinId, currentDateForCalendar);//ON CHARGE NVX CLAENDRIER
        });

        card.querySelector('.next-week').addEventListener('click', function() {//POUR LA SEMAINE SUIVANTE
            currentDateForCalendar.setDate(currentDateForCalendar.getDate() + 7);//ON AJOUTE ICI LES 7J
            fetchAvailabilitiesAndRender(medecinId, currentDateForCalendar);//ON ACTUALISE ENCORE LE CALENDRIER
        });
    });
});
</script>

</body>
</html>