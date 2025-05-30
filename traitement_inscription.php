<?php
$host = 'localhost';// APPEL LA BASE DE DONNEE
$dbname = 'base_donne_web';//CELLE CREER
$user = 'root';
$pass = '';

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("Connexion échouée : " . mysqli_connect_error());
}


$nom = trim($_POST['nom']);//VARIABLE SUR LE NOM
$prenom = trim($_POST['prenom']);//VARIABLE DU PRENOM
$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);//VARIABLE EMAIL
$password = $_POST['password']; //VARIABLE MDP
$telephone = trim($_POST['telephone']);//VARIABLE DE TELEPHONE 
$carteVitale = trim($_POST['carte_vitale']);//VARIABLE DE CARTE VITALE
$adresse_rue = trim($_POST['adresse_rue']);//VARIABLE ADRESSE
$ville = trim($_POST['ville']);//VARIABLE VILLE
$code_postal = trim($_POST['code_postal']);//CDP
$infos_complementaires = trim($_POST['infos_complementaires']);//INFO EN PLUS

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: register.php?inscription=error&message=" . urlencode("Email invalide."));
    exit();
}


mysqli_begin_transaction($conn);

try {
    $stmt1 = mysqli_prepare($conn, "INSERT INTO adresse (Adresse, Ville, CodePostal, InfosComplementaires) VALUES (?, ?, ?, ?)");// ICI ON VA AJOUTER LES INFORMATION DE L UTILISATEUR DANS LA BASE DE DONN?E
    mysqli_stmt_bind_param($stmt1, "ssss", $adresse_rue, $ville, $code_postal, $infos_complementaires);//ON ASSOCIE A CE QU ON A INDIQUE DANS LE FORMULAIRE
    mysqli_stmt_execute($stmt1);
    $adresse_id = mysqli_insert_id($conn);//ON RECUPERE ID POUR POUVOIR LA RELIER
    mysqli_stmt_close($stmt1);

    $typeCompte = 'client';
    $stmt2 = mysqli_prepare($conn, "INSERT INTO utilisateurs (Nom, Prenom, Email, MotDePasse, TypeCompte) VALUES (?, ?, ?, ?, ?)");//ICI ON VA AJHOUTER LES INFORMZTION CONCERNANT LA PERSONNE AVEC SES INFO PERSONNELLES
    mysqli_stmt_bind_param($stmt2, "sssss", $nom, $prenom, $email, $password, $typeCompte);//ON ASSOCIE A CE QU ON A INDIQUE DANS LE FORMULAIRE
    $utilisateur_id = mysqli_insert_id($conn);//ON REPREND NOTRE ID QUE NOTRE TABLE A CREE APRES SAISIE INFO
    mysqli_stmt_close($stmt2);

    $stmt3 = mysqli_prepare($conn, "INSERT INTO utilisateurs_client (ID, Telephone, CarteVitale, ID_Adresse) VALUES (?, ?, ?, ?)");//VARIABLE DE LA TABLE
    mysqli_stmt_bind_param($stmt3, "issi", $utilisateur_id, $telephone, $carteVitale, $adresse_id);//ON ASSOCIE A CE QU ON A INDIQUE DANS LE FORMULAIRE
    mysqli_stmt_execute($stmt3);//ON LES LIE
    mysqli_stmt_close($stmt3);

    mysqli_commit($conn);
    mysqli_close($conn);

    header("Location: login.php?inscription=success");//PERMET A L UTILISATEUR DE POUVOIR SE CONNECTER UNE FOIS QU IL A CREE SON COMPTE PERSO
    exit();

} catch (Exception $e) {//SI IL Y A UNE ERREUR AVEC TOITES LES INFO DE L UTILISATEUR
    mysqli_rollback($conn);//ON ARRETE ET ON SUPPRIME LES INFO AVANT DE METTRE DANS BASE
    mysqli_close($conn);
    error_log("Erreur inscription : " . $e->getMessage());//ON INDIQUE A L UTILISATEUR QU IL A MAL REMPLI UN TRUC
    header("Location: register.php?inscription=error&message=" . urlencode("Erreur pendant l'inscription."));//ON REFAIT LA PAGE D INSCRIPTION POUR QU IL PUISSE RECOMMENCER 
    exit();
}
?>
