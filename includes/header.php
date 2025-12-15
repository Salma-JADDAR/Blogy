<?php
// Inclure la configuration et l'authentification
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/auth.php';

// Si $pageTitle n'est pas défini, utiliser une valeur par défaut
if (!isset($pageTitle)) {
    $pageTitle = SITE_NAME;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - <?= SITE_NAME ?></title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
    
    <!-- TinyMCE (pour éditeur d'articles) -->
    <?php if (isset($loadTinyMCE) && $loadTinyMCE): ?>
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: '#contenu',
            height: 400,
            menubar: false,
            plugins: 'link lists image table code',
            toolbar: 'undo redo | bold italic underline | alignleft aligncenter alignright | bullist numlist | link image | code',
            language: 'fr_FR'
        });
    </script>
    <?php endif; ?>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../core/index.php">
                <i class="bi bi-pen me-2"></i><?= SITE_NAME ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../core/index.php">
                            <i class="bi bi-house"></i> Accueil
                        </a>
                    </li>
                    
                    <!-- Menu pour auteurs/éditeurs/admin -->
                    <?php if ($auth->isAuthor()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-pencil"></i> Écrire
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../author/articles.php">
                                <i class="bi bi-file-text me-2"></i>Mes articles
                            </a></li>
                            <li><a class="dropdown-item" href="../author/add_article.php">
                                <i class="bi bi-plus-circle me-2"></i>Nouvel article
                            </a></li>
                            <li><a class="dropdown-item" href="../author/dashboard.php">
                                <i class="bi bi-speedometer2 me-2"></i>Tableau de bord
                            </a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    
                    <!-- Menu admin -->
                    <?php if ($auth->isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="../admin/dashboard.php">
                            <i class="bi bi-shield-check"></i> Admin
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <!-- Catégories dropdown -->
                    <?php
                    $categories = getCategories($pdo);
                    if (!empty($categories)):
                    ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-tags"></i> Catégories
                        </a>
                        <ul class="dropdown-menu">
                            <?php foreach ($categories as $cat): ?>
                            <li><a class="dropdown-item" href="../public/category.php?id=<?= $cat['id_categorie'] ?>">
                                <?= htmlspecialchars($cat['nom_categorie']) ?>
                            </a></li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <!-- Barre de recherche -->
                <form class="d-flex me-3" action="../public/search.php" method="GET">
                    <div class="input-group">
                        <input type="text" class="form-control form-control-sm" name="q" 
                               placeholder="Rechercher..." value="<?= $_GET['q'] ?? '' ?>">
                        <button class="btn btn-light btn-sm" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>
                
                <!-- Menu utilisateur -->
                <ul class="navbar-nav">
                    <?php if ($auth->isLoggedIn()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i>
                            <?= htmlspecialchars($_SESSION['nom']) ?>
                            <span class="badge bg-<?= 
                                $_SESSION['role'] === 'admin' ? 'danger' : 
                                ($_SESSION['role'] === 'editor' ? 'warning' : 
                                ($_SESSION['role'] === 'auteur' ? 'success' : 'secondary'))
                            ?> ms-1">
                                <?= $_SESSION['role'] ?>
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header">Mon compte</h6></li>
                            <li><a class="dropdown-item" href="../public/profile.php">
                                <i class="bi bi-person me-2"></i>Mon profil
                            </a></li>
                            <?php if ($auth->isAuthor()): ?>
                            <li><a class="dropdown-item" href="../author/articles.php">
                                <i class="bi bi-file-text me-2"></i>Mes articles
                            </a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../core/logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>Déconnexion
                            </a></li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="../core/login.php">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Connexion
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../core/register.php">
                            <i class="bi bi-person-plus me-1"></i>Inscription
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Messages flash -->
    <div class="container mt-3">
        <?php displayFlashMessages(); ?>
    </div>