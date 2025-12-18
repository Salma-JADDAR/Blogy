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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <?php require_once 'includes/header.php'; ?>
    <style>
        .profile-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin: 0 auto 15px;
            border: 3px solid white;
        }
        
        .profile-body {
            padding: 30px;
        }
        
        .stat-card {
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            background: #f8f9fa;
            margin-bottom: 20px;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #667eea;
        }
        
        .stat-label {
            font-size: 14px;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .nav-pills .nav-link {
            border-radius: 10px;
            padding: 12px 20px;
            margin-bottom: 10px;
            color: #495057;
        }
        
        .nav-pills .nav-link.active {
            background: #667eea;
            color: white;
        }
        
        .form-label {
            font-weight: 500;
            color: #495057;
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
            <!-- Message d'alerte -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <!-- Sidebar Profil -->
                <div class="col-lg-4 mb-4">
                    <div class="profile-card">
                        <div class="profile-header">
                            <div class="profile-avatar">
                                <i class="bi bi-person"></i>
                            </div>
                            <h3 class="mb-2"><?php echo htmlspecialchars($user['nom']); ?></h3>
                            <p class="mb-1">@<?php echo htmlspecialchars($user['username']); ?></p>
                            <span class="badge badge-<?php echo $user['role']; ?>">
                                <?php echo $user['role']; ?>
                            </span>
                        </div>
                        
                        <div class="profile-body">
                            <div class="row">
                                <div class="col-6">
                                    <div class="stat-card">
                                        <div class="stat-number">
                                            <?php 
                                            try {
                                                $sql = "SELECT COUNT(*) as count FROM Article WHERE username = :username";
                                                $stmt = $pdo->prepare($sql);
                                                $stmt->execute([':username' => $username]);
                                                echo $stmt->fetch()['count'];
                                            } catch(PDOException $e) {
                                                echo "0";
                                            }
                                            ?>
                                        </div>
                                        <div class="stat-label">Articles</div>
                                    </div>
                                </div>
                                
                                <div class="col-6">
                                    <div class="stat-card">
                                        <div class="stat-number">
                                            <?php 
                                            try {
                                                $sql = "SELECT COUNT(*) as count FROM Commentaire WHERE username = :username";
                                                $stmt = $pdo->prepare($sql);
                                                $stmt->execute([':username' => $username]);
                                                echo $stmt->fetch()['count'];
                                            } catch(PDOException $e) {
                                                echo "0";
                                            }
                                            ?>
                                        </div>
                                        <div class="stat-label">Commentaires</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <p><strong>Email :</strong><br><?php echo htmlspecialchars($user['email']); ?></p>
                                <p><strong>Date d'inscription :</strong><br>
                                    <?php echo date('d/m/Y', strtotime($user['date_creation'])); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Contenu principal -->
                <div class="col-lg-8">
                    <div class="profile-card">
                        <div class="profile-body">
                            <h3 class="mb-4">Modifier mon profil</h3>
                            
                            <form method="POST" action="">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="nom" class="form-label">Nom complet *</label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="nom" 
                                                   name="nom" 
                                                   value="<?php echo htmlspecialchars($user_data['nom'] ?? ''); ?>"
                                                   required>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="username" class="form-label">Nom d'utilisateur</label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="username" 
                                                   value="<?php echo htmlspecialchars($user['username']); ?>"
                                                   disabled>
                                            <small class="text-muted">Le nom d'utilisateur ne peut pas être modifié</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" 
                                           class="form-control" 
                                           id="email" 
                                           name="email" 
                                           value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>"
                                           required>
                                </div>
                                
                                <hr class="my-4">
                                
                                <h5 class="mb-3">Changer le mot de passe</h5>
                                <p class="text-muted">Laissez ces champs vides si vous ne voulez pas changer votre mot de passe</p>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="current_password" class="form-label">Mot de passe actuel</label>
                                            <input type="password" 
                                                   class="form-control" 
                                                   id="current_password" 
                                                   name="current_password">
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
                                                   minlength="6">
                                            <small class="text-muted">Minimum 6 caractères</small>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe</label>
                                            <input type="password" 
                                                   class="form-control" 
                                                   id="confirm_password" 
                                                   name="confirm_password">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                    <a href="index.php" class="btn btn-outline-secondary me-2">Annuler</a>
                                    <button type="submit" class="btn btn-primary">Mettre à jour</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php require_once 'includes/footer.php'; ?>
</body>
</html>