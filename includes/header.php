<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title><?php echo $page_title ?? SITE_NAME; ?> - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="<?php echo $page_description ?? 'Blog professionnel'; ?>">
    
    <!-- Favicons -->
    <link href="assets/img/favicon.png" rel="icon">
    <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- CSS -->
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
</head>

<body class="<?php echo $body_class ?? ''; ?>">

<header id="header" class="header position-relative">
    <div class="container-fluid container-xl position-relative">
        <div class="top-row d-flex align-items-center justify-content-between">
            <a href="index.php" class="logo d-flex align-items-end">
                <h1 class="sitename"><?php echo SITE_NAME; ?></h1><span>.</span>
            </a>
            
            <div class="d-flex align-items-center">
                <div class="social-links">
                    <a href="#" class="facebook"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="twitter"><i class="bi bi-twitter"></i></a>
                    <a href="#" class="instagram"><i class="bi bi-instagram"></i></a>
                </div>
                
                <form class="search-form ms-4" method="GET" action="search.php">
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
                    <li><a href="index.php" <?php echo ($current_page == 'index.php') ? 'class="active"' : ''; ?>>Home</a></li>
                    <li><a href="about.php" <?php echo ($current_page == 'about.php') ? 'class="active"' : ''; ?>>About</a></li>
                    <li><a href="category.php" <?php echo ($current_page == 'category.php') ? 'class="active"' : ''; ?>>Category</a></li>
                    <?php if(isset($_SESSION['user'])): ?>
                        <li><a href="admin/dashboard.php">Dashboard</a></li>
                        <li><a href="logout.php">Logout (<?php echo $_SESSION['user']['username']; ?>)</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Login</a></li>
                    <?php endif; ?>
                    <li><a href="contact.php" <?php echo ($current_page == 'contact.php') ? 'class="active"' : ''; ?>>Contact</a></li>
                </ul>
                <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
            </nav>
        </div>
    </div>
</header>

<main class="main">