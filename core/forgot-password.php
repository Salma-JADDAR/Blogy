<?php
$pageTitle = "Mot de passe oublié";
require_once '../includes/header.php';

if ($auth->isLoggedIn()) {
    header('Location: index.php');
    exit();
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-warning">
                    <h3 class="text-center mb-0"><i class="bi bi-key me-2"></i>Réinitialiser le mot de passe</h3>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted mb-4">
                        Entrez votre adresse email. Vous recevrez un lien pour réinitialiser votre mot de passe.
                    </p>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="bi bi-envelope me-2"></i>Adresse email
                            </label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-warning btn-lg">
                                <i class="bi bi-send me-2"></i>Envoyer le lien
                            </button>
                        </div>
                    </form>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <a href="login.php" class="text-decoration-none">
                            <i class="bi bi-arrow-left me-1"></i>Retour à la connexion
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>