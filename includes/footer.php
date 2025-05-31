<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Carte avec Leaflet</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <style>
        /* css pour le footer de la carte*/
         footer {
            background-color: #0a7abf;
            color: white;
            padding: 2rem 1rem;
            text-align: center;
        }

        .footer-container {
            max-width: 1100px; 
            margin: 0 auto; 
        }


        .footer-contact p{
            margin: 0.4rem 0; /*pour réduire les espacement entre les lignes de contact */
            font-size: 0.95rem;
        }


        .footer-contact a{
            color: white;
            text-decoration:none;
        }

        .footer-contact a:hover{
            text-decoration: underline;
        }

        /pour le style de la carte/
        #map{
            height: 200px; 
            width: 100%;
            max-width: 500px; 
            margin: 1rem auto; 
            border-radius: 6px; 
            border: 1px solid #086aa3; 
        }

        /lien du bas de page/
        .footer-links {
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #0f8bcd;/* Séparateur  */
        }

        .footer-links ul {
            list-style:none;
            padding: 0;
            margin: 0;
            display: flex;
            justify-content:center;
            flex-wrap: wrap; 
            gap: 10px 20px;
        }

        .footer-links li a {
            color: #e0f2ff; 
            text-decoration: none;
            font-size: 0.9rem;
            padding: 5px 8px;
            border-radius: 4px;
            transition: background-color 0.2s ease;
        }

        .footer-links li a:hover{
            background-color: rgba(255, 255, 255, 0.1);
            text-decoration: none; 
            color: white;
        }

        .footer-copyright{
            margin-top: 1.5rem;
            font-size: 0.85rem;
            color: #cce7ff;
        }
    </style>
</head>
<body>

<footer>
    <div class="footer-container">
        <!--info de contact-->
        <div class="footer-contact">
            <p>Contactez-nous:<a href="mailto:contact@medicare.omnes">contact@medicare.omnes</a></p>
            <p>Téléphone:<a href="tel:0123456789">01 23 45 67 89</a></p>
            <p>Adresse:10 rue des Médecins, Paris</p>

        </div>
<!--bloc pour la carte interactive Leaflet-->

        <div id="map"></div>
<!--source: https://leafletjs.com -->

        <div class="footer-links">
            <ul>
                <li><a href="conditions_utilisation.php">Conditions d'utilisation</a></li>
                <li><a href="mentions_legales.php">Mentions légales</a></li>
                <li><a href="politique_confidentialite.php">Politique de confidentialité</a></li>
                <li><a href="faq.php">FAQ</a></li>
          </ul>
        </div>

<!-- copyright avec année dynamique --> 
        <div class="footer-copyright">
            <p>© <?php echo date("Y"); ?> Medicare. Tous droits réservés.</p>
        </div>
    </div>

</footer>

<!--import de la bibli JS de Leaflet( librairie de carte interactive ) -->
<!-- source : https://www.openstreetmap.org -->
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
    //on verifie que l'element mamp existe avant de continuer
    if (document.getElementById('map')){
        const map=L.map('map').setView([48.84702, 2.35617],17);//initialisation de la carte centrée sur paris
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution:'© OpenStreetMap contributors'//ajout de la carte de fond
        }).addTo(map);

        L.marker([48.84702, 2.35617]).addTo(map)//ajout du marqueur avec une bulle d'info
            .bindPopup('<strong>Medicare</strong><br>10 rue des Médecins, Paris')
            .openPopup();//ouvre le popup directement
    }
</script>

</body>
</html>