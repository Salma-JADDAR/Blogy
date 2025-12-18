<?php
// En haut de header.php, définir le chemin de base
$base_url = 'http://' . $_SERVER['HTTP_HOST'] . '/Blogy';
define('BASE_URL', $base_url);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

if (!function_exists('hasRole')) {
    require_once __DIR__ . '/auth.php';
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title><?php echo $page_title ?? SITE_NAME; ?> - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="<?php echo $page_description ?? 'Blog professionnel'; ?>">
    
    <!-- Favicons AVEC CHEMINS ABSOLUS -->
    <link href="<?php echo BASE_URL; ?>/assets/img/favicon.png" rel="icon">
    <link href="<?php echo BASE_URL; ?>/assets/img/apple-touch-icon.png" rel="apple-touch-icon">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- CSS AVEC CHEMINS ABSOLUS -->
    <link href="<?php echo BASE_URL; ?>/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/assets/css/main.css" rel="stylesheet">
    
    <!-- CSS spécifique pour les pages admin -->
    
</head>

<body class="<?php echo $body_class ?? ''; ?>">

<header id="header" class="header position-relative">
    <div class="container-fluid container-xl position-relative">
        <div class="top-row d-flex align-items-center justify-content-between">
            <a href="<?php echo BASE_URL; ?>/index.php" class="logo d-flex align-items-end">
                <h1 class="sitename"><?php echo SITE_NAME; ?></h1><span>.</span>
                <?php if(isset($_SESSION['user'])): ?>
                <span class="user-role-badge badge-<?php echo $_SESSION['user']['role']; ?>">
                    <?php echo $_SESSION['user']['role']; ?>
                </span>
                <?php endif; ?>
            </a>
            
            <div class="d-flex align-items-center">
                <div class="social-links">
                    <a href="#" class="facebook"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="twitter"><i class="bi bi-twitter"></i></a>
                    <a href="#" class="instagram"><i class="bi bi-instagram"></i></a>
                </div>
                
                <form class="search-form ms-4" method="GET" action="<?php echo BASE_URL; ?>/search.php">
                    <input type="text" name="q" placeholder="Search..." class="form-control" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
                    <button type="submit" class="btn"><i class="bi bi-search"></i></button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="nav-wrap">
        <div class="container d-flex justify-content-center position-relative">
            <nav id="navmenu" class="navmenu">
                <ul>
                    <li><a href="<?php echo BASE_URL; ?>/index.php" <?php echo ($current_page == 'index.php') ? 'class="active"' : ''; ?>>Home</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/about.php" <?php echo ($current_page == 'about.php') ? 'class="active"' : ''; ?>>About</a></li>
                    
                    <!-- Menu déroulant Catégories -->
                    <?php 
                    $categories = getCategories();
                    if (!empty($categories)): ?>
                    <li class="dropdown">
                        <a href="#" <?php echo ($current_page == 'category.php') ? 'class="active"' : ''; ?>>
                            <span>Catégories</span> <i class="bi bi-chevron-down"></i>
                        </a>
                        <ul>
                            <?php foreach(array_slice($categories, 0, 6) as $cat): ?>
                            <li>
                                <a href="<?php echo BASE_URL; ?>/category.php?id=<?php echo $cat['id_categorie']; ?>">
                                    <?php echo htmlspecialchars($cat['nom_categorie']); ?>
                                    <?php if($cat['nb_articles'] > 0): ?>
                                    <span class="badge bg-secondary ms-1"><?php echo $cat['nb_articles']; ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <?php endforeach; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a href="<?php echo BASE_URL; ?>/category.php">Voir toutes</a></li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <li><a href="<?php echo BASE_URL; ?>/category.php" <?php echo ($current_page == 'category.php') ? 'class="active"' : ''; ?>>Category</a></li>
                    <?php endif; ?>
                    
                    <!-- Menu Articles -->
                    <?php if(isset($_SESSION['user']) && hasAnyRole(['admin', 'editor', 'auteur'])): ?>
                    <li class="dropdown">
                        <a href="#">
                            <span>Articles</span> <i class="bi bi-chevron-down"></i>
                        </a>
                        <ul>
                            <?php if(hasAnyRole(['admin', 'editor', 'auteur'])): ?>
                            <li><a href="<?php echo BASE_URL; ?>/author/articles.php">Mes articles</a></li>
                            <li><a href="<?php echo BASE_URL; ?>/author/article_new.php">Nouvel article</a></li>
                            <?php endif; ?>
                            
                            <?php if(hasAnyRole(['admin', 'editor'])): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a href="<?php echo BASE_URL; ?>/admin/comments.php">Modération commentaires</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>
                    
                    <!-- Menu Admin -->
                    <?php if(isset($_SESSION['user']) && hasAnyRole(['admin', 'editor'])): ?>
                    <li class="dropdown">
                        <a href="#">
                            <span>Admin</span> <i class="bi bi-chevron-down"></i>
                            <span class="admin-indicator">
                                <?php 
                                if(hasRole('admin')) echo 'Admin';
                                elseif(hasRole('editor')) echo 'Éditeur';
                                ?>
                            </span>
                        </a>
                        <ul>
                            <li><a href="<?php echo BASE_URL; ?>/admin/dashboard.php"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a></li>
                            
                            <?php if(hasRole('admin')): ?>
                            <li><a href="<?php echo BASE_URL; ?>/admin/users.php"><i class="bi bi-people me-1"></i> Utilisateurs</a></li>
                            <?php endif; ?>
                            
                            <?php if(hasAnyRole(['admin', 'editor'])): ?>
                            <li><a href="<?php echo BASE_URL; ?>/admin/categories.php"><i class="bi bi-bookmarks me-1"></i> Catégories</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a href="<?php echo BASE_URL; ?>/admin/comments.php"><i class="bi bi-chat me-1"></i> Commentaires</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>
                    
                    <!-- Menu Utilisateur -->
                    <?php if(isset($_SESSION['user'])): ?>
                    <li class="dropdown user-dropdown">
                        <a href="#">
                            <i class="bi bi-person-circle me-1"></i>
                            <span><?php echo htmlspecialchars($_SESSION['user']['nom']); ?></span>
                            <i class="bi bi-chevron-down"></i>
                        </a>
                        <ul>
                            <li>
                                <div class="px-3 py-2">
                                    <small class="text-muted">Connecté en tant que</small>
                                    <div class="fw-bold"><?php echo htmlspecialchars($_SESSION['user']['nom']); ?></div>
                                    <div class="small">
                                        <span class="badge badge-<?php echo $_SESSION['user']['role']; ?>">
                                            <?php echo $_SESSION['user']['role']; ?>
                                        </span>
                                    </div>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a href="<?php echo BASE_URL; ?>/profile.php"><i class="bi bi-person me-1"></i> Mon profil</a></li>
                            <li><a href="<?php echo BASE_URL; ?>/settings.php"><i class="bi bi-gear me-1"></i> Paramètres</a></li>
                            
                            <?php if(hasRole('admin')): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a href="<?php echo BASE_URL; ?>/admin/dashboard.php"><i class="bi bi-speedometer2 me-1"></i> Administration</a></li>
                            <?php endif; ?>
                            
                            <li><hr class="dropdown-divider"></li>
                            <li><a href="<?php echo BASE_URL; ?>/logout.php"><i class="bi bi-box-arrow-right me-1"></i> Déconnexion</a></li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <li class="dropdown">
                        <a href="#" <?php echo ($current_page == 'login.php' || $current_page == 'register.php') ? 'class="active"' : ''; ?>>
                            <span>Connexion</span> <i class="bi bi-chevron-down"></i>
                        </a>
                        <ul>
                            <li><a href="<?php echo BASE_URL; ?>/login.php"><i class="bi bi-box-arrow-in-right me-1"></i> Se connecter</a></li>
                            <li><a href="<?php echo BASE_URL; ?>/register.php"><i class="bi bi-person-plus me-1"></i> S'inscrire</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    
                    
                </ul>
                <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
            </nav>
        </div>
    </div>
</header>

<main class="main">