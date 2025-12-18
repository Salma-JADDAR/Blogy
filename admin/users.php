<?php
require_once '../includes/init.php';

// Vérifier que l'utilisateur est admin
requireRole('admin');

$page_title = "Gestion des utilisateurs";
$body_class = "admin-users";

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $username = $_POST['username'] ?? '';
    
    try {
        switch ($action) {
            case 'add':
                $nom = trim($_POST['nom'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $new_username = trim($_POST['new_username'] ?? '');
                $mot_de_passe = $_POST['mot_de_passe'] ?? '';  // Changé de 'password' à 'mot_de_passe'
                $role = $_POST['role'] ?? 'subscriber';
                $etat = $_POST['etat'] ?? 'actif';
                
                if (empty($nom) || empty($email) || empty($new_username) || empty($mot_de_passe)) {
                    $_SESSION['error'] = "Tous les champs sont requis";
                    header("Location: users.php");
                    exit;
                } else {
                    // Vérifier si l'utilisateur existe déjà
                    $sql = "SELECT COUNT(*) as count FROM Utilisateur WHERE username = :username OR email = :email";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([':username' => $new_username, ':email' => $email]);
                    $result = $stmt->fetch();
                    
                    if ($result['count'] > 0) {
                        $_SESSION['error'] = "Un utilisateur avec ce nom d'utilisateur ou cet email existe déjà";
                        header("Location: users.php");
                        exit;
                    } else {
                        // Hasher le mot de passe
                        $hashed_password = password_hash($mot_de_passe, PASSWORD_DEFAULT);
                        
                        $sql = "INSERT INTO Utilisateur (username, nom, email, mot_de_passe, role, etat, date_creation) 
                                VALUES (:username, :nom, :email, :mot_de_passe, :role, :etat, NOW())";
                        
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            ':username' => $new_username,
                            ':nom' => $nom,
                            ':email' => $email,
                            ':mot_de_passe' => $hashed_password,
                            ':role' => $role,
                            ':etat' => $etat
                        ]);
                        
                        $_SESSION['success'] = "Utilisateur ajouté avec succès";
                        header("Location: users.php");
                        exit;
                    }
                }
                break;
                
            case 'edit':
                $nom = trim($_POST['nom'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $role = $_POST['role'] ?? 'subscriber';
                $etat = $_POST['etat'] ?? 'actif';
                $new_mot_de_passe = $_POST['new_mot_de_passe'] ?? '';  // Changé de 'new_password' à 'new_mot_de_passe'
                
                if (empty($nom) || empty($email)) {
                    $_SESSION['error'] = "Tous les champs sont requis";
                    header("Location: users.php");
                    exit;
                } else {
                    if (!empty($new_mot_de_passe)) {
                        // Si un nouveau mot de passe est fourni
                        $hashed_password = password_hash($new_mot_de_passe, PASSWORD_DEFAULT);
                        $sql = "UPDATE Utilisateur SET nom = :nom, email = :email, mot_de_passe = :mot_de_passe, role = :role, etat = :etat 
                                WHERE username = :username";
                        
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            ':nom' => $nom,
                            ':email' => $email,
                            ':mot_de_passe' => $hashed_password,
                            ':role' => $role,
                            ':etat' => $etat,
                            ':username' => $username
                        ]);
                    } else {
                        // Sans changer le mot de passe
                        $sql = "UPDATE Utilisateur SET nom = :nom, email = :email, role = :role, etat = :etat 
                                WHERE username = :username";
                        
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            ':nom' => $nom,
                            ':email' => $email,
                            ':role' => $role,
                            ':etat' => $etat,
                            ':username' => $username
                        ]);
                    }
                    
                    $_SESSION['success'] = "Utilisateur mis à jour avec succès";
                    header("Location: users.php");
                    exit;
                }
                break;
                
            case 'delete':
                if ($username === $_SESSION['user']['username']) {
                    $_SESSION['error'] = "Vous ne pouvez pas supprimer votre propre compte";
                    header("Location: users.php");
                    exit;
                } else {
                    $sql = "DELETE FROM Utilisateur WHERE username = :username";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([':username' => $username]);
                    
                    $_SESSION['success'] = "Utilisateur supprimé avec succès";
                    header("Location: users.php");
                    exit;
                }
                break;
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = "Erreur: " . $e->getMessage();
        header("Location: users.php");
        exit;
    }
}

