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

    <!-- Panneaux d'informations -->
    <section class="info-section">
        <div class="info-card">
            <h3>Urgence</h3>
            <p>Appelez-nous immédiatement pour toute urgence médicale 24/7.</p>
            <p><strong>+33 1 23 45 67 89</strong></p>
            <a href="urgence.php">En savoir plus</a>
        </div>
        <div class="info-card">
            <h3>Emploi du temps</h3>
            <p>Consultez les disponibilités des médecins et réservez vos créneaux facilement.</p>
            <a href="parcourir.php">Voir les horaires</a>
        </div>
    </section>

    <?php require 'includes/footer.php'; ?>

</body>

</html>