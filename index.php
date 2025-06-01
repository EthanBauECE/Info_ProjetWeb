<!DOCTYPE html>
<html lang="fr">
<?php require 'includes/head.php'; ?>

<body>

    <?php require 'includes/header.php'; ?>
    <section class="hero">
        <div class="hero-content">
            <h1>Bienvenue sur le site Medicare</h1><!--phrase introduction du site-->
            <p>Votre santé est notre priorité. Prenez rendez-vous dès maintenant avec l’un de nos spécialistes.</p><!--pour attirer de nouvellles personnes-->
            <div class="hero-buttons">
                <a href="register.php">Prendre RDV</a><!--bouton pour pouvoir prendr rendez-vous-->
                <a href="parcourir.php">Voir Emploi du Temps</a><!--et un autrea aussi pour voir emploi du temps-->
            </div>
        </div>
    </section>

    <section class="event-bulletin-section">
        <div class="event-bulletin-container">
            <h2 class="section-title">Événements & Bulletin de Santé de la Semaine</h2><!--on affiche ici les differents activit ee t truc de santé-->
            <p class="intro-text">Découvrez les activités importantes de Medicare, les dernières nouvelles en image</p><!--on parle ici encore a l utilisateur pour qu il soit au courant-->

            <div class="content-wrapper">
                <div class="health-news-block">
                    <h3>Actualités & Infos Santé</h3><!--titre de la partie POUR INFORMER-->
                    <div class="news-item">
                        <h4><i class="fas fa-calendar-alt"></i> Porte Ouverte Medicare</h4><!--titre en haut pour savoir ou on est-->
                        <p>Participez à notre journée portes ouvertes le <strong>Samedi 10 Juin de 9h à 17h</strong>. Explorez nos nouvelles salles de consultation, rencontrez nos équipes et découvrez les dernières technologies. Des mini-conférences sur la prévention  et des démonstrations de nos équipements seront organisées. </p><!--messsage poiur indiquer et avec un lien avec utilisateru-->
                    </div>
                    <div class="news-item">
                        <h4><i class="fas fa-medkit"></i> Campagne de Don du Sang Urgente</h4><!--titre pour la partie-->
                        <p>En collaboration avec l'Établissement Français du Sang (EFS), Medicare organise une collecte de sang exceptionnelle le <strong>Jeudi 15 Juin de 10h à 18h</strong>. Chaque don compte et peut sauver jusqu'à trois vies. Prenez rendez-vous sur notre site. Merci de votre engagement !</p><!--messsage poiur indiquer et avec un lien avec utilisateru-->
                    </div>
                    <div class="news-item">
                        <h4><i class="fas fa-virus"></i> Bulletin COVID-19 : Les gestes barrières restent essentiels</h4><!--titre pour la partie-->
                        <p>Bien que la situation s'améliore, le virus circule toujours. Continuez à respecter les gestes barrières (lavage des mains, port du masque en cas de symptômes, aération des espaces). Retrouvez toutes les informations et les centres de dépistage PCR/antigénique disponibles sur notre page dédiée à la santé publique.</p><!--messsage poiur indiquer et avec un lien avec utilisateru-->
                    </div>
                    <div class="news-item">
                        <h4><i class="fas fa-heartbeat"></i> Nouveau : Service de Téléconsultation Cardiologique</h4><!--titre pour la partie-->
                        <p>Pour un suivi régulier ou un premier avis, nos cardiologues sont désormais disponibles en téléconsultation. Gagnez du temps et bénéficiez de l'expertise de nos spécialistes depuis chez vous. Prise de rendez-vous facile via votre espace patient.</p><!--messsage poiur indiquer et avec un lien avec utilisateru-->
                    </div>
                    <div class="news-item">
                        <h4><i class="fas fa-calendar-check"></i> Séminaire sur la Santé Mentale et le Bien-être</h4><!--titre pour la partie-->
                        <p>Rejoignez-nous pour un séminaire interactif sur la gestion du stress et l'amélioration de la qualité de vie. Ce sera animé par notre équipe de psychologues spécialisés. <strong>Mardi 20 Juin à 18h</strong> en salle de conférence EM-204. Inscription gratuite et obligatoire.</p><!--messsage poiur indiquer et avec un lien avec utilisateru-->
                    </div>
                </div>

                <div class="general-photo-carousel"><!--ON va creer un carroussel de photo pour que ca soity plus beau-->
                    <h3>Découvrez Medicare en Images</h3>
                    <div class="carousel-container">
                        <div class="carousel-slides">
                            <div class="carousel-slide">
                                <img src="./images/carousel/photoCarous1.jpg" alt="Centre Médical Moderne"><!--On appel la photo sauvegarder-->
                                <p class="carousel-caption">Nos installations modernes</p><!--toujours en donnant un titre-->
                            </div>
                            <div class="carousel-slide">
                                <img src="./images/carousel/photoCarous2.jpg" alt="Accueil chaleureux"><!--On appel la photo sauvegarder encore-->
                                <p class="carousel-caption">Un accueil à votre écoute</p><!--un petit message pour expliquer -->
                            </div>
                            <div class="carousel-slide">
                                <img src="./images/carousel/photoCarous3.jpg" alt="Équipement de pointe"><!--On appel la photo sauvegarde encore -->
                                <p class="carousel-caption">Technologie de pointe</p><!--un petit message pour expliquer -->
                            </div>
                            <div class="carousel-slide">
                                <img src="./images/carousel/photoCarous4.jpg" alt="Salle d'attente confortable"><!--On appel la photo sauvegarde encore -->
                                <p class="carousel-caption">Votre confort, notre priorité</p><!--un petit message pour expliquer -->
                            </div>
                            <div class="carousel-slide">
                                <img src="./images/carousel/photoCarous5.jpg" alt="Équipe médicale dévouée"><!--On appel la photo sauvegarde encore -->
                                <p class="carousel-caption">Une équipe dédiée à votre santé</p><!--un petit message pour expliquer -->
                            </div>
                            <div class="carousel-slide">
                                <img src="./images/carousel/photoCarous6.jpg" alt="Laboratoire d'analyses"><!--On appel la photo sauvegarde encore -->
                                <p class="carousel-caption">Nos laboratoires d'analyses</p><!--un petit message pour expliquer -->
                            </div>
                            <div class="carousel-slide">
                                <img src="./images/carousel/photoCarous7.jpg" alt="Consultation en ligne"><!--On appel la photo sauvegarde encore -->
                                <p class="carousel-caption">La téléconsultation simplifiée</p><!--un petit message pour expliquer -->
                            </div>
                            <div class="carousel-slide">
                                <img src="./images/carousel/photoCarous8.jpg" alt="Soins pédiatriques"><!--On appel la photo sauvegarde encore -->
                                <p class="carousel-caption">Prendre soin de vos enfants</p><!--un petit message pour expliquer -->
                            </div>
                            <div class="carousel-slide">
                                <img src="./images/carousel/photoCarous9.jpg" alt="Salles de consultation"><!--On appel la photo sauvegarde encore -->
                                <p class="carousel-caption">Des espaces adaptés à vos besoins</p><!--un petit message pour expliquer -->
                            </div>
                            <div class="carousel-slide">
                                <img src="./images/carousel/photoCarous10.jpg" alt="Innovation médicale"><!--On appel la photo sauvegarde encore -->
                                <p class="carousel-caption">L'innovation au service de votre bien-être</p><!--un petit message pour expliquer -->
                            </div>
                        </div>
                        <button class="carousel-nav-btn prev"><</button><!--pour les boutons on fait un precedent pour aller sur la page precedente -->
                        <button class="carousel-nav-btn next">></button><!--et la un pour pouvoir aller sur le truc juste avant-->
                        <div class="carousel-indicators"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="info-section"><!--la on change de partiee pour le visuel-->
        <div class="info-card">
            <h3>Urgence</h3><!--la partie avec toutes les inforamtions du site-->
            <p>Appelez-nous pour toute urgence médicale 24/7.</p><!--un petit message pour expliquer -->
            <p><strong>+33 1 23 45 67 89</strong></p><!--indiquer le njmero a appekler -->
        </div>
        <div class="info-card">
            <h3>Emploi du temps</h3><!--in endroti pour l enploi du temps-->
            <p>Consultez les disponibilités des médecins et réservez vos créneaux facilement.</p><!--un petit message pour expliquer ce qu il faut faire-->
            <a href="parcourir.php">Voir les horaires</a><!--retour a la page parcourit pour voir les goraire-->
        </div>
    </section>

    <?php require 'includes/footer.php'; ?><!--on garde toujours le footer en bas de la page -->

    <script>
        document.addEventListener('DOMContentLoaded', function() {//ON VERIFIE ICI SI TOUT EST BON
            const carouselContainer = document.querySelector('.carousel-container');//ON CHOSITI NOTRE CARROUSEL
            if (carouselContainer) { //ON REGARDE SI IL EXISTE BIEN
                const carouselSlides = carouselContainer.querySelector('.carousel-slides');//LZS SLIDES DU CARRROUSSEK
                const slides = Array.from(carouselSlides.children);
                const prevBtn = carouselContainer.querySelector('.carousel-nav-btn.prev');//FAIT APPEL AU BOUTON POUR ALLER SUR LE TUCS AVANTT
                const nextBtn = carouselContainer.querySelector('.carousel-nav-btn.next');//ET LA POUR ALLER SUR LE TRUC APRES
                const indicatorsContainer = carouselContainer.querySelector('.carousel-indicators');

                let currentIndex = 0;//LA SLIDE ON INIT
                let slideWidth = slides[0].clientWidth; //LA TAILLE DE LA DKAPOSITIVE

                function updateSlideWidth() {//ON EUT METTRE A JOUR
                    if (slides.length > 0) {
                        slideWidth = slides[0].clientWidth;//ACTUALISATION
                        showSlide(currentIndex); //ON REMET AVEC LES BONNEES DIMENSIONS
                    }
                }
                updateSlideWidth(); //POUR REMETTREN A JOUR LES INFOS
                window.addEventListener('resize', updateSlideWidth);//ON FAIT DES REAJUSTEMENT POUR LES PHOTOS

                slides.forEach((_, i) => {//POUR METTRE DES POINTS EN BAS
                    const indicator = document.createElement('div');
                    indicator.classList.add('indicator');
                    if (i === 0) indicator.classList.add('active');
                    indicator.addEventListener('click', () => showSlide(i));//ON APPUIE POUR ALLER A LA SUIVANTE
                    indicatorsContainer.appendChild(indicator);
                });
                const indicators = Array.from(indicatorsContainer.children);//CE QUI PERMET D ACTIVER OU DESACTIVER

                function showSlide(index) {//POUR LE DEPLACEMENT DES IMAGES
                    if (index >= slides.length) {
                        currentIndex = 0;//ON INIT A 0
                    } else if (index < 0) {
                        currentIndex = slides.length - 1; //POUR CREER BOUCLE SANS IN EN REVENANT AU DEBUT
                    } else {
                        currentIndex = index;//SINON ON FAIT NORMALMENT EN ALLANT A LA SIUVANTE
                    }

                    carouselSlides.style.transform = `translateX(-${currentIndex * slideWidth}px)`;//POUR QUE CA AILLE DE GAUCHE A DROITRE ET PAS DE BAS EN HAUT

                    indicators.forEach((ind, i) => {
                        if (i === currentIndex) {//SI C EST LA BONNE IMAGE QUI EST MONTRE
                            ind.classList.add('active');//ON ACTIVE
                        } else {//SINON
                            ind.classList.remove('active');//ON ENLEVE
                        }
                    });
                }

                prevBtn.addEventListener('click', () => showSlide(currentIndex - 1));//FAIR ELE BOUTON POUR DIMIBUER
                nextBtn.addEventListener('click', () => showSlide(currentIndex + 1));//FAIRE AUSSI POUR POUVOIR AUGMENETR 

                showSlide(currentIndex);

+                let autoPlayInterval = setInterval(() => showSlide(currentIndex + 1), 5000); //les imagess changent toutes les 5 seconds

                carouselContainer.addEventListener('mouseenter', () => clearInterval(autoPlayInterval));
                carouselContainer.addEventListener('mouseleave', () => {
                    autoPlayInterval = setInterval(() => showSlide(currentIndex + 1), 5000);
                });
            }
        });
    </script>
