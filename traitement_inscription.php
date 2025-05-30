<?php
// Connexion BDD
$host = 'localhost';
$dbname = 'base_donne_web';
$user = 'root';
$pass = '';

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("Connexion échouée : " . mysqli_connect_error());
}

// Données du formulaire
$nom = trim($_POST['nom']);
$prenom = trim($_POST['prenom']);
$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
$password = $_POST['password']; // ❗ Mot de passe non hashé
$telephone = trim($_POST['telephone']);
$carteVitale = trim($_POST['carte_vitale']);
$adresse_rue = trim($_POST['adresse_rue']);
$ville = trim($_POST['ville']);
$code_postal = trim($_POST['code_postal']);
$infos_complementaires = trim($_POST['infos_complementaires']);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: register.php?inscription=error&message=" . urlencode("Email invalide."));
    exit();
}

// Démarrer la transaction
mysqli_begin_transaction($conn);

try {
    // Étape 1 : Insérer l'adresse
    $stmt1 = mysqli_prepare($conn, "INSERT INTO adresse (Adresse, Ville, CodePostal, InfosComplementaires) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt1, "ssss", $adresse_rue, $ville, $code_postal, $infos_complementaires);
    mysqli_stmt_execute($stmt1);
    $adresse_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt1);

    // Étape 2 : Insérer l'utilisateur
    $typeCompte = 'client';
    $stmt2 = mysqli_prepare($conn, "INSERT INTO utilisateurs (Nom, Prenom, Email, MotDePasse, TypeCompte) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt2, "sssss", $nom, $prenom, $email, $password, $typeCompte);
    mysqli_stmt_execute($stmt2);
    $utilisateur_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt2);

    // Étape 3 : Insérer les détails client
    $stmt3 = mysqli_prepare($conn, "INSERT INTO utilisateurs_client (ID, Telephone, CarteVitale, ID_Adresse) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt3, "issi", $utilisateur_id, $telephone, $carteVitale, $adresse_id);
    mysqli_stmt_execute($stmt3);
    mysqli_stmt_close($stmt3);

    mysqli_commit($conn);
    mysqli_close($conn);

    header("Location: login.php?inscription=success");
    exit();

} catch (Exception $e) {
    mysqli_rollback($conn);
    mysqli_close($conn);
    error_log("Erreur inscription : " . $e->getMessage());
    header("Location: register.php?inscription=error&message=" . urlencode("Erreur lors de l'inscription."));
    exit();
}
?>
