<?php
// Connexion BDD
$host = 'localhost';
$dbname = 'base_donne_web';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur BDD : " . $e->getMessage());
}

// Données du formulaire
$nom = $_POST['nom'];
$prenom = $_POST['prenom'];
$email = $_POST['email'];
$password = $_POST['password']; // ❗ Pas de hashage ici
$typeCompte = 'client';
$telephone = $_POST['telephone'];
$carteVitale = $_POST['carte_vitale'];

// Étape 1 : insérer dans `utilisateurs`
$sql_user = "INSERT INTO utilisateurs (Nom, Prenom, Email, MotDePasse, TypeCompte)
             VALUES (:nom, :prenom, :email, :mot_de_passe, :type_compte)";
$stmt = $pdo->prepare($sql_user);
$stmt->execute([
    ':nom' => $nom,
    ':prenom' => $prenom,
    ':email' => $email,
    ':mot_de_passe' => $password,
    ':type_compte' => $typeCompte
]);

// Récupérer l'ID généré automatiquement
$utilisateur_id = $pdo->lastInsertId();

// Étape 2 : insérer dans `utilisateurs_client`
$sql_client = "INSERT INTO utilisateurs_client (Telephone, CarteVitale, ID_Adresse)
               VALUES (:telephone, :carte_vitale, :utilisateur_id)";
$stmt = $pdo->prepare($sql_client);
$stmt->execute([
    ':telephone' => $telephone,
    ':carte_vitale' => $carteVitale,
    ':utilisateur_id' => $utilisateur_id
]);

// Redirection
header("Location: login.php?inscription=success");
exit();
?>