</body>
</html>


<style>

.event-bulletin-section {
    padding: 2.5rem;
    background-color:rgb(248, 248, 248);
    display: flex;
    justify-content: center;
    align-items: flex-start;
    margin-bottom: 2rem; 
    border-bottom: 1px solidrgb(224, 224, 224);
}

.event-bulletin-container {
    max-width: 1200px;
    width: 100%;
    margin: auto;
    background: #fff; 
    padding: 2.5rem;
    border-radius: 10px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    box-sizing: border-box;
    text-align: center;
}

.event-bulletin-container .section-title {
    color:rgb(10, 122, 191); 
    margin-bottom: 1.5rem;
    font-size: 2.2rem;
    font-weight: 700;
    text-align: center;
}

.intro-text {
    font-size: 1.15rem;
    color: #555;
    margin-bottom: 2.5rem;
    line-height: 1.6;
}

.content-wrapper {
    display: flex;
    flex-wrap: wrap;
    gap: 2rem; 
    justify-content: center;
    align-items: flex-start; 
}

.health-news-block {
    flex: 2; 
    min-width: 350px; 
    background-color:rgb(240, 247, 255); 
    border: 1px solidrgb(204, 224, 255);
    border-radius: 8px;
    padding: 1.5rem;
    text-align: left;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.health-news-block h3 {
    color:rgb(0, 86, 179);
    font-size: 1.5rem;
    margin-top: 0;
    margin-bottom: 1.5rem;
    border-bottom: 1px dashedrgb(168, 207, 255);
    padding-bottom: 0.8rem;
}

.news-item {
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px dottedrgb(209, 231, 255);
}
.news-item:last-child {
    border-bottom: none; 
    margin-bottom: 0;
    padding-bottom: 0;
}

.news-item h4 {
    color:rgb(10, 122, 191);
    font-size: 1.1rem;
    margin-top: 0;
    margin-bottom: 0.5rem;
}

.news-item i { 
    margin-right: 8px;
    color:rgb(0, 123, 255);
}

.news-item p {
    font-size: 0.95rem;
    color: #444;
    line-height: 1.5;
}

.btn-more-news {
    display: inline-block;
    margin-top: 1.5rem;
    padding: 10px 20px;
    background-color:rgb(10, 122, 191);
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-weight: 500;
    transition: background-color 0.3s ease;
}
.btn-more-news:hover {
    background-color:rgb(7, 92, 146);
}

.general-photo-carousel {
    flex: 1; 
    min-width: 300px;
    background-color:white;
    border: 1px solid white;
    border-radius: 8px;
    padding: 1.5rem;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.general-photo-carousel h3 {
    color:rgb(0, 123, 255);
    font-size: 1.5rem;
    margin-top: 0;
    margin-bottom: 1.5rem;
    border-bottom: 1px dashedrgb(184, 218, 255);
    padding-bottom: 0.8rem;
}

.carousel-container {
    position: relative;
    width: 100%;
    overflow: hidden; 
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

.carousel-slides {
    display: flex;
    transition: transform 0.5s ease-in-out;
}

.carousel-slide {
    min-width: 100%; 
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    background-color: #fff;
    flex-shrink: 0; 
}

.carousel-slide img {
    max-width: 100%;
    max-height: 250px; 
    height: auto;
    width: auto;
    border-radius: 8px; 
    border: 1px solid #ddd; 
    margin-bottom: 1rem;
    object-fit: cover; 
    aspect-ratio: 16 / 9; 
}

.carousel-caption {
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
    margin: 0;
    line-height: 1.4;
}

.carousel-nav-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background-color: rgba(0, 0, 0, 0.5);
    color: white;
    border: none;
    padding: 10px 15px;
    cursor: pointer;
    font-size: 1.5rem;
    border-radius: 50%;
    z-index: 10;
    transition: background-color 0.3s ease;
}

.carousel-nav-btn:hover {
    background-color: rgba(0, 0, 0, 0.7);
}

.carousel-nav-btn.prev {
    left: 10px;
}

.carousel-nav-btn.next {
    right: 10px;
}

.carousel-indicators {
    position: absolute;
    bottom: 10px;
    width: 100%;
    display: flex;
    justify-content: center;
    gap: 8px;
    z-index: 10;
}

.indicator {
    width: 12px;
    height: 12px;
    background-color: rgba(255, 255, 255, 0.6);
    border: 1px solid rgba(0, 0, 0, 0.3);
    border-radius: 50%;
    cursor: pointer;
    transition: background-color 0.3s ease, border-color 0.3s ease;
}

.indicator.active {
    background-color:rgb(10, 122, 191);
    border-color:rgb(10, 122, 191);
}


@media (max-width: 992px) { 
    .content-wrapper {
        flex-direction: column; 
        gap: 1.5rem;
    }
    .health-news-block,
    .general-photo-carousel {
        flex: none; 
        width: 100%; 
        max-width: none;
    }
    .event-bulletin-container {
        padding: 1.5rem;
    }
    .general-photo-carousel img {
        max-height: 200px; 
    }
}

@media (max-width: 480px) {
    .event-bulletin-container .section-title {
        font-size: 1.8rem;
    }
    .general-photo-carousel img {
        max-height: 150px; 
    }
}
</style>