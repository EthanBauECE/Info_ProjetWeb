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
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" placeholder="Entrez votre e-mail" required>
                </div>
                <div class="form-group">
                    <label for="adresse_rue">Adresse (N° et Rue)</label>
                    <input type="text" id="adresse_rue" name="adresse_rue" placeholder="Ex: 123 Rue de la Paix" required>
                </div>
                <div class="form-group">
                    <label for="ville">Ville</label>
                    <input type="text" id="ville" name="ville" placeholder="Ex: Paris" required>
                </div>
                <div class="form-group">
                    <label for="code_postal">Code Postal</label>
                    <input type="text" id="code_postal" name="code_postal" placeholder="Ex: 75001" required maxlength="5" pattern="\d{5}" title="Cinq chiffres requis">
                </div>
                <div class="form-group">
                    <label for="infos_complementaires">Informations Complémentaires (optionnel)</label>
                    <input type="text" id="infos_complementaires" name="infos_complementaires" placeholder="Ex: Appt 42, Bâtiment C">
                </div>
                <div class="form-group">
                    <label for="carte_vitale">Carte Vitale</label>
                    <input type="text" id="carte_vitale" name="carte_vitale" placeholder="Ex : 123456789012345" required>
                </div>
                <div class="form-group">
                     <label for="telephone">Téléphone</label>
                     <input type="tel" id="telephone" name="telephone" placeholder="Ex : 0601020304" required>
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