<?php
$pageTitle = "Connexion";
require_once '../includes/header.php';

if ($auth->isLoggedIn()) {
    // Redirection selon le rôle
    $role = $auth->getUserRole();
    $redirect = match($role) {
        'admin' => '../admin/dashboard.php',
        'editor', 'auteur' => '../author/dashboard.php',
        default => 'index.php'
    };
    header("Location: $redirect");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($auth->login($username, $password)) {
        $role = $auth->getUserRole();
        $redirect = match($role) {
            'admin' => '../admin/dashboard.php',
            'editor', 'auteur' => '../author/dashboard.php',
            default => 'index.php'
        };
        header("Location: $redirect");
        exit();
    }
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="text-center mb-0"><i class="bi bi-box-arrow-in-right me-2"></i>Connexion</h3>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="username" class="form-label">
                                <i class="bi bi-person me-2"></i>Nom d'utilisateur ou Email
                            </label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?= $_POST['username'] ?? '' ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="bi bi-lock me-2"></i>Mot de passe
                            </label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Se souvenir de moi</label>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Se connecter
                            </button>
                        </div>
                    </form>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <p class="mb-2">
                            Pas encore de compte ? 
                            <a href="register.php" class="text-decoration-none">S'inscrire</a>
                        </p>
                        <p class="mb-0">
                            <a href="forgot-password.php" class="text-decoration-none">Mot de passe oublié ?</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>