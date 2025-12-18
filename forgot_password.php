<?php
require_once 'includes/init.php';

// Rediriger si déjà connecté
if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

$page_title = "Mot de passe oublié";
$body_class = "forgot-password-page";
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $message = "Veuillez saisir votre email";
        $message_type = "danger";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Email invalide";
        $message_type = "danger";
    } else {
        try {
            // Vérifier si l'email existe
            $sql = "SELECT * FROM Utilisateur WHERE email = :email AND etat = 'actif'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Générer un nouveau mot de passe
                $new_password = substr(md5(uniqid()), 0, 8); // 8 caractères aléatoires
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Mettre à jour dans la base
                $sql = "UPDATE Utilisateur SET mot_de_passe = :password WHERE email = :email";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':password' => $hashed_password,
                    ':email' => $email
                ]);
                
                // Enregistrer le message de succès
                $_SESSION['reset_info'] = [
                    'email' => $email,
                    'new_password' => $new_password,
                    'username' => $user['username']
                ];
                
                // Rediriger vers la page de confirmation
                header('Location: password_reset_confirmation.php');
                exit();
                
            } else {
                $message = "Aucun compte actif trouvé avec cet email";
                $message_type = "danger";
            }
        } catch(PDOException $e) {
            $message = "Erreur : " . $e->getMessage();
            $message_type = "danger";
            error_log("Forgot password error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        .forgot-password-page {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px;
        }
        
        .forgot-container {
            max-width: 500px;
            width: 100%;
            margin: 0 auto;
        }
        
        .forgot-card {
            background: white;
            border-radius: 20px;
            padding: 40px 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .forgot-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .forgot-header h1 {
            color: #333;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .forgot-header p {
            color: #666;
            margin: 0;
        }
        
        .instructions {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            border-left: 4px solid #6a11cb;
        }
        
        .instructions p {
            margin: 0;
            font-size: 14px;
            color: #495057;
        }
        
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 16px;
        }
        
        .form-control:focus {
            border-color: #6a11cb;
            box-shadow: 0 0 0 0.2rem rgba(106, 17, 203, 0.25);
        }
        
        .btn-reset {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            border: none;
            color: white;
            padding: 12px;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            border-radius: 10px;
            margin-top: 10px;
            transition: all 0.3s ease;
        }
        
        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(106, 17, 203, 0.3);
        }
        
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .logo h2 {
            color: white;
            font-weight: 700;
        }
        
        .logo span {
            color: #ffd700;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: white;
            text-decoration: none;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body class="<?php echo $body_class; ?>">
    <div class="forgot-container">
        <div class="logo">
            <h2><?php echo SITE_NAME; ?><span>.</span></h2>
        </div>
        
        <div class="forgot-card">
            <div class="forgot-header">
                <h1>Mot de passe oublié</h1>
                <p>Réinitialisez votre mot de passe</p>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <i class="bi bi-<?php echo $message_type == 'danger' ? 'exclamation-circle' : 'info-circle'; ?> me-2"></i>
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="instructions">
                <p><i class="bi bi-info-circle me-2"></i>Saisissez votre email. Un nouveau mot de passe vous sera généré.</p>
            </div>
            
            <form method="POST" action="" id="forgotForm">
                <div class="mb-4">
                    <label for="email" class="form-label">Votre adresse email</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-envelope"></i>
                        </span>
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               required
                               placeholder="exemple@email.com">
                    </div>
                </div>
                
                <button type="submit" class="btn-reset" id="resetBtn">
                    <span id="btnText">Réinitialiser le mot de passe</span>
                    <span id="loadingSpinner" class="spinner-border spinner-border-sm" role="status" style="display: none;"></span>
                </button>
            </form>
            
            <div class="back-link mt-4">
                <a href="login.php" class="d-block mb-2">
                    <i class="bi bi-arrow-left me-1"></i> Retour à la connexion
                </a>
            </div>
        </div>
        
        <div class="text-center mt-3">
            <a href="index.php" class="text-white">
                <i class="bi bi-house me-1"></i> Retour à l'accueil
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form submission
        document.getElementById('forgotForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('resetBtn');
            const btnText = document.getElementById('btnText');
            const spinner = document.getElementById('loadingSpinner');
            
            const email = document.getElementById('email').value;
            
            if (!email) {
                e.preventDefault();
                alert('Veuillez saisir votre email');
                return;
            }
            
            btn.disabled = true;
            btnText.textContent = 'Traitement...';
            spinner.style.display = 'inline-block';
        });
        
        // Auto-focus on email field
        document.getElementById('email').focus();
    </script>
</body>
</html>