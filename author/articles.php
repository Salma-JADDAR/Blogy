<?php
require_once '../includes/init.php';

// Vérifier que l'utilisateur est auteur (ou admin/éditeur)
requireAnyRole(['admin', 'editor', 'auteur']);

$page_title = "Mes articles";
$body_class = "author-articles";
$message = '';
$message_type = '';

$username = $_SESSION['user']['username'];
$user_role = $_SESSION['user']['role'];

// Traitement de la suppression d'article
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_article'])) {
    $article_id = intval($_POST['article_id'] ?? 0);
    
    try {
        // Vérifier que l'utilisateur peut supprimer cet article
        $sql = "SELECT username FROM Article WHERE id_article = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $article_id]);
        $article = $stmt->fetch();
        
        if ($article) {
            if ($user_role == 'admin' || ($user_role == 'auteur' && $article['username'] == $username) || ($user_role == 'editor' && $article['username'] == $username)) {
                $sql = "DELETE FROM Article WHERE id_article = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':id' => $article_id]);
                
                $message = "Article supprimé avec succès";
                $message_type = "success";
            } else {
                $message = "Vous n'avez pas la permission de supprimer cet article";
                $message_type = "danger";
            }
        }
    } catch(PDOException $e) {
        $message = "Erreur: " . $e->getMessage();
        $message_type = "danger";
    }
}

// Récupérer les articles selon le rôle
try {
    if ($user_role == 'admin' || $user_role == 'editor') {
        // Admins et éditeurs voient tous les articles
        $sql = "SELECT a.*, u.nom as auteur_nom, c.nom_categorie 
                FROM Article a 
                JOIN Utilisateur u ON a.username = u.username 
                JOIN Categorie c ON a.id_categorie = c.id_categorie 
                ORDER BY a.date_creation DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    } else {
        // Les auteurs ne voient que leurs propres articles
        $sql = "SELECT a.*, u.nom as auteur_nom, c.nom_categorie 
                FROM Article a 
                JOIN Utilisateur u ON a.username = u.username 
                JOIN Categorie c ON a.id_categorie = c.id_categorie 
                WHERE a.username = :username 
                ORDER BY a.date_creation DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':username' => $username]);
    }
    
    $articles = $stmt->fetchAll();
} catch(PDOException $e) {
    $articles = [];
}

