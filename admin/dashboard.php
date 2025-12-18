<?php
require_once '../includes/init.php';

// Vérifier que l'utilisateur est admin
requireRole('admin');

$page_title = "Tableau de bord";
$body_class = "admin-dashboard";

// Récupérer les statistiques
$stats = getStats();

// Récupérer les derniers articles
try {
    $sql = "SELECT a.*, u.nom as auteur_nom, c.nom_categorie 
            FROM Article a 
            JOIN Utilisateur u ON a.username = u.username 
            JOIN Categorie c ON a.id_categorie = c.id_categorie 
            WHERE a.status = 'published' 
            ORDER BY a.date_creation DESC 
            LIMIT 5";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $recent_articles = $stmt->fetchAll();
} catch(PDOException $e) {
    $recent_articles = [];
}

// Récupérer les derniers commentaires
try {
    $sql = "SELECT c.*, a.titre as article_titre, u.nom as username_display 
            FROM Commentaire c 
            LEFT JOIN Article a ON c.id_article = a.id_article 
            LEFT JOIN Utilisateur u ON c.username = u.username 
            ORDER BY c.date_commentaire DESC 
            LIMIT 5";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $recent_comments = $stmt->fetchAll();
} catch(PDOException $e) {
    $recent_comments = [];
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
        /* Statistiques */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card.articles { border-top: 4px solid #f75815; }
        .stat-card.users { border-top: 4px solid #f75815; }
        .stat-card.comments { border-top: 4px solid #f75815; }
        .stat-card.categories { border-top: 4px solid #f75815; }
        
        .stat-icon {
            font-size: 40px;
            margin-bottom: 15px;
            opacity: 0.8;
        }
        
        .stat-number {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #000;
            font-size: 14px;
        }
        
        /* Actions rapides */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 30px;
        }
        
        .quick-action {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            text-decoration: none;
            color: #333;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .quick-action:hover {
            border-color: #f75815;
            transform: translateY(-3px);
            text-decoration: none;
            color: #333;
        }
        
        .quick-action i {
            font-size: 30px;
            margin-bottom: 10px;
            color: #f75815;
        }
        
        /* Carte pour articles récents */
        .recent-articles-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            height: 100%;
            border: 1px solid #f0f0f0;
        }
        
        /* Carte pour commentaires récents */
        .recent-comments-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            height: 100%;
            border: 1px solid #f0f0f0;
        }
        
        .recent-articles-card:hover, .recent-comments-card:hover {
            box-shadow: 0 15px 40px rgba(0,0,0,0.12);
        }
        
        /* En-tête commun */
        .recent-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f8f9fa;
        }
        
        .recent-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #f75815 0%, #e77f52 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: white;
            font-size: 22px;
        }
        
        .recent-header h4 {
            font-weight: 700;
            color: #333;
            margin: 0;
        }
        
        /* Item d'article */
        .article-item {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 15px;
            display: flex;
            align-items: flex-start;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        
        .article-item:hover {
            background: white;
            border-left-color: #f75815;
            box-shadow: 0 5px 15px rgba(247, 88, 21, 0.1);
            transform: translateX(3px);
        }
        
        .article-number {
            background: white;
            color: #f75815;
            font-weight: 700;
            font-size: 14px;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
            box-shadow: 0 3px 8px rgba(0,0,0,0.08);
        }
        
        .article-content {
            flex-grow: 1;
        }
        
        .article-title {
            font-weight: 600;
            font-size: 15px;
            line-height: 1.4;
        }
        
        .article-link {
            color: #333;
            transition: color 0.3s ease;
        }
        
        .article-link:hover {
            color: #f75815;
            text-decoration: none;
        }
        
        .article-meta {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 5px;
            font-size: 13px;
        }
        
        .meta-item {
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .meta-item i {
            font-size: 11px;
        }
        
        .meta-divider {
            color: #dee2e6;
        }
        
        .article-stats {
            flex-shrink: 0;
            margin-left: 10px;
        }
        
        .views-badge {
            background: #dee2e6;
            color: #f28d62;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            border: none;
        }
        
        .views-badge i {
            font-size: 11px;
        }
        
        .article-category {
            background: rgba(108, 117, 125, 0.1);
            color: #6c757d;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .article-category i {
            font-size: 11px;
            margin-right: 4px;
        }
        
        .btn-read-more {
            color: #f75815;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
            padding: 5px 10px;
            border-radius: 6px;
        }
        
        .btn-read-more:hover {
            background: rgba(247, 88, 21, 0.1);
            color: #f75815;
            gap: 8px;
        }
        
        .btn-read-more i {
            font-size: 11px;
            transition: transform 0.3s ease;
        }
        
        .btn-read-more:hover i {
            transform: translateX(3px);
        }
        
        /* Item de commentaire */
        .comment-item {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 15px;
            display: flex;
            align-items: flex-start;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        
        .comment-item:hover {
            background: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transform: translateX(3px);
        }
        
        .comment-item[data-status="pending"] {
            border-left-color: #ffc107;
            background: rgba(255, 193, 7, 0.05);
        }
        
        .comment-item[data-status="approved"] {
            border-left-color: #28a745;
            background: rgba(40, 167, 69, 0.05);
        }
        
        .comment-item[data-status="rejected"] {
            border-left-color: #dc3545;
            background: rgba(220, 53, 69, 0.05);
        }
        
        .comment-avatar {
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .avatar-circle {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 18px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
        }
        
        .comment-content {
            flex-grow: 1;
            min-width: 0;
        }
        
        .comment-author {
            font-weight: 600;
            font-size: 15px;
            color: #333;
        }
        
        .comment-meta {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 3px;
            font-size: 12px;
        }
        
        .comment-text {
            margin: 10px 0;
            line-height: 1.5;
            font-size: 14px;
            color: #444;
        }
        
        .comment-text p {
            margin-bottom: 0;
        }
        
        .comment-status {
            flex-shrink: 0;
        }
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .status-badge.pending {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }
        
        .status-badge.approved {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
        }
        
        .status-badge.rejected {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
        }
        
        .comment-actions {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .comment-email {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
        }
        
        .comment-email i {
            font-size: 11px;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn-action {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-action.approve {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }
        
        .btn-action.approve:hover {
            background: #28a745;
            color: white;
        }
        
        .btn-action.reject {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }
        
        .btn-action.reject:hover {
            background: #ffc107;
            color: white;
        }
        
        .btn-action.delete {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        
        .btn-action.delete:hover {
            background: #dc3545;
            color: white;
        }
        
        /* État vide */
        .empty-state, .empty-comments {
            text-align: center;
            padding: 40px 20px;
        }
        
        .empty-icon {
            width: 70px;
            height: 70px;
            background: #e9ecef;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: #adb5bd;
            font-size: 28px;
        }
        
        .empty-state h5, .empty-comments h5 {
            color: #6c757d;
            margin-bottom: 10px;
        }
        
        /* Pied de section */
        .recent-footer, .comments-footer {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 2px solid #f8f9fa;
            text-align: center;
        }
        
        .btn-view-all, .btn-manage-comments {
            background: linear-gradient(135deg, #f75815 0%, #e77f52 100%);
            color: white;
            padding: 10px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-view-all:hover, .btn-manage-comments:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(247, 88, 21, 0.3);
            color: white;
            text-decoration: none;
            gap: 12px;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .recent-articles-card,
            .recent-comments-card {
                padding: 20px;
            }
            
            .article-item,
            .comment-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .article-number,
            .comment-avatar {
                margin-bottom: 10px;
            }
            
            .article-meta,
            .comment-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
            
            .meta-divider {
                display: none;
            }
        }
    </style>
</head>
<body class="<?php echo $body_class; ?>">
    <?php require_once '../includes/header.php'; ?>
    
    <div class="main">
        <div class="container py-5">
            <!-- Page Header -->
            <div class="mb-5 text-center">
                <div class="card border-0 shadow-sm py-4" style="background: linear-gradient(135deg, #f75815 0%, #e77f52 100%);">
                    <div class="card-body text-white">
                        <h1 class="mb-3" style="color:white;"><i class="bi bi-speedometer2 me-2"></i>Tableau de bord</h1>
                        <p class="mb-0">
                            <i class="bi bi-person-circle me-1"></i>
                            Bonjour, <strong><?php echo htmlspecialchars($_SESSION['user']['nom']); ?></strong>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Statistiques -->
            <div class="stats-grid">
                <div class="stat-card articles">
                    <div class="stat-icon">
                        <i class="bi bi-file-earmark-text" style="color: #f75815;"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['total_articles']; ?></div>
                    <div class="stat-label">Articles publiés</div>
                </div>
                
                <div class="stat-card users">
                    <div class="stat-icon">
                        <i class="bi bi-people" style="color: #f75815;"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                    <div class="stat-label">Utilisateurs</div>
                </div>
                
                <div class="stat-card comments">
                    <div class="stat-icon">
                        <i class="bi bi-chat" style="color: #f75815;"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['total_comments']; ?></div>
                    <div class="stat-label">Commentaires</div>
                </div>
                
                <div class="stat-card categories">
                    <div class="stat-icon">
                        <i class="bi bi-bookmarks" style="color: #f75815;"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['total_categories']; ?></div>
                    <div class="stat-label">Catégories</div>
                </div>
            </div>
            
            <!-- Articles récents ET Commentaires récents CÔTE À CÔTE -->
            <div class="row mt-5">
                <!-- Colonne de gauche : Articles récents -->
                <div class="col-lg-6 mb-4">
                    <div class="recent-articles-card h-100">
                        <div class="recent-header">
                            <div class="d-flex align-items-center">
                                <div class="recent-icon">
                                    <i class="bi bi-file-earmark-text"></i>
                                </div>
                                <div>
                                    <h4 class="mb-1">Articles récents</h4>
                                    <p class="text-muted small mb-0">Dernières publications sur le blog</p>
                                </div>
                            </div>
                           
                        </div>
                        
                        <div class="articles-list">
                            <?php if (!empty($recent_articles)): ?>
                                <?php foreach($recent_articles as $index => $article): ?>
                                    <div class="article-item">
                                        <div class="article-number">
                                            <?php echo str_pad($index + 1, 2, '0', STR_PAD_LEFT); ?>
                                        </div>
                                        
                                        <div class="article-content">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <h6 class="article-title mb-1">
                                                        <a href="../article.php?id=<?php echo $article['id_article']; ?>" 
                                                           class="text-decoration-none article-link">
                                                            <?php echo truncateText(htmlspecialchars($article['titre']), 35); ?>
                                                        </a>
                                                    </h6>
                                                    <div class="article-meta">
                                                        <span class="meta-item">
                                                            <i class="bi bi-person"></i>
                                                            <?php echo htmlspecialchars($article['auteur_nom']); ?>
                                                        </span>
                                                        <span class="meta-divider">•</span>
                                                        <span class="meta-item">
                                                            <i class="bi bi-calendar3"></i>
                                                            <?php echo formatDate($article['date_creation']); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="article-stats">
                                                    <span class="badge views-badge" 
                                                          data-bs-toggle="tooltip" 
                                                          title="<?php echo $article['view_count']; ?> vues">
                                                        <i class="bi bi-eye"></i> <?php echo $article['view_count']; ?>
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <div class="d-flex justify-content-between align-items-center mt-2">
                                                <span class="article-category">
                                                    <i class="bi bi-bookmark"></i>
                                                    <?php echo htmlspecialchars($article['nom_categorie']); ?>
                                                </span>
                                                <a href="../article.php?id=<?php echo $article['id_article']; ?>" 
                                                   class="btn-read-more">
                                                    Lire <i class="bi bi-arrow-right"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <div class="empty-icon">
                                        <i class="bi bi-file-earmark-text"></i>
                                    </div>
                                    <h5>Aucun article récent</h5>
                                    <p class="text-muted">Aucun article n'a été publié pour le moment</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="recent-footer">
                            <a href="articles.php" class="btn-view-all">
                                <i class="bi bi-list"></i> Voir tous les articles
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Colonne de droite : Commentaires récents -->
                <div class="col-lg-6 mb-4">
                    <div class="recent-comments-card h-100">
                        <div class="recent-header">
                            <div class="d-flex align-items-center" >
                                <div class="recent-icon">
                                    <i class="bi bi-chat"></i>
                                </div>
                                <div>
                                    <h4 class="mb-1">Commentaires récents</h4>
                                    <p class="text-muted small mb-0">Derniers commentaires sur le blog</p>
                                </div>
                            </div>
                            
                        </div>
                        
                        <div class="comments-list">
                            <?php if (!empty($recent_comments)): ?>
                                <?php foreach($recent_comments as $index => $comment): ?>
                                    <?php 
                                    // Sécurité contre les valeurs null
                                    $comment_id = $comment['id_commentaire'] ?? 0;
                                    $username = $comment['username_display'] ?? 'Anonyme';
                                    $content = $comment['contenu'] ?? '';
                                    $email = $comment['email'] ?? '';
                                    $status = $comment['status'] ?? 'pending';
                                    $article_id = $comment['id_article'] ?? 0;
                                    $article_title = $comment['article_titre'] ?? 'Inconnu';
                                    $date = $comment['date_commentaire'] ?? date('Y-m-d H:i:s');
                                    
                                    // Initiale pour l'avatar
                                    $initial = !empty($username) ? strtoupper(substr($username, 0, 1)) : 'A';
                                    
                                    // Couleur pour l'avatar
                                    $bg_colors = ['#667eea', '#f093fb', '#4facfe', '#43e97b', '#ffc107'];
                                    $color_index = $comment_id % count($bg_colors);
                                    $bg_color = $bg_colors[$color_index];
                                    ?>
                                    
                                    <div class="comment-item" data-status="<?php echo $status; ?>">
                                        <div class="comment-avatar">
                                            <div class="avatar-circle" style="background: <?php echo $bg_color; ?>;">
                                                <?php echo $initial; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="comment-content">
                                            <div class="comment-header">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="comment-author mb-1">
                                                            <?php echo htmlspecialchars($username); ?>
                                                        </h6>
                                                        <div class="comment-meta">
                                                            <span class="meta-item">
                                                                <i class="bi bi-calendar3"></i>
                                                                <?php echo formatDate($date); ?>
                                                            </span>
                                                            <?php if($article_title && $article_title != 'Inconnu'): ?>
                                                                <span class="meta-divider">•</span>
                                                                <span class="meta-item">
                                                                    <i class="bi bi-file-earmark-text"></i>
                                                                    <?php if($article_id): ?>
                                                                        <a href="../article.php?id=<?php echo $article_id; ?>" 
                                                                           class="article-link" target="_blank">
                                                                            <?php echo truncateText(htmlspecialchars($article_title), 25); ?>
                                                                        </a>
                                                                    <?php else: ?>
                                                                        <?php echo truncateText(htmlspecialchars($article_title), 25); ?>
                                                                    <?php endif; ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="comment-status">
                                                        <?php if($status == 'pending'): ?>
                                                            <span class="status-badge pending">
                                                                <i class="bi bi-clock"></i> En attente
                                                            </span>
                                                        <?php elseif($status == 'approved'): ?>
                                                            <span class="status-badge approved">
                                                                <i class="bi bi-check-circle"></i> Approuvé
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="status-badge rejected">
                                                                <i class="bi bi-x-circle"></i> Rejeté
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="comment-text">
                                                <p><?php echo truncateText(nl2br(htmlspecialchars($content)), 100); ?></p>
                                                <?php if(strlen($content) > 100): ?>
                                                    <span class="text-muted small">[...]</span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="comment-actions">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <?php if(!empty($email)): ?>
                                                        <span class="comment-email small text-muted">
                                                            <i class="bi bi-envelope"></i>
                                                            <?php echo htmlspecialchars($email); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                 <div class="action-buttons">
    <?php if($status == 'pending'): ?>
        <form method="POST" action="comments.php" class="d-inline">
            <input type="hidden" name="comment_id" value="<?php echo $comment_id; ?>">
            <button type="submit" name="action" value="approve" class="btn-action approve" 
                    data-bs-toggle="tooltip" title="Approuver">
                <i class="bi bi-check-lg"></i>
            </button>
        </form>
        <form method="POST" action="comments.php" class="d-inline">
            <input type="hidden" name="comment_id" value="<?php echo $comment_id; ?>">
            <button type="submit" name="action" value="reject" class="btn-action reject" 
                    data-bs-toggle="tooltip" title="Rejeter">
                <i class="bi bi-x-lg"></i>
            </button>
        </form>
    <?php endif; ?>
    
    <form method="POST" action="comments.php" class="d-inline" 
          onsubmit="return confirm('Supprimer ce commentaire ?')">
        <input type="hidden" name="comment_id" value="<?php echo $comment_id; ?>">
        <button type="submit" name="action" value="delete" class="btn-action delete" 
                data-bs-toggle="tooltip" title="Supprimer">
            <i class="bi bi-trash"></i>
        </button>
    </form>
</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-comments">
                                    <div class="empty-icon">
                                        <i class="bi bi-chat"></i>
                                    </div>
                                    <h5>Aucun commentaire récent</h5>
                                    <p class="text-muted">Aucun commentaire n'a été posté récemment</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="comments-footer">
                            <a href="comments.php" class="btn-manage-comments">
                                <i class="bi bi-gear"></i> Gérer tous les commentaires
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
           
        </div>
    </div>
    
    <?php require_once '../includes/footer.php'; ?>
    
    <script>
        // Initialiser les tooltips Bootstrap
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>