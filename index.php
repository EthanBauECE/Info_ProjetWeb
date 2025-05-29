<!DOCTYPE html>
<html lang="fr">

<!-- Importation du head -->
<?php require 'includes/head.php'; ?>

<body>

    <!-- Importation du header -->
    <?php require 'includes/header.php'; ?>

    <!-- Présentation site -->
    <section class="hero">
        <div class="hero-content">
            <h1>Welcome to Medical Services You Can Trust</h1>
            <p>Votre santé est notre priorité. Prenez rendez-vous dès maintenant avec l’un de nos spécialistes.</p>
            <div class="hero-buttons">
                <a href="register.php">Prendre RDV</a>
                <a href="parcourir.php">Voir Emploi du Temps</a>
            </div>
        </div>
    </section>

    <!-- NOUVEAU : Section Événement / Bulletin de Santé de la Semaine -->
    <section class="event-bulletin-section">
        <div class="event-bulletin-container">
            <h2 class="section-title">Événements & Bulletin de Santé de la Semaine</h2>

            <p class="intro-text">Découvrez les activités importantes de Medicare, les dernières nouvelles en matière de santé publique et parcourez nos installations en images.</p>

            <div class="content-wrapper">
                <!-- Bloc pour les actualités ou événements principaux -->
                <div class="health-news-block">
                    <h3>Actualités & Infos Santé</h3>
                    <div class="news-item">
                        <h4><i class="fas fa-calendar-alt"></i> Porte Ouverte Medicare : Innovation et Bien-être !</h4>
                        <p>Participez à notre journée portes ouvertes le <strong>Samedi 10 Juin de 9h à 17h</strong>. Explorez nos nouvelles salles de consultation, rencontrez nos équipes et découvrez les dernières avancées technologiques en médecine. Des mini-conférences sur la prévention des maladies chroniques et des démonstrations de nos équipements seront organisées. Venez nombreux !</p>
                    </div>
                    <div class="news-item">
                        <h4><i class="fas fa-medkit"></i> Campagne de Don du Sang Urgente</h4>
                        <p>En collaboration avec l'Établissement Français du Sang (EFS), Medicare organise une collecte de sang exceptionnelle le <strong>Jeudi 15 Juin de 10h à 18h</strong>. Chaque don compte et peut sauver jusqu'à trois vies. Votre générosité est plus que jamais nécessaire. Prenez rendez-vous sur notre site ou présentez-vous directement. Merci de votre engagement !</p>
                    </div>
                    <div class="news-item">
                        <h4><i class="fas fa-virus"></i> Bulletin COVID-19 : Les gestes barrières restent essentiels</h4>
                        <p>Bien que la situation s'améliore, le virus circule toujours. Continuez à respecter les gestes barrières (lavage des mains, port du masque en cas de symptômes, aération des espaces). Retrouvez toutes les informations et les centres de dépistage PCR/antigénique disponibles sur notre page dédiée à la santé publique.</p>
                    </div>
                    <div class="news-item">
                        <h4><i class="fas fa-heartbeat"></i> Nouveau : Service de Téléconsultation Cardiologique</h4>
                        <p>Pour un suivi régulier ou un premier avis, nos cardiologues sont désormais disponibles en téléconsultation. Gagnez du temps et bénéficiez de l'expertise de nos spécialistes depuis chez vous. Prise de rendez-vous facile via votre espace patient.</p>
                    </div>
                    <div class="news-item">
                        <h4><i class="fas fa-calendar-check"></i> Séminaire sur la Santé Mentale et le Bien-être</h4>
                        <p>Rejoignez-nous pour un séminaire interactif sur la gestion du stress et l'amélioration de la qualité de vie, animé par notre équipe de psychologues. <strong>Mardi 20 Juin à 18h</strong> en salle de conférence EM-204. Inscription gratuite et obligatoire.</p>
                    </div>
                </div>

                <!-- Carrousel de photos générales -->
                <div class="general-photo-carousel">
                    <h3>Découvrez Medicare en Images</h3>
                    <div class="carousel-container">
                        <div class="carousel-slides">
                            <div class="carousel-slide">
                                <img src="./images/carousel/photoCarous1.jpg" alt="Centre Médical Moderne">
                                <p class="carousel-caption">Nos installations modernes</p>
                            </div>
                            <div class="carousel-slide">
                                <img src="./images/carousel/photoCarous2.jpg" alt="Accueil chaleureux">
                                <p class="carousel-caption">Un accueil à votre écoute</p>
                            </div>
                            <div class="carousel-slide">
                                <img src="./images/carousel/photoCarous3.jpg" alt="Équipement de pointe">
                                <p class="carousel-caption">Technologie de pointe</p>
                            </div>
                            <div class="carousel-slide">
                                <img src="./images/carousel/photoCarous4.jpg" alt="Salle d'attente confortable">
                                <p class="carousel-caption">Votre confort, notre priorité</p>
                            </div>
                            <div class="carousel-slide">
                                <img src="./images/carousel/photoCarous5.jpg" alt="Équipe médicale dévouée">
                                <p class="carousel-caption">Une équipe dédiée à votre santé</p>
                            </div>
                            <div class="carousel-slide">
                                <img src="./images/carousel/photoCarous6.jpg" alt="Laboratoire d'analyses">
                                <p class="carousel-caption">Nos laboratoires d'analyses</p>
                            </div>
                            <div class="carousel-slide">
                                <img src="./images/carousel/photoCarous7.jpg" alt="Consultation en ligne">
                                <p class="carousel-caption">La téléconsultation simplifiée</p>
                            </div>
                            <div class="carousel-slide">
                                <img src="./images/carousel/photoCarous8.jpg" alt="Soins pédiatriques">
                                <p class="carousel-caption">Prendre soin de vos enfants</p>
                            </div>
                            <div class="carousel-slide">
                                <img src="./images/carousel/photoCarous9.jpg" alt="Salles de consultation">
                                <p class="carousel-caption">Des espaces adaptés à vos besoins</p>
                            </div>
                            <div class="carousel-slide">
                                <img src="./images/carousel/photoCarous10.jpg" alt="Innovation médicale">
                                <p class="carousel-caption">L'innovation au service de votre bien-être</p>
                            </div>
                        </div>
                        <button class="carousel-nav-btn prev"><</button>
                        <button class="carousel-nav-btn next">></button>
                        <div class="carousel-indicators"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- FIN NOUVEAU : Section Événement / Bulletin de Santé de la Semaine -->

    <!-- Panneaux d'informations -->
    <section class="info-section">
        <div class="info-card">
            <h3>Urgence</h3>
            <p>Appelez-nous immédiatement pour toute urgence médicale 24/7.</p>
            <p><strong>+33 1 23 45 67 89</strong></p>
        </div>
        <div class="info-card">
            <h3>Emploi du temps</h3>
            <p>Consultez les disponibilités des médecins et réservez vos créneaux facilement.</p>
            <a href="parcourir.php">Voir les horaires</a>
        </div>
    </section>

    <?php require 'includes/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const carouselContainer = document.querySelector('.carousel-container');
            if (carouselContainer) { // Vérifie si le carrousel existe sur la page
                const carouselSlides = carouselContainer.querySelector('.carousel-slides');
                const slides = Array.from(carouselSlides.children);
                const prevBtn = carouselContainer.querySelector('.carousel-nav-btn.prev');
                const nextBtn = carouselContainer.querySelector('.carousel-nav-btn.next');
                const indicatorsContainer = carouselContainer.querySelector('.carousel-indicators');

                let currentIndex = 0;
                let slideWidth = slides[0].clientWidth; // Largeur d'une slide

                // Mettre à jour la largeur de slide au chargement et au redimensionnement
                function updateSlideWidth() {
                    if (slides.length > 0) {
                        slideWidth = slides[0].clientWidth;
                        showSlide(currentIndex); // Réajuster la position
                    }
                }
                updateSlideWidth(); // Appel initial
                window.addEventListener('resize', updateSlideWidth); // Gérer le redimensionnement

                // Créer les indicateurs
                slides.forEach((_, i) => {
                    const indicator = document.createElement('div');
                    indicator.classList.add('indicator');
                    if (i === 0) indicator.classList.add('active');
                    indicator.addEventListener('click', () => showSlide(i));
                    indicatorsContainer.appendChild(indicator);
                });
                const indicators = Array.from(indicatorsContainer.children);

                // Fonction pour montrer une slide spécifique
                function showSlide(index) {
                    // Si l'index dépasse les limites, revenir au début ou à la fin
                    if (index >= slides.length) {
                        currentIndex = 0;
                    } else if (index < 0) {
                        currentIndex = slides.length - 1;
                    } else {
                        currentIndex = index;
                    }

                    // Déplacer les slides
                    carouselSlides.style.transform = `translateX(-${currentIndex * slideWidth}px)`;

                    // Mettre à jour les indicateurs actifs
                    indicators.forEach((ind, i) => {
                        if (i === currentIndex) {
                            ind.classList.add('active');
                        } else {
                            ind.classList.remove('active');
                        }
                    });
                }

                // Gérer les boutons Précédent/Suivant
                prevBtn.addEventListener('click', () => showSlide(currentIndex - 1));
                nextBtn.addEventListener('click', () => showSlide(currentIndex + 1));

                // Initialiser la première slide
                showSlide(currentIndex);

                // Optionnel: Auto-play
                let autoPlayInterval = setInterval(() => showSlide(currentIndex + 1), 5000); // Change de slide toutes les 5 secondes

                // Pause auto-play au survol et reprise à la sortie
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

/* --- NOUVEAU : Styles pour la Section Événement / Bulletin de Santé --- */
.event-bulletin-section {
    padding: 2.5rem;
    background-color: #f8f8f8; /* Fond légèrement différent du blanc pur pour la section */
    display: flex;
    justify-content: center;
    align-items: flex-start;
    margin-bottom: 2rem; /* Espace avant la section suivante */
    border-bottom: 1px solid #e0e0e0;
}

.event-bulletin-container {
    max-width: 1200px; /* Largeur maximale du contenu */
    width: 100%;
    margin: auto;
    background: #fff; /* Fond blanc pour le conteneur interne */
    padding: 2.5rem;
    border-radius: 10px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    box-sizing: border-box;
    text-align: center;
}

.event-bulletin-container .section-title {
    color: #0a7abf; /* Couleur principale du thème */
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
    flex-wrap: wrap; /* Permet aux blocs de passer à la ligne sur mobile */
    gap: 2rem; /* Espace entre les deux colonnes (Actualités et Carrousel) */
    justify-content: center;
    align-items: flex-start; /* Align les éléments en haut */
}

/* Bloc des actualités/événements textuels */
.health-news-block {
    flex: 2; /* Prend plus d'espace que le carrousel (2/3 vs 1/3) */
    min-width: 350px; /* Largeur minimale avant de passer à la ligne */
    background-color: #f0f7ff; /* Fond bleu clair */
    border: 1px solid #cce0ff;
    border-radius: 8px;
    padding: 1.5rem;
    text-align: left;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.health-news-block h3 {
    color: #0056b3; /* Bleu foncé pour le titre */
    font-size: 1.5rem;
    margin-top: 0;
    margin-bottom: 1.5rem;
    border-bottom: 1px dashed #a8cfff;
    padding-bottom: 0.8rem;
}

.news-item {
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px dotted #d1e7ff; /* Séparateur léger entre les actus */
}
.news-item:last-child {
    border-bottom: none; /* Pas de séparateur après le dernier élément */
    margin-bottom: 0;
    padding-bottom: 0;
}

.news-item h4 {
    color: #0a7abf;
    font-size: 1.1rem;
    margin-top: 0;
    margin-bottom: 0.5rem;
}

.news-item i { /* Icônes Font Awesome si utilisées */
    margin-right: 8px;
    color: #007bff;
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
    background-color: #0a7abf;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-weight: 500;
    transition: background-color 0.3s ease;
}
.btn-more-news:hover {
    background-color: #075c92;
}


/* Styles du Carrousel de Photos Générales */
.general-photo-carousel {
    flex: 1; /* Prend 1/3 de l'espace */
    min-width: 300px; /* Largeur minimale */
    background-color: #fdfdfd;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 1.5rem;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.general-photo-carousel h3 {
    color: #007bff;
    font-size: 1.5rem;
    margin-top: 0;
    margin-bottom: 1.5rem;
    border-bottom: 1px dashed #b8daff;
    padding-bottom: 0.8rem;
}

.carousel-container {
    position: relative;
    width: 100%;
    overflow: hidden; /* Cache ce qui dépasse */
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

.carousel-slides {
    display: flex;
    transition: transform 0.5s ease-in-out; /* Animation de transition */
}

.carousel-slide {
    min-width: 100%; /* Chaque slide prend 100% de la largeur du conteneur */
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    background-color: #fff;
    flex-shrink: 0; /* Empêche les slides de rétrécir */
}

.carousel-slide img {
    /* Les images ici sont des photosCarous, qui ne sont pas nécessairement rondes. */
    /* On les laisse rectangulaires mais bien contenues dans leur espace. */
    max-width: 100%;
    max-height: 250px; /* Limiter la hauteur des images pour qu'elles ne soient pas trop grandes */
    height: auto;
    width: auto; /* Permet à l'image de conserver son ratio */
    border-radius: 8px; /* Coins légèrement arrondis pour les images */
    border: 1px solid #ddd; /* Bordure subtile */
    margin-bottom: 1rem;
    object-fit: cover; /* Recouvre le cadre en coupant si nécessaire */
    aspect-ratio: 16 / 9; /* Si toutes tes images sont 16:9, ça les rendra uniformes */
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
    background-color: #0a7abf;
    border-color: #0a7abf;
}

/* Media Queries pour la responsivité */
@media (max-width: 992px) { /* Ajusté le breakpoint pour la flex wrap */
    .content-wrapper {
        flex-direction: column; /* Les blocs s'empilent sur les écrans intermédiaires */
        gap: 1.5rem;
    }
    .health-news-block,
    .general-photo-carousel {
        flex: none; /* Annule le flex: 1 */
        width: 100%; /* Prend toute la largeur disponible */
        max-width: none; /* Annule le max-width si défini */
    }
    .event-bulletin-container {
        padding: 1.5rem;
    }
    .general-photo-carousel img {
        max-height: 200px; /* Un peu moins haut sur les écrans intermédiaires */
    }
}

@media (max-width: 480px) {
    .event-bulletin-container .section-title {
        font-size: 1.8rem;
    }
    .general-photo-carousel img {
        max-height: 150px; /* Encore moins haut sur les très petits écrans */
    }
}
</style>