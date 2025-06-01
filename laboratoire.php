<?php 
//initialisation de la session et connexion a la base de donnée

//si pas de session deja active alors on demarre une session php
   if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

//verifie que l'utilisateur est connecté
    $user_is_logged_in = isset($_SESSION["user_id"]);


//connexion a la base de données
    $db_host='localhost';
    $db_user='root';
    $db_pass='';
    $db_name='base_donne_web';
    $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

//connexion a la base MySQL
    if (!$conn) {
        die("ERREUR:il est malheureusement impossible de se connecter à la base de données. " . mysqli_connect_error());
    }
    //force l'encodage des donnes en UTF-8
    mysqli_set_charset($conn, 'utf8');


 
//fonction qui sers a securiser l'affichage HTML

//pour empecher l'injection HTML, avec les caracteres speciaux par exmeple
    function safe_html($value)
    {
        return $value !==null ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : '';
    }

//pour la recuperation des laboratoires depuis la base de donnée
    $laboratoires=[];
    $sqlLabos="
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

    //permet d'executer la tache demandée
    $resultLabos=mysqli_query($conn, $sqlLabos);

    //alors que si la tache bien effectué on peut stocker les labos dansun tableau
    if ($resultLabos){
        while ($row=mysqli_fetch_assoc($resultLabos)){
            $laboratoires[]=$row;
        }

        mysqli_free_result($resultLabos);//pour liberer la memoire
    } 

    else{
        die("ERREUR:il n'est malheureuesement pas possible de récupérer la liste des laboratoires" . mysqli_error($conn));
    }
?>



