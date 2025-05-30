<?php /////////////////////////////////////////////// PHP //////////////////////////////////////////

    // ______________/ Type d'utilsateur \_____________________

    /// NOUVEAU UTILISATEUR
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    /// UTILISATEUR DEJA CONNECTE
    if (isset($_SESSION["user_id"])) {
        header("Location: index.php");
        exit();
    }

    $error_message = '';

    // ______________/ Formulaire /_____________________
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $email = $_POST['email'];
        $password = $_POST['password'];

        /// On se connecte a la base de donne
        $db_host = 'localhost';
        $db_user = 'root';
        $db_pass = ''; 
        $db_name = 'base_donne_web';
        $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

        /// Vérification de la connexion MySQLi
        if (!$conn) {
            die("Erreur de connexion BDD: " . mysqli_connect_error()); 
        }
        mysqli_set_charset($conn, 'utf8');


        /// Modification de la gestion des erreurs
        $email_escaped = mysqli_real_escape_string($conn, $email);
        $sql = "SELECT * FROM utilisateurs WHERE email = '$email_escaped'";
        $result = mysqli_query($conn, $sql);
        $user = mysqli_fetch_assoc($result);


        /// Vérification des identifiants
        if ($user && $password === $user['MotDePasse']) { /// IDENTIFIANTS CORRECTE
        
            // Si les identifiants sont corrects on initialise la session
            $_SESSION["user_id"] = $user['ID'];
            $_SESSION["user_prenom"] = $user['Prenom'];
            $_SESSION["nom"] = $user['Nom'];
            $_SESSION["email"] = $user['Email'];
            $_SESSION["user_type"] = $user['TypeCompte'];

            // On redirige vers la page d'accueil
            $redirect_page = 'index.php';
            if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
                $redirect_page = urldecode($_GET['redirect']);
            }
            
            // On ferme la connexion a la BD (avant la redirection)
            if ($result) { mysqli_free_result($result); }
            mysqli_close($conn);
            header("Location: " . $redirect_page);
            exit();
        }
        else {          /// IDENTIFIANTS INCORRECTE
            /// On met un message d'erreur
            $error_message = "Email ou mot de passe incorrect.";
        }
        // On ferme la connexion a la BD si elle n'a pas été fermée avant
        if ($result) { mysqli_free_result($result); }
        mysqli_close($conn);
    }
?>

<!DOCTYPE html> <!-- ////////////////////////////////////////// HTML ///////////////////////////////////////////-->

<html lang="fr">

    <!-- Importation head -->
    <?php require 'includes/head.php'; ?>

    <body>

        <!-- Importation header -->
        <?php require 'includes/header.php'; ?>

        <main class="login-main">
            <div class="login-container">

                <h2>Connexion à votre compte</h2>

                <!-- Message d'erreur en cas de mauvaise connexion -->
                <?php if (!empty($error_message)) : ?>
                    <p style="color: red;"><?php echo $error_message; ?></p>
                <?php endif; ?>

                <!-- Formulaire de connexion -->
                <form action="login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>" method="post">
                    
                
                    <div class="form-group"> <!-- MAIL -->
                        <label for="email">Adresse E-mail</label>
                        <input type="email" id="email" name="email" placeholder="Entrez votre e-mail" required>
                    </div>

                    <div class="form-group"> <!-- MOT DE PASSE -->
                        <label for="password">Mot de passe</label>
                        <input type="password" id="password" name="password" placeholder="Entrez votre mot de passe" required>
                    </div>

                    <button type="submit" class="login-button">Se connecter</button> <!-- ENVOYER -->
                </form>

                <div class="login-footer"> <!-- REGISTER ET MOT DE PASSE OUBLIER -->
                    <a href="#">Mot de passe oublié ?</a>
                    <p>Pas de compte ? <a href="register.php">Inscrivez-vous</a></p>
                </div>

            </div>
        </main>

        <!-- Importation footer -->
        <?php require 'includes/footer.php'; ?>

    </body>
</html>