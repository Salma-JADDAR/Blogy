<?php
require_once 'includes/init.php';

// Rediriger si pas d'information de réinitialisation
if (!isset($_SESSION['reset_info'])) {
    header('Location: forgot_password.php');
    exit();
}

$reset_info = $_SESSION['reset_info'];
unset($_SESSION['reset_info']);

$page_title = "Mot de passe réinitialisé";
$body_class = "reset-confirmation-page";
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
        .reset-confirmation-page {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px;
        }
        
        .confirmation-container {
            max-width: 600px;
            width: 100%;
            margin: 0 auto;
        }
        
        .confirmation-card {
            background: white;
            border-radius: 20px;
            padding: 40px 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
        }
        
        .success-icon {
            font-size: 80px;
            color: #28a745;
            margin-bottom: 20px;
        }
        
        .confirmation-header h1 {
            color: #333;
            font-weight: 700;
            margin-bottom: 15px;
        }
        
        .confirmation-header p {
            color: #666;
            margin-bottom: 30px;
        }
        
        .credentials-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin: 25px 0;
            border: 2px solid #e9ecef;
        }
        
        .credential-item {
            margin: 15px 0;
            text-align: left;
            padding-left: 30px;
            position: relative;
        }
        
        .credential-item i {
            position: absolute;
            left: 0;
            top: 3px;
            color: #6a11cb;
        }
        
        .credential-label {
            font-weight: 500;
            color: #495057;
            display: block;
            margin-bottom: 5px;
        }
        
        .credential-value {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            word-break: break-all;
        }
        
        .password-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 14px;
        }
        
        .btn-login {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            border: none;
            color: white;
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 10px;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(67, 233, 123, 0.3);
            color: white;
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
        
        .copy-btn {
            background: #6c757d;
            border: none;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            margin-left: 10px;
            cursor: pointer;
        }
        
        .copy-btn:hover {
            background: #5a6268;
        }
    </style>
</head>
<body class="<?php echo $body_class; ?>">
    <div class="confirmation-container">
        <div class="logo">
            <h2><?php echo SITE_NAME; ?><span>.</span></h2>
        </div>
        
        <div class="confirmation-card">
            <div class="success-icon">
                <i class="bi bi-check-circle"></i>
            </div>
            
            <div class="confirmation-header">
                <h1>Mot de passe réinitialisé !</h1>
                <p>Votre mot de passe a été réinitialisé avec succès.</p>
            </div>
            
            <div class="credentials-box">
                <div class="credential-item">
                    <i class="bi bi-person"></i>
                    <span class="credential-label">Nom d'utilisateur :</span>
                    <span class="credential-value"><?php echo htmlspecialchars($reset_info['username']); ?></span>
                </div>
                
                <div class="credential-item">
                    <i class="bi bi-envelope"></i>
                    <span class="credential-label">Email :</span>
                    <span class="credential-value"><?php echo htmlspecialchars($reset_info['email']); ?></span>
                </div>
                
                <div class="credential-item">
                    <i class="bi bi-key"></i>
                    <span class="credential-label">Nouveau mot de passe :</span>
                    <div class="d-flex align-items-center">
                        <span class="credential-value" id="passwordDisplay">
                            <?php echo htmlspecialchars($reset_info['new_password']); ?>
                        </span>
                        <button class="copy-btn" onclick="copyToClipboard()">
                            <i class="bi bi-clipboard"></i> Copier
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="password-warning">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>Important :</strong> Notez bien ce mot de passe. Une fois cette page fermée, vous ne pourrez plus le voir.
            </div>
            
            <div class="instructions">
                <p><i class="bi bi-lightbulb me-2"></i>Une fois connecté, changez votre mot de passe dans la section "Mon profil".</p>
            </div>
            
            <a href="login.php" class="btn-login">
                <i class="bi bi-box-arrow-in-right me-2"></i> Se connecter maintenant
            </a>
        </div>
        
        <div class="text-center mt-3">
            <a href="index.php" class="text-white">
                <i class="bi bi-house me-1"></i> Retour à l'accueil
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copyToClipboard() {
            const password = "<?php echo $reset_info['new_password']; ?>";
            
            navigator.clipboard.writeText(password).then(function() {
                const btn = document.querySelector('.copy-btn');
                const originalHTML = btn.innerHTML;
                
                btn.innerHTML = '<i class="bi bi-check"></i> Copié !';
                btn.style.background = '#28a745';
                
                setTimeout(function() {
                    btn.innerHTML = originalHTML;
                    btn.style.background = '';
                }, 2000);
                
            }).catch(function(err) {
                alert('Erreur lors de la copie : ' + err);
            });
        }
        
        // Auto-select password on click
        document.getElementById('passwordDisplay').addEventListener('click', function(e) {
            const range = document.createRange();
            range.selectNodeContents(this);
            const selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(range);
        });
    </script>
</body>
</html>