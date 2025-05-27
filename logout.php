<?php
session_start(); // On doit démarrer la session pour pouvoir y accéder

// Détruit toutes les variables de session
$_SESSION = array();

// Détruit la session
session_destroy();

// Redirige vers la page d'accueil
header("location: index.php");
exit;
?>