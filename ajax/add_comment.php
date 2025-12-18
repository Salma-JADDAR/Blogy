<?php
require_once '../includes/init.php';

// Vérifier que c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit();
}

// Récupérer les données
$article_id = intval($_POST['article_id'] ?? 0);
$contenu = trim($_POST['contenu'] ?? '');
$email = trim($_POST['email'] ?? '');
$name = trim($_POST['name'] ?? '');

// Déterminer le username
$username = null;
if (isset($_SESSION['user'])) {
    $username = $_SESSION['user']['username'];
    $email = $_SESSION['user']['email'];
} elseif (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email requis pour les visiteurs']);
    exit();
}

// Validation
if (empty($contenu)) {
    echo json_encode(['success' => false, 'message' => 'Le commentaire ne peut pas être vide']);
    exit();
}

if (!isset($_SESSION['user']) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email invalide']);
    exit();
}

try {
    // Vérifier que l'article existe et est publié
    $sql = "SELECT id_article FROM Article WHERE id_article = :id AND status = 'published'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $article_id]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Article non trouvé ou non publié']);
        exit();
    }
    
    // Déterminer le statut
    $status = isset($_SESSION['user']) ? 'approved' : 'pending';
    
    // Insérer le commentaire
    $sql = "INSERT INTO Commentaire (contenu, username, email, id_article, status) 
            VALUES (:contenu, :username, :email, :article_id, :status)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':contenu' => $contenu,
        ':username' => $username,
        ':email' => $email,
        ':article_id' => $article_id,
        ':status' => $status
    ]);
    
    $comment_id = $pdo->lastInsertId();
    
    // Récupérer le commentaire avec le nom d'affichage
    $sql = "SELECT c.*, 
            COALESCE(u.nom, 
            CASE 
                WHEN c.username IS NOT NULL THEN c.username
                WHEN :name != '' THEN :name
                ELSE SUBSTRING_INDEX(c.email, '@', 1)
            END) as username_display 
            FROM Commentaire c 
            LEFT JOIN Utilisateur u ON c.username = u.username 
            WHERE c.id_commentaire = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $comment_id, ':name' => $name]);
    $comment = $stmt->fetch();
    
    if ($comment) {
        $date = new DateTime($comment['date_commentaire']);
        $comment['date_formatted'] = $date->format('d M, Y à H:i');
    }
    
    echo json_encode([
        'success' => true,
        'message' => $status == 'pending' ? 
            'Commentaire soumis pour modération' : 
            'Commentaire ajouté avec succès',
        'comment' => $comment
    ]);
    
} catch(PDOException $e) {
    error_log("Erreur ajout commentaire: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Une erreur est survenue. Veuillez réessayer.']);
}
?>