<?php
require_once '../includes/init.php';

// Vérifier que l'utilisateur est admin ou éditeur
requireAnyRole(['admin', 'editor']);

$page_title = "Gestion des catégories";
$body_class = "admin-categories";

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add':
                $nom = trim($_POST['nom_categorie'] ?? '');
                $description = trim($_POST['description'] ?? '');
                
                if (empty($nom)) {
                    $_SESSION['error'] = "Le nom de la catégorie est requis";
                    header("Location: categories.php");
                    exit;
                } else {
                    $sql = "INSERT INTO Categorie (nom_categorie, description) VALUES (:nom, :desc)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([':nom' => $nom, ':desc' => $description]);
                    $_SESSION['success'] = "Catégorie ajoutée avec succès";
                    header("Location: categories.php");
                    exit;
                }
                break;
                
            case 'edit':
                $id = intval($_POST['id_categorie']);
                $nom = trim($_POST['nom_categorie'] ?? '');
                $description = trim($_POST['description'] ?? '');
                
                if (empty($nom)) {
                    $_SESSION['error'] = "Le nom de la catégorie est requis";
                    header("Location: categories.php");
                    exit;
                } else {
                    $sql = "UPDATE Categorie SET nom_categorie = :nom, description = :desc WHERE id_categorie = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([':nom' => $nom, ':desc' => $description, ':id' => $id]);
                    $_SESSION['success'] = "Catégorie mise à jour avec succès";
                    header("Location: categories.php");
                    exit;
                }
                break;
                
            case 'delete':
                $id = intval($_POST['id_categorie']);
                
                // Vérifier si la catégorie contient des articles
                $sql = "SELECT COUNT(*) as count FROM Article WHERE id_categorie = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':id' => $id]);
                $article_count = $stmt->fetch()['count'];
                
                if ($article_count > 0) {
                    $_SESSION['error'] = "Impossible de supprimer cette catégorie car elle contient $article_count article(s)";
                    header("Location: categories.php");
                    exit;
                } else {
                    $sql = "DELETE FROM Categorie WHERE id_categorie = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([':id' => $id]);
                    $_SESSION['success'] = "Catégorie supprimée avec succès";
                    header("Location: categories.php");
                    exit;
                }
                break;
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = "Erreur: " . $e->getMessage();
        header("Location: categories.php");
        exit;
    }
}

// Récupérer toutes les catégories avec le nombre d'articles
try {
    $sql = "SELECT c.*, COUNT(a.id_article) as nb_articles 
            FROM Categorie c 
            LEFT JOIN Article a ON c.id_categorie = a.id_categorie 
            GROUP BY c.id_categorie 
            ORDER BY c.nom_categorie ASC";
    $stmt = $pdo->query($sql);
    $categories = $stmt->fetchAll();
    $total_categories = count($categories);
} catch(PDOException $e) {
    $categories = [];
    $total_categories = 0;
    error_log("Erreur SQL: " . $e->getMessage());
}

// Compter les catégories avec articles
$categories_avec_articles = 0;
try {
    $sql = "SELECT COUNT(DISTINCT id_categorie) as count FROM Article WHERE id_categorie IS NOT NULL";
    $stmt = $pdo->query($sql);
    $result = $stmt->fetch();
    $categories_avec_articles = $result['count'] ?? 0;
} catch(PDOException $e) {
    $categories_avec_articles = 0;
}

// Compter les catégories vides
$categories_vides = $total_categories - $categories_avec_articles;

