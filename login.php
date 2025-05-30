<?php 
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (isset($_SESSION["user_id"])) {//SI LA PERSONNE EST DEJA CONNECTEE
        header("Location: index.php");//ON OUVRE DIRECT SA PAGE
        exit();
    }

    $error_message = '';

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $email = $_POST['email'];//SE CONECTE AVEC SON ADRESSE MAIL
        $password = $_POST['password'];//ET DSAISI LE MDP

        $db_host = 'localhost';//ON PRENDS LES DONNES DE LA BDD POUR POUVOIR COMPLETER
        $db_user = 'root';
        $db_pass = ''; 
        $db_name = 'base_donne_web';
        $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

        if (!$conn) {//SI ON ARRIVE PAS A SE CONNECTER
            die("Erreur de connexion BDD: " . mysqli_connect_error()); //ON INDIQUE
        }
        mysqli_set_charset($conn, 'utf8');

        $sql = "SELECT * FROM utilisateurs WHERE email = '$email_escaped'";//EN FONCTUION DE SON EMAIL ON PEUT SAVOIR TOUT SUR LA PERSONNE
        $result = mysqli_query($conn, $sql);//ON TROUVE TOUTES LES INFOS
        $user = mysqli_fetch_assoc($result);//ON AFFICHE

        if ($user && $password === $user['MotDePasse']) { //ON VERIFIE SI C EST BIEN LA BONNE PERSONNES
        
            $_SESSION["user_id"] = $user['ID'];//O INITIALISE L ID
            $_SESSION["user_prenom"] = $user['Prenom'];//ON PEUT AUSSI INIT LE PRENOM
            $_SESSION["nom"] = $user['Nom'];//INIT LE NOM
            $_SESSION["email"] = $user['Email'];//INIT LE MAIL
            $_SESSION["user_type"] = $user['TypeCompte'];//INIT LE TYPE DU COMPTE DE LA PERSONNE


            $redirect_page = 'index.php';//ON RETOURNE SUR L ACCUEIL
            if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
                $redirect_page = urldecode($_GET['redirect']);///ON SE REDIRIGE
            }
            
            if ($result) { mysqli_free_result($result); }
            mysqli_close($conn);
            header("Location: " . $redirect_page);
            exit();
        }
        else {//SI LA CONNEXION EST PAS BIEN
            $error_message = "Email ou mot de passe incorrect.";//ON INDIQUE A LA PERSONNE
        }
        if ($result) { mysqli_free_result($result); }
        mysqli_close($conn);//ON REFERME
    }
?>

<!DOCTYPE html> 
<html lang="fr">
    <?php require 'includes/head.php'; ?>

    <body>
        <?php require 'includes/header.php'; ?><!--ON GARDE LE HEADER DE LA PAGE POUR QUE CA SOIT BEAU-->

        <main class="login-main"> <!--LA PREMIERE PARTIE DE LA PAGE-->
            <div class="login-container">

                <h2>Connexion à votre compte</h2><!--TITRE DE LA PAGE POUR INDICATION-->

                <?php if (!empty($error_message)) : ?><!--si pas bien fait pour la connexion on indique avec un message-->
                    <p style="color: red;"><?php echo $error_message; ?></p>
                <?php endif; ?>
                <form action="login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>" method="post"><!--ON LE REDIRIGE SUR LA PAGE POUR REFAIRE-->
                    
                
                    <div class="form-group"> <!--METTRE SON EMAIL-->
                        <label for="email">Adresse E-mail</label><!--INDIQUER LA DEMANDE POUR LA PEROSNNE-->
                        <input type="email" id="email" name="email" placeholder="Entrez votre e-mail" required><!--donner exemple dans la barre -->
                    </div>

                    <div class="form-group"> 
                        <label for="password">Mot de passe</label><!--INDIQUER LA DEMANDE POUR LA PEROSNNE-->
                        <input type="password" id="password" name="password" placeholder="Entrez votre mot de passe" required><!--DEMA?DER A UTILISATEUR DE METTRE SON MDP-->
                    </div>

                    <button type="submit" class="login-button">Se connecter</button> <!--BOUTON POUR POUVOIR ENVOYER SON IDENTIFIER ET MDP POUR SE CONNECTER-->
                </form>

                <div class="login-footer"> <!-- SI LA PERSONNE SE SOUVIENT PLUS DE SON MDP -->
                    <a href="#">Mot de passe oublié ?</a><!--DEMANDER SI CEST LE CAS-->
                    <p>Pas de compte ? <a href="register.php">Inscrivez-vous</a></p><!-- SINON PRPOSER AUSSI DE SE CREER UN COMPTE -->
                </div>

            </div>
        </main>

        <?php require 'includes/footer.php'; ?><!-- ON GARDE LE BAS DE LA PAGE ENCORE -->

    </body>
</html>