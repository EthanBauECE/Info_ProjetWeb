<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si l'utilisateur est déjà connecté, on le redirige vers l'accueil
if (isset($_SESSION["user_id"])) {
    header("Location: index.php"); // FIX: Added missing header location
    exit();
}

$error_message = '';


// On vérifie si le formulaire a été envoyé
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Connexion à la base de données
    $pdo = new PDO('mysql:host=localhost;dbname=base_donne_web;charset=utf8', 'root', '');

    // Préparation de la requête pour éviter les injections SQL
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // =================================================================
    // MODIFICATION : la vérification par hachage est enlevée.
    // On compare directement le mot de passe tapé avec celui de la BDD.
    // =================================================================
    if ($user && $password === $user['MotDePasse']) {
    
    // CORRECTION : On utilise le tableau $user qui contient les données de la BDD
    // et on utilise des noms de session cohérents.
    $_SESSION["user_id"] = $user['ID'];
    $_SESSION["user_prenom"] = $user['Prenom'];
    $_SESSION["nom"] = $user['Nom']; // You had this, good
    $_SESSION["email"] = $user['Email'];
    $_SESSION["user_type"] = $user['TypeCompte']; // MAKE SURE THIS 


    // Pour les champs qui peuvent être vides, on ajoute une vérification
    // These values will be fetched more reliably from the database when accessing the profile
    // $_SESSION["adresse"] = isset($user['Adresse']) ? $user['Adresse'] : 'Non renseignée'; 
    // $_SESSION["carte_vitale"] = isset($user['CarteVitale']) ? $user['CarteVitale'] : 'Non renseignée';


    // Redirection vers la page d'accueil
    // Handle redirect parameter if present (from login.php?redirect=...)
    $redirect_page = 'index.php';
    if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
        $redirect_page = urldecode($_GET['redirect']);
    }
    header("Location: " . $redirect_page);
    exit();
    }        else {
        // Identifiants incorrects
        $error_message = "Email ou mot de passe incorrect.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<?php require 'includes/head.php'; ?>
<body>
    <?php require 'includes/header.php'; ?>
    <main class="login-main">
        <div class="login-container">
            <h2>Connexion à votre compte</h2>
            <?php if (!empty($error_message)) : ?>
                <p style="color: red;"><?php echo $error_message; ?></p>
            <?php endif; ?>
            <form action="login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>" method="post">
                <div class="form-group">
                    <label for="email">Adresse E-mail</label>
                    <input type="email" id="email" name="email" placeholder="Entrez votre e-mail" required>
                </div>
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" placeholder="Entrez votre mot de passe" required>
                </div>
                <button type="submit" class="login-button">Se connecter</button>
            </form>
            <div class="login-footer">
                <a href="#">Mot de passe oublié ?</a>
                <p>Pas de compte ? <a href="register.php">Inscrivez-vous</a></p>
            </div>
        </div>
    </main>
    <?php require 'includes/footer.php'; ?>
</body>
</html>