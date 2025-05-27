<!DOCTYPE html>
<html lang="fr">

<?php require 'includes/head.php'; ?>

<body>
    <?php require 'includes/header.php'; ?>

    <main class="register-main">
        <div class="register-container">
            <h2>Créer un compte</h2>
            <form action="traitement_inscription.php" method="post">
                <div class="form-group">
                    <label for="nom">Nom</label>
                    <input type="text" id="nom" name="nom" placeholder="Entrez votre nom" required>
                </div>
                <div class="form-group">
                    <label for="prenom">Prénom</label>
                    <input type="text" id="prenom" name="prenom" placeholder="Entrez votre prénom" required>
                </div>
                <div class="form-group">
                    <label for="adresse">Adresse</label>
                    <input type="text" id="adresse" name="adresse" placeholder="Entrez votre adresse complète" required>
                </div>
                <div class="form-group">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" placeholder="Entrez votre e-mail" required>
                </div>
                <div class="form-group">
                    <label for="carte_vitale">Carte Vitale</label>
                    <input type="text" id="carte_vitale" name="carte_vitale" placeholder="Ex : 123456789012345" required>
                </div>
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" placeholder="Créez un mot de passe sécurisé" required>
                </div>
                <button type="submit">Créer le compte</button>
            </form>
            <p>Déjà un compte ? <a href="login.php">Connectez-vous</a></p>
        </div>
    </main>

    <?php require 'includes/footer.php'; ?>
</body>

</html>
