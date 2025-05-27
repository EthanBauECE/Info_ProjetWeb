<!DOCTYPE html>
<html lang="fr">

<!-- Importation du head -->
<?php require 'includes/head.php'; ?>

<body>
    <!-- Importation du header -->
    <?php require 'includes/header.php'; ?>
    <main class="login-main">
        <div class="login-container">
            <h2>Connexion à votre compte</h2>
            <form action="#" method="post">
                <div class="form-group">
                    <label for="email">Adresse E-mail</label>
                    <input type="email" id="email" name="email" placeholder="Entrez votre e-mail" required>
                </div>
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" placeholder="Entrez votre mot de passe"
                        required>
                </div>
                <button type="submit" class="login-button">Se connecter</button>
            </form>
            <div class="login-footer">
                <a href="#">Mot de passe oublié ?</a>
                <p>Pas de compte ? <a href="register.php">Inscrivez-vous</a></p>
            </div>
        </div>
    </main>

    <!-- Importation du footer -->
    <?php require 'includes/footer.php'; ?>

</body>

</html>