// Récupérer les statistiques pour les auteurs
$author_stats = [];
if ($user_role == 'auteur') {
    $author_stats = getAuthorStats($username);
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
        .article-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            border-left: 4px solid #dee2e6;
            transition: transform 0.3s ease;
        }
        
        .article-card:hover {
            transform: translateY(-3px);
        }
        
        .article-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .article-actions {
            display: flex;
            gap: 10px;
        }
        
        .article-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 14px;
            color: #6c757d;
        }
        
        .status-badge {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-published { background: #28a745; color: white; }
        .status-draft { background: #ffc107; color: #000; }
        .status-archived { background: #6c757d; color: white; }
        
        .btn-action {
            padding: 5px 12px;
            font-size: 13px;
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
        
        .stats-summary {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }
        
        .stat-card {
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            color: white;
        }
        
        .stat-card.bg-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-card.bg-warning { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-card.bg-secondary { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stat-card.bg-info { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        
        .stat-number {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
    </style>
</head>
<body class="<?php echo $body_class; ?>">
    <?php require_once '../includes/header.php'; ?>
    
    <div class="main">
        <div class="container py-5">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-5">
                <div>
                    <h1 class="mb-2">
                        <?php 
                        if ($user_role == 'admin' || $user_role == 'editor') {
                            echo 'Tous les articles';
                        } else {
                            echo 'Mes articles';
                        }
                        ?>
                    </h1>
                    <p class="text-muted mb-0">
                        <?php 
                        if ($user_role == 'admin' || $user_role == 'editor') {
                            echo 'Gérer tous les articles du blog';
                        } else {
                            echo 'Gérez vos articles publiés et brouillons';
                        }
                        ?>
                    </p>
                </div>
                <a href="article_new.php" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Nouvel article
                </a>
            </div>
            
            <!-- Message d'alerte -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Statistiques (pour les auteurs) -->
            <?php if ($user_role == 'auteur' && !empty($author_stats)): ?>
                <div class="stats-summary">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="stat-card bg-primary">
                                <div class="stat-number"><?php echo $author_stats['published']; ?></div>
                                <div class="stat-label">Articles publiés</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card bg-warning">
                                <div class="stat-number"><?php echo $author_stats['draft']; ?></div>
                                <div class="stat-label">Brouillons</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card bg-secondary">
                                <div class="stat-number"><?php echo $author_stats['archived']; ?></div>
                                <div class="stat-label">Archivés</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card bg-info">
                                <div class="stat-number"><?php echo $author_stats['total_views']; ?></div>
                                <div class="stat-label">Vues totales</div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Liste des articles -->
            <?php if (!empty($articles)): ?>
                <div class="articles-list">
                    <?php foreach($articles as $article): ?>
                        <div class="article-card">
                            <div class="article-header">
                                <div style="flex-grow: 1;">
                                    <h5 class="mb-2">
                                        <a href="../article.php?id=<?php echo $article['id_article']; ?>" 
                                           class="text-decoration-none" target="_blank">
                                            <?php echo htmlspecialchars($article['titre']); ?>
                                        </a>
                                    </h5>
                                    
                                    <div class="article-meta">
                                        <span class="status-badge status-<?php echo $article['status']; ?>">
                                            <?php 
                                            if($article['status'] == 'published') echo 'Publié';
                                            elseif($article['status'] == 'draft') echo 'Brouillon';
                                            else echo 'Archivé';
                                            ?>
                                        </span>
                                        
                                        <?php if ($user_role == 'admin' || $user_role == 'editor'): ?>
                                            <span>
                                                <i class="bi bi-person"></i>
                                                <?php echo htmlspecialchars($article['auteur_nom']); ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <span>
                                            <i class="bi bi-bookmarks"></i>
                                            <?php echo htmlspecialchars($article['nom_categorie']); ?>
                                        </span>
                                        
                                        <span>
                                            <i class="bi bi-calendar3"></i>
                                            <?php echo formatDate($article['date_creation']); ?>
                                        </span>
                                        
                                        <span>
                                            <i class="bi bi-eye"></i>
                                            <?php echo $article['view_count']; ?> vues
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="article-actions">
                                    <?php if(canEditArticle($article['username'])): ?>
                                        <a href="article_edit.php?id=<?php echo $article['id_article']; ?>" 
                                           class="btn btn-sm btn-outline-primary btn-action">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if(canDeleteArticle($article['username'])): ?>
                                        <form method="POST" action="" class="d-inline"
                                              onsubmit="return confirm('Supprimer cet article ?')">
                                            <input type="hidden" name="article_id" value="<?php echo $article['id_article']; ?>">
                                            <button type="submit" name="delete_article" class="btn btn-sm btn-outline-danger btn-action">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <a href="../article.php?id=<?php echo $article['id_article']; ?>" 
                                       class="btn btn-sm btn-outline-info btn-action" target="_blank">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </div>
                            </div>
                            
                            <div class="article-preview">
                                <?php 
                                $contenu = strip_tags($article['contenu']);
                                echo truncateText($contenu, 150);
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-file-earmark-text"></i>
                    <h4 class="mt-3">Aucun article</h4>
                    <p class="text-muted">
                        <?php 
                        if ($user_role == 'admin' || $user_role == 'editor') {
                            echo 'Aucun article publié sur le blog';
                        } else {
                            echo 'Vous n\'avez pas encore créé d\'article';
                        }
                        ?>
                    </p>
                    <a href="article_new.php" class="btn btn-primary mt-3">
                        <i class="bi bi-plus-lg me-1"></i> Créer votre premier article
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php require_once '../includes/footer.php'; ?>
</body>
</html>