<!DOCTYPE html> <!--la partie HTML du code-->
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Nos Laboratoires - Medicare</title>
    <link rel="icon" type="image/png" href="./images/medicare_logo.png" />
    <link rel="stylesheet" href="./css/style.css" />

    <style>
 /* on a fait sur le meme style que la page precedentes */
        .main-content-page {  
            padding: 2rem;
            background-color: #f2f2f2;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2.5rem;/* pour les espace entre les blocs laboratoire */
        }
        .main-content-page h1 {
            color: #333;
            margin-bottom: 2rem;
            font-size: 2.2rem;
            text-align: center;
        }

        .labo-card {/* aussi le meme que .doctor-card */
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

        .labo-header-section {/*pareil que .doctor-header */
            display: flex;
            gap: 2rem;
            align-items: center;
        }
        .labo-photo-display {
            width: 150px;/*on a juste changée la taille pour labo */
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
        .labo-details-info{ 
            flex-grow: 1;
        }
        .labo-details-info .labo-name-title{ 
            font-size: 1.8rem;
            font-weight: 600;
            color: #0a7abf;
            background-color: #eaf5ff;
            padding: 12px 18px;
            border-radius: 8px;
            margin: 0 0 1rem 0; 
            display: inline-block;/*on a fait en sorte que le fond prenne pas toute la largeur */
        }
        .labo-contact-grid{ 
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.5rem;
        }
        .labo-contact-grid p{
            font-size: 0.95rem;
            margin: 0.3rem 0;
            color: #454545;
        }
        .labo-contact-grid strong{ font-weight: 500; color: #333;}
        .labo-contact-grid a { color: #007bff; text-decoration: none;}
        .labo-contact-grid a:hover {text-decoration: underline;}

        .labo-description-display{
            font-size: 0.95rem;
            line-height: 1.6;
            margin: 1rem 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-left: 4px solid #17a2b8;
            border-radius: 0 6px 6px 0;
            color: #495057;
        }
        
        .labo-services-section-title{
            font-size: 1.3rem;
            color: #0a7abf;
            margin-top: 1.5rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #0a7abf;
            display: inline-block;
        }
        .labo-services-list{ display:flex;flex-wrap:wrap; gap:0.8rem; }
        .service-select-button{
            background-color: #f0f7ff; color: #0056b3; border: 1px solid #cce0ff;
            padding: 0.7rem 1.2rem; border-radius: 25px; text-decoration: none;
            text-align: center; font-weight: 500; transition: all 0.2s ease-in-out;
            cursor: pointer; font-size: 0.9rem;
        }
        .service-select-button:hover {background-color: #d1e7ff; border-color: #a8cfff; transform: translateY(-1px); box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .service-select-button.active-service { background-color: #0a7abf; color: white; border-color: #005c99; transform: translateY(0); box-shadow: inset 0 1px 3px rgba(0,0,0,0.1); }
        .service-price { font-size:0.85em;color: #6c757d; margin-left: 6px;}
        .service-select-button:hover .service-price, .service-select-button.active-service .service-price { color: #e0f0ff; }
        .labo-bricks-list {
            display:flex;
            flex-direction:column;
            gap:1.5rem;
        }


        /*le style pour le calendrier*/
        .lab-calendar-container { margin-top: 2rem; padding-top:1.5rem; border-top: 2px dashed #d4eaff; display: none; }
        .lab-calendar-container h5 { font-size: 1.2rem; color: #0056b3; margin-bottom: 1.2rem; font-weight: 500; }
        .calendar-controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .calendar-controls button { background-color:#007bff; color: white; border: none; padding: 9px 16px; border-radius: 5px; cursor: pointer; font-size:0.9rem; transition: background-color 0.2s; }
        .calendar-controls button:hover { background-color:#0056b3;}
        .calendar-controls button:disabled { background-color:#ccc; cursor: not-allowed;}
        .week-display { font-weight: 600; font-size: 1.1rem; color:#0a7abf;  }
        .availability-grid { width: 100%; border-collapse: collapse; text-align: center; table-layout: fixed; margin-top: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border-radius: 6px; overflow: hidden;  }
        .availability-grid th { background-color: #0a7abf; color: white; padding: 12px 5px; font-weight: 500; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;}
        .availability-grid td { border: 1px solid #dee2e6; padding: 4px;  color: #495057; height: auto; vertical-align: top; background-color: #fff; }
        .availability-grid td:empty { border:1px solid #f8f9fa; background-color: #f8f9fa; }
        .time-slot-button { display:block; width:100%; padding: 9px 5px; margin-bottom: 3px; border: 1px solid #b8daff; border-radius: 4px; background-color: #e7f3ff; color: #004085; cursor: pointer; font-size: 0.85rem; font-weight: 500; box-sizing: border-box; line-height: 1.3; transition: all 0.2s; }
        .time-slot-button .slot-price { display:block;font-size: 0.75rem; font-weight: normal; color: #5a6268; margin-top: 3px;}
        .time-slot-button.selected { background-color:#28a745 !important; color: white !important; border-color: #1e7e34 !important; transform: scale(1.02); box-shadow: 0 3px 7px rgba(40,167,69,0.35); }
        .time-slot-button.selected .slot-price { color:#f0f8ff;}
        .time-slot-button:hover:not(.selected) {background-color: #cfe2ff; border-color: #9fceff; transform: translateY(-1px); }
        
        /* le style pour les creneaux qui sont deja passées */
        .time-slot-button.past-slot {
            background-color: #e9ecef !important;
            color: #6c757d !important;/* couleur plus fonce pour differencier*/
            border-color: #ced4da !important; 
            cursor: not-allowed !important;
            opacity: 0.7; 
            text-decoration:line-through;
            pointer-events:none;
            box-shadow:none;
        }
        
        
        .time-slot-button.past-slot .slot-price {
            color: #888 !important; /* avec le prix plus fonce */
        }
        /*permet d'ajuster les boutons desactivés */
        .time-slot-button:disabled { background-color: #ccc; cursor: not-allowed; border-color: #bbb; color: #666; opacity:0.7; }
        
        .lab-actions-container { display: flex; justify-content: center; gap: 1rem; padding-top: 1.5rem; margin-top: 1.5rem; border-top: 1px solid #e9ecef; }
        .btn-action.btn-rdv { background-image: linear-gradient(to right, #0062E6 0%, #33AEFF 100%); color: white; padding: 12px 30px; font-size: 1.05rem; font-weight: 600; border: none; border-radius: 25px; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); text-transform: uppercase; letter-spacing: 1px; }
        .btn-action.btn-rdv:disabled { background-image: none; background-color: #adb5bd; cursor: not-allowed; opacity: 0.6; box-shadow: none; }
        .btn-action.btn-rdv.active { background-image: linear-gradient(to right, #28a745 0%, #218838 100%); box-shadow: 0 4px 15px rgba(40, 167, 69, 0.2); }
        .btn-action.btn-rdv:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15); }

    </style>
</head>
<body>
    <!--l importation ds le header -->
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
                        <div class="labo-header-section"> <!--pr l'enquete d lab-->
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
                           

                           <?php //recup services par labo
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

                        <div class="lab-calendar-container" id="lab-calendar-container-<?php echo $laboId; ?>"> <!--calendrier ici-->
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
                                <input type="hidden" name="type_rdv" value="laboratoire"> <!--tres important pr la page de payement-->
                                <input type="hidden" name="labo_id" value="<?php echo $laboId; ?>">
                                <input type="hidden" name="labo_nom" value="<?php echo safe_html($labo['Nom']); ?>">
                                <input type="hidden" name="selected_service_id" id="selected-service-id-<?php echo $laboId; ?>">
                                <input type="hidden" name="selected_service_nom" id="selected-service-nom-<?php echo $laboId; ?>">
                                <input type="hidden" name="selected_date_db" id="selected-date-db-lab-<?php echo $laboId; ?>">
                                <input type="hidden" name="selected_heure_debut_db" id="selected-heure-debut-db-lab-<?php echo $laboId; ?>">
                                <input type="hidden" name="selected_heure_fin_db" id="selected-heure-fin-db-lab-<?php echo $laboId; ?>">
                                <input type="hidden" name="selected_prix" id="selected-prix-lab-<?php echo $laboId; ?>">
                                <div class="lab-actions-container"> <!--bouton d action ici pr le payement et tt-->
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

    <!--l importation pr le footer -->
    <?php require 'includes/footer.php'; ?>

<?php //pr la fermeture connexion avec base de donneess
    if ($conn) {
        mysqli_close($conn);
    }
?>


<script>

//pr la gestion dynamiques des calendriers  (JavaScript)
document.addEventListener('DOMContentLoaded', function() {
    

    //foction pour prendre une date et apres retourner un tableau de 7 dates correspondant a la semaine entiere
    function getWeekDates(date) {
        const startOfWeek = new Date(date);//duplique la date d'entree pour ne pas modifier l'originale
        const dayOfWeek = startOfWeek.getDay();//on aura Lundi prend la valeur 1 et ect
        const diff = startOfWeek.getDate() - dayOfWeek + (dayOfWeek === 0 ? -6 : 1);//calcule de combien de jours il faut reculé pr tombé  sur lundi precedent
        startOfWeek.setDate(diff);//on mettra la date au lindi de la sem
        startOfWeek.setHours(0, 0, 0, 0);//mise a jour de l'heure a minuit pr pas de confus
        const week = [];//tableau final avec 7 jour de la semaine
        
        for (let i = 0; i < 7; i++) {//pr creer les 7 j a partir du lundi
            const day = new Date(startOfWeek);//pr cloner la date du lundi
            day.setDate(startOfWeek.getDate() + i);//ajouter i jour pourobt chaque j de la sem
            week.push(day);//pr ajouter la j au tableau
        }
        return week;//tableau creer avec les 7j
    }

// Formate une date en chainee "YYYY-MM-DD"
    function formatDateToYYYYMMDD(date) {
        const d = new Date(date);
        let month = '' + (d.getMonth() + 1);//js retourne 0 pour janvier a partir de ca on ajoute +1
        let day = '' + d.getDate();//j du mois
        const year = d.getFullYear();//annee

        if (month.length < 2) month = '0' + month;//on rajoute un 0 devant si le mois ou le j fait un seul chiffre
        if (day.length < 2) day = '0' + day;

        //pour ratourner le tt dans l'ordre: annee-mois--jour
        return [year, month, day].join('-');
    }

    //fonction pour recup l'h et les min a partir de la chaine "hh:mm:ss"
    function formatTimeHHMM(timeStr) { return timeStr.substring(0, 5); }//coup chaine au 5e caracter

//varaible globales pr le calendrier
    let currentLabSelectedSlots = {};//enregistre le crenau clique pr chaque labo
    let currentLabCalendarDate = {};//stocke la date de debut de sem actuellement afficher pr chaq labo
    let currentLabSelectedService = {};//memorise le servise selectionne pr chaque labo


   
 //permet d'afficher le calendrier des crenaux horaires pr un laboratore donner
    function renderLabCalendar(laboId,serviceId,serviceNom,servicePrixBase,weekDates,availabilities) {
        //selection des elements Html ciblées 
        const calendarContainer=document.getElementById(`lab-calendar-container-${laboId}`);
        const calendarBody=calendarContainer.querySelector(`#calendar-lab-${laboId}tbody`);
        const weekDisplay=calendarContainer.querySelector(`#week-display-lab-${laboId}`);
        const btnRdv=calendarContainer.querySelector(`#btn-rdv-lab-${laboId}`);
        const serviceNameDisplay=calendarContainer.querySelector('.selected-service-name');
        
       //afiche base
        calendarBody.innerHTML=''; //vide tab calendrier
        calendarContainer.style.display='block';
        serviceNameDisplay.textContent=serviceNom; //affiche le nom du service en haut

        //affiche la semaine 
        weekDisplay.textContent=`${formatDateToYYYYMMDD(weekDates[0]).substring(8,10)}/${formatDateToYYYYMMDD(weekDates[0]).substring(5,7)} au ${formatDateToYYYYMMDD(weekDates[6]).substring(8,10)}/${formatDateToYYYYMMDD(weekDates[6]).substring(5,7)}`;

        //prepa de la structure par h
        const slotsByTime={};
       
        //verif que c bien un tableau
        if (Array.isArray(availabilities)){
            availabilities.forEach(dispo=>{ 
                const heureDebut=formatTimeHHMM(dispo.HeureDebut);

                //si cette h n'est tjs pas connu alors on initialise un tableau de 7j
                if (!slotsByTime[heureDebut]) slotsByTime[heureDebut] = Array(7).fill(null); // Initialise pour les 7 jours
                const dispoDate=new Date(dispo.Date + 'T00:00:00'); //transforme la date brute
                //pr la recherche du j de semaine par rapport a la dispo
                const dayIndex=weekDates.findIndex(weekDate => weekDate.getTime() === dispoDate.getTime());
                if (dayIndex !==-1) { 

                    //si pas de crenau a cette date
                    if (!slotsByTime[heureDebut][dayIndex])slotsByTime[heureDebut][dayIndex] = [];
                   //stocke les infos du creneau pr cette case
                    slotsByTime[heureDebut][dayIndex].push({
                        heureDebut:dispo.HeureDebut,heureFin: dispo.HeureFin,
                        prix: dispo.Prix, idServiceLabo: dispo.IdServiceLabo, date: dispo.Date,
                        status: dispo.status
                    });
                }
            });
        }


        const sortedTimes = Object.keys(slotsByTime).sort();//focntion pr trier les h
        if (sortedTimes.length === 0) {
            calendarBody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:15px; font-style:italic; color:#6c757d;">Aucun créneau disponible pour ce service cette semaine.</td></tr>`;
        } else {
            sortedTimes.forEach(heureDebutAffichage=>{ 
                const tr = calendarBody.insertRow();
                const th = document.createElement('th');
                th.textContent = heureDebutAffichage;//pr afficher l'h du debit dans la premiere colonne
                tr.appendChild(th);
                slotsByTime[heureDebutAffichage].forEach(daySlots => {//pr tt les j de la semaine
                    const td = tr.insertCell();
                    if (daySlots && daySlots.length > 0) {//que si des creneau existe pr cette h et ce j
                        const slotData = daySlots[0];//prend le premier et un seul seulement
                        const slotButton = document.createElement('button');
                        slotButton.classList.add('time-slot-button');

                        if (slotData.status === 'past') {//creneau qui sont passé
                            slotButton.classList.add('past-slot');
                            slotButton.disabled = true;
                            slotButton.innerHTML = `Passé<span class="slot-price">${parseFloat(slotData.prix).toFixed(2)} €</span>`;
                        } else {//tt les crenau dispo
                            slotButton.innerHTML = `${formatTimeHHMM(slotData.heureDebut)}<span class="slot-price">(${parseFloat(slotData.prix).toFixed(2)} €)</span>`;
                            slotButton.dataset.dateDb = slotData.date;
                            slotButton.dataset.heureDebutDb = slotData.heureDebut;
                            slotButton.dataset.heureFinDb = slotData.heureFin;
                            slotButton.dataset.prix = slotData.prix; 
                            slotButton.dataset.idServiceLabo = slotData.idServiceLabo;
                            slotButton.onclick = function() { selectLabSlot(laboId, this); };
                        }
                        td.appendChild(slotButton);
                    } else { td.innerHTML = ' '; }//la case se retoruve vide si pas de crenau
                });
            });
        }
        
        if (btnRdv) {//desactivation bouton valider creneau si aucun selectionnee
            btnRdv.disabled = true;
            btnRdv.textContent = 'Choisir un créneau';
            btnRdv.classList.remove('active');
        }
    
        //vider les champs cachés du formu pr restart a zero
        const form = document.getElementById(`form-lab-${laboId}`);
        form.querySelector(`#selected-service-id-${laboId}`).value = serviceId;
        form.querySelector(`#selected-service-nom-${laboId}`).value = serviceNom;
        form.querySelector(`#selected-prix-lab-${laboId}`).value = ''; 
        form.querySelector(`#selected-date-db-lab-${laboId}`).value = '';
        form.querySelector(`#selected-heure-debut-db-lab-${laboId}`).value = '';
        form.querySelector(`#selected-heure-fin-db-lab-${laboId}`).value = ''; 
    }


//Récup des Dispo via AJAX


//fonction pr initialiser la date de ref si existe pas encore
    function fetchLabAvailabilitiesAndRender(laboId, serviceId, serviceNom, servicePrixBase, dateRef) {
        if (!currentLabCalendarDate[laboId]) {
            currentLabCalendarDate[laboId] = new Date(dateRef);
        }
        //calcul 7 dates semaines en cours
        const weekDates = getWeekDates(currentLabCalendarDate[laboId]);
        const startDateStr = formatDateToYYYYMMDD(weekDates[0]);
        const endDateStr = formatDateToYYYYMMDD(weekDates[6]);
       
        //pr encregistrer les iinfos du service selc
        currentLabSelectedService[laboId] = { id: serviceId, nom: serviceNom, prixBase: servicePrixBase };

        //prep visuel chargement 
        const calendarContainer = document.getElementById(`lab-calendar-container-${laboId}`);
        const serviceNameDisp = calendarContainer.querySelector('.selected-service-name');
        const calendarBody = calendarContainer.querySelector(`#calendar-lab-${laboId} tbody`);
        const weekDisplay = calendarContainer.querySelector(`#week-display-lab-${laboId}`);

        serviceNameDisp.textContent = serviceNom; 
        weekDisplay.textContent = "Chargement...."; 
        calendarBody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:15px; font-style:italic; color:#6c757d;">Chargement des disponibilités...</td></tr>`;
        calendarContainer.style.display = 'block'; 

        //requete AJAX vers le scrip PHP
        fetch(`get_disponibilites_labo.php?service_id=${serviceId}&start_date=${startDateStr}&end_date=${endDateStr}`)
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => { 
                        throw new Error(`probleme du réseau: ${response.status} - ${response.statusText}. Réponse: ${text}`);
                    });
                }
                return response.json();
            })

            .then(data =>{
                if (data.error){
                    throw new Error(data.error); 
                }
             const availabilitiesData = Array.isArray(data) ? data : (data.data || []);
                renderLabCalendar(laboId, serviceId, serviceNom, servicePrixBase, weekDates, availabilitiesData); 
            })

            .catch(error => {
                console.error(`Erreur chargement dispos labo ${laboId}, service ${serviceId}:`, error);
               //AFFICHE LE MESS D'ERREUr dans le tableau
                serviceNameDisp.textContent = serviceNom;//pr maintenir le nom du service affiché
                //pr afficher le  mess des semaines
                weekDisplay.textContent = `${formatDateToYYYYMMDD(weekDates[0]).substring(8,10)}/${formatDateToYYYYMMDD(weekDates[0]).substring(5,7)} au ${formatDateToYYYYMMDD(weekDates[6]).substring(8,10)}/${formatDateToYYYYMMDD(weekDates[6]).substring(5,7)}`;
                calendarBody.innerHTML = `<tr><td colspan="8" style="color:red;text-align:center;padding:10px;">Erreur : ${error.message}</td></tr>`;
                calendarContainer.style.display = 'block';
            });
    }


 // pr la sélection d'un Créneau

   
    function selectLabSlot(laboId, slotButtonElement) { 
        //deselectionne le creneau precedent si il y avait un
        if (currentLabSelectedSlots[laboId]){ 
            currentLabSelectedSlots[laboId].classList.remove('selected');
        }

        //pr selectionner le nv bouton
        slotButtonElement.classList.add('selected');
        currentLabSelectedSlots[laboId] = slotButtonElement;

       //pr mettre a jour ts les champs caches du formu
        const form=document.getElementById(`form-lab-${laboId}`);
        form.querySelector(`#selected-date-db-lab-${laboId}`).value = slotButtonElement.dataset.dateDb;
        form.querySelector(`#selected-heure-debut-db-lab-${laboId}`).value = slotButtonElement.dataset.heureDebutDb;
        form.querySelector(`#selected-heure-fin-db-lab-${laboId}`).value = slotButtonElement.dataset.heureFinDb; 
        form.querySelector(`#selected-prix-lab-${laboId}`).value = slotButtonElement.dataset.prix; 
    
        //pr activer le bouton "valider le creneau"
        const btnRdv=form.querySelector(`#btn-rdv-lab-${laboId}`);
        if (btnRdv){ 
            btnRdv.disabled=false;
            btnRdv.textContent='Valider le créneau';
            btnRdv.classList.add('active');
        }
    }

//Gestion des Événements
//ecouteurs pr les boutons de selection de service
    document.querySelectorAll('.service-select-button').forEach(button => {
        button.addEventListener('click', function() {
            const laboId=this.dataset.laboId;//tt les boutons
            const serviceId=this.dataset.serviceId;
            const serviceNom=this.dataset.serviceNom;
            const servicePrix=this.dataset.servicePrix; 

        
            //donc on enleve le 'actif' de ts les autres bouttons du labo
            document.querySelectorAll(`#services-list-${laboId} .service-select-button.active-service`).forEach(activeBtn => {
                activeBtn.classList.remove('active-service');
            });
            //pr ajouté la classe activE a celui qui est cliquer
            this.classList.add('active-service');

            //pr remettre le calendrier de la semaine actuelle
            currentLabCalendarDate[laboId]=new Date();
            //recherche des creneaux dispo pr le nv service selec
            fetchLabAvailabilitiesAndRender(laboId,serviceId,serviceNom,servicePrix,currentLabCalendarDate[laboId]);
        });

    });

   
    //ecouteur les boutons de navig du calendrier (semaineprec)
    document.querySelectorAll('.lab-calendar-container').forEach(container => { 
        const laboId=container.id.split('-')[3];//extrait de l'id du lab

        container.querySelector('.prev-week').addEventListener('click', function() {
            if (currentLabCalendarDate[laboId] && currentLabSelectedService[laboId]) {
               //on recules d une semaine
                currentLabCalendarDate[laboId].setDate(currentLabCalendarDate[laboId].getDate() - 7); 
                const service = currentLabSelectedService[laboId];
                //recharge avec nv dates
                fetchLabAvailabilitiesAndRender(laboId, service.id, service.nom, service.prixBase, currentLabCalendarDate[laboId]);
            }

        });

        container.querySelector('.next-week').addEventListener('click', function() {
             if (currentLabCalendarDate[laboId] && currentLabSelectedService[laboId]) {
             //pr avancer d une sem
                currentLabCalendarDate[laboId].setDate(currentLabCalendarDate[laboId].getDate() + 7);
                const service = currentLabSelectedService[laboId];
               //rechargmeent des crenaux avec nvelle dates
                fetchLabAvailabilitiesAndRender(laboId, service.id, service.nom, service.prixBase, currentLabCalendarDate[laboId]);
            }
        });
    });
});
</script>
</body>
</html>