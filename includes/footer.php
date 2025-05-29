<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Carte avec Leaflet</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <style>
        /* Styles pour le footer. Certains peuvent déjà exister dans style.txt */
        /* Si c'est le cas, ceux-ci peuvent être plus spécifiques ou surcharger. */
        footer {
            background-color: #0a7abf; /* Couleur de fond principale */
            color: white;
            padding: 2rem 1rem; /* Padding ajusté pour un peu moins d'espace vertical */
            text-align: center;
        }

        .footer-container {
            max-width: 1100px; /* Limite la largeur du contenu du footer */
            margin: 0 auto; /* Centre le contenu */
        }

        .footer-contact p {
            margin: 0.4rem 0; /* Espacement réduit entre les lignes de contact */
            font-size: 0.95rem;
        }

        .footer-contact a {
            color: white;
            text-decoration: none;
        }
        .footer-contact a:hover {
            text-decoration: underline;
        }

        #map {
            height: 200px; /* TAILLE DE LA CARTE RÉDUITE */
            width: 100%;
            max-width: 500px; /* Limite la largeur max de la carte pour ne pas qu'elle soit trop imposante */
            margin: 1rem auto; /* Centrer la carte et ajouter de la marge */
            border-radius: 6px; /* Coins légèrement arrondis */
            border: 1px solid #086aa3; /* Petite bordure discrète */
        }

        .footer-links {
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #0f8bcd; /* Séparateur discret */
        }

        .footer-links ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            justify-content: center;
            flex-wrap: wrap; /* Permet aux liens de passer à la ligne sur petits écrans */
            gap: 10px 20px; /* Espace vertical et horizontal entre les liens */
        }

        .footer-links li a {
            color: #e0f2ff; /* Couleur de lien légèrement plus claire pour se distinguer un peu */
            text-decoration: none;
            font-size: 0.9rem;
            padding: 5px 8px;
            border-radius: 4px;
            transition: background-color 0.2s ease;
        }

        .footer-links li a:hover {
            background-color: rgba(255, 255, 255, 0.1);
            text-decoration: none; /* Garder sans soulignement au survol pour un look plus 'bouton' */
            color: white;
        }

        .footer-copyright {
            margin-top: 1.5rem;
            font-size: 0.85rem;
            color: #cce7ff; /* Couleur discrète pour le copyright */
        }
    </style>
</head>
<body>

<footer>
    <div class="footer-container">
        <div class="footer-contact">
            <p>Contactez-nous : <a href="mailto:contact@medicare.omnes">contact@medicare.omnes</a></p>
            <p>Téléphone : <a href="tel:0123456789">01 23 45 67 89</a></p>
            <p>Adresse : 10 rue des Médecins, Paris</p>
        </div>

        <div id="map"></div>

        <div class="footer-links">
            <ul>
                <li><a href="conditions_utilisation.php">Conditions d'utilisation</a></li>
                <li><a href="mentions_legales.php">Mentions légales</a></li>
                <li><a href="politique_confidentialite.php">Politique de confidentialité</a></li>
                <li><a href="faq.php">FAQ</a></li>
                <!-- Tu peux ajouter d'autres liens ici si besoin -->
            </ul>
        </div>

        <div class="footer-copyright">
            <p>© <?php echo date("Y"); ?> Medicare. Tous droits réservés.</p>
        </div>
    </div>
</footer>

<!-- Script Leaflet -->
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
    // On vérifie que l'élément map existe avant d'initialiser
    if (document.getElementById('map')) {
        const map = L.map('map').setView([48.84702, 2.35617], 17); // 10 rue des Médecins, Paris

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        L.marker([48.84702, 2.35617]).addTo(map)
            .bindPopup('<strong>Medicare</strong><br>10 rue des Médecins, Paris')
            .openPopup();
    }
</script>

</body>
</html>