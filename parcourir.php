<!DOCTYPE html>

<?php require 'includes/head.php'; ?><!--mettre le haut de la page pour que ca soit beua-->

<body>
    <?php require 'includes/header.php'; ?>

    <main style="padding: 2rem; background-color: #f9f9f9;"><!--ici c'est pour quand on est sur le bouton parcourir pour voir ce qu il propose-->
        <section class="info-section">
            <div class="info-card"><!--la premiere proposition qu il y a le medecin generaliste-->
                <h3>1. Médecine générale</h3>
                <p>Consultez la liste complète de nos médecins généralistes, leur disponibilité, leur CV, et prenez
                    rendez-vous directement.</p>
                <a href="medecine_general.php">Explorer Médecine générale</a>
            </div>

            <div class="info-card"><!--la deuxieme proposition c'est de pouvoir choisir uin specialisé-->
                <h3>2. Médecins spécialistes</h3><!--l'indiquer avec un titre pour utilisateur-->
                <p>Découvrez nos spécialistes dans plusieurs domaines : cardiologie, dermatologie, pédiatrie, etc.</p>
                <a href="medecins_special.php">Explorer Médecins spécialistes</a><!--la page pour les specialisés-->
            </div>

            <div class="info-card">
                <h3>3. Laboratoire de biologie médicale</h3><!--ou aussi pouvoir choisir un laboratoire pour client-->
                <p>Accès aux services de prélèvement, analyses biologiques, bilans complets avec suivi de vos résultats.
                </p>
                <a href="labo.php">Explorer Laboratoire</a><!--la page pour les labo-->
            </div>
        </section>
    </main>


    <?php require 'includes/footer.php'; ?><!--laissser le nbas de la pasge pour esthetique-->

</body>

</html>