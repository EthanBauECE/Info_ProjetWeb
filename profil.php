<?php
session_start();

// Si l'utilisateur n'est pas connecté, on redirige
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// Connexion à la base de données
try {
    $pdo = new PDO("mysql:host=localhost;dbname=base_donne_web;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion BDD : " . $e->getMessage());
}

// Récupération de l'utilisateur connecté
$user_id = $_SESSION["user_id"];
$sql = "SELECT * FROM utilisateurs WHERE ID = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    die("Utilisateur non trouvé.");
}

// Récupération du type de compte
$type = $user["TypeCompte"];
$infos_supp = [];

if ($type === "client") {
    $stmt2 = $pdo->prepare("SELECT * FROM utilisateurs_client WHERE ID_Adresse = ?");
    $stmt2->execute([$user_id]);
    $infos_supp = $stmt2->fetch();

} elseif ($type === "personnel") {
    $stmt2 = $pdo->prepare("SELECT * FROM utilisateurs_personnel WHERE ID_Adresse = ?");
    $stmt2->execute([$user_id]);
    $infos_supp = $stmt2->fetch();
}
?>

<!DOCTYPE html>
<html lang="fr">
<?php require 'includes/head.php'; ?>
<body>
<?php require 'includes/header.php'; ?>

<main style="padding: 2rem; background-color: #f9f9f9;">
    <div style="max-width: 700px; margin: auto; background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h2 style="text-align: center; color: #0a7abf; margin-bottom: 2rem;">Mon Profil</h2>

        <p><strong>Nom :</strong> <?= htmlspecialchars($user["Nom"]) ?></p>
        <p><strong>Prénom :</strong> <?= htmlspecialchars($user["Prenom"]) ?></p>
        <p><strong>Email :</strong> <?= htmlspecialchars($user["Email"]) ?></p>
        <p><strong>Type de compte :</strong> <?= htmlspecialchars($type) ?></p>

        <?php if ($type === "client") : ?>
            <p><strong>Téléphone :</strong> <?= htmlspecialchars($infos_supp["Telephone"] ?? "Non renseigné") ?></p>
            <p><strong>Carte Vitale :</strong> <?= htmlspecialchars($infos_supp["CarteVitale"] ?? "Non renseignée") ?></p>
            <p><strong>ID Adresse :</strong> <?= htmlspecialchars($infos_supp["ID_Adresse"] ?? "Non renseignée") ?></p>

        <?php elseif ($type === "personnel") : ?>
            <p><strong>Téléphone :</strong> <?= htmlspecialchars($infos_supp["Telephone"] ?? "Non renseigné") ?></p>
            <p><strong>Description :</strong> <?= htmlspecialchars($infos_supp["Description"] ?? "") ?></p>
            <p><strong>Spécialité :</strong> <?= htmlspecialchars($infos_supp["Type"] ?? "") ?></p>
            <p><strong>ID Adresse :</strong> <?= htmlspecialchars($infos_supp["ID_Adresse"] ?? "") ?></p>
            <p><strong>Photo :</strong>
                <?php if (!empty($infos_supp["Photo"])): ?>
                    <img src="uploads/<?= htmlspecialchars($infos_supp["Photo"]) ?>" alt="Photo" height="60">
                <?php else: ?>
                    Aucune photo
                <?php endif; ?>
            </p>
            <p><strong>Vidéo :</strong>
                <?php if (!empty($infos_supp["Video"])): ?>
                    <a href="uploads/<?= htmlspecialchars($infos_supp["Video"]) ?>" target="_blank">Voir la vidéo</a>
                <?php else: ?>
                    Aucune vidéo
                <?php endif; ?>
            </p>

        <?php else: ?>
            <p>Ceci est un compte administrateur.</p>
        <?php endif; ?>

        <div style="text-align: center; margin-top: 2rem;">
            <a href="logout.php" style="display: inline-block; padding: 12px 25px; background-color: #d9534f; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">
                Se déconnecter
            </a>
        </div>
    </div>
</main>

<?php require 'includes/footer.php'; ?>
</body>
</html>
