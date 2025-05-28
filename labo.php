<!DOCTYPE html>

<!-- Importation du head -->
<?php require 'includes/head.php'; ?>

<body>

    <!-- Importation du header -->
    <?php require 'includes/header.php'; ?>

    <main style="padding: 2rem; background-color: #f9f9f9;">

        <!-- Exemple de fiche médecin -->
        <section id="alice-dupont" class="info-card" style="background-color: #ffffff;">
            <h2>Dr. Alice Dupont</h2>
            <img src="alice_dupont.jpg" alt="Dr. Alice Dupont"
                style="width: 200px; border-radius: 8px; margin: 1rem 0;" />
            <p><strong>Bureau :</strong> Bâtiment A, Bureau 203</p>
            <h3>Disponibilité cette semaine :</h3>
            <ul>
                <li>Lundi : 09h00 - 13h00</li>
                <li>Mardi : 14h00 - 18h00</li>
                <li>Mercredi : 09h00 - 12h00</li>
                <li>Jeudi : 14h00 - 17h00</li>
                <li>Vendredi : 10h00 - 13h00</li>
            </ul>

            <h3>CV :</h3>
            <p>Diplômée de l’Université de Paris Descartes. 10 ans d'expérience en médecine générale. Spécialisée en
                suivi de patients chroniques et prévention.</p>

            <h3>Contact :</h3>
            <p>Ce médecin est disponible.</p>
            <ul>
                <li><a href="#">Envoyer un message texte</a></li>
                <li><a href="#">Envoyer un message vocal</a></li>
                <li><a href="#">Prendre contact sur place</a></li>
            </ul>
        </section>

        <!-- Autres fiches en exemple -->
        <section id="marc-bernard" class="info-card" style="background-color: #ffffff;">
            <h2>Dr. Marc Bernard</h2>
            <p>Voir les détails bientôt disponibles.</p>
        </section>

        <section id="emma-robitaille" class="info-card" style="background-color: #ffffff;">
            <h2>Dr. Emma Robitaille</h2>
            <p>Voir les détails bientôt disponibles.</p>
        </section>

    </main>

    <!-- Importation du footer -->
    <?php require 'includes/footer.php'; ?>

</body>

</html>