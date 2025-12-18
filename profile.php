<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Vérifier que l'utilisateur est connecté
requireLogin();

$page_title = "Mon Profil";
$body_class = "profile-page";
$message = '';
$message_type = '';

// Récupérer les informations utilisateur
$user = $_SESSION['user'];
$username = $user['username'];

// Récupérer les informations complètes depuis la base
try {
    $sql = "SELECT * FROM Utilisateur WHERE username = :username";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':username' => $username]);
    $user_data = $stmt->fetch();
} catch(PDOException $e) {
    error_log("Profile error: " . $e->getMessage());
    $user_data = [];
}

// Traitement de la mise à jour du profil
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    // Validation du nom
    if (empty($nom)) {
        $errors[] = "Le nom est obligatoire";
    }
    
    // Validation de l'email
    if (empty($email)) {
        $errors[] = "L'email est obligatoire";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email invalide";
    }
    
    // Vérifier si l'email est déjà utilisé
    if ($email !== $user_data['email']) {
        try {
            $sql = "SELECT username FROM Utilisateur WHERE email = :email AND username != :username";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':email' => $email, ':username' => $username]);
            if ($stmt->rowCount() > 0) {
                $errors[] = "Cet email est déjà utilisé par un autre compte";
            }
        } catch(PDOException $e) {
            error_log("Email check error: " . $e->getMessage());
        }
    }
    
    // Validation du mot de passe
    if (!empty($new_password)) {
        if (empty($current_password)) {
            $errors[] = "Veuillez saisir votre mot de passe actuel";
        } elseif (!password_verify($current_password, $user_data['mot_de_passe'])) {
            $errors[] = "Mot de passe actuel incorrect";
        } elseif (strlen($new_password) < 6) {
            $errors[] = "Le nouveau mot de passe doit contenir au moins 6 caractères";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "Les nouveaux mots de passe ne correspondent pas";
        }
    }
    
    if (empty($errors)) {
        try {
            // Préparer la requête de mise à jour
            $update_data = [
                ':nom' => $nom,
                ':email' => $email,
                ':username' => $username
            ];
            
            $sql = "UPDATE Utilisateur SET nom = :nom, email = :email";
            
            // Si un nouveau mot de passe est fourni
            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $sql .= ", mot_de_passe = :mot_de_passe";
                $update_data[':mot_de_passe'] = $hashed_password;
            }
            
            $sql .= " WHERE username = :username";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($update_data);
            
            // Mettre à jour la session
            $_SESSION['user']['nom'] = $nom;
            $_SESSION['user']['email'] = $email;
            $user = $_SESSION['user'];
            
            $message = "Profil mis à jour avec succès !";
            $message_type = "success";
            
            // Rafraîchir les données utilisateur
            $sql = "SELECT * FROM Utilisateur WHERE username = :username";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':username' => $username]);
            $user_data = $stmt->fetch();
            
        } catch(PDOException $e) {
            $message = "Erreur lors de la mise à jour: " . $e->getMessage();
            $message_type = "danger";
            error_log("Update profile error: " . $e->getMessage());
        }
    } else {
        $message = implode("<br>", $errors);
        $message_type = "danger";
    }
}

// Récupérer les statistiques
try {
    $sql_articles = "SELECT COUNT(*) as count FROM Article WHERE username = :username";
    $stmt_articles = $pdo->prepare($sql_articles);
    $stmt_articles->execute([':username' => $username]);
    $articles_count = $stmt_articles->fetch()['count'];
} catch(PDOException $e) {
    $articles_count = 0;
}

