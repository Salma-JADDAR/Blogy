<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$article_id = $_GET['article_id'] ?? 0;

if (!$article_id) {
    echo json_encode(['success' => false, 'message' => 'Article ID requis']);
    exit;
}

// Récupérer les commentaires
$comments = getComments($article_id);

// Formater les dates
foreach ($comments as &$comment) {
    $date = new DateTime($comment['date_commentaire']);
    $comment['date_formatted'] = $date->format('d M, Y à H:i');
}

echo json_encode([
    'success' => true,
    'comments' => $comments,
    'count' => count($comments)
]);
?>