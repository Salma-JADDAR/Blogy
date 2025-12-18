<?php
// Démarrer la session UNIQUEMENT si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Charger la configuration
require_once 'config.php';

// Charger les fonctions UTILITAIRES (articles, commentaires, etc.)
require_once 'functions.php';

// Charger les fonctions d'AUTHENTIFICATION et PERMISSIONS
require_once 'auth.php';

// Définir le fuseau horaire
date_default_timezone_set('Europe/Paris');

// Détection d'environnement
define('ENVIRONMENT', 'development'); // Changer en 'production' en prod

// Gestion des erreurs selon l'environnement
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Vérifier la connexion à la base de données
try {
    $pdo->query('SELECT 1');
} catch(PDOException $e) {
    die("Erreur de connexion à la base de données. Veuillez réessayer plus tard.");
}

// Initialiser les messages de session
$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message'], $_SESSION['message_type']);

// Fonction pour rediriger avec message
function redirectWithMessage($url, $message, $type = 'success') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
    header('Location: ' . $url);
    exit();
}
?>