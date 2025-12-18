<?php
require_once 'includes/init.php';

// Rediriger si déjà connecté
if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

$page_title = "Connexion";
$body_class = "login-page";
$error = '';
$success = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = "Veuillez remplir tous les champs";
    } else {
        try {
            // Récupérer l'utilisateur
            $sql = "SELECT * FROM Utilisateur WHERE username = :username OR email = :email";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':username', $username);
            $stmt->bindValue(':email', $username);
            $stmt->execute();
            
            $user = $stmt->fetch();
            
            if ($user) {
                // Vérifier l'état du compte
                if ($user['etat'] == 'inactif') {
                    $error = "Votre compte est désactivé. Contactez l'administrateur.";
                } 
                // Vérifier le mot de passe
                elseif (password_verify($password, $user['mot_de_passe'])) {
                    // Connexion réussie
                    $_SESSION['user'] = [
                        'username' => $user['username'],
                        'nom' => $user['nom'],
                        'email' => $user['email'],
                        'role' => $user['role'],
                        'date_creation' => $user['date_creation']
                    ];
                    
                    // Rediriger selon le rôle
                    if (hasRole('admin') || hasRole('editor')) {
                        header('Location: admin/dashboard.php');
                    } else {
                        header('Location: index.php');
                    }
                    exit();
                } else {
                    $error = "Identifiants incorrects";
                }
            } else {
                $error = "Identifiants incorrects";
            }
        } catch (PDOException $e) {
            $error = "Une erreur est survenue. Veuillez réessayer.";
            error_log("Login error: " . $e->getMessage());
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
        .login-page {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px;
        }
        
        .login-container {
            max-width: 400px;
            width: 100%;
            margin: 0 auto;
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            padding: 40px 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: #333;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: #666;
            margin: 0;
        }
        
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 16px;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .login-links {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }
        
        .login-links a {
            color: #667eea;
            text-decoration: none;
        }
        
        .login-links a:hover {
            text-decoration: underline;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            padding: 15px;
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
        
        .password-toggle {
            cursor: pointer;
            color: #6c757d;
        }
    </style>
</head>
<body class="<?php echo $body_class; ?>">
    <div class="login-container">
        <div class="logo">
            <h2><?php echo SITE_NAME; ?><span>.</span></h2>
        </div>
        
        <div class="login-card">
            <div class="login-header">
                <h1>Connexion</h1>
                <p>Accédez à votre compte</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="loginForm">
                <div class="mb-3">
                    <label for="username" class="form-label">Nom d'utilisateur ou Email</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-person"></i>
                        </span>
                        <input type="text" 
                               class="form-control" 
                               id="username" 
                               name="username" 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                               required
                               autocomplete="username"
                               placeholder="Votre nom d'utilisateur ou email">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Mot de passe</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-lock"></i>
                        </span>
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password" 
                               required
                               autocomplete="current-password"
                               placeholder="Votre mot de passe">
                        <span class="input-group-text password-toggle" onclick="togglePassword()">
                            <i class="bi bi-eye"></i>
                        </span>
                    </div>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Se souvenir de moi</label>
                </div>
                
                <button type="submit" class="btn-login" id="loginBtn">
                    <span id="btnText">Se connecter</span>
                    <span id="loadingSpinner" class="spinner-border spinner-border-sm" role="status" style="display: none;"></span>
                </button>
            </form>
            
            <div class="login-links">
                <a href="forgot_password.php" class="d-block mb-2">
                    <i class="bi bi-question-circle me-1"></i> Mot de passe oublié ?
                </a>
                <span>Pas encore de compte ? <a href="register.php">S'inscrire</a></span>
            </div>
        </div>
        
        <div class="text-center mt-3">
            <a href="index.php" class="text-white">
                <i class="bi bi-arrow-left me-1"></i> Retour au site
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const icon = document.querySelector('.password-toggle i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }
        
        // Form submission
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            const btnText = document.getElementById('btnText');
            const spinner = document.getElementById('loadingSpinner');
            
            btn.disabled = true;
            btnText.textContent = 'Connexion...';
            spinner.style.display = 'inline-block';
        });
        
        // Auto-focus on username field
        document.getElementById('username').focus();
    </script>
</body>
</html>