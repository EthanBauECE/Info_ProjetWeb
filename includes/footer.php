<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Carte avec Leaflet</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <style>
        #map {
            height: 300px;
            width: 100%;
            margin-top: 10px;
        }
    </style>
</head>
<body>

<footer>
    <p>Contactez-nous : contact@medicare.omnes</p>
    <p>Téléphone : 01 23 45 67 89</p>
    <p>Adresse : 10 rue des Médecins, Paris</p>
    <div id="map"></div>
</footer>

<!-- Script Leaflet -->
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
    const map = L.map('map').setView([48.84702, 2.35617], 17); // 10 rue des Médecins, Paris

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    L.marker([48.84702, 2.35617]).addTo(map)
        .bindPopup('10 rue des Médecins, Paris')
        .openPopup();
</script>

</body>
</html>
