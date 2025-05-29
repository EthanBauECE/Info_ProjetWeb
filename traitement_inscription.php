<?php
// Connexion BDD
$host = 'localhost';
$dbname = 'base_donne_web';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->beginTransaction(); // Start a transaction for atomicity
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

// New address fields from the modified form
$adresse_rue = $_POST['adresse_rue'];
$ville = $_POST['ville'];
$code_postal = $_POST['code_postal'];
$infos_complementaires = $_POST['infos_complementaires'];

try {
    // Étape 1 : Insérer l'adresse dans la table `adresse`
    $sql_adresse = "INSERT INTO adresse (Adresse, Ville, CodePostal, InfosComplementaires)
                    VALUES (:adresse, :ville, :code_postal, :infos_complementaires)";
    $stmt_adresse = $pdo->prepare($sql_adresse);
    $stmt_adresse->execute([
        ':adresse' => $adresse_rue,
        ':ville' => $ville,
        ':code_postal' => $code_postal,
        ':infos_complementaires' => $infos_complementaires
    ]);
    $adresse_id_inserted = $pdo->lastInsertId(); // Get the ID of the newly inserted address

    // Étape 2 : Insérer l'utilisateur dans la table `utilisateurs`
    $sql_user = "INSERT INTO utilisateurs (Nom, Prenom, Email, MotDePasse, TypeCompte)
                 VALUES (:nom, :prenom, :email, :mot_de_passe, :type_compte)";
    $stmt_user = $pdo->prepare($sql_user);
    $stmt_user->execute([
        ':nom' => $nom,
        ':prenom' => $prenom,
        ':email' => $email,
        ':mot_de_passe' => $password,
        ':type_compte' => $typeCompte
    ]);
    $utilisateur_id = $pdo->lastInsertId(); // Get the ID of the newly inserted user

    // Étape 3 : Insérer les détails du client dans la table `utilisateurs_client`
    // IMPORTANT: Based on existing code (profil.php, modifier_profil.php) that joins `utilisateurs`
    // and `utilisateurs_client` on `u.ID = uc.ID`, the `ID` column in `utilisateurs_client`
    // is expected to match `utilisateurs.ID` despite being AUTO_INCREMENT in the schema.
    // MySQL allows explicit insertion into an AUTO_INCREMENT column.
    $sql_client = "INSERT INTO utilisateurs_client (ID, Telephone, CarteVitale, ID_Adresse)
                   VALUES (:id_utilisateur, :telephone, :carte_vitale, :id_adresse)";
    $stmt_client = $pdo->prepare($sql_client);
    $stmt_client->execute([
        ':id_utilisateur' => $utilisateur_id, // Link the client's specific details to their main user ID
        ':telephone' => $telephone,
        ':carte_vitale' => $carteVitale,
        ':id_adresse' => $adresse_id_inserted // Link to the newly inserted address ID
    ]);

    $pdo->commit(); // Commit the transaction
    header("Location: login.php?inscription=success");
    exit();

} catch (PDOException $e) {
    $pdo->rollBack(); // Rollback on error
    // Log the error for debugging purposes (e.g., to a file or system log)
    error_log("Inscription failed: " . $e->getMessage());
    // Redirect with an error message, perhaps back to the registration form
    header("Location: register.php?inscription=error&message=" . urlencode("Erreur lors de l'inscription. Veuillez réessayer."));
    exit();
}