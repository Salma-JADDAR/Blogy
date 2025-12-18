<?php
require_once '../includes/init.php';
requireAnyRole(['admin', 'editor', 'auteur']);

$page_title = "Éditer l'article";
$body_class = "author-article-edit";
$message = '';
$message_type = '';

// Récupérer l'ID de l'article
$article_id = $_GET['id'] ?? 0;

// Récupérer l'article
try {
    $sql = "SELECT a.*, u.nom as auteur_nom, c.nom_categorie
            FROM Article a 
            JOIN Utilisateur u ON a.username = u.username 
            JOIN Categorie c ON a.id_categorie = c.id_categorie
            WHERE a.id_article = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $article_id]);
    $article = $stmt->fetch();
    
    if (!$article) {
        header('Location: articles.php');
        exit();
    }
    
    // Vérifier les permissions
    if ($_SESSION['user']['role'] == 'auteur' && $article['username'] != $_SESSION['user']['username']) {
        $_SESSION['error'] = "Vous ne pouvez éditer que vos propres articles";
        header('Location: articles.php');
        exit();
    }
} catch(PDOException $e) {
    die("Erreur: " . $e->getMessage());
}

// Récupérer les catégories
$categories = getCategories();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titre = trim($_POST['titre'] ?? '');
    $contenu = $_POST['contenu'] ?? '';
    $id_categorie = intval($_POST['id_categorie'] ?? 0);
    $status = $_POST['status'] ?? 'draft';
    $excerpt = trim($_POST['excerpt'] ?? '');
    
    // Validation
    $errors = [];
    
    if (empty($titre)) {
        $errors[] = "Le titre est obligatoire";
    }
    
    if (empty($contenu)) {
        $errors[] = "Le contenu est obligatoire";
    }
    
    if ($id_categorie <= 0) {
        $errors[] = "Veuillez sélectionner une catégorie";
    }
    
    // Les auteurs ne peuvent pas publier directement
    if ($_SESSION['user']['role'] == 'auteur' && $status == 'published') {
        $errors[] = "Les auteurs doivent soumettre leurs articles pour publication";
        $status = 'draft';
    }
    
    if (empty($errors)) {
        try {
            // Mettre à jour l'article
            $sql = "UPDATE Article 
                    SET titre = :titre, 
                        contenu = :contenu,
                        excerpt = :excerpt,
                        id_categorie = :id_categorie, 
                        status = :status,
                        date_modification = NOW()
                    WHERE id_article = :id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':titre' => $titre,
                ':contenu' => $contenu,
                ':excerpt' => $excerpt,
                ':id_categorie' => $id_categorie,
                ':status' => $status,
                ':id' => $article_id
            ]);
            
            $message = "Article mis à jour avec succès !";
            $message_type = "success";
            
            // Rafraîchir les données
            $stmt->execute([':id' => $article_id]);
            $article = $stmt->fetch();
            
        } catch(PDOException $e) {
            $message = "Erreur lors de la mise à jour: " . $e->getMessage();
            $message_type = "danger";
        }
    } else {
        $message = implode("<br>", $errors);
        $message_type = "danger";
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
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
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
        
        /* Carte article - MODIFIÉ POUR 1 CARTE PAR LIGNE (mais style similaire) */
        .article-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            width: 100%;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        
        .article-card:hover {
            transform: translateY(-5px);
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
            margin-bottom: 1.5rem;
            gap: 1.5rem;
        }
        
        .article-icon {
            width: 70px;
            height: 70px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: bold;
            color: white;
            flex-shrink: 0;
            position: relative;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
        }
        
        .article-info {
            flex-grow: 1;
            min-width: 0;
        }
        
        .article-title {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 0.75rem;
        }
        
        .article-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark-color);
            margin: 0;
            line-height: 1.3;
            flex: 1;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
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
            color: white;
        }
        
        .status-archived { 
            background: linear-gradient(135deg, var(--gray-color), #9ca3af);
            color: white;
        }
        
        .article-meta {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            color: var(--gray-color);
            font-size: 0.9rem;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .form-section {
            margin-top: 2rem;
        }
        
        .form-label {
            display: block;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
            background: white;
            font-family: inherit;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(247, 88, 21, 0.1);
        }
        
        .form-control-lg {
            font-size: 1.25rem;
            font-weight: 600;
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 300px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
        }
        
        /* Sidebar Card */
        .sidebar-card {
            background: white;
            border-radius: 16px;
            padding: 1.75rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
        }
        
        .sidebar-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(to bottom, var(--primary-color), var(--primary-light));
            border-radius: 16px 0 0 16px;
        }
        
        .sidebar-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 1.5rem;
        }
        
        .sidebar-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 22px;
            box-shadow: 0 4px 12px rgba(247, 88, 21, 0.3);
            flex-shrink: 0;
        }
        
        .sidebar-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark-color);
            margin: 0;
        }
        
        .sidebar-title::after {
            content: '';
            display: block;
            width: 40px;
            height: 3px;
            background: var(--primary-color);
            margin-top: 8px;
            border-radius: 2px;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            background: var(--light-color);
            border-radius: 10px;
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s ease;
        }
        
        .stat-item:hover {
            background: #e5e7eb;
            transform: translateX(5px);
        }
        
        .stat-label {
            color: var(--gray-color);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .stat-value {
            font-weight: 700;
            color: var(--dark-color);
            font-size: 1.1rem;
        }
        
        /* Buttons */
        .btn {
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
            width: 100%;
            margin-bottom: 10px;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(247, 88, 21, 0.3);
        }
        
        .btn-secondary {
            background: white;
            color: var(--dark-color);
            border: 1px solid var(--border-color);
            width: 100%;
            margin-bottom: 10px;
        }
        
        .btn-secondary:hover {
            background: var(--light-color);
            border-color: var(--primary-color);
        }
        
        .btn-info {
            background: linear-gradient(135deg, #3b82f6, #60a5fa);
            color: white;
            width: 100%;
            margin-bottom: 10px;
        }
        
        .btn-info:hover {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            transform: translateY(-3px);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, var(--danger-color), #f87171);
            color: white;
            width: 100%;
        }
        
        .btn-danger:hover {
            background: linear-gradient(135deg, #dc2626, var(--danger-color));
            transform: translateY(-3px);
        }
        
        /* Alertes */
        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem 1.25rem;
            margin-bottom: 2rem;
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
                width: 60px;
                height: 60px;
                font-size: 24px;
            }
            
            .article-name {
                font-size: 1.3rem;
            }
        }
        
        /* Animation pour les modals */
        .modal.fade .modal-dialog {
            transform: translate(0, -50px);
            transition: transform 0.3s ease-out;
        }
        
        .modal.show .modal-dialog {
            transform: translate(0, 0);
        }
        
        /* Status selector */
        .status-selector {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .status-option {
            flex: 1;
            padding: 12px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            font-size: 14px;
            background: white;
        }
        
        .status-option:hover {
            border-color: var(--primary-color);
            background: rgba(247, 88, 21, 0.05);
        }
        
        .status-option.active {
            border-color: var(--primary-color);
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
        }
        
        .char-counter {
            text-align: right;
            font-size: 0.85rem;
            color: var(--gray-color);
            margin-top: 5px;
        }
        
        .hint-text {
            font-size: 0.85rem;
            color: var(--gray-color);
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
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
                                    <i class="bi bi-pencil-square"></i>
                                </div>
                                <div>
                                    <h1 class="header-title-orange mb-1">Éditer l'article</h1>
                                    <nav class="breadcrumb-orange">
                                        <a href="dashboard.php" class="breadcrumb-link">Dashboard</a>
                                        <span class="breadcrumb-separator">/</span>
                                        <a href="articles.php" class="breadcrumb-link">Articles</a>
                                        <span class="breadcrumb-separator">/</span>
                                        <span class="breadcrumb-current">Édition</span>
                                    </nav>
                                </div>
                            </div>
                            <p class="header-desc">
                                <i class="bi bi-file-earmark-text me-1"></i>
                                Modifiez le contenu et les paramètres de votre article
                            </p>
                        </div>
                        
                        <div class="col-md-4 text-md-end">
                            <div class="d-flex flex-column align-items-end gap-3">
                                <a href="../article.php?id=<?php echo $article_id; ?>" 
                                   class="btn btn-primary" target="_blank" style="background-color:#f75815; border:white;">
                                    <i class="bi bi-eye me-1"></i> Voir l'article
                                </a>
                                <div class="admin-card-orange">
                                    <div class="d-flex align-items-center">
                                        <div class="admin-avatar-orange me-2">
                                            <?php 
                                            $adminInitial = isset($_SESSION['user']['nom']) ? strtoupper(substr($_SESSION['user']['nom'], 0, 1)) : 'A';
                                            echo $adminInitial;
                                            ?>
                                        </div>
                                        <div class="text-start">
                                            <div class="admin-name"><?php echo htmlspecialchars($_SESSION['user']['nom'] ?? 'Utilisateur'); ?></div>
                                            <div class="admin-status">
                                                <i class="bi bi-circle-fill text-success me-1"></i>
                                                <span><?php echo htmlspecialchars($_SESSION['user']['role']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stats-container mt-4">
                        <div class="row g-3">
                            <div class="col-md-3 col-sm-6">
                                <div class="stat-box-orange">
                                    <div class="stat-icon">
                                        <i class="bi bi-hash"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3 class="stat-number">#<?php echo $article['id_article']; ?></h3>
                                        <p class="stat-label">ID de l'article</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 col-sm-6">
                                <div class="stat-box-orange">
                                    <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                                        <i class="bi bi-eye"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3 class="stat-number"><?php echo $article['view_count']; ?></h3>
                                        <p class="stat-label">Nombre de vues</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 col-sm-6">
                                <div class="stat-box-orange">
                                    <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                                        <i class="bi bi-calendar3"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3 class="stat-number"><?php echo date('d/m/Y', strtotime($article['date_creation'])); ?></h3>
                                        <p class="stat-label">Date de création</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 col-sm-6">
                                <div class="stat-box-orange">
                                    <div class="stat-icon" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                                        <i class="bi bi-clock-history"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3 class="stat-number"><?php echo date('d/m/Y H:i', strtotime($article['date_modification'])); ?></h3>
                                        <p class="stat-label">Dernière modification</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Messages d'alerte -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi <?php echo $message_type == 'success' ? 'bi-check-circle fs-4 me-3' : 'bi-exclamation-triangle fs-4 me-3'; ?>"></i>
                        <div class="flex-grow-1">
                            <?php echo $message; ?>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <!-- Colonne principale - Édition de l'article -->
                <div class="col-lg-8">
                    <div class="article-card">
                        <div class="article-header">
                            <div class="article-icon">
                                <i class="bi bi-file-text"></i>
                            </div>
                            <div class="article-info">
                                <div class="article-title">
                                    <h1 class="article-name">Modifier l'article</h1>
                                    <span class="status-badge status-<?php echo $article['status']; ?>">
                                        <?php echo ucfirst($article['status']); ?>
                                    </span>
                                </div>
                                <div class="article-meta">
                                    <div class="meta-item">
                                        <i class="bi bi-person"></i>
                                        <span><?php echo htmlspecialchars($article['auteur_nom']); ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="bi bi-bookmark"></i>
                                        <span><?php echo htmlspecialchars($article['nom_categorie']); ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="bi bi-clock"></i>
                                        <span>Dernière modification: <?php echo date('d/m/Y H:i', strtotime($article['date_modification'])); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <form method="POST" action="" id="articleForm">
                            <!-- Titre -->
                            <div class="form-section">
                                <label for="titre" class="form-label">Titre de l'article *</label>
                                <input type="text" 
                                       class="form-control form-control-lg" 
                                       id="titre" 
                                       name="titre" 
                                       value="<?php echo htmlspecialchars($article['titre']); ?>"
                                       required
                                       placeholder="Entrez le titre de votre article..."
                                       maxlength="200">
                                <div class="char-counter" id="titleCounter"><?php echo strlen($article['titre']); ?>/200 caractères</div>
                            </div>
                            
                            <!-- Résumé -->
                            <div class="form-section">
                                <label for="excerpt" class="form-label">Résumé (optionnel)</label>
                                <textarea class="form-control" 
                                          id="excerpt" 
                                          name="excerpt" 
                                          rows="3"
                                          placeholder="Un court résumé qui apparaîtra dans les aperçus..."
                                          maxlength="200"><?php echo htmlspecialchars($article['excerpt'] ?? ''); ?></textarea>
                                <div class="char-counter" id="excerptCounter"><?php echo strlen($article['excerpt'] ?? ''); ?>/200 caractères</div>
                            </div>
                            
                            <!-- Contenu -->
                            <div class="form-section">
                                <label for="contenu" class="form-label">Contenu de l'article *</label>
                                <textarea class="form-control" 
                                          id="contenu" 
                                          name="contenu" 
                                          rows="15"
                                          required
                                          placeholder="Rédigez votre article ici..."><?php echo htmlspecialchars($article['contenu']); ?></textarea>
                                <div class="hint-text">
                                    <i class="bi bi-info-circle"></i>
                                    Utilisez des paragraphes clairs et des titres pour une meilleure lisibilité
                                </div>
                            </div>
                            
                            <input type="hidden" id="status" name="status" value="<?php echo $article['status']; ?>">
                        </form>
                    </div>
                </div>
                
                <!-- Sidebar - Paramètres -->
                <div class="col-lg-4">
                    <!-- Catégorie -->
                    <div class="sidebar-card">
                        <div class="sidebar-header">
                            <div class="sidebar-icon">
                                <i class="bi bi-bookmark"></i>
                            </div>
                            <h3 class="sidebar-title">Catégorie</h3>
                        </div>
                        <div class="form-section">
                            <label for="id_categorie" class="form-label">Catégorie *</label>
                            <select class="form-control" id="id_categorie" name="id_categorie" form="articleForm" required>
                                <option value="">Sélectionnez une catégorie</option>
                                <?php foreach($categories as $cat): ?>
                                    <option value="<?php echo $cat['id_categorie']; ?>" 
                                        <?php echo $article['id_categorie'] == $cat['id_categorie'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['nom_categorie']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Statut -->
                    <div class="sidebar-card">
                        <div class="sidebar-header">
                            <div class="sidebar-icon">
                                <i class="bi bi-flag"></i>
                            </div>
                            <h3 class="sidebar-title">Statut</h3>
                        </div>
                        <div class="form-section">
                            <label class="form-label">Statut de publication</label>
                            <div class="status-selector">
                                <div class="status-option <?php echo $article['status'] == 'draft' ? 'active' : ''; ?>" data-status="draft">
                                    Brouillon
                                </div>
                                <?php if ($_SESSION['user']['role'] != 'auteur'): ?>
                                <div class="status-option <?php echo $article['status'] == 'published' ? 'active' : ''; ?>" data-status="published">
                                    Publié
                                </div>
                                <?php endif; ?>
                                <div class="status-option <?php echo $article['status'] == 'archived' ? 'active' : ''; ?>" data-status="archived">
                                    Archivé
                                </div>
                            </div>
                            <?php if ($_SESSION['user']['role'] == 'auteur'): ?>
                            <div class="hint-text">
                                <i class="bi bi-shield-check"></i>
                                Votre article sera soumis pour approbation par un éditeur
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="sidebar-card">
                        <div class="sidebar-header">
                            <div class="sidebar-icon">
                                <i class="bi bi-gear"></i>
                            </div>
                            <h3 class="sidebar-title">Actions</h3>
                        </div>
                        <div class="stats-grid">
                            <button type="submit" form="articleForm" class="btn btn-primary">
                                <i class="bi bi-save"></i> Enregistrer les modifications
                            </button>
                            
                            <a href="articles.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Retour aux articles
                            </a>
                            
                            <?php if(canDeleteArticle($article['username'])): ?>
                                <button type="button" class="btn btn-danger" 
                                        data-bs-toggle="modal" data-bs-target="#deleteModal">
                                    <i class="bi bi-trash"></i> Supprimer l'article
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de suppression -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Confirmer la suppression
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="articles.php">
                    <input type="hidden" name="article_id" value="<?php echo $article_id; ?>">
                    <div class="modal-body">
                        <p class="text-center">Êtes-vous sûr de vouloir supprimer cet article ?</p>
                        
                        <div class="alert alert-danger">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-exclamation-octagon fs-5 me-3"></i>
                                <div>
                                    <strong class="d-block">Cette action est irréversible !</strong>
                                    <small>Tous les commentaires associés seront également supprimés.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg me-2"></i> Annuler
                        </button>
                        <button type="submit" name="delete_article" class="btn btn-danger">
                            <i class="bi bi-trash me-2"></i> Supprimer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php require_once '../includes/footer.php'; ?>
    
    <script>
        // Character counters
        const titleInput = document.getElementById('titre');
        const excerptInput = document.getElementById('excerpt');
        const titleCounter = document.getElementById('titleCounter');
        const excerptCounter = document.getElementById('excerptCounter');

        function updateCounter(input, counter, max) {
            const length = input.value.length;
            counter.textContent = `${length}/${max} caractères`;
            
            if (length > max) {
                counter.style.color = '#ef4444';
            } else if (length > max * 0.9) {
                counter.style.color = '#f59e0b';
            } else {
                counter.style.color = '#6b7280';
            }
        }

        titleInput.addEventListener('input', () => {
            updateCounter(titleInput, titleCounter, 200);
        });

        excerptInput.addEventListener('input', () => {
            updateCounter(excerptInput, excerptCounter, 200);
        });

        // Status selector
        document.querySelectorAll('.status-option').forEach(option => {
            option.addEventListener('click', function() {
                // Pour les auteurs, seuls les brouillons sont autorisés
                const userRole = '<?php echo $_SESSION['user']['role']; ?>';
                if (userRole === 'auteur' && this.dataset.status !== 'draft') {
                    alert('Les auteurs ne peuvent que soumettre des articles en brouillon pour approbation.');
                    return;
                }
                
                document.querySelectorAll('.status-option').forEach(o => o.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('status').value = this.dataset.status;
            });
        });

        // Form validation
        document.getElementById('articleForm').addEventListener('submit', function(e) {
            const titre = document.getElementById('titre').value.trim();
            const categorie = document.getElementById('id_categorie').value;
            const contenu = document.getElementById('contenu').value.trim();
            const excerpt = document.getElementById('excerpt').value;

            if (!titre) {
                e.preventDefault();
                alert('Veuillez saisir un titre pour votre article');
                document.getElementById('titre').focus();
                return;
            }

            if (!categorie) {
                e.preventDefault();
                alert('Veuillez sélectionner une catégorie pour votre article');
                document.getElementById('id_categorie').focus();
                return;
            }

            if (!contenu) {
                e.preventDefault();
                alert('Veuillez saisir le contenu de l\'article');
                document.getElementById('contenu').focus();
                return;
            }

            if (excerpt.length > 200) {
                e.preventDefault();
                alert('Le résumé ne peut pas dépasser 200 caractères');
                document.getElementById('excerpt').focus();
                return;
            }
            
            // Show loading state
            const submitBtn = document.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Enregistrement...';
            submitBtn.disabled = true;
        });

        // Initialize counters
        if (titleInput.value) updateCounter(titleInput, titleCounter, 200);
        if (excerptInput.value) updateCounter(excerptInput, excerptCounter, 200);
    </script>
</body>
</html>