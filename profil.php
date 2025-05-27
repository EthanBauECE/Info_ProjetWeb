<?php
// On démarre la session en premier, car on en a besoin tout de suite.
// Votre header.php le fait aussi, ce qui est redondant mais pas grave pour le moment.
// L'important est que ce soit fait AVANT de vérifier la session.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si la variable de session 'user_id' n'existe pas, cela signifie que
// l'utilisateur n'est pas connecté. On le redirige immédiatement.
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit(); // On arrête le script ici pour ne pas charger le reste de la page.
}

// Si on arrive ici, c'est que l'utilisateur est bien connecté.
// On peut maintenant commencer à construire la page HTML.
?>
<!DOCTYPE html>
<html lang="fr">

<?php require 'includes/head.php'; ?>

<body>

    <?php require 'includes/header.php'; ?>

    <main style="padding: 2rem; background-color: #f9f9f9;">
        <div style="max-width: 700px; margin: auto; background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            
            <h2 style="text-align: center; color: #0a7abf; margin-bottom: 2rem;">Mon Profil</h2>

            <p><strong>Prénom :</strong> <?php echo htmlspecialchars($_SESSION["user_prenom"]); ?></p>
            <p><strong>Nom :</strong> <?php echo htmlspecialchars($_SESSION["nom"]); ?></p>
            <p><strong>Email :</strong> <?php echo htmlspecialchars($_SESSION["email"]); ?></p>
            <p><strong>Adresse :</strong> <?php echo htmlspecialchars($_SESSION["adresse"]); ?></p>
            <p><strong>Numéro de Carte Vitale :</strong> <?php echo htmlspecialchars($_SESSION["carte_vitale"]); ?></p>
            <p><strong>Type de compte :</strong> <?php echo htmlspecialchars(ucfirst($_SESSION["user_type"])); ?></p>

            <br>
            
            <div style="text-align: center; margin-top: 1.5rem;">
                <a href="logout.php" style="display: inline-block; padding: 12px 25px; background-color: #d9534f; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; transition: background-color 0.3s;">
                    Se déconnecter
                </a>
            </div>
        </div>
    </main>

    <?php require 'includes/footer.php'; ?>

</body>
</html>