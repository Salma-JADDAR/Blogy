<?php
session_start();
require_once 'includes/config.php';

// Rediriger si déjà connecté
if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

$page_title = "Inscription";
$body_class = "register-page";
$error = '';
$success = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($username) || empty($nom) || empty($email) || empty($password)) {
        $error = "Tous les champs sont obligatoires";
    } elseif ($password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas";
    } elseif (strlen($password) < 6) {
        $error = "Le mot de passe doit contenir au moins 6 caractères";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email invalide";
    } else {
        try {
            // Vérifier si l'utilisateur existe déjà
            $sql = "SELECT username, email FROM Utilisateur WHERE username = ? OR email = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$username, $email]);
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch();
                if ($user['username'] === $username) {
                    $error = "Ce nom d'utilisateur est déjà pris";
                } else {
                    $error = "Cet email est déjà utilisé";
                }
            } else {
                // Créer l'utilisateur
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $sql = "INSERT INTO Utilisateur (username, nom, email, mot_de_passe, role) 
                        VALUES (?, ?, ?, ?, 'subscriber')";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$username, $nom, $email, $hashed_password]);
                
                $success = "Compte créé avec succès ! Vous pouvez maintenant vous connecter.";
                
                // Réinitialiser le formulaire
                $_POST = [];
            }
        } catch (PDOException $e) {
            $error = "Une erreur est survenue lors de l'inscription";
            error_log("Registration error: " . $e->getMessage());
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
        body.register-page {
            background:linear-gradient(135deg, #f75815 0%, #e77f52 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .register-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            width: 100%;
            max-width: 500px;
        }
        
        .register-header {
            background: #f8f9fa;
            padding: 30px;
            text-align: center;
            border-bottom: 1px solid #e9ecef;
        }
        
        .register-header h1 {
            color: #333;
            font-weight: 700;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .register-body {
            padding: 30px;
        }
        
        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -10px;
        }
        
        .form-col {
            flex: 1;
            padding: 0 10px;
            min-width: 250px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 8px;
            display: block;
        }
        
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #f75815
            box-shadow: 0 0 0 0.2rem rgba(106, 17, 203, 0.25);
        }
        
        .password-strength {
            margin-top: 5px;
            height: 5px;
            background: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .strength-meter {
            height: 100%;
            width: 0;
            transition: all 0.3s ease;
            border-radius: 3px;
        }
        
        .strength-0 { width: 0%; background: #dc3545; }
        .strength-1 { width: 25%; background: #dc3545; }
        .strength-2 { width: 50%; background: #ffc107; }
        .strength-3 { width: 75%; background: #28a745; }
        .strength-4 { width: 100%; background: #28a745; }
        
        .password-hint {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .btn-register {
            background: linear-gradient(135deg, #f75815 0%, #e77f52 100%);
            border: none;
            color: white;
            padding: 14px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(106, 17, 203, 0.3);
        }
        
        .register-footer {
            text-align: center;
            padding: 20px 30px;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
            font-size: 14px;
            color: #666;
        }
        
        .register-footer a {
            color: #f75815;
            text-decoration: none;
            font-weight: 500;
        }
        
        .register-footer a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 576px) {
            .register-card {
                margin: 0 15px;
            }
            
            .register-header, .register-body {
                padding: 20px;
            }
            
            .form-col {
                min-width: 100%;
            }
        }
    </style>
</head>
<body class="<?php echo $body_class; ?>">
    <div class="register-card">
        <div class="register-header">
            <div class="logo" style="font-size: 36px; color: #f75815;margin-bottom: 15px;">
                <i class="bi bi-person-plus" ></i>
            </div>
            <h1>Créer un compte</h1>
            <p>Rejoignez <?php echo SITE_NAME; ?></p>
        </div>
        
        <div class="register-body">
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="registerForm">
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="nom">Nom complet *</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="nom" 
                                   name="nom" 
                                   value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>"
                                   required
                                   placeholder="Votre nom complet">
                        </div>
                    </div>
                    
                    <div class="form-col">
                        <div class="form-group">
                            <label for="username">Nom d'utilisateur *</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="username" 
                                   name="username" 
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                   required
                                   placeholder="Choisissez un nom d'utilisateur">
                            <small class="text-muted">Utilisé pour vous connecter</small>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" 
                           class="form-control" 
                           id="email" 
                           name="email" 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                           required
                           placeholder="votre@email.com">
                </div>
                
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="password">Mot de passe *</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   required
                                   placeholder="Minimum 6 caractères"
                                   minlength="6">
                            <div class="password-strength">
                                <div class="strength-meter strength-0" id="strengthMeter"></div>
                            </div>
                            <div class="password-hint" id="passwordHint"></div>
                        </div>
                    </div>
                    
                    <div class="form-col">
                        <div class="form-group">
                            <label for="confirm_password">Confirmer le mot de passe *</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="confirm_password" 
                                   name="confirm_password" 
                                   required
                                   placeholder="Retapez votre mot de passe">
                            <div class="mt-2" id="passwordMatch"></div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group form-check">
                    <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                    <label class="form-check-label" for="terms">
                        J'accepte les <a href="#" class="text-primary">conditions d'utilisation</a> et la 
                        <a href="#" class="text-primary">politique de confidentialité</a>
                    </label>
                </div>
                
                <button type="submit" class="btn-register" id="registerBtn">
                    <span id="btnText">S'inscrire</span>
                    <span id="loadingSpinner" class="spinner-border spinner-border-sm" role="status" style="display: none;"></span>
                </button>
            </form>
        </div>
        
        <div class="register-footer">
            <p>Vous avez déjà un compte ? <a href="login.php">Se connecter</a></p>
            <p>© <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Tous droits réservés.</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const strengthMeter = document.getElementById('strengthMeter');
            const passwordHint = document.getElementById('passwordHint');
            const passwordMatch = document.getElementById('passwordMatch');
            
            // Vérifier la force du mot de passe
            function checkPasswordStrength(password) {
                let strength = 0;
                
                // Longueur
                if (password.length >= 6) strength++;
                if (password.length >= 8) strength++;
                
                // Caractères variés
                if (/[A-Z]/.test(password)) strength++;
                if (/[0-9]/.test(password)) strength++;
                if (/[^A-Za-z0-9]/.test(password)) strength++;
                
                return Math.min(strength, 4);
            }
            
            // Mettre à jour l'indicateur de force
            function updateStrengthMeter() {
                const password = passwordInput.value;
                const strength = checkPasswordStrength(password);
                
                strengthMeter.className = 'strength-meter strength-' + strength;
                
                // Afficher des conseils
                if (password.length === 0) {
                    passwordHint.textContent = '';
                } else if (password.length < 6) {
                    passwordHint.textContent = '❌ Trop court (minimum 6 caractères)';
                    passwordHint.style.color = '#dc3545';
                } else if (strength < 2) {
                    passwordHint.textContent = ' Mot de passe faible';
                    passwordHint.style.color = '#ffc107';
                } else if (strength < 4) {
                    passwordHint.textContent = ' Mot de passe moyen';
                    passwordHint.style.color = '#28a745';
                } else {
                    passwordHint.textContent = ' Mot de passe fort';
                    passwordHint.style.color = '#28a745';
                }
            }
            
            // Vérifier la correspondance des mots de passe
            function checkPasswordMatch() {
                const password = passwordInput.value;
                const confirm = confirmPasswordInput.value;
                
                if (confirm.length === 0) {
                    passwordMatch.textContent = '';
                    passwordMatch.style.color = '';
                } else if (password === confirm) {
                    passwordMatch.innerHTML = '✅ Les mots de passe correspondent';
                    passwordMatch.style.color = '#28a745';
                } else {
                    passwordMatch.innerHTML = '❌ Les mots de passe ne correspondent pas';
                    passwordMatch.style.color = '#dc3545';
                }
            }
            
            // Événements
            passwordInput.addEventListener('input', updateStrengthMeter);
            passwordInput.addEventListener('input', checkPasswordMatch);
            confirmPasswordInput.addEventListener('input', checkPasswordMatch);
            
            // Form submission
            document.getElementById('registerForm').addEventListener('submit', function(e) {
                const password = passwordInput.value;
                const confirm = confirmPasswordInput.value;
                const terms = document.getElementById('terms').checked;
                
                if (password !== confirm) {
                    e.preventDefault();
                    alert("Les mots de passe ne correspondent pas !");
                    return;
                }
                
                if (!terms) {
                    e.preventDefault();
                    alert("Vous devez accepter les conditions d'utilisation");
                    return;
                }
                
                const btn = document.getElementById('registerBtn');
                const btnText = document.getElementById('btnText');
                const spinner = document.getElementById('loadingSpinner');
                
                btn.disabled = true;
                btnText.textContent = 'Inscription...';
                spinner.style.display = 'inline-block';
            });
            
            // Initial check
            updateStrengthMeter();
        });
    </script>
</body>
</html>