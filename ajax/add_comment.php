<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Vérifier si c'est une requête AJAX
if (!isset($_POST['ajax'])) {
    echo json_encode(['success' => false, 'message' => 'Requête invalide']);
    exit;
}

// Vérifier les données
$article_id = $_POST['article_id'] ?? 0;
$contenu = $_POST['contenu'] ?? '';
$email = $_POST['email'] ?? '';
$username = $_SESSION['user']['username'] ?? null;

// Validation
if (empty($contenu)) {
    echo json_encode(['success' => false, 'message' => 'Le commentaire ne peut pas être vide']);
    exit;
}

if (strlen($contenu) > 500) {
    echo json_encode(['success' => false, 'message' => 'Le commentaire ne peut pas dépasser 500 caractères']);
    exit;
}

if (!isset($_SESSION['user']) && empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Veuillez saisir votre email']);
    exit;
}

if (!isset($_SESSION['user']) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email invalide']);
    exit;
}

// Préparer les données
$data = [
    'contenu' => trim($contenu),
    'email' => trim($email),
    'username' => $username
];

// Ajouter le commentaire
$comment = addComment($article_id, $data);

if ($comment) {
    // Formater la date pour l'affichage
    $date = new DateTime($comment['date_commentaire']);
    $comment['date_formatted'] = $date->format('d M, Y');
    
    echo json_encode([
        'success' => true,
        'message' => 'Votre commentaire a été ajouté avec succès!',
        'comment' => $comment
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Une erreur est survenue lors de l\'ajout du commentaire'
    ]);
}
?>