// Catégorie la plus utilisée
$categorie_populaire = null;
try {
    $sql = "SELECT c.nom_categorie, COUNT(a.id_article) as nb_articles 
            FROM Categorie c 
            LEFT JOIN Article a ON c.id_categorie = a.id_categorie 
            GROUP BY c.id_categorie 
            ORDER BY nb_articles DESC 
            LIMIT 1";
    $stmt = $pdo->query($sql);
    $categorie_populaire = $stmt->fetch();
} catch(PDOException $e) {
    $categorie_populaire = null;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
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
        
        /* Carte catégorie - MODIFIÉ POUR 2 CARTES PAR LIGNE */
        .category-card {
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
        
        .category-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-light);
        }
        
        .category-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            background: linear-gradient(to bottom, var(--primary-color), var(--primary-light));
            border-radius: 16px 0 0 16px;
        }
        
        .category-header {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1.25rem;
        }
        
        .category-icon {
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
        
        .category-info {
            flex-grow: 1;
            min-width: 0;
            padding-right: 10px;
        }
        
        .category-title {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 0.5rem;
        }
        
        .category-name {
            font-size: 1.3rem; /* Augmenté la taille pour 2 cartes */
            font-weight: 700;
            color: var(--dark-color);
            margin: 0;
            line-height: 1.3;
            word-break: break-word;
        }
        
        .article-count-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.3px;
            white-space: nowrap;
            background: linear-gradient(135deg, var(--secondary-color), #8b5cf6);
            color: white;
        }
        
        .category-description {
            color: var(--gray-color);
            font-size: 0.95rem; /* Augmenté la taille pour 2 cartes */
            line-height: 1.5;
        }
        
        .category-actions {
            display: flex;
            gap: 6px;
            flex-shrink: 0;
            align-items: flex-start;
        }
        
        .btn-action {
            width: 38px; /* Légèrement plus grand pour 2 cartes */
            height: 38px;
            border-radius: 10px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px; /* Légèrement plus grand */
            cursor: pointer;
            transition: all 0.3s ease;
            background: var(--light-color);
            color: var(--gray-color);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
            flex-shrink: 0;
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
        
        /* Métadonnées catégorie - MODIFIÉ */
        .category-meta {
            display: grid;
            grid-template-columns: repeat(3, 1fr); /* 3 colonnes au lieu de 2 */
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
            
            .category-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .category-icon {
                margin-bottom: 1rem;
                width: 70px;
                height: 70px;
                font-size: 28px;
            }
            
            .category-actions {
                position: absolute;
                top: 1.5rem;
                right: 1.5rem;
            }
            
            .category-meta {
                grid-template-columns: 1fr;
            }
            
            .category-name {
                font-size: 1.4rem;
            }
            
            .category-card {
                padding: 1.5rem;
            }
        }
        
        @media (min-width: 1200px) {
            .category-card {
                padding: 1.5rem;
            }
            
            .category-icon {
                width: 60px;
                height: 60px;
                font-size: 24px;
            }
            
            .category-name {
                font-size: 1.35rem;
            }
            
            .category-meta {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (min-width: 1400px) {
            .category-card {
                padding: 1.75rem;
            }
            
            .category-icon {
                width: 65px;
                height: 65px;
                font-size: 26px;
            }
            
            .category-name {
                font-size: 1.4rem;
            }
            
            .category-meta {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        /* Pour les très petits écrans */
        @media (max-width: 576px) {
            .category-actions {
                position: static;
                margin-top: 1rem;
                width: 100%;
                justify-content: flex-end;
            }
            
            .btn-action {
                width: 42px;
                height: 42px;
                font-size: 17px;
            }
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .category-card {
            animation: fadeIn 0.5s ease forwards;
        }
        
        /* Style pour que les boutons soient toujours visibles */
        .category-header {
            position: relative;
        }
        
        /* S'assurer que le texte ne dépasse pas */
        .category-info {
            overflow: hidden;
        }
        
        .category-name, .category-description {
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        
        .category-description {
            -webkit-line-clamp: 3; /* Permettre plus de lignes */
        }
        
        /* Animation pour les modals */
        .modal.fade .modal-dialog {
            transform: translate(0, -50px);
            transition: transform 0.3s ease-out;
        }
        
        .modal.show .modal-dialog {
            transform: translate(0, 0);
        }
        
        /* Couleurs pour les icônes de catégories */
        .category-icon-1 { background: linear-gradient(135deg, #f75815, #ff7c47); }
        .category-icon-2 { background: linear-gradient(135deg, #667eea, #8b5cf6); }
        .category-icon-3 { background: linear-gradient(135deg, #10b981, #34d399); }
        .category-icon-4 { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
        .category-icon-5 { background: linear-gradient(135deg, #ec4899, #f472b6); }
        .category-icon-6 { background: linear-gradient(135deg, #14b8a6, #2dd4bf); }
        .category-icon-7 { background: linear-gradient(135deg, #8b5cf6, #a78bfa); }
        .category-icon-8 { background: linear-gradient(135deg, #f97316, #fb923c); }
        
        /* Badge pour catégories avec articles */
        .has-articles-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            background: rgba(16, 185, 129, 0.15);
            color: var(--success-color);
        }
        
        .no-articles-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            background: rgba(156, 163, 175, 0.15);
            color: var(--gray-color);
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
                                    <i class="bi bi-bookmarks-fill"></i>
                                </div>
                                <div>
                                    <h1 class="header-title-orange mb-1">Gestion des catégories</h1>
                                    <nav class="breadcrumb-orange">
                                        <a href="dashboard.php" class="breadcrumb-link">Dashboard</a>
                                        <span class="breadcrumb-separator">/</span>
                                        <span class="breadcrumb-current">Catégories</span>
                                    </nav>
                                </div>
                            </div>
                            <p class="header-desc">
                                <i class="bi bi-tags me-1"></i>
                                Organisez vos articles en catégories pour une meilleure navigation et organisation
                            </p>
                        </div>
                        
                        <div class="col-md-4 text-md-end">
                            <div class="d-flex flex-column align-items-end gap-3">
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal" style="background-color:#f75815; border:white;">
                                    <i class="bi bi-plus-lg me-1"></i> Nouvelle catégorie
                                </button>
                                <div class="admin-card-orange">
                                    <div class="d-flex align-items-center">
                                        <div class="admin-avatar-orange me-2">
                                            <?php 
                                            $adminInitial = isset($_SESSION['user']['nom']) ? strtoupper(substr($_SESSION['user']['nom'], 0, 1)) : 'A';
                                            echo $adminInitial;
                                            ?>
                                        </div>
                                        <div class="text-start">
                                            <div class="admin-name"><?php echo htmlspecialchars($_SESSION['user']['nom'] ?? 'Administrateur'); ?></div>
                                            <div class="admin-status">
                                                <i class="bi bi-circle-fill text-success me-1"></i>
                                                <span>En ligne</span>
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
                                        <i class="bi bi-bookmarks"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3 class="stat-number"><?php echo $total_categories; ?></h3>
                                        <p class="stat-label">Total catégories</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 col-sm-6">
                                <div class="stat-box-orange">
                                    <div class="stat-icon stat-icon-green">
                                        <i class="bi bi-file-earmark-text"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3 class="stat-number"><?php echo $categories_avec_articles; ?></h3>
                                        <p class="stat-label">Catégories avec articles</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 col-sm-6">
                                <div class="stat-box-orange">
                                    <div class="stat-icon stat-icon-blue">
                                        <i class="bi bi-folder-x"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3 class="stat-number"><?php echo $categories_vides; ?></h3>
                                        <p class="stat-label">Catégories vides</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 col-sm-6">
                                <div class="stat-box-orange">
                                    <div class="stat-icon stat-icon-purple">
                                        <i class="bi bi-star"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3 class="stat-number">
                                            <?php echo $categorie_populaire ? $categorie_populaire['nb_articles'] : '0'; ?>
                                        </h3>
                                        <p class="stat-label">
                                            <?php echo $categorie_populaire ? htmlspecialchars($categorie_populaire['nom_categorie']) : 'Aucune'; ?>
                                        </p>
                                        <small class="text-muted">Articles dans la catégorie la plus utilisée</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Messages d'alerte -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle fs-4 me-3"></i>
                        <div class="flex-grow-1">
                            <?php echo $_SESSION['success']; ?>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle fs-4 me-3"></i>
                        <div class="flex-grow-1">
                            <?php echo $_SESSION['error']; ?>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
       
            <!-- Liste des catégories EN GRID 2x2 (2 cartes par ligne) -->
            <?php if (!empty($categories)): ?>
                <div class="categories-list">
                    <div class="row row-cols-1 row-cols-md-2 g-4"> <!-- Changé à 2 colonnes -->
                        <?php 
                        $colors = ['category-icon-1', 'category-icon-2', 'category-icon-3', 'category-icon-4', 'category-icon-5', 'category-icon-6', 'category-icon-7', 'category-icon-8'];
                        foreach($categories as $index => $category): 
                            $color_index = $index % count($colors);
                            $icon_class = $colors[$color_index];
                        ?>
                            <div class="col">
                                <div class="category-card">
                                    <div class="category-header">
                                        <div class="category-icon <?php echo $icon_class; ?>">
                                            <i class="bi bi-bookmark"></i>
                                        </div>
                                        
                                        <div class="category-info">
                                            <div class="category-title">
                                                <h3 class="category-name"><?php echo htmlspecialchars($category['nom_categorie']); ?></h3>
                                                <span class="article-count-badge">
                                                    <?php echo $category['nb_articles']; ?> article(s)
                                                </span>
                                            </div>
                                            
                                            <?php if (!empty($category['description'])): ?>
                                                <div class="category-description">
                                                    <?php echo htmlspecialchars($category['description']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="category-actions">
                                            <button class="btn-action edit edit-category" 
                                                    data-id="<?php echo $category['id_categorie']; ?>"
                                                    data-nom="<?php echo htmlspecialchars($category['nom_categorie']); ?>"
                                                    data-desc="<?php echo htmlspecialchars($category['description'] ?? ''); ?>"
                                                    title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            
                                            <!-- Bouton de suppression pour TOUTES les catégories -->
                                            <button class="btn-action delete delete-category"
                                                    data-id="<?php echo $category['id_categorie']; ?>"
                                                    data-nom="<?php echo htmlspecialchars($category['nom_categorie']); ?>"
                                                    data-articles="<?php echo $category['nb_articles']; ?>"
                                                    title="Supprimer">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="category-meta">
                                        <!-- SUPPRIMÉ : ID Catégorie -->
                                        
                                        <div class="meta-item">
                                            <div class="meta-icon">
                                                <i class="bi bi-file-earmark-text"></i>
                                            </div>
                                            <div class="meta-content">
                                                <span class="meta-label">Nombre d'articles</span>
                                                <span class="meta-value"><?php echo $category['nb_articles']; ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="meta-item">
                                            <div class="meta-icon">
                                                <i class="bi bi-check-circle"></i>
                                            </div>
                                            <div class="meta-content">
                                                <span class="meta-label">Statut</span>
                                                <span class="meta-value">
                                                    <?php if ($category['nb_articles'] > 0): ?>
                                                        <span class="has-articles-badge">
                                                            <i class="bi bi-check-circle"></i> Active
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="no-articles-badge">
                                                            <i class="bi bi-dash-circle"></i> Vide
                                                        </span>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="meta-item">
                                            <div class="meta-icon">
                                                <i class="bi bi-info-circle"></i>
                                            </div>
                                            <div class="meta-content">
                                                <span class="meta-label">Description</span>
                                                <span class="meta-value">
                                                    <?php echo !empty($category['description']) ? 'Présente' : 'Absente'; ?>
                                                </span>
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
                        <i class="bi bi-bookmarks"></i>
                    </div>
                    <h4>Aucune catégorie trouvée</h4>
                    <p>Il n'y a pas encore de catégories créées sur la plateforme.</p>
                    <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#addCategoryModal" style="background-color:#f75815; border:white;">
                        <i class="bi bi-plus-lg me-1"></i> Créer une catégorie
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal Ajout -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-lg me-2"></i>
                        Ajouter une nouvelle catégorie
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nom_categorie" class="form-label">Nom de la catégorie *</label>
                            <input type="text" class="form-control" id="nom_categorie" name="nom_categorie" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description (optionnelle)</label>
                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Décrivez brièvement cette catégorie..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg me-2"></i> Annuler
                        </button>
                        <button type="submit" name="action" value="add" class="btn btn-primary">
                            <i class="bi bi-check-lg me-2"></i> Ajouter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Édition -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil me-2"></i>
                        Modifier la catégorie
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="id_categorie" id="edit_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_nom_categorie" class="form-label">Nom de la catégorie *</label>
                            <input type="text" class="form-control" id="edit_nom_categorie" name="nom_categorie" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description (optionnelle)</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3" placeholder="Décrivez brièvement cette catégorie..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg me-2"></i> Annuler
                        </button>
                        <button type="submit" name="action" value="edit" class="btn btn-warning text-white">
                            <i class="bi bi-check-lg me-2"></i> Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Suppression -->
    <div class="modal fade" id="deleteCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Confirmer la suppression
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="id_categorie" id="delete_id">
                    <div class="modal-body">
                        <p class="text-center">Êtes-vous sûr de vouloir supprimer la catégorie <strong id="delete_nom"></strong> ?</p>
                        
                        <!-- Message d'alerte dynamique -->
                        <div id="deleteWarning" class="alert alert-warning d-none">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-exclamation-triangle fs-5 me-3"></i>
                                <div>
                                    <strong class="d-block">Attention ! Cette catégorie contient <span id="articleCount">0</span> article(s)</strong>
                                    <small>Vous ne pouvez pas supprimer une catégorie qui contient des articles. Veuillez d'abord déplacer ou supprimer les articles associés.</small>
                                </div>
                            </div>
                        </div>
                        
                        <div id="deleteConfirm" class="alert alert-danger">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-exclamation-octagon fs-5 me-3"></i>
                                <div>
                                    <strong class="d-block">Cette action est irréversible !</strong>
                                    <small>La catégorie sera définitivement supprimée de la base de données.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg me-2"></i> Annuler
                        </button>
                        <button type="submit" name="action" value="delete" class="btn btn-danger" id="deleteButton">
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
        // Gérer l'édition des catégories
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM chargé - gestion des catégories');
            
            // Édition des catégories
            document.querySelectorAll('.edit-category').forEach(button => {
                button.addEventListener('click', function() {
                    console.log('Bouton édition cliqué');
                    
                    const id = this.getAttribute('data-id');
                    const nom = this.getAttribute('data-nom');
                    const desc = this.getAttribute('data-desc');
                    
                    document.getElementById('edit_id').value = id;
                    document.getElementById('edit_nom_categorie').value = nom;
                    document.getElementById('edit_description').value = desc;
                    
                    // Ouvrir le modal
                    const editModal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
                    editModal.show();
                });
            });
            
            // Suppression des catégories
            document.querySelectorAll('.delete-category').forEach(button => {
                button.addEventListener('click', function() {
                    console.log('Bouton suppression cliqué');
                    
                    const id = this.getAttribute('data-id');
                    const nom = this.getAttribute('data-nom');
                    const articles = parseInt(this.getAttribute('data-articles'));
                    
                    document.getElementById('delete_id').value = id;
                    document.getElementById('delete_nom').textContent = nom;
                    document.getElementById('articleCount').textContent = articles;
                    
                    // Afficher/masquer les messages d'alerte
                    const deleteWarning = document.getElementById('deleteWarning');
                    const deleteConfirm = document.getElementById('deleteConfirm');
                    const deleteButton = document.getElementById('deleteButton');
                    
                    if (articles > 0) {
                        // Catégorie avec articles - empêcher la suppression
                        deleteWarning.classList.remove('d-none');
                        deleteConfirm.classList.add('d-none');
                        deleteButton.disabled = true;
                        deleteButton.classList.add('disabled');
                    } else {
                        // Catégorie vide - permettre la suppression
                        deleteWarning.classList.add('d-none');
                        deleteConfirm.classList.remove('d-none');
                        deleteButton.disabled = false;
                        deleteButton.classList.remove('disabled');
                    }
                    
                    // Ouvrir le modal
                    const deleteModal = new bootstrap.Modal(document.getElementById('deleteCategoryModal'));
                    deleteModal.show();
                });
            });
            
            // Pour le bouton d'ajout, vérifier qu'il fonctionne
            const addButton = document.querySelector('[data-bs-target="#addCategoryModal"]');
            if (addButton) {
                addButton.addEventListener('click', function() {
                    console.log('Bouton ajout cliqué');
                });
            }
            
            // Focus sur le champ nom lors de l'ouverture du modal d'ajout
            document.getElementById('addCategoryModal').addEventListener('shown.bs.modal', function() {
                document.getElementById('nom_categorie').focus();
            });
        });
    </script>
</body>
</html>