try {
    $sql_comments = "SELECT COUNT(*) as count FROM Commentaire WHERE username = :username";
    $stmt_comments = $pdo->prepare($sql_comments);
    $stmt_comments->execute([':username' => $username]);
    $comments_count = $stmt_comments->fetch()['count'];
} catch(PDOException $e) {
    $comments_count = 0;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <?php require_once 'includes/header.php'; ?>
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
        
        /* Cartes profil */
        .profile-card {
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
        
        .profile-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-light);
        }
        
        .profile-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            background: linear-gradient(to bottom, var(--primary-color), var(--primary-light));
            border-radius: 16px 0 0 16px;
        }
        
        /* Header orange comme dans l'exemple */
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

        /* Carte utilisateur */
        .user-card-orange {
            display: inline-block;
            background: #f9fafb;
            padding: 1rem 1.25rem;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            transition: all 0.3s;
        }

        .user-card-orange:hover {
            background: white;
            border-color: #f75815;
            box-shadow: 0 4px 12px rgba(247, 88, 21, 0.1);
        }

        .user-avatar-orange {
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

        .user-name {
            font-weight: 600;
            color: #1f2937;
            font-size: 0.95rem;
        }

        .user-status {
            font-size: 0.8rem;
            color: #6b7280;
        }
        
        /* Stats Grid */
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
        
        /* Carte profil détaillée */
        .profile-detail-card {
            background: white;
            border-radius: 16px;
            padding: 1.75rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
        }
        
        .profile-detail-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(to bottom, var(--primary-color), var(--primary-light));
            border-radius: 16px 0 0 16px;
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 1.5rem;
        }
        
        .profile-icon {
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
        
        .profile-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark-color);
            margin: 0;
        }
        
        .profile-title::after {
            content: '';
            display: block;
            width: 40px;
            height: 3px;
            background: var(--primary-color);
            margin-top: 8px;
            border-radius: 2px;
        }
        
        /* Badge role */
        .role-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            white-space: nowrap;
            background: linear-gradient(135deg, var(--success-color), #34d399);
            color: white;
        }
        
        .role-badge.admin {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
        }
        
        .role-badge.editor {
            background: linear-gradient(135deg, #3b82f6, #60a5fa);
        }
        
        .role-badge.auteur {
            background: linear-gradient(135deg, var(--warning-color), #fbbf24);
        }
        
        .role-badge.user {
            background: linear-gradient(135deg, var(--gray-color), #9ca3af);
        }
        
        /* Formulaires */
        .form-section {
            margin-top: 1.5rem;
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
        
        .form-control:disabled {
            background-color: #f9fafb;
            cursor: not-allowed;
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 100px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
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
        }
        
        .btn-secondary:hover {
            background: var(--light-color);
            border-color: var(--primary-color);
        }
        
        /* Alertes */
        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem 1.25rem;
            margin-bottom: 2rem;
        }
        
        /* Info utilisateur */
        .user-info-grid {
            display: grid;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            background: var(--light-color);
            border-radius: 10px;
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s ease;
        }
        
        .info-item:hover {
            background: #e5e7eb;
            transform: translateX(5px);
        }
        
        .info-label {
            color: var(--gray-color);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .info-value {
            font-weight: 700;
            color: var(--dark-color);
            font-size: 1.1rem;
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
            
            .user-card-orange {
                margin-top: 1rem;
            }
        }
        
        .hint-text {
            font-size: 0.85rem;
            color: var(--gray-color);
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .char-counter {
            text-align: right;
            font-size: 0.85rem;
            color: var(--gray-color);
            margin-top: 5px;
        }
    </style>
</head>
<body class="<?php echo $body_class; ?>">
    <?php 
    // Ré-inclure header.php pour la structure complète
    $include_css_only = false;
    require_once 'includes/header.php'; 
    ?>
    
    <div class="main">
        <div class="container py-5">
            <!-- Page Header -->
            <div class="orange-header">
                <div class="container-fluid">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center mb-3">
                                <div class="header-icon-orange me-3">
                                    <i class="bi bi-person-circle"></i>
                                </div>
                                <div>
                                    <h1 class="header-title-orange mb-1">Mon Profil</h1>
                                    <nav class="breadcrumb-orange">
                                        <a href="index.php" class="breadcrumb-link">Accueil</a>
                                        <span class="breadcrumb-separator">/</span>
                                        <span class="breadcrumb-current">Profil</span>
                                    </nav>
                                </div>
                            </div>
                            <p class="header-desc">
                                <i class="bi bi-person-badge me-1"></i>
                                Gérez vos informations personnelles et vos paramètres de compte
                            </p>
                        </div>
                        
                        <div class="col-md-4 text-md-end">
                            <div class="d-flex flex-column align-items-end gap-3">
                                <div class="user-card-orange">
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar-orange me-2">
                                            <?php 
                                            $userInitial = isset($user['nom']) ? strtoupper(substr($user['nom'], 0, 1)) : 'U';
                                            echo $userInitial;
                                            ?>
                                        </div>
                                        <div class="text-start">
                                            <div class="user-name"><?php echo htmlspecialchars($user['nom'] ?? 'Utilisateur'); ?></div>
                                            <div class="user-status">
                                                <i class="bi bi-circle-fill text-success me-1"></i>
                                                <span>Connecté en tant que <?php echo htmlspecialchars($user['role']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stats-container">
                        <div class="row g-3">
                            <div class="col-md-3 col-sm-6">
                                <div class="stat-box-orange">
                                    <div class="stat-icon">
                                        <i class="bi bi-file-text"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3 class="stat-number"><?php echo $articles_count; ?></h3>
                                        <p class="stat-label">Articles publiés</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 col-sm-6">
                                <div class="stat-box-orange">
                                    <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                                        <i class="bi bi-chat-dots"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3 class="stat-number"><?php echo $comments_count; ?></h3>
                                        <p class="stat-label">Commentaires</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 col-sm-6">
                                <div class="stat-box-orange">
                                    <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                                        <i class="bi bi-calendar3"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3 class="stat-number"><?php echo date('d/m/Y', strtotime($user['date_creation'])); ?></h3>
                                        <p class="stat-label">Date d'inscription</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 col-sm-6">
                                <div class="stat-box-orange">
                                    <div class="stat-icon" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                                        <i class="bi bi-activity"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3 class="stat-number">Actif</h3>
                                        <p class="stat-label">Statut du compte</p>
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
                <!-- Colonne gauche - Informations du profil -->
                <div class="col-lg-4">
                    <!-- Carte Profil -->
                    <div class="profile-detail-card">
                        <div class="profile-header">
                            <div class="profile-icon">
                                <i class="bi bi-person"></i>
                            </div>
                            <h3 class="profile-title">Profil</h3>
                        </div>
                        
                        <div class="user-info-grid">
                            <div class="info-item">
                                <div class="info-label">
                                    <i class="bi bi-person"></i>
                                    <span>Nom complet</span>
                                </div>
                                <div class="info-value"><?php echo htmlspecialchars($user['nom']); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">
                                    <i class="bi bi-at"></i>
                                    <span>Nom d'utilisateur</span>
                                </div>
                                <div class="info-value">@<?php echo htmlspecialchars($user['username']); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">
                                    <i class="bi bi-envelope"></i>
                                    <span>Email</span>
                                </div>
                                <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">
                                    <i class="bi bi-person-badge"></i>
                                    <span>Rôle</span>
                                </div>
                                <div class="info-value">
                                    <span class="role-badge <?php echo $user['role']; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">
                                    <i class="bi bi-calendar"></i>
                                    <span>Membre depuis</span>
                                </div>
                                <div class="info-value"><?php echo date('d/m/Y', strtotime($user['date_creation'])); ?></div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <a href="index.php" class="btn btn-secondary w-100">
                                <i class="bi bi-arrow-left"></i> Retour à l'accueil
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Colonne droite - Formulaire de modification -->
                <div class="col-lg-8">
                    <div class="profile-card">
                        <div class="profile-header">
                            <div class="profile-icon">
                                <i class="bi bi-gear"></i>
                            </div>
                            <h3 class="profile-title">Modifier mon profil</h3>
                        </div>
                        
                        <form method="POST" action="" id="profileForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-section">
                                        <label for="nom" class="form-label">Nom complet *</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="nom" 
                                               name="nom" 
                                               value="<?php echo htmlspecialchars($user_data['nom'] ?? ''); ?>"
                                               required
                                               placeholder="Votre nom complet..."
                                               maxlength="100">
                                        <div class="char-counter" id="nameCounter">
                                            <?php echo strlen($user_data['nom'] ?? ''); ?>/100 caractères
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-section">
                                        <label for="username" class="form-label">Nom d'utilisateur</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="username" 
                                               value="<?php echo htmlspecialchars($user['username']); ?>"
                                               disabled>
                                        <div class="hint-text">
                                            <i class="bi bi-info-circle"></i>
                                            Le nom d'utilisateur ne peut pas être modifié
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>"
                                       required
                                       placeholder="votre.email@exemple.com"
                                       maxlength="150">
                                <div class="char-counter" id="emailCounter">
                                    <?php echo strlen($user_data['email'] ?? ''); ?>/150 caractères
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <div class="form-section">
                                <h5 class="mb-3" style="color: var(--primary-color);">
                                    <i class="bi bi-shield-lock me-2"></i>
                                    Changer le mot de passe
                                </h5>
                                <p class="text-muted">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Laissez ces champs vides si vous ne voulez pas changer votre mot de passe
                                </p>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="current_password" class="form-label">Mot de passe actuel</label>
                                            <input type="password" 
                                                   class="form-control" 
                                                   id="current_password" 
                                                   name="current_password"
                                                   placeholder="Saisissez votre mot de passe actuel...">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="new_password" class="form-label">Nouveau mot de passe</label>
                                            <input type="password" 
                                                   class="form-control" 
                                                   id="new_password" 
                                                   name="new_password"
                                                   minlength="6"
                                                   placeholder="Minimum 6 caractères...">
                                            <div class="hint-text">
                                                <i class="bi bi-key"></i>
                                                Minimum 6 caractères
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe</label>
                                            <input type="password" 
                                                   class="form-control" 
                                                   id="confirm_password" 
                                                   name="confirm_password"
                                                   placeholder="Retapez le nouveau mot de passe...">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                <button type="reset" class="btn btn-secondary me-2">
                                    <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Mettre à jour le profil
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php require_once 'includes/footer.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Character counters
        const nameInput = document.getElementById('nom');
        const emailInput = document.getElementById('email');
        const nameCounter = document.getElementById('nameCounter');
        const emailCounter = document.getElementById('emailCounter');

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

        nameInput.addEventListener('input', () => {
            updateCounter(nameInput, nameCounter, 100);
        });

        emailInput.addEventListener('input', () => {
            updateCounter(emailInput, emailCounter, 150);
        });

        // Form validation
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            const nom = document.getElementById('nom').value.trim();
            const email = document.getElementById('email').value.trim();
            const currentPassword = document.getElementById('current_password').value;
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            // Validation de base
            if (!nom) {
                e.preventDefault();
                alert('Veuillez saisir votre nom complet');
                document.getElementById('nom').focus();
                return;
            }
            
            if (!email) {
                e.preventDefault();
                alert('Veuillez saisir votre email');
                document.getElementById('email').focus();
                return;
            }
            
            // Validation email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Veuillez saisir une adresse email valide');
                document.getElementById('email').focus();
                return;
            }
            
            // Validation mot de passe
            if (newPassword || confirmPassword) {
                if (!currentPassword) {
                    e.preventDefault();
                    alert('Veuillez saisir votre mot de passe actuel pour changer votre mot de passe');
                    document.getElementById('current_password').focus();
                    return;
                }
                
                if (newPassword.length < 6) {
                    e.preventDefault();
                    alert('Le nouveau mot de passe doit contenir au moins 6 caractères');
                    document.getElementById('new_password').focus();
                    return;
                }
                
                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    alert('Les nouveaux mots de passe ne correspondent pas');
                    document.getElementById('confirm_password').focus();
                    return;
                }
            }
            
            // Si tout est bon, montrer un état de chargement
            const submitBtn = document.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Mise à jour en cours...';
            submitBtn.disabled = true;
        });

        // Initialize counters
        if (nameInput.value) updateCounter(nameInput, nameCounter, 100);
        if (emailInput.value) updateCounter(emailInput, emailCounter, 150);
        
        // Reset form handler
        document.querySelector('button[type="reset"]').addEventListener('click', function() {
            if (confirm('Voulez-vous vraiment réinitialiser le formulaire ? Toutes les modifications non enregistrées seront perdues.')) {
                // Re-initialize counters
                setTimeout(() => {
                    if (nameInput.value) updateCounter(nameInput, nameCounter, 100);
                    if (emailInput.value) updateCounter(emailInput, emailCounter, 150);
                }, 100);
            } else {
                return false;
            }
        });
    </script>
</body>
</html>