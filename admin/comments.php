<?php
require_once '../includes/init.php';

// Vérifier que l'utilisateur est admin ou éditeur
requireAnyRole(['admin', 'editor']);

$page_title = "Modération des commentaires";
$body_class = "admin-comments";

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
                $_SESSION['success'] = "Commentaire approuvé";
                header("Location: comments.php");
                exit;
                break;
                
            case 'reject':
                $sql = "UPDATE Commentaire SET status = 'rejected' WHERE id_commentaire = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':id' => $comment_id]);
                $_SESSION['success'] = "Commentaire rejeté";
                header("Location: comments.php");
                exit;
                break;
                
            case 'delete':
                $sql = "DELETE FROM Commentaire WHERE id_commentaire = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':id' => $comment_id]);
                $_SESSION['success'] = "Commentaire supprimé";
                header("Location: comments.php");
                exit;
                break;
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = "Erreur: " . $e->getMessage();
        header("Location: comments.php");
        exit;
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
    $total_comments = count($comments);
} catch(PDOException $e) {
    $comments = [];
    $total_comments = 0;
}

// Compter les commentaires par statut
$pending_count = 0;
$approved_count = 0;
$rejected_count = 0;
$total_all = 0;

try {
    $sql = "SELECT status, COUNT(*) as count FROM Commentaire GROUP BY status";
    $stmt = $pdo->query($sql);
    $status_counts = $stmt->fetchAll();
    
    foreach ($status_counts as $status) {
        switch ($status['status']) {
            case 'pending':
                $pending_count = $status['count'];
                break;
            case 'approved':
                $approved_count = $status['count'];
                break;
            case 'rejected':
                $rejected_count = $status['count'];
                break;
        }
    }
    
    $total_all = $pending_count + $approved_count + $rejected_count;
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
        
        /* Carte commentaire - MODIFIÉ POUR 2 CARTES PAR LIGNE */
        .comment-card {
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
        
        .comment-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        
        /* Bordure colorée selon le statut */
        .comment-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            border-radius: 16px 0 0 16px;
        }
        
        .comment-card.pending::before {
            background: linear-gradient(to bottom, var(--warning-color), #fbbf24);
        }
        
        .comment-card.approved::before {
            background: linear-gradient(to bottom, var(--success-color), #34d399);
        }
        
        .comment-card.rejected::before {
            background: linear-gradient(to bottom, var(--danger-color), #f87171);
        }
        
        .comment-header {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1.25rem;
        }
        
        .comment-avatar {
            width: 65px;
            height: 65px;
            border-radius: 50%;
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
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
        }
        
        .comment-info {
            flex-grow: 1;
            min-width: 0;
            padding-right: 10px;
        }
        
        .comment-title {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 0.5rem;
        }
        
        .comment-author {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark-color);
            margin: 0;
            line-height: 1.3;
            word-break: break-word;
        }
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            white-space: nowrap;
        }
        
        .status-pending { 
            background: linear-gradient(135deg, var(--warning-color), #fbbf24);
            color: white;
        }
        
        .status-approved { 
            background: linear-gradient(135deg, var(--success-color), #34d399);
            color: white;
        }
        
        .status-rejected { 
            background: linear-gradient(135deg, var(--danger-color), #f87171);
            color: white;
        }
        
        .comment-meta {
            color: var(--gray-color);
            font-size: 0.95rem;
            line-height: 1.5;
        }
        
        .comment-article {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        .comment-actions {
            display: flex;
            gap: 6px;
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
        }
        
        .btn-action:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }
        
        .btn-action.approve:hover {
            background: var(--success-color);
            color: white;
        }
        
        .btn-action.reject:hover {
            background: var(--warning-color);
            color: white;
        }
        
        .btn-action.delete:hover {
            background: var(--danger-color);
            color: white;
        }
        
        /* Contenu du commentaire */
        .comment-content {
            color: var(--dark-color);
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 1.25rem;
            padding: 1rem;
            background: var(--light-color);
            border-radius: 12px;
            border-left: 4px solid var(--primary-light);
        }
        
        /* Métadonnées commentaire */
        .comment-meta-data {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
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
        
        .stat-icon-yellow {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }
        
        .stat-icon-green {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }
        
        .stat-icon-red {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
        
        .stat-icon-blue {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
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
        
        /* Filtres */
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        
        .filter-tab {
            padding: 10px 24px;
            border-radius: 30px;
            text-decoration: none;
            color: var(--gray-color);
            background: var(--light-color);
            transition: all 0.3s ease;
            font-weight: 500;
            border: 2px solid transparent;
        }
        
        .filter-tab:hover {
            background: #e5e7eb;
            color: var(--dark-color);
            text-decoration: none;
            transform: translateY(-2px);
        }
        
        .filter-tab.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            box-shadow: 0 4px 12px rgba(247, 88, 21, 0.2);
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
            
            .comment-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .comment-avatar {
                margin-bottom: 1rem;
                width: 70px;
                height: 70px;
                font-size: 28px;
            }
            
            .comment-actions {
                position: absolute;
                top: 1.5rem;
                right: 1.5rem;
            }
            
            .comment-meta-data {
                grid-template-columns: 1fr;
            }
            
            .comment-author {
                font-size: 1.4rem;
            }
            
            .comment-card {
                padding: 1.5rem;
            }
        }
        
        @media (min-width: 1200px) {
            .comment-card {
                padding: 1.5rem;
            }
            
            .comment-avatar {
                width: 60px;
                height: 60px;
                font-size: 24px;
            }
            
            .comment-author {
                font-size: 1.35rem;
            }
            
            .comment-meta-data {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (min-width: 1400px) {
            .comment-card {
                padding: 1.75rem;
            }
            
            .comment-avatar {
                width: 65px;
                height: 65px;
                font-size: 26px;
            }
            
            .comment-author {
                font-size: 1.4rem;
            }
            
            .comment-meta-data {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        /* Pour les très petits écrans */
        @media (max-width: 576px) {
            .comment-actions {
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
            
            .filter-tabs {
                flex-direction: column;
            }
            
            .filter-tab {
                text-align: center;
                width: 100%;
            }
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .comment-card {
            animation: fadeIn 0.5s ease forwards;
        }
        
        /* Style pour que les boutons soient toujours visibles */
        .comment-header {
            position: relative;
        }
        
        /* S'assurer que le texte ne dépasse pas */
        .comment-info {
            overflow: hidden;
        }
        
        .comment-author, .comment-meta {
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        
        .comment-content {
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 4;
            -webkit-box-orient: vertical;
        }
        
        .comment-content.expanded {
            -webkit-line-clamp: unset;
            display: block;
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
                                    <i class="bi bi-chat-dots-fill"></i>
                                </div>
                                <div>
                                    <h1 class="header-title-orange mb-1">Modération des commentaires</h1>
                                    <nav class="breadcrumb-orange">
                                        <a href="dashboard.php" class="breadcrumb-link">Dashboard</a>
                                        <span class="breadcrumb-separator">/</span>
                                        <span class="breadcrumb-current">Commentaires</span>
                                    </nav>
                                </div>
                            </div>
                            <p class="header-desc">
                                <i class="bi bi-shield-check me-1"></i>
                                Approuvez, rejetez ou supprimez les commentaires pour maintenir la qualité des discussions
                            </p>
                        </div>
                        
                        <div class="col-md-4 text-md-end">
                            <div class="d-flex flex-column align-items-end gap-3">
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
                                        <i class="bi bi-chat"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3 class="stat-number"><?php echo $total_all; ?></h3>
                                        <p class="stat-label">Total commentaires</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 col-sm-6">
                                <div class="stat-box-orange">
                                    <div class="stat-icon stat-icon-yellow">
                                        <i class="bi bi-clock-history"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3 class="stat-number"><?php echo $pending_count; ?></h3>
                                        <p class="stat-label">En attente</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 col-sm-6">
                                <div class="stat-box-orange">
                                    <div class="stat-icon stat-icon-green">
                                        <i class="bi bi-check-circle"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3 class="stat-number"><?php echo $approved_count; ?></h3>
                                        <p class="stat-label">Approuvés</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 col-sm-6">
                                <div class="stat-box-orange">
                                    <div class="stat-icon stat-icon-red">
                                        <i class="bi bi-x-circle"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3 class="stat-number"><?php echo $rejected_count; ?></h3>
                                        <p class="stat-label">Rejetés</p>
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
                    switch ($key) {
                        case 'pending':
                            $count = $pending_count;
                            break;
                        case 'approved':
                            $count = $approved_count;
                            break;
                        case 'rejected':
                            $count = $rejected_count;
                            break;
                        case 'all':
                            $count = $total_all;
                            break;
                    }
                ?>
                    <a href="?filter=<?php echo $key; ?>" 
                       class="filter-tab <?php echo $filter == $key ? 'active' : ''; ?>">
                        <?php echo $label; ?>
                        <span class="badge bg-white text-dark ms-1" style="opacity: 0.9;"><?php echo $count; ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <!-- Liste des commentaires EN GRID 2x2 (2 cartes par ligne) -->
            <?php if (!empty($comments)): ?>
                <div class="comments-list">
                    <div class="row row-cols-1 row-cols-md-2 g-4">
                        <?php foreach($comments as $comment): 
                            $authorInitial = isset($comment['username_display']) ? strtoupper(substr($comment['username_display'], 0, 1)) : 'U';
                        ?>
                            <div class="col">
                                <div class="comment-card <?php echo $comment['status']; ?>">
                                    <div class="comment-header">
                                        <div class="comment-avatar">
                                            <?php echo $authorInitial; ?>
                                        </div>
                                        
                                        <div class="comment-info">
                                            <div class="comment-title">
                                                <h3 class="comment-author"><?php echo htmlspecialchars($comment['username_display'] ?? 'Utilisateur inconnu'); ?></h3>
                                                <span class="status-badge status-<?php echo $comment['status']; ?>">
                                                    <?php 
                                                    if($comment['status'] == 'pending') echo 'En attente';
                                                    elseif($comment['status'] == 'approved') echo 'Approuvé';
                                                    else echo 'Rejeté';
                                                    ?>
                                                </span>
                                            </div>
                                            
                                            <div class="comment-meta">
                                                <i class="bi bi-calendar3"></i>
                                                <?php echo formatDate($comment['date_commentaire']); ?>
                                                <?php if($comment['article_titre']): ?>
                                                    • 
                                                    <i class="bi bi-file-earmark-text ms-2 me-1"></i>
                                                    <span class="comment-article">
                                                        <?php echo truncateText(htmlspecialchars($comment['article_titre']), 40); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="comment-actions">
                                            <?php if($comment['status'] == 'pending'): ?>
                                                <form method="POST" action="" class="d-inline">
                                                    <input type="hidden" name="comment_id" value="<?php echo $comment['id_commentaire']; ?>">
                                                    <button type="submit" name="action" value="approve" class="btn-action approve" title="Approuver">
                                                        <i class="bi bi-check-lg"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" action="" class="d-inline">
                                                    <input type="hidden" name="comment_id" value="<?php echo $comment['id_commentaire']; ?>">
                                                    <button type="submit" name="action" value="reject" class="btn-action reject" title="Rejeter">
                                                        <i class="bi bi-x-lg"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <form method="POST" action="" class="d-inline" 
                                                  onsubmit="return confirm('Voulez-vous vraiment supprimer ce commentaire ?')">
                                                <input type="hidden" name="comment_id" value="<?php echo $comment['id_commentaire']; ?>">
                                                <button type="submit" name="action" value="delete" class="btn-action delete" title="Supprimer">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    
                                    <div class="comment-content" id="comment-<?php echo $comment['id_commentaire']; ?>">
                                        <?php echo nl2br(htmlspecialchars($comment['contenu'])); ?>
                                    </div>
                                    
                                    <div class="comment-meta-data">
                                        <div class="meta-item">
                                            <div class="meta-icon">
                                                <i class="bi bi-envelope"></i>
                                            </div>
                                            <div class="meta-content">
                                                <span class="meta-label">Email</span>
                                                <span class="meta-value"><?php echo htmlspecialchars($comment['email']); ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="meta-item">
                                            <div class="meta-icon">
                                                <i class="bi bi-calendar"></i>
                                            </div>
                                            <div class="meta-content">
                                                <span class="meta-label">Date</span>
                                                <span class="meta-value"><?php echo date('d/m/Y H:i', strtotime($comment['date_commentaire'])); ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="meta-item">
                                            <div class="meta-icon">
                                                <i class="bi bi-journal-text"></i>
                                            </div>
                                            <div class="meta-content">
                                                <span class="meta-label">Article</span>
                                                <span class="meta-value">
                                                    <?php if($comment['article_titre']): ?>
                                                        <a href="../article.php?id=<?php echo $comment['id_article']; ?>" 
                                                           class="text-decoration-none" target="_blank" title="Voir l'article">
                                                            <?php echo truncateText(htmlspecialchars($comment['article_titre']), 25); ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">Article supprimé</span>
                                                    <?php endif; ?>
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
                        <i class="bi bi-chat"></i>
                    </div>
                    <h4>Aucun commentaire</h4>
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
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Fonction pour développer/réduire le contenu des commentaires
        document.addEventListener('DOMContentLoaded', function() {
            // Ajouter un événement de clic sur les commentaires pour les développer
            document.querySelectorAll('.comment-content').forEach(content => {
                content.addEventListener('click', function() {
                    this.classList.toggle('expanded');
                });
            });
            
            // Ajouter une indication que le contenu est cliquable
            document.querySelectorAll('.comment-content').forEach(content => {
                if (content.scrollHeight > content.clientHeight) {
                    content.style.cursor = 'pointer';
                    content.title = 'Cliquez pour voir tout le contenu';
                }
            });
        });
    </script>
</body>
</html>