<?php
require_once '../includes/init.php';

// Vérifier que l'utilisateur est admin ou éditeur
requireAnyRole(['admin', 'editor']);

$page_title = "Modération des commentaires";
$body_class = "admin-comments";
$message = '';
$message_type = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $comment_id = intval($_POST['comment_id'] ?? 0);
    
    try {
        switch ($action) {
            case 'approve':
                $sql = "UPDATE Commentaire SET status = 'approved' WHERE id_commentaire = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':id' => $comment_id]);
                $message = "Commentaire approuvé";
                $message_type = "success";
                break;
                
            case 'reject':
                $sql = "UPDATE Commentaire SET status = 'rejected' WHERE id_commentaire = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':id' => $comment_id]);
                $message = "Commentaire rejeté";
                $message_type = "success";
                break;
                
            case 'delete':
                $sql = "DELETE FROM Commentaire WHERE id_commentaire = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':id' => $comment_id]);
                $message = "Commentaire supprimé";
                $message_type = "success";
                break;
        }
    } catch(PDOException $e) {
        $message = "Erreur: " . $e->getMessage();
        $message_type = "danger";
    }
}

// Récupérer les commentaires selon le filtre
$filter = $_GET['filter'] ?? 'pending';
$status_condition = '';

switch ($filter) {
    case 'approved':
        $status_condition = "AND c.status = 'approved'";
        break;
    case 'rejected':
        $status_condition = "AND c.status = 'rejected'";
        break;
    case 'all':
        $status_condition = "";
        break;
    default:
        $status_condition = "AND c.status = 'pending'";
}

try {
    $sql = "SELECT c.*, a.titre as article_titre, u.nom as username_display 
            FROM Commentaire c 
            LEFT JOIN Article a ON c.id_article = a.id_article 
            LEFT JOIN Utilisateur u ON c.username = u.username 
            WHERE 1=1 $status_condition
            ORDER BY c.date_commentaire DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $comments = $stmt->fetchAll();
} catch(PDOException $e) {
    $comments = [];
}

