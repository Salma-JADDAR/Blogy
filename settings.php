<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Vérifier que l'utilisateur est connecté
requireLogin();

$page_title = "Paramètres du Compte";
$body_class = "settings-page";
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
    error_log("Settings error: " . $e->getMessage());
    $user_data = [];
}

// Traitement de la mise à jour des paramètres
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        // Mise à jour du profil
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
        
    } elseif ($action === 'update_preferences') {
        // Mise à jour des préférences
        $theme = $_POST['theme'] ?? 'light';
        $langue = $_POST['langue'] ?? 'fr';
        $notifications_email = isset($_POST['notifications_email']) ? 1 : 0;
        $notifications_push = isset($_POST['notifications_push']) ? 1 : 0;
        $newsletter = isset($_POST['newsletter']) ? 1 : 0;
        $affichage_articles = $_POST['affichage_articles'] ?? 'grid';
        
        try {
            // Ici, vous devriez avoir une table Preferences ou ajouter ces champs à la table Utilisateur
            // Pour cet exemple, on va supposer qu'ils sont dans la table Utilisateur
            $sql = "UPDATE Utilisateur 
                    SET theme = :theme,
                        langue = :langue,
                        notifications_email = :notifications_email,
                        notifications_push = :notifications_push,
                        newsletter = :newsletter,
                        affichage_articles = :affichage_articles
                    WHERE username = :username";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':theme' => $theme,
                ':langue' => $langue,
                ':notifications_email' => $notifications_email,
                ':notifications_push' => $notifications_push,
                ':newsletter' => $newsletter,
                ':affichage_articles' => $affichage_articles,
                ':username' => $username
            ]);
            
            // Mettre à jour la session
            $_SESSION['user']['theme'] = $theme;
            $_SESSION['user']['langue'] = $langue;
            
            $message = "Préférences mises à jour avec succès !";
            $message_type = "success";
            
            // Rafraîchir les données utilisateur
            $sql = "SELECT * FROM Utilisateur WHERE username = :username";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':username' => $username]);
            $user_data = $stmt->fetch();
            
        } catch(PDOException $e) {
            $message = "Erreur lors de la mise à jour des préférences: " . $e->getMessage();
            $message_type = "danger";
            error_log("Update preferences error: " . $e->getMessage());
        }
        
    } elseif ($action === 'update_privacy') {
        // Mise à jour des paramètres de confidentialité
        $profil_public = isset($_POST['profil_public']) ? 1 : 0;
        $afficher_email = isset($_POST['afficher_email']) ? 1 : 0;
        $accepter_messages = isset($_POST['accepter_messages']) ? 1 : 0;
        $indexation_moteurs = isset($_POST['indexation_moteurs']) ? 1 : 0;
        
        try {
            $sql = "UPDATE Utilisateur 
                    SET profil_public = :profil_public,
                        afficher_email = :afficher_email,
                        accepter_messages = :accepter_messages,
                        indexation_moteurs = :indexation_moteurs
                    WHERE username = :username";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':profil_public' => $profil_public,
                ':afficher_email' => $afficher_email,
                ':accepter_messages' => $accepter_messages,
                ':indexation_moteurs' => $indexation_moteurs,
                ':username' => $username
            ]);
            
            $message = "Paramètres de confidentialité mis à jour avec succès !";
            $message_type = "success";
            
            // Rafraîchir les données utilisateur
            $sql = "SELECT * FROM Utilisateur WHERE username = :username";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':username' => $username]);
            $user_data = $stmt->fetch();
            
        } catch(PDOException $e) {
            $message = "Erreur lors de la mise à jour de la confidentialité: " . $e->getMessage();
            $message_type = "danger";
            error_log("Update privacy error: " . $e->getMessage());
        }
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
        
        /* Page des paramètres */
        .settings-page {
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
            min-height: 100vh;
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
        
        /* Navigation des paramètres */
        .settings-nav {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        
        .settings-nav .nav {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .settings-nav .nav-link {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            color: var(--gray-color);
            font-weight: 500;
            border: 1px solid transparent;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .settings-nav .nav-link:hover {
            color: var(--primary-color);
            background: rgba(247, 88, 21, 0.05);
            border-color: rgba(247, 88, 21, 0.1);
        }
        
        .settings-nav .nav-link.active {
            color: white;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            border-color: var(--primary-color);
            box-shadow: 0 4px 12px rgba(247, 88, 21, 0.2);
        }
        
        /* Cartes de paramètres */
        .settings-card {
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
        
        .settings-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-light);
        }
        
        .settings-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            background: linear-gradient(to bottom, var(--primary-color), var(--primary-light));
            border-radius: 16px 0 0 16px;
        }
        
        .settings-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 1.5rem;
        }
        
        .settings-icon {
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
        
        .settings-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark-color);
            margin: 0;
        }
        
        .settings-title::after {
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
        
        /* Checkboxes et radios personnalisés */
        .form-check {
            margin-bottom: 1rem;
            padding-left: 2.5rem;
        }
        
        .form-check-input {
            width: 20px;
            height: 20px;
            margin-top: 0.2rem;
            margin-left: -2.5rem;
            border: 2px solid var(--border-color);
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .form-check-input:focus {
            box-shadow: 0 0 0 3px rgba(247, 88, 21, 0.1);
        }
        
        .form-check-label {
            color: var(--dark-color);
            font-size: 0.95rem;
            cursor: pointer;
            line-height: 1.5;
        }
        
        .form-check-label small {
            display: block;
            color: var(--gray-color);
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }
        
        /* Switch personnalisé */
        .form-switch .form-check-input {
            width: 3em;
            height: 1.5em;
            margin-top: 0.3rem;
        }
        
        /* Select personnalisé */
        select.form-control {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 16px 12px;
            padding-right: 2.5rem;
            appearance: none;
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
        
        /* Tab Content */
        .tab-content {
            margin-top: 1.5rem;
        }
        
        .tab-pane {
            display: none;
            animation: fadeIn 0.3s ease;
        }
        
        .tab-pane.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
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
        
        /* Hint text */
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
        
        /* Danger zone */
        .danger-zone {
            border: 2px solid var(--danger-color);
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 2rem;
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.05), rgba(220, 38, 38, 0.02));
        }
        
        .danger-zone-header {
            color: var(--danger-color);
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
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
            
            .settings-nav .nav {
                flex-direction: column;
            }
            
            .settings-nav .nav-link {
                text-align: center;
            }
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
                                    <i class="bi bi-gear"></i>
                                </div>
                                <div>
                                    <h1 class="header-title-orange mb-1">Paramètres du Compte</h1>
                                    <nav class="breadcrumb-orange">
                                        <a href="index.php" class="breadcrumb-link">Accueil</a>
                                        <span class="breadcrumb-separator">/</span>
                                        <a href="profile.php" class="breadcrumb-link">Profil</a>
                                        <span class="breadcrumb-separator">/</span>
                                        <span class="breadcrumb-current">Paramètres</span>
                                    </nav>
                                </div>
                            </div>
                            <p class="header-desc">
                                <i class="bi bi-sliders me-1"></i>
                                Gérez vos paramètres personnels, préférences et confidentialité
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
                                        <p class="stat-label">Articles</p>
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
                                        <p class="stat-label">Membre depuis</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 col-sm-6">
                                <div class="stat-box-orange">
                                    <div class="stat-icon" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                                        <i class="bi bi-gear-fill"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3 class="stat-number"><?php echo htmlspecialchars($user['role']); ?></h3>
                                        <p class="stat-label">Rôle actuel</p>
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
            
            <!-- Navigation des paramètres -->
            <div class="settings-nav">
                <ul class="nav nav-pills" id="settingsTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab">
                            <i class="bi bi-person me-1"></i> Profil
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="preferences-tab" data-bs-toggle="tab" data-bs-target="#preferences" type="button" role="tab">
                            <i class="bi bi-sliders me-1"></i> Préférences
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="privacy-tab" data-bs-toggle="tab" data-bs-target="#privacy" type="button" role="tab">
                            <i class="bi bi-shield-lock me-1"></i> Confidentialité
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="danger-tab" data-bs-toggle="tab" data-bs-target="#danger" type="button" role="tab">
                            <i class="bi bi-exclamation-triangle me-1"></i> Zone de danger
                        </button>
                    </li>
                </ul>
            </div>
            
            <!-- Contenu des onglets -->
            <div class="tab-content" id="settingsTabContent">
                <!-- Onglet Profil -->
                <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                    <div class="settings-card">
                        <div class="settings-header">
                            <div class="settings-icon">
                                <i class="bi bi-person"></i>
                            </div>
                            <h3 class="settings-title">Informations du profil</h3>
                        </div>
                        
                        <form method="POST" action="" id="profileForm">
                            <input type="hidden" name="action" value="update_profile">
                            
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
                                    <i class="bi bi-save"></i> Enregistrer les modifications
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Onglet Préférences -->
                <div class="tab-pane fade" id="preferences" role="tabpanel" aria-labelledby="preferences-tab">
                    <div class="settings-card">
                        <div class="settings-header">
                            <div class="settings-icon">
                                <i class="bi bi-sliders"></i>
                            </div>
                            <h3 class="settings-title">Préférences personnelles</h3>
                        </div>
                        
                        <form method="POST" action="" id="preferencesForm">
                            <input type="hidden" name="action" value="update_preferences">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-section">
                                        <label class="form-label">Thème de l'interface</label>
                                        <select class="form-control" name="theme">
                                            <option value="light" <?php echo ($user_data['theme'] ?? 'light') == 'light' ? 'selected' : ''; ?>>Thème clair</option>
                                            <option value="dark" <?php echo ($user_data['theme'] ?? 'light') == 'dark' ? 'selected' : ''; ?>>Thème sombre</option>
                                            <option value="auto" <?php echo ($user_data['theme'] ?? 'light') == 'auto' ? 'selected' : ''; ?>>Automatique (système)</option>
                                        </select>
                                        <div class="hint-text">
                                            <i class="bi bi-palette"></i>
                                            Choisissez l'apparence du site
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-section">
                                        <label class="form-label">Langue</label>
                                        <select class="form-control" name="langue">
                                            <option value="fr" <?php echo ($user_data['langue'] ?? 'fr') == 'fr' ? 'selected' : ''; ?>>Français</option>
                                            <option value="en" <?php echo ($user_data['langue'] ?? 'fr') == 'en' ? 'selected' : ''; ?>>English</option>
                                            <option value="es" <?php echo ($user_data['langue'] ?? 'fr') == 'es' ? 'selected' : ''; ?>>Español</option>
                                            <option value="ar" <?php echo ($user_data['langue'] ?? 'fr') == 'ar' ? 'selected' : ''; ?>>العربية</option>
                                        </select>
                                        <div class="hint-text">
                                            <i class="bi bi-translate"></i>
                                            Choisissez la langue de l'interface
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-section">
                                        <label class="form-label">Affichage des articles</label>
                                        <select class="form-control" name="affichage_articles">
                                            <option value="grid" <?php echo ($user_data['affichage_articles'] ?? 'grid') == 'grid' ? 'selected' : ''; ?>>Grille</option>
                                            <option value="list" <?php echo ($user_data['affichage_articles'] ?? 'grid') == 'list' ? 'selected' : ''; ?>>Liste</option>
                                            <option value="compact" <?php echo ($user_data['affichage_articles'] ?? 'grid') == 'compact' ? 'selected' : ''; ?>>Compact</option>
                                        </select>
                                        <div class="hint-text">
                                            <i class="bi bi-grid-3x3-gap"></i>
                                            Comment afficher les articles sur le site
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <div class="form-section">
                                <h5 class="mb-3" style="color: var(--primary-color);">
                                    <i class="bi bi-bell me-2"></i>
                                    Notifications
                                </h5>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="notifications_email" name="notifications_email" 
                                               <?php echo ($user_data['notifications_email'] ?? 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="notifications_email">
                                            Notifications par email
                                            <small>Recevoir les notifications par email (nouvelles réponses, mentions, etc.)</small>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="notifications_push" name="notifications_push" 
                                               <?php echo ($user_data['notifications_push'] ?? 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="notifications_push">
                                            Notifications push
                                            <small>Recevoir les notifications sur le site</small>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="newsletter" name="newsletter" 
                                               <?php echo ($user_data['newsletter'] ?? 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="newsletter">
                                            Newsletter
                                            <small>Recevoir notre newsletter hebdomadaire</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Enregistrer les préférences
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Onglet Confidentialité -->
                <div class="tab-pane fade" id="privacy" role="tabpanel" aria-labelledby="privacy-tab">
                    <div class="settings-card">
                        <div class="settings-header">
                            <div class="settings-icon">
                                <i class="bi bi-shield-lock"></i>
                            </div>
                            <h3 class="settings-title">Confidentialité</h3>
                        </div>
                        
                        <form method="POST" action="" id="privacyForm">
                            <input type="hidden" name="action" value="update_privacy">
                            
                            <div class="form-section">
                                <h5 class="mb-3" style="color: var(--primary-color);">
                                    <i class="bi bi-eye me-2"></i>
                                    Visibilité du profil
                                </h5>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="profil_public" name="profil_public" 
                                               <?php echo ($user_data['profil_public'] ?? 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="profil_public">
                                            Profil public
                                            <small>Rendre votre profil visible aux autres utilisateurs</small>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="afficher_email" name="afficher_email" 
                                               <?php echo ($user_data['afficher_email'] ?? 0) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="afficher_email">
                                            Afficher l'email public
                                            <small>Afficher votre adresse email sur votre profil public</small>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="accepter_messages" name="accepter_messages" 
                                               <?php echo ($user_data['accepter_messages'] ?? 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="accepter_messages">
                                            Accepter les messages privés
                                            <small>Autoriser les autres utilisateurs à vous envoyer des messages</small>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="indexation_moteurs" name="indexation_moteurs" 
                                               <?php echo ($user_data['indexation_moteurs'] ?? 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="indexation_moteurs">
                                            Autoriser l'indexation
                                            <small>Permettre aux moteurs de recherche d'indexer votre profil</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <div class="form-section">
                                <h5 class="mb-3" style="color: var(--primary-color);">
                                    <i class="bi bi-download me-2"></i>
                                    Données personnelles
                                </h5>
                                
                                <p class="text-muted mb-4">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Vous pouvez télécharger une copie de vos données personnelles ou demander leur suppression.
                                </p>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <button type="button" class="btn btn-secondary w-100 mb-3" id="downloadDataBtn">
                                            <i class="bi bi-download me-1"></i> Télécharger mes données
                                        </button>
                                    </div>
                                    <div class="col-md-6">
                                        <button type="button" class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                                            <i class="bi bi-trash me-1"></i> Supprimer mon compte
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Enregistrer les paramètres
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Onglet Zone de danger -->
                <div class="tab-pane fade" id="danger" role="tabpanel" aria-labelledby="danger-tab">
                    <div class="settings-card">
                        <div class="settings-header">
                            <div class="settings-icon" style="background: linear-gradient(135deg, var(--danger-color), #f87171);">
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                            <h3 class="settings-title">Zone de danger</h3>
                        </div>
                        
                        <div class="danger-zone">
                            <div class="danger-zone-header">
                                <i class="bi bi-exclamation-octagon"></i>
                                <span>Suppression du compte</span>
                            </div>
                            
                            <p class="text-muted mb-3">
                                La suppression de votre compte est définitive. Toutes vos données seront effacées de manière irréversible :
                            </p>
                            
                            <ul class="text-muted mb-4">
                                <li>Tous vos articles seront supprimés</li>
                                <li>Tous vos commentaires seront supprimés</li>
                                <li>Votre profil ne sera plus accessible</li>
                                <li>Cette action ne peut pas être annulée</li>
                            </ul>
                            
                            <div class="alert alert-warning">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-exclamation-triangle fs-5 me-3"></i>
                                    <div>
                                        <strong class="d-block">Attention !</strong>
                                        <small>Avant de supprimer votre compte, assurez-vous d'avoir sauvegardé toutes les données importantes.</small>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="button" class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                                <i class="bi bi-trash me-1"></i> Supprimer définitivement mon compte
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php require_once 'includes/footer.php'; ?>
    
    <!-- Modal de suppression du compte -->
    <div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Confirmer la suppression
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-center">Êtes-vous absolument sûr de vouloir supprimer votre compte ?</p>
                    
                    <div class="alert alert-danger">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-exclamation-octagon fs-5 me-3"></i>
                            <div>
                                <strong class="d-block">Cette action est irréversible !</strong>
                                <small>Toutes vos données seront définitivement effacées.</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="confirmDelete" name="confirmDelete">
                        <label class="form-check-label" for="confirmDelete">
                            Je comprends que toutes mes données seront définitivement supprimées et que cette action ne peut pas être annulée.
                        </label>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="deletePassword" class="form-label">Mot de passe actuel</label>
                        <input type="password" class="form-control" id="deletePassword" placeholder="Saisissez votre mot de passe pour confirmer">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-2"></i> Annuler
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn" disabled>
                        <i class="bi bi-trash me-2"></i> Supprimer définitivement
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Initialisation des tabs
        const triggerTabList = document.querySelectorAll('#settingsTab button');
        triggerTabList.forEach(triggerEl => {
            const tabTrigger = new bootstrap.Tab(triggerEl);
            
            triggerEl.addEventListener('click', event => {
                event.preventDefault();
                tabTrigger.show();
            });
        });
        
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

        // Validation des formulaires
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
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Enregistrement...';
            submitBtn.disabled = true;
        });

        // Initialize counters
        if (nameInput.value) updateCounter(nameInput, nameCounter, 100);
        if (emailInput.value) updateCounter(emailInput, emailCounter, 150);
        
        // Reset form handler pour le formulaire profil
        document.querySelector('#profileForm button[type="reset"]').addEventListener('click', function() {
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
        
        // Gestion de la suppression du compte
        const confirmDeleteCheckbox = document.getElementById('confirmDelete');
        const deletePasswordInput = document.getElementById('deletePassword');
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        
        function updateDeleteButton() {
            const isChecked = confirmDeleteCheckbox.checked;
            const hasPassword = deletePasswordInput.value.length > 0;
            confirmDeleteBtn.disabled = !(isChecked && hasPassword);
        }
        
        confirmDeleteCheckbox.addEventListener('change', updateDeleteButton);
        deletePasswordInput.addEventListener('input', updateDeleteButton);
        
        confirmDeleteBtn.addEventListener('click', function() {
            if (confirm('Êtes-vous ABSOLUMENT SÛR ? Cette action ne peut PAS être annulée !')) {
                // Ici, vous devriez faire un appel AJAX pour supprimer le compte
                alert('Fonctionnalité de suppression de compte en cours de développement...');
                
                // Pour l'instant, rediriger vers la page de déconnexion
                // window.location.href = 'logout.php?delete=1';
            }
        });
        
        // Téléchargement des données
        document.getElementById('downloadDataBtn').addEventListener('click', function() {
            alert('Fonctionnalité de téléchargement des données en cours de développement...');
            
            // Ici, vous devriez faire un appel AJAX pour générer et télécharger les données
            // window.location.href = 'export_data.php';
        });
        
        // Validation des autres formulaires
        document.getElementById('preferencesForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Enregistrement...';
            submitBtn.disabled = true;
        });
        
        document.getElementById('privacyForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Enregistrement...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>