// Récupérer TOUS les utilisateurs (pas de pagination)
try {
    $sql = "SELECT * FROM Utilisateur ORDER BY date_creation DESC";
    $stmt = $pdo->query($sql);
    $users = $stmt->fetchAll();
    $total_users = count($users);
} catch(PDOException $e) {
    $users = [];
    $total_users = 0;
    error_log("Erreur SQL: " . $e->getMessage());
}

// Compter les utilisateurs actifs
$active_users = 0;
try {
    $sql = "SELECT COUNT(*) as count FROM Utilisateur WHERE etat = 'actif'";
    $stmt = $pdo->query($sql);
    $result = $stmt->fetch();
    $active_users = $result['count'] ?? 0;
} catch(PDOException $e) {
    $active_users = 0;
}

// Compter les administrateurs
$admin_count = 0;
try {
    $sql = "SELECT COUNT(*) as count FROM Utilisateur WHERE role = 'admin'";
    $stmt = $pdo->query($sql);
    $result = $stmt->fetch();
    $admin_count = $result['count'] ?? 0;
} catch(PDOException $e) {
    $admin_count = 0;
}

// Compter les nouveaux utilisateurs (30 derniers jours)
$new_users = 0;
try {
    $sql = "SELECT COUNT(*) as count FROM Utilisateur WHERE date_creation >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $stmt = $pdo->query($sql);
    $result = $stmt->fetch();
    $new_users = $result['count'] ?? 0;
} catch(PDOException $e) {
    $new_users = 0;
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
        
        /* Carte utilisateur - MODIFIÉ POUR 3 CARTES PAR LIGNE */
        .user-card {
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
        
        .user-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-light);
        }
        
        .user-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            background: linear-gradient(to bottom, var(--primary-color), var(--primary-light));
            border-radius: 16px 0 0 16px;
        }
        
        .user-header {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1.25rem;
        }
        
        .user-avatar {
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
        }
        
        .user-info {
            flex-grow: 1;
            min-width: 0;
            padding-right: 10px; /* Espace pour les boutons */
        }
        
        .user-title {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 0.5rem;
        }
        
        .user-name {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--dark-color);
            margin: 0;
            line-height: 1.3;
            word-break: break-word;
        }
        
        .role-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            white-space: nowrap;
        }
        
        .role-admin { 
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
        }
        
        .role-editor { 
            background: linear-gradient(135deg, #8b5cf6, #a78bfa);
            color: white;
        }
        
        .role-auteur { 
            background: linear-gradient(135deg, #10b981, #34d399);
            color: white;
        }
        
        .role-subscriber { 
            background: linear-gradient(135deg, #3b82f6, #60a5fa);
            color: white;
        }
        
        .user-details {
            color: var(--gray-color);
            font-size: 0.9rem;
            line-height: 1.5;
        }
        
        .user-username {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        .user-actions {
            display: flex;
            gap: 6px;
            flex-shrink: 0;
            align-items: flex-start;
        }
        
        .btn-action {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
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
        
        /* Métadonnées utilisateur */
        .user-meta {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
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
        
        .state-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .state-active {
            background: rgba(16, 185, 129, 0.15);
            color: var(--success-color);
        }
        
        .state-inactive {
            background: rgba(239, 68, 68, 0.15);
            color: var(--danger-color);
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
            
            .user-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .user-avatar {
                margin-bottom: 1rem;
                width: 70px;
                height: 70px;
                font-size: 28px;
            }
            
            .user-actions {
                position: absolute;
                top: 1.5rem;
                right: 1.5rem;
            }
            
            .user-meta {
                grid-template-columns: 1fr;
            }
            
            .user-name {
                font-size: 1.3rem;
            }
            
            .user-card {
                padding: 1.5rem;
            }
        }
        
        @media (min-width: 1200px) {
            .user-card {
                padding: 1.5rem;
            }
            
            .user-avatar {
                width: 60px;
                height: 60px;
                font-size: 24px;
            }
            
            .user-name {
                font-size: 1.15rem;
            }
        }
        
        @media (min-width: 1400px) {
            .user-card {
                padding: 1.75rem;
            }
            
            .user-avatar {
                width: 65px;
                height: 65px;
                font-size: 26px;
            }
            
            .user-name {
                font-size: 1.2rem;
            }
        }
        
        /* Pour les très petits écrans */
        @media (max-width: 576px) {
            .user-actions {
                position: static;
                margin-top: 1rem;
                width: 100%;
                justify-content: flex-end;
            }
            
            .btn-action {
                width: 40px;
                height: 40px;
            }
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .user-card {
            animation: fadeIn 0.5s ease forwards;
        }
        
        /* Style pour que les boutons soient toujours visibles */
        .user-header {
            position: relative;
        }
        
        /* S'assurer que le texte ne dépasse pas */
        .user-info {
            overflow: hidden;
        }
        
        .user-name, .user-details {
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        
        .user-details {
            -webkit-line-clamp: 2;
        }
        
        /* Animation pour les modals */
        .modal.fade .modal-dialog {
            transform: translate(0, -50px);
            transition: transform 0.3s ease-out;
        }
        
        .modal.show .modal-dialog {
            transform: translate(0, 0);
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
                                    <i class="bi bi-people-fill"></i>
                                </div>
                                <div>
                                    <h1 class="header-title-orange mb-1">Gestion des utilisateurs</h1>
                                    <nav class="breadcrumb-orange">
                                        <a href="dashboard.php" class="breadcrumb-link">Dashboard</a>
                                        <span class="breadcrumb-separator">/</span>
                                        <span class="breadcrumb-current">Utilisateurs</span>
                                    </nav>
                                </div>
                            </div>
                            <p class="header-desc">
                                <i class="bi bi-gear me-1"></i>
                                Administrez les comptes utilisateurs, attribuez les rôles et gérez les permissions
                            </p>
                        </div>
                        
                        <div class="col-md-4 text-md-end">
                            <div class="d-flex flex-column align-items-end gap-3">
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal" style="background-color:#f75815; border:white;">
                                    <i class="bi bi-person-plus me-1" style="background-color:#f75815;"></i> Ajouter un utilisateur
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
                                        <i class="bi bi-people"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3 class="stat-number"><?php echo $total_users; ?></h3>
                                        <p class="stat-label">Total utilisateurs</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 col-sm-6">
                                <div class="stat-box-orange">
                                    <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                                        <i class="bi bi-person-check"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3 class="stat-number"><?php echo $active_users; ?></h3>
                                        <p class="stat-label">Utilisateurs actifs</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 col-sm-6">
                                <div class="stat-box-orange">
                                    <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                                        <i class="bi bi-shield-check"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3 class="stat-number"><?php echo $admin_count; ?></h3>
                                        <p class="stat-label">Administrateurs</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 col-sm-6">
                                <div class="stat-box-orange">
                                    <div class="stat-icon" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                                        <i class="bi bi-person-plus"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3 class="stat-number"><?php echo $new_users; ?></h3>
                                        <p class="stat-label">Nouveaux (30j)</p>
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
       
            <!-- Liste des utilisateurs EN GRID 3x3 (3 cartes par ligne) -->
            <?php if (!empty($users)): ?>
                <div class="users-list">
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                        <?php 
                        $colors = ['#f75815', '#667eea', '#10b981', '#8b5cf6', '#f59e0b', '#ec4899', '#14b8a6', '#f97316'];
                        foreach($users as $index => $user): 
                            $color_index = $index % count($colors);
                            $bg_color = $colors[$color_index];
                            
                            // Compter les articles
                            $article_count = 0;
                            try {
                                $sql = "SELECT COUNT(*) as count FROM Article WHERE username = :username";
                                $stmt = $pdo->prepare($sql);
                                $stmt->execute([':username' => $user['username']]);
                                $result = $stmt->fetch();
                                $article_count = $result['count'] ?? 0;
                            } catch(PDOException $e) {
                                $article_count = 0;
                            }
                            
                            // Compter les commentaires
                            $comment_count = 0;
                            try {
                                $sql = "SELECT COUNT(*) as count FROM Commentaire WHERE username = :username";
                                $stmt = $pdo->prepare($sql);
                                $stmt->execute([':username' => $user['username']]);
                                $result = $stmt->fetch();
                                $comment_count = $result['count'] ?? 0;
                            } catch(PDOException $e) {
                                $comment_count = 0;
                            }
                        ?>
                            <div class="col">
                                <div class="user-card">
                                    <div class="user-header">
                                        <div class="user-avatar" style="background: linear-gradient(135deg, <?php echo $bg_color; ?>, <?php echo $bg_color; ?>80);">
                                            <?php echo strtoupper(substr($user['nom'], 0, 1)); ?>
                                        </div>
                                        
                                        <div class="user-info">
                                            <div class="user-title">
                                                <h3 class="user-name"><?php echo htmlspecialchars($user['nom']); ?></h3>
                                                <span class="role-badge role-<?php echo htmlspecialchars($user['role']); ?>">
                                                    <?php echo htmlspecialchars($user['role']); ?>
                                                </span>
                                            </div>
                                            
                                            <div class="user-details">
                                                <span class="user-username">
                                                    <i class="bi bi-at"></i><?php echo htmlspecialchars($user['username']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="user-actions">
                                            <button class="btn-action edit edit-user" 
                                                    data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                                    data-nom="<?php echo htmlspecialchars($user['nom']); ?>"
                                                    data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                                    data-role="<?php echo htmlspecialchars($user['role']); ?>"
                                                    data-etat="<?php echo htmlspecialchars($user['etat']); ?>"
                                                    title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            
                                            <?php if(isset($user['username']) && isset($_SESSION['user']['username']) && $user['username'] !== $_SESSION['user']['username']): ?>
                                                <button class="btn-action delete delete-user"
                                                        data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                                        data-nom="<?php echo htmlspecialchars($user['nom']); ?>"
                                                        title="Supprimer">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="user-meta">
                                        <div class="meta-item">
                                            <div class="meta-icon">
                                                <i class="bi bi-calendar3"></i>
                                            </div>
                                            <div class="meta-content">
                                                <span class="meta-label">Date d'inscription</span>
                                                <span class="meta-value">
                                                    <?php echo isset($user['date_creation']) ? date('d/m/Y', strtotime($user['date_creation'])) : 'N/A'; ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="meta-item">
                                            <div class="meta-icon">
                                                <i class="bi bi-file-earmark-text"></i>
                                            </div>
                                            <div class="meta-content">
                                                <span class="meta-label">Articles publiés</span>
                                                <span class="meta-value"><?php echo $article_count; ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="meta-item">
                                            <div class="meta-icon">
                                                <i class="bi bi-chat"></i>
                                            </div>
                                            <div class="meta-content">
                                                <span class="meta-label">Commentaires</span>
                                                <span class="meta-value"><?php echo $comment_count; ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="meta-item">
                                            <div class="meta-icon">
                                                <i class="bi bi-circle-fill"></i>
                                            </div>
                                            <div class="meta-content">
                                                <span class="meta-label">Statut</span>
                                                <span class="meta-value state-badge state-<?php echo htmlspecialchars($user['etat']); ?>">
                                                    <i class="bi bi-circle-fill"></i>
                                                    <?php echo htmlspecialchars($user['etat']); ?>
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
                        <i class="bi bi-people"></i>
                    </div>
                    <h4>Aucun utilisateur trouvé</h4>
                    <p>Il n'y a pas encore d'utilisateurs inscrits sur la plateforme.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal Ajout -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-person-plus me-2"></i>
                        Ajouter un nouvel utilisateur
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="add_nom" class="form-label">Nom complet *</label>
                                <input type="text" class="form-control" id="add_nom" name="nom" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="add_email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="add_email" name="email" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="add_username" class="form-label">Nom d'utilisateur *</label>
                                <input type="text" class="form-control" id="add_username" name="new_username" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="add_mot_de_passe" class="form-label">Mot de passe *</label>
                                <input type="password" class="form-control" id="add_mot_de_passe" name="mot_de_passe" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="add_role" class="form-label">Rôle</label>
                                <select class="form-select" id="add_role" name="role">
                                    <option value="subscriber">Abonné (Lecture seule)</option>
                                    <option value="auteur">Auteur (Créer des articles)</option>
                                    <option value="editor">Éditeur (Modérer + publier)</option>
                                    <option value="admin">Administrateur (Tous droits)</option>
                                </select>
                                <small class="text-muted mt-1 d-block">Définit les permissions de l'utilisateur</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="add_etat" class="form-label">État du compte</label>
                                <select class="form-select" id="add_etat" name="etat">
                                    <option value="actif">Actif (Accès autorisé)</option>
                                    <option value="inactif">Inactif (Accès bloqué)</option>
                                </select>
                                <small class="text-muted mt-1 d-block">Contrôle l'accès au compte</small>
                            </div>
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
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-person-gear me-2"></i>
                        Modifier l'utilisateur
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="username" id="edit_username">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_nom" class="form-label">Nom complet *</label>
                                <input type="text" class="form-control" id="edit_nom" name="nom" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="edit_email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="edit_email" name="email" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_role" class="form-label">Rôle</label>
                                <select class="form-select" id="edit_role" name="role">
                                    <option value="subscriber">Abonné (Lecture seule)</option>
                                    <option value="auteur">Auteur (Créer des articles)</option>
                                    <option value="editor">Éditeur (Modérer + publier)</option>
                                    <option value="admin">Administrateur (Tous droits)</option>
                                </select>
                                <small class="text-muted mt-1 d-block">Définit les permissions de l'utilisateur</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="edit_etat" class="form-label">État du compte</label>
                                <select class="form-select" id="edit_etat" name="etat">
                                    <option value="actif">Actif (Accès autorisé)</option>
                                    <option value="inactif">Inactif (Accès bloqué)</option>
                                </select>
                                <small class="text-muted mt-1 d-block">Contrôle l'accès au compte</small>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="edit_mot_de_passe" class="form-label">Nouveau mot de passe (laisser vide pour ne pas changer)</label>
                                <input type="password" class="form-control" id="edit_mot_de_passe" name="new_mot_de_passe">
                                <small class="text-muted">Remplissez uniquement si vous souhaitez modifier le mot de passe</small>
                            </div>
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
    <div class="modal fade" id="deleteUserModal" tabindex="-1">
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
                    <input type="hidden" name="username" id="delete_username">
                    <div class="modal-body">
                        <p class="text-center">Êtes-vous sûr de vouloir supprimer l'utilisateur <strong id="delete_nom"></strong> ?</p>
                        
                        <div class="alert alert-danger">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-exclamation-octagon fs-5 me-3"></i>
                                <div>
                                    <strong class="d-block">Cette action est irréversible !</strong>
                                    <small>Tous les articles et commentaires de cet utilisateur seront également supprimés.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg me-2"></i> Annuler
                        </button>
                        <button type="submit" name="action" value="delete" class="btn btn-danger">
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
        // Gérer l'édition des utilisateurs
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM chargé - gestion des utilisateurs');
            
            // Édition des utilisateurs
            document.querySelectorAll('.edit-user').forEach(button => {
                button.addEventListener('click', function() {
                    console.log('Bouton édition cliqué');
                    
                    const username = this.getAttribute('data-username');
                    const nom = this.getAttribute('data-nom');
                    const email = this.getAttribute('data-email');
                    const role = this.getAttribute('data-role');
                    const etat = this.getAttribute('data-etat');
                    
                    document.getElementById('edit_username').value = username;
                    document.getElementById('edit_nom').value = nom;
                    document.getElementById('edit_email').value = email;
                    document.getElementById('edit_role').value = role;
                    document.getElementById('edit_etat').value = etat;
                    
                    // Ouvrir le modal
                    const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
                    editModal.show();
                });
            });
            
            // Suppression des utilisateurs
            document.querySelectorAll('.delete-user').forEach(button => {
                button.addEventListener('click', function() {
                    console.log('Bouton suppression cliqué');
                    
                    const username = this.getAttribute('data-username');
                    const nom = this.getAttribute('data-nom');
                    
                    document.getElementById('delete_username').value = username;
                    document.getElementById('delete_nom').textContent = nom;
                    
                    // Ouvrir le modal
                    const deleteModal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
                    deleteModal.show();
                });
            });
            
            // Pour le bouton d'ajout, vérifier qu'il fonctionne
            const addButton = document.querySelector('[data-bs-target="#addUserModal"]');
            if (addButton) {
                addButton.addEventListener('click', function() {
                    console.log('Bouton ajout cliqué');
                });
            }
        });
    </script>
</body>
</html>