// Compter les commentaires par statut
try {
    $sql = "SELECT status, COUNT(*) as count FROM Commentaire GROUP BY status";
    $stmt = $pdo->query($sql);
    $status_counts = $stmt->fetchAll();
} catch(PDOException $e) {
    $status_counts = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <?php require_once '../includes/header.php'; ?>
    <style>
        .comment-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            border-left: 4px solid #dee2e6;
        }
        
        .comment-card.pending {
            border-left-color: #ffc107;
            background: #fffcf5;
        }
        
        .comment-card.approved {
            border-left-color: #28a745;
            background: #f8fff9;
        }
        
        .comment-card.rejected {
            border-left-color: #dc3545;
            background: #fff5f5;
        }
        
        .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .comment-meta {
            flex-grow: 1;
        }
        
        .comment-actions {
            display: flex;
            gap: 10px;
        }
        
        .comment-status {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-pending {
            background: #ffc107;
            color: #000;
        }
        
        .status-approved {
            background: #28a745;
            color: white;
        }
        
        .status-rejected {
            background: #dc3545;
            color: white;
        }
        
        .comment-content {
            line-height: 1.6;
            color: #333;
            margin-bottom: 15px;
            white-space: pre-wrap;
        }
        
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .filter-tab {
            padding: 8px 20px;
            border-radius: 20px;
            text-decoration: none;
            color: #6c757d;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }
        
        .filter-tab:hover {
            background: #e9ecef;
            color: #495057;
            text-decoration: none;
        }
        
        .filter-tab.active {
            background: #667eea;
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px 20px;
        }
        
        .empty-state i {
            font-size: 50px;
            color: #dee2e6;
            margin-bottom: 20px;
        }
    </style>
</head>
<body class="<?php echo $body_class; ?>">
    <?php require_once '../includes/header.php'; ?>
    
    <div class="main">
        <div class="container py-5">
            <!-- Page Header -->
            <div class="mb-5">
                <h1 class="mb-3">Modération des commentaires</h1>
                <p class="text-muted mb-0">Approuvez, rejetez ou supprimez les commentaires</p>
            </div>
            
            <!-- Message d'alerte -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Filtres -->
            <div class="filter-tabs">
                <?php 
                $filters = [
                    'pending' => 'En attente',
                    'approved' => 'Approuvés', 
                    'rejected' => 'Rejetés',
                    'all' => 'Tous'
                ];
                
                foreach ($filters as $key => $label): 
                    $count = 0;
                    foreach ($status_counts as $status) {
                        if ($key == 'all') {
                            $count = array_sum(array_column($status_counts, 'count'));
                            break;
                        } elseif ($status['status'] == $key) {
                            $count = $status['count'];
                            break;
                        }
                    }
                ?>
                    <a href="?filter=<?php echo $key; ?>" 
                       class="filter-tab <?php echo $filter == $key ? 'active' : ''; ?>">
                        <?php echo $label; ?>
                        <span class="badge bg-secondary ms-1"><?php echo $count; ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <!-- Liste des commentaires -->
            <?php if (!empty($comments)): ?>
                <div class="comments-list">
                    <?php foreach($comments as $comment): ?>
                        <div class="comment-card <?php echo $comment['status']; ?>">
                            <div class="comment-header">
                                <div class="comment-meta">
                                    <div class="d-flex align-items-center mb-2">
                                        <strong><?php echo htmlspecialchars($comment['username_display']); ?></strong>
                                        <span class="comment-status status-<?php echo $comment['status']; ?> ms-2">
                                            <?php 
                                            if($comment['status'] == 'pending') echo 'En attente';
                                            elseif($comment['status'] == 'approved') echo 'Approuvé';
                                            else echo 'Rejeté';
                                            ?>
                                        </span>
                                    </div>
                                    <div class="text-muted small">
                                        <i class="bi bi-calendar3 me-1"></i>
                                        <?php echo formatDate($comment['date_commentaire']); ?>
                                        <?php if($comment['article_titre']): ?>
                                            • 
                                            <i class="bi bi-file-earmark-text ms-2 me-1"></i>
                                            <a href="../article.php?id=<?php echo $comment['id_article']; ?>" 
                                               class="text-decoration-none" target="_blank">
                                                <?php echo truncateText(htmlspecialchars($comment['article_titre']), 40); ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="comment-actions">
                                    <?php if($comment['status'] == 'pending'): ?>
                                        <form method="POST" action="" class="d-inline">
                                            <input type="hidden" name="comment_id" value="<?php echo $comment['id_commentaire']; ?>">
                                            <button type="submit" name="action" value="approve" class="btn btn-sm btn-success">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="" class="d-inline">
                                            <input type="hidden" name="comment_id" value="<?php echo $comment['id_commentaire']; ?>">
                                            <button type="submit" name="action" value="reject" class="btn btn-sm btn-warning">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <form method="POST" action="" class="d-inline" 
                                          onsubmit="return confirm('Supprimer ce commentaire ?')">
                                        <input type="hidden" name="comment_id" value="<?php echo $comment['id_commentaire']; ?>">
                                        <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            
                            <div class="comment-content">
                                <?php echo nl2br(htmlspecialchars($comment['contenu'])); ?>
                            </div>
                            
                            <div class="text-muted small">
                                <i class="bi bi-envelope me-1"></i>
                                <?php echo htmlspecialchars($comment['email']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-chat"></i>
                    <h4 class="mt-3">Aucun commentaire</h4>
                    <p class="text-muted">
                        <?php if($filter == 'pending'): ?>
                            Aucun commentaire en attente de modération
                        <?php elseif($filter == 'approved'): ?>
                            Aucun commentaire approuvé
                        <?php elseif($filter == 'rejected'): ?>
                            Aucun commentaire rejeté
                        <?php else: ?>
                            Aucun commentaire trouvé
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php require_once '../includes/footer.php'; ?>
</body>
</html>