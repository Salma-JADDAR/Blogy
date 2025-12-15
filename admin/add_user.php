<?php
$pageTitle = "Ajouter un utilisateur";
require_once '../includes/header.php';

$auth->requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $nom = sanitize($_POST['nom'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'auteur';
    $etat = $_POST['etat'] ?? 'actif';
    
    $errors = [];
    
    if (strlen($username) < 3) {
        $errors[] = "Le nom d'utilisateur doit contenir au moins 3 caractères";
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Adresse email invalide";
    }
    
    if (strlen($password) < 6) {
        $errors[] = "Le mot de passe doit contenir au moins 6 caractères";
    }
    
    if (empty($errors)) {
        // Vérifier si l'utilisateur existe déjà
        $stmt = $pdo->prepare("SELECT username FROM Utilisateur WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetch()) {
            addFlashMessage('warning', 'Nom d\'utilisateur ou email déjà utilisé');
        } else {
            // Créer l'utilisateur
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO Utilisateur (username, nom, email, mot_de_passe, role, etat) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            if ($stmt->execute([$username, $nom, $email, $hashedPassword, $role, $etat])) {
                addFlashMessage('success', 'Utilisateur créé avec succès');
                header('Location: users.php');
                exit();
            } else {
                addFlashMessage('danger', 'Erreur lors de la création de l\'utilisateur');
            }
        }
    } else {
        foreach ($errors as $error) {
            addFlashMessage('danger', $error);
        }
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Ajouter un utilisateur</h1>
                <a href="users.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Retour
                </a>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label">Nom d'utilisateur *</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?= $_POST['username'] ?? '' ?>" required>
                                <div class="form-text">Minimum 3 caractères</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="nom" class="form-label">Nom complet *</label>
                                <input type="text" class="form-control" id="nom" name="nom" 
                                       value="<?= $_POST['nom'] ?? '' ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Adresse email *</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= $_POST['email'] ?? '' ?>" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Mot de passe *</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="form-text">Minimum 6 caractères</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">Confirmer le mot de passe *</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="role" class="form-label">Rôle *</label>
                                <select class="form-select" id="role" name="role" required>
                                    <?php foreach (USER_ROLES as $roleOpt): ?>
                                    <option value="<?= $roleOpt ?>" <?= ($_POST['role'] ?? '') === $roleOpt ? 'selected' : '' ?>>
                                        <?= ucfirst($roleOpt) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="etat" class="form-label">État *</label>
                                <select class="form-select" id="etat" name="etat" required>
                                    <?php foreach (USER_STATES as $state): ?>
                                    <option value="<?= $state ?>" <?= ($_POST['etat'] ?? '') === $state ? 'selected' : '' ?>>
                                        <?= ucfirst($state) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="users.php" class="btn btn-secondary me-md-2">Annuler</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-person-plus me-2"></i>Créer l'utilisateur
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Validation du mot de passe
document.getElementById('password').addEventListener('input', validatePassword);
document.getElementById('confirm_password').addEventListener('input', validatePassword);

function validatePassword() {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const submitBtn = document.querySelector('button[type="submit"]');
    
    if (password.length < 6) {
        submitBtn.disabled = true;
        submitBtn.title = "Le mot de passe doit contenir au moins 6 caractères";
        return;
    }
    
    if (password !== confirmPassword) {
        submitBtn.disabled = true;
        submitBtn.title = "Les mots de passe ne correspondent pas";
        return;
    }
    
    submitBtn.disabled = false;
    submitBtn.title = "";
}
</script>

<?php require_once '../includes/footer.php'; ?>