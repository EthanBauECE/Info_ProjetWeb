<!DOCTYPE html>

<!-- Importation du head -->
<?php require 'includes/head.php'; ?>

<body>

    <!-- Importation du header -->
    <?php require 'includes/header.php'; ?>

    <main style="padding: 2rem; background-color: #f9f9f9;">
        <section class="info-section">
            <div class="info-card">
                <h3>1. Médecine générale</h3>
                <p>Consultez la liste complète de nos médecins généralistes, leur disponibilité, leur CV, et prenez
                    rendez-vous directement.</p>
                <a href="medecine_general.php">Explorer Médecine générale</a>
            </div>

            <div class="info-card">
                <h3>2. Médecins spécialistes</h3>
                <p>Découvrez nos spécialistes dans plusieurs domaines : cardiologie, dermatologie, pédiatrie, etc.</p>
                <a href="medecins_special.php">Explorer Médecins spécialistes</a>
            </div>

            <div class="info-card">
                <h3>3. Laboratoire de biologie médicale</h3>
                <p>Accès aux services de prélèvement, analyses biologiques, bilans complets avec suivi de vos résultats.
                </p>
                <a href="labo.php">Explorer Laboratoire</a>
            </div>
        </section>
    </main>

    <!-- Importation du footer -->
    <?php require 'includes/footer.php'; ?>

</body>

</html>