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

// Calculer les statistiques globales pour les admins/éditeurs
$global_stats = [];
if ($user_role == 'admin' || $user_role == 'editor') {
    try {
        // Total articles
        $sql = "SELECT COUNT(*) as total FROM Article";
        $stmt = $pdo->query($sql);
        $global_stats['total'] = $stmt->fetch()['total'];
        
        // Articles publiés
        $sql = "SELECT COUNT(*) as published FROM Article WHERE status = 'published'";
        $stmt = $pdo->query($sql);
        $global_stats['published'] = $stmt->fetch()['published'];
        
        // Articles brouillons
        $sql = "SELECT COUNT(*) as draft FROM Article WHERE status = 'draft'";
        $stmt = $pdo->query($sql);
        $global_stats['draft'] = $stmt->fetch()['draft'];
        
        // Vues totales
        $sql = "SELECT SUM(view_count) as total_views FROM Article";
        $stmt = $pdo->query($sql);
        $result = $stmt->fetch();
        $global_stats['total_views'] = $result['total_views'] ?: 0;
        
    } catch(PDOException $e) {
        $global_stats = ['total' => 0, 'published' => 0, 'draft' => 0, 'total_views' => 0];
    }
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
        /* Variables de couleurs */
        :root {
            --primary-color: #f75815;
            --primary-light: #ff7c47;
            --secondary-color: #667eea;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --dark-color: #1f2937;
            --light-color: #f9fafb;
            --gray-color: #6b7280;
            --border-color: #e5e7eb;
        }
        
        /* Carte article */
        .article-card {
            background: white;
            border-radius: 16px;
            padding: 1.75rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            height: 100%;
            width: 100%;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        
        .article-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-light);
        }
        
        .article-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            background: linear-gradient(to bottom, var(--primary-color), var(--primary-light));
            border-radius: 16px 0 0 16px;
        }
        
        .article-header {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1.25rem;
        }
        
        .article-icon {
            width: 65px;
            height: 65px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            font-weight: bold;
            color: white;
            margin-right: 1rem;
            flex-shrink: 0;
            position: relative;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .article-info {
            flex-grow: 1;
            min-width: 0;
            padding-right: 10px;
        }
        
        .article-title {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 0.75rem;
        }
        
        .article-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark-color);
            margin: 0;
            line-height: 1.3;
            word-break: break-word;
        }
        
        .article-name a {
            color: inherit;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .article-name a:hover {
            color: var(--primary-color);
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            white-space: nowrap;
        }
        
        .status-published { 
            background: linear-gradient(135deg, var(--success-color), #34d399);
            color: white;
        }
        
        .status-draft { 
            background: linear-gradient(135deg, var(--warning-color), #fbbf24);
            color: #000;
        }
        
        .status-archived { 
            background: linear-gradient(135deg, var(--gray-color), #9ca3af);
            color: white;
        }
        
        .article-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 14px;
            color: #6c757d;
        }
        
        .article-meta span {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .article-description {
            color: var(--gray-color);
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 1rem;
        }
        
        .article-actions {
            display: flex;
            gap: 8px;
            flex-shrink: 0;
            align-items: flex-start;
        }
        
        .btn-action {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: var(--light-color);
            color: var(--gray-color);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
            flex-shrink: 0;
            text-decoration: none;
        }
        
        .btn-action:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }
        
        .btn-action.edit:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-action.delete:hover {
            background: var(--danger-color);
            color: white;
        }
        
        .btn-action.view:hover {
            background: var(--secondary-color);
            color: white;
        }
        
        /* Métadonnées article */
        .article-footer {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.75rem;
            padding-top: 1.25rem;
            border-top: 2px solid var(--border-color);
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px;
            background: var(--light-color);
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .meta-item:hover {
            background: #e5e7eb;
            transform: translateX(5px);
        }
        
        .meta-icon {
            width: 32px;
            height: 32px;
            background: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-size: 14px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
            flex-shrink: 0;
        }
        
        .meta-content {
            flex-grow: 1;
            min-width: 0;
        }
        
        .meta-label {
            display: block;
            font-size: 11px;
            color: var(--gray-color);
            margin-bottom: 2px;
            font-weight: 500;
        }
        
        .meta-value {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--dark-color);
            word-break: break-word;
        }
        
        /* ===== HEADER AVEC COULEUR #f75815 ===== */
        .orange-header {
            background: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 12px;
            border-left: 5px solid #f75815;
            box-shadow: 0 4px 12px rgba(247, 88, 21, 0.08);
        }

        .header-icon-orange {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #f75815, #ff7c47);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 26px;
            box-shadow: 0 4px 12px rgba(247, 88, 21, 0.3);
        }

        .header-title-orange {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1f2937;
            margin: 0;
        }

        .header-title-orange::after {
            content: '';
            display: block;
            width: 50px;
            height: 3px;
            background: #f75815;
            margin-top: 8px;
            border-radius: 2px;
        }

        .breadcrumb-orange {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        .breadcrumb-link {
            color: #6b7280;
            text-decoration: none;
            transition: color 0.3s;
        }

        .breadcrumb-link:hover {
            color: #f75815;
        }

        .breadcrumb-separator {
            color: #d1d5db;
        }

        .breadcrumb-current {
            color: #1f2937;
            font-weight: 500;
        }

        .header-desc {
            color: #6b7280;
            font-size: 0.95rem;
            max-width: 600px;
            margin: 1rem 0 0 0;
        }

        .admin-card-orange {
            display: inline-block;
            background: #f9fafb;
            padding: 1rem 1.25rem;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            transition: all 0.3s;
        }

        .admin-card-orange:hover {
            background: white;
            border-color: #f75815;
            box-shadow: 0 4px 12px rgba(247, 88, 21, 0.1);
        }

        .admin-avatar-orange {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #f75815, #ff7c47);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 18px;
            box-shadow: 0 3px 8px rgba(247, 88, 21, 0.3);
        }

        .admin-name {
            font-weight: 600;
            color: #1f2937;
            font-size: 0.95rem;
        }

        .admin-status {
            font-size: 0.8rem;
            color: #6b7280;
        }

        .stats-container {
            border-top: 1px solid #e5e7eb;
            padding-top: 1.5rem;
            margin-top: 1.5rem;
        }

        .stat-box-orange {
            background: white;
            border-radius: 10px;
            padding: 1.25rem;
            border: 1px solid #e5e7eb;
            transition: all 0.3s;
            height: 100%;
        }

        .stat-box-orange:hover {
            transform: translateY(-5px);
            border-color: #f75815;
            box-shadow: 0 8px 20px rgba(247, 88, 21, 0.1);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: rgba(247, 88, 21, 0.1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #f75815;
            font-size: 22px;
            margin-bottom: 1rem;
        }
        
        .stat-icon-blue {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }
        
        .stat-icon-green {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }
        
        .stat-icon-purple {
            background: rgba(139, 92, 246, 0.1);
            color: #8b5cf6;
        }
        
        .stat-icon-yellow {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin: 0;
        }

        .stat-label {
            font-size: 0.85rem;
            color: #6b7280;
            margin: 0.25rem 0 0 0;
        }
        
        /* État vide */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 16px;
            border: 2px dashed var(--border-color);
        }
        
        .empty-icon {
            width: 80px;
            height: 80px;
            background: var(--light-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: var(--gray-color);
            font-size: 32px;
        }
        
        /* Alertes */
        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem 1.25rem;
            margin-bottom: 2rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .orange-header {
                padding: 1.5rem 0;
            }
            
            .header-title-orange {
                font-size: 1.5rem;
            }
            
            .header-icon-orange {
                width: 50px;
                height: 50px;
                font-size: 22px;
            }
            
            .stat-box-orange {
                padding: 1rem;
            }
            
            .stat-number {
                font-size: 1.3rem;
            }
            
            .admin-card-orange {
                margin-top: 1rem;
            }
            
            .article-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .article-icon {
                margin-bottom: 1rem;
                width: 70px;
                height: 70px;
                font-size: 28px;
            }
            
            .article-actions {
                position: absolute;
                top: 1.5rem;
                right: 1.5rem;
            }
            
            .article-footer {
                grid-template-columns: 1fr;
            }
            
            .article-name {
                font-size: 1.4rem;
            }
            
            .article-card {
                padding: 1.5rem;
            }
            
            .article-meta {
                flex-direction: column;
                gap: 8px;
            }
        }
        
        @media (min-width: 992px) {
            .article-footer {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        
        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .article-card {
            animation: fadeIn 0.5s ease forwards;
        }
        
        /* Style pour que les boutons soient toujours visibles */
        .article-header {
            position: relative;
        }
        
        /* S'assurer que le texte ne dépasse pas */
        .article-info {
            overflow: hidden;
        }
        
        .article-name, .article-description {
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        
        .article-description {
            -webkit-line-clamp: 3;
        }
        
        /* Couleurs pour les icônes d'articles */
        .article-icon-1 { background: linear-gradient(135deg, #f75815, #ff7c47); }
        .article-icon-2 { background: linear-gradient(135deg, #667eea, #8b5cf6); }
        .article-icon-3 { background: linear-gradient(135deg, #10b981, #34d399); }
        .article-icon-4 { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
        .article-icon-5 { background: linear-gradient(135deg, #ec4899, #f472b6); }
        .article-icon-6 { background: linear-gradient(135deg, #14b8a6, #2dd4bf); }
        .article-icon-7 { background: linear-gradient(135deg, #8b5cf6, #a78bfa); }
        .article-icon-8 { background: linear-gradient(135deg, #f97316, #fb923c); }
    </style>
</head>
<body class="<?php echo $body_class; ?>">
    <?php require_once '../includes/header.php'; ?>
    
    <div class="main">
        <div class="container py-5">
            <!-- Page Header -->
            <div class="orange-header">
                <div class="container-fluid">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center mb-3">
                                <div class="header-icon-orange me-3">
                                    <i class="bi bi-file-earmark-text-fill"></i>
                                </div>
                                <div>
                                    <h1 class="header-title-orange mb-1">
                                        <?php 
                                        if ($user_role == 'admin' || $user_role == 'editor') {
                                            echo 'Gestion des articles';
                                        } else {
                                            echo 'Mes articles';
                                        }
                                        ?>
                                    </h1>
                                    <nav class="breadcrumb-orange">
                                        <a href="dashboard.php" class="breadcrumb-link">Dashboard</a>
                                        <span class="breadcrumb-separator">/</span>
                                        <span class="breadcrumb-current">Articles</span>
                                    </nav>
                                </div>
                            </div>
                            <p class="header-desc">
                                <i class="bi bi-journal-text me-1"></i>
                                <?php 
                                if ($user_role == 'admin' || $user_role == 'editor') {
                                    echo 'Gérez tous les articles du blog, publiez, modifiez et organisez votre contenu';
                                } else {
                                    echo 'Gérez vos articles publiés, brouillons et archivés';
                                }
                                ?>
                            </p>
                        </div>
                        
                        <div class="col-md-4 text-md-end">
                            <div class="d-flex flex-column align-items-end gap-3">
                                <a href="article_new.php" class="btn btn-primary" style="background-color:#f75815; border:white;">
                                    <i class="bi bi-plus-lg me-1"></i> Nouvel article
                                </a>
                                <div class="admin-card-orange">
                                    <div class="d-flex align-items-center">
                                        <div class="admin-avatar-orange me-2">
                                            <?php 
                                            $userInitial = isset($_SESSION['user']['nom']) ? strtoupper(substr($_SESSION['user']['nom'], 0, 1)) : 'U';
                                            echo $userInitial;
                                            ?>
                                        </div>
                                        <div class="text-start">
                                            <div class="admin-name"><?php echo htmlspecialchars($_SESSION['user']['nom'] ?? 'Utilisateur'); ?></div>
                                            <div class="admin-status">
                                                <i class="bi bi-circle-fill text-success me-1"></i>
                                                <span><?php echo htmlspecialchars($user_role); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stats-container mt-4">
                        <div class="row g-3">
                            <?php if ($user_role == 'auteur' && !empty($author_stats)): ?>
                                <!-- Statistiques pour les auteurs -->
                                <div class="col-md-3 col-sm-6">
                                    <div class="stat-box-orange">
                                        <div class="stat-icon stat-icon-green">
                                            <i class="bi bi-check-circle"></i>
                                        </div>
                                        <div class="stat-content">
                                            <h3 class="stat-number"><?php echo $author_stats['published']; ?></h3>
                                            <p class="stat-label">Articles publiés</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3 col-sm-6">
                                    <div class="stat-box-orange">
                                        <div class="stat-icon stat-icon-yellow">
                                            <i class="bi bi-pencil"></i>
                                        </div>
                                        <div class="stat-content">
                                            <h3 class="stat-number"><?php echo $author_stats['draft']; ?></h3>
                                            <p class="stat-label">Brouillons</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3 col-sm-6">
                                    <div class="stat-box-orange">
                                        <div class="stat-icon stat-icon-blue">
                                            <i class="bi bi-archive"></i>
                                        </div>
                                        <div class="stat-content">
                                            <h3 class="stat-number"><?php echo $author_stats['archived']; ?></h3>
                                            <p class="stat-label">Archivés</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3 col-sm-6">
                                    <div class="stat-box-orange">
                                        <div class="stat-icon stat-icon-purple">
                                            <i class="bi bi-eye"></i>
                                        </div>
                                        <div class="stat-content">
                                            <h3 class="stat-number"><?php echo $author_stats['total_views']; ?></h3>
                                            <p class="stat-label">Vues totales</p>
                                        </div>
                                    </div>
                                </div>
                                
                            <?php elseif (($user_role == 'admin' || $user_role == 'editor') && !empty($global_stats)): ?>
                                <!-- Statistiques pour les admins/éditeurs -->
                                <div class="col-md-3 col-sm-6">
                                    <div class="stat-box-orange">
                                        <div class="stat-icon">
                                            <i class="bi bi-file-earmark-text"></i>
                                        </div>
                                        <div class="stat-content">
                                            <h3 class="stat-number"><?php echo $global_stats['total']; ?></h3>
                                            <p class="stat-label">Total articles</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3 col-sm-6">
                                    <div class="stat-box-orange">
                                        <div class="stat-icon stat-icon-green">
                                            <i class="bi bi-check-circle"></i>
                                        </div>
                                        <div class="stat-content">
                                            <h3 class="stat-number"><?php echo $global_stats['published']; ?></h3>
                                            <p class="stat-label">Articles publiés</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3 col-sm-6">
                                    <div class="stat-box-orange">
                                        <div class="stat-icon stat-icon-yellow">
                                            <i class="bi bi-pencil"></i>
                                        </div>
                                        <div class="stat-content">
                                            <h3 class="stat-number"><?php echo $global_stats['draft']; ?></h3>
                                            <p class="stat-label">Articles brouillons</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3 col-sm-6">
                                    <div class="stat-box-orange">
                                        <div class="stat-icon stat-icon-purple">
                                            <i class="bi bi-eye"></i>
                                        </div>
                                        <div class="stat-content">
                                            <h3 class="stat-number"><?php echo $global_stats['total_views']; ?></h3>
                                            <p class="stat-label">Vues totales</p>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Messages d'alerte -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi <?php echo $message_type == 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle'; ?> fs-4 me-3"></i>
                        <div class="flex-grow-1">
                            <?php echo $message; ?>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            <?php endif; ?>
       
            <!-- Liste des articles EN GRID 2x2 -->
            <?php if (!empty($articles)): ?>
                <div class="articles-list">
                    <div class="row row-cols-1 row-cols-md-2 g-4">
                        <?php 
                        $colors = ['article-icon-1', 'article-icon-2', 'article-icon-3', 'article-icon-4', 'article-icon-5', 'article-icon-6', 'article-icon-7', 'article-icon-8'];
                        foreach($articles as $index => $article): 
                            $color_index = $index % count($colors);
                            $icon_class = $colors[$color_index];
                        ?>
                            <div class="col">
                                <div class="article-card">
                                    <div class="article-header">
                                        <div class="article-icon <?php echo $icon_class; ?>">
                                            <i class="bi bi-file-earmark-text"></i>
                                        </div>
                                        
                                        <div class="article-info">
                                            <div class="article-title">
                                                <h3 class="article-name">
                                                    <a href="../article.php?id=<?php echo $article['id_article']; ?>" target="_blank">
                                                        <?php echo htmlspecialchars($article['titre']); ?>
                                                    </a>
                                                </h3>
                                                <span class="status-badge status-<?php echo $article['status']; ?>">
                                                    <?php 
                                                    if($article['status'] == 'published') echo 'PUBLIÉ';
                                                    elseif($article['status'] == 'draft') echo 'BROUILLON';
                                                    else echo 'ARCHIVÉ';
                                                    ?>
                                                </span>
                                            </div>
                                            
                                            <div class="article-meta">
                                                <?php if ($user_role == 'admin' || $user_role == 'editor'): ?>
                                                    <span title="Auteur">
                                                        <i class="bi bi-person"></i>
                                                        <?php echo htmlspecialchars($article['auteur_nom']); ?>
                                                    </span>
                                                <?php endif; ?>
                                                
                                                <span title="Catégorie">
                                                    <i class="bi bi-bookmarks"></i>
                                                    <?php echo htmlspecialchars($article['nom_categorie']); ?>
                                                </span>
                                                
                                                <span title="Date de création">
                                                    <i class="bi bi-calendar3"></i>
                                                    <?php echo formatDate($article['date_creation']); ?>
                                                </span>
                                            </div>
                                            
                                            <?php 
                                            $contenu = strip_tags($article['contenu']);
                                            $preview = truncateText($contenu, 120);
                                            if (!empty($preview)): 
                                            ?>
                                                <div class="article-description">
                                                    <?php echo $preview; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="article-actions">
                                            <?php if(canEditArticle($article['username'])): ?>
                                                <a href="article_edit.php?id=<?php echo $article['id_article']; ?>" 
                                                   class="btn-action edit" title="Modifier">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if(canDeleteArticle($article['username'])): ?>
                                                <form method="POST" action="" class="d-inline"
                                                      onsubmit="return confirm('Supprimer cet article ?')">
                                                    <input type="hidden" name="article_id" value="<?php echo $article['id_article']; ?>">
                                                    <button type="submit" name="delete_article" class="btn-action delete" title="Supprimer">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <a href="../article.php?id=<?php echo $article['id_article']; ?>" 
                                               class="btn-action view" target="_blank" title="Voir">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </div>
                                    </div>
                                    
                                    <div class="article-footer">
                                        <div class="meta-item">
                                            <div class="meta-icon">
                                                <i class="bi bi-eye"></i>
                                            </div>
                                            <div class="meta-content">
                                                <span class="meta-label">Vues</span>
                                                <span class="meta-value"><?php echo $article['view_count']; ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="meta-item">
                                            <div class="meta-icon">
                                                <i class="bi bi-chat"></i>
                                            </div>
                                            <div class="meta-content">
                                                <span class="meta-label">Commentaires</span>
                                                <span class="meta-value">0</span>
                                            </div>
                                        </div>
                                        
                                        <div class="meta-item">
                                            <div class="meta-icon">
                                                <i class="bi bi-star"></i>
                                            </div>
                                            <div class="meta-content">
                                                <span class="meta-label">Popularité</span>
                                                <span class="meta-value">
                                                    <?php 
                                                    if ($article['view_count'] > 100) echo 'Élevée';
                                                    elseif ($article['view_count'] > 50) echo 'Moyenne';
                                                    else echo 'Basse';
                                                    ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="meta-item">
                                            <div class="meta-icon">
                                                <i class="bi bi-clock"></i>
                                            </div>
                                            <div class="meta-content">
                                                <span class="meta-label">Dernière modification</span>
                                                <span class="meta-value"><?php echo formatDate($article['date_modification'] ?? $article['date_creation']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>
                    <h4>Aucun article trouvé</h4>
                    <p>
                        <?php 
                        if ($user_role == 'admin' || $user_role == 'editor') {
                            echo 'Il n\'y a pas encore d\'articles publiés sur la plateforme.';
                        } else {
                            echo 'Vous n\'avez pas encore créé d\'article. Commencez dès maintenant !';
                        }
                        ?>
                    </p>
                    <a href="article_new.php" class="btn btn-primary mt-3" style="background-color:#f75815; border:white;">
                        <i class="bi bi-plus-lg me-1"></i> Créer un article
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php require_once '../includes/footer.php'; ?>
    
    <script>
        // Animation pour les cartes
        document.addEventListener('DOMContentLoaded', function() {
            // Confirmation pour la suppression
            const deleteForms = document.querySelectorAll('form[onsubmit*="confirm"]');
            deleteForms.forEach(form => {
                form.onsubmit = function(e) {
                    if (!confirm('Êtes-vous sûr de vouloir supprimer cet article ? Cette action est irréversible.')) {
                        e.preventDefault();
                    }
                };
            });
            
            // Animation d'apparition des cartes
            const cards = document.querySelectorAll('.article-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });
        });
    </script>
</body>
</html>