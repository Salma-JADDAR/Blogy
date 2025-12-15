<?php
// Définir le titre de la page
$pageTitle = "Accueil";

// Inclure les fichiers de configuration et d'authentification AVANT header.php
require_once '../config/config.php';
require_once '../includes/auth.php';

// Maintenant inclure header.php
require_once '../includes/header.php';

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = ITEMS_PER_PAGE;
$offset = ($page - 1) * $limit;

// Récupérer les articles publiés
$query = "
    SELECT a.*, u.nom, u.username, c.nom_categorie 
    FROM Article a 
    LEFT JOIN Utilisateur u ON a.username = u.username 
    LEFT JOIN Categorie c ON a.id_categorie = c.id_categorie 
    WHERE a.status = 'published' 
    ORDER BY a.date_creation DESC
";

$stmt = $pdo->prepare($query . " LIMIT ? OFFSET ?");
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$articles = $stmt->fetchAll();

// Compter le total
$stmt = $pdo->query("SELECT COUNT(*) as total FROM Article WHERE status = 'published'");
$totalArticles = $stmt->fetch()['total'];
$totalPages = ceil($totalArticles / $limit);

// Récupérer les articles populaires
$popularArticles = getPopularArticles($pdo, 5);

// Récupérer les catégories
$categories = getCategories($pdo);
?>

<div class="container mt-4">
    <!-- Hero Section -->
    <div class="p-5 mb-4 bg-light rounded-3">
        <div class="container-fluid py-5">
            <h1 class="display-5 fw-bold">Bienvenue sur <?= SITE_NAME ?></h1>
            <p class="col-md-8 fs-4">Découvrez nos derniers articles, partagez vos connaissances et rejoignez notre communauté.</p>
            <?php if (!$auth->isLoggedIn()): ?>
            <a class="btn btn-primary btn-lg" href="register.php">Créer un compte</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <!-- Articles -->
        <div class="col-lg-8">
            <h2 class="mb-4">Derniers articles</h2>
            
            <?php if (empty($articles)): ?>
            <div class="alert alert-info">
                Aucun article publié pour le moment.
                <?php if ($auth->isAuthor()): ?>
                <a href="../author/add_article.php" class="alert-link">Soyez le premier à publier !</a>
                <?php endif; ?>
            </div>
            <?php else: ?>
                <?php foreach ($articles as $article): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span class="badge bg-secondary mb-2">
                                    <?= htmlspecialchars($article['nom_categorie'] ?? 'Non catégorisé') ?>
                                </span>
                                <h4 class="card-title">
                                    <a href="../public/article.php?id=<?= $article['id_article'] ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($article['titre']) ?>
                                    </a>
                                </h4>
                            </div>
                            <?php if ($auth->isAdmin() || $auth->isEditor() || ($auth->getUserInfo() && $auth->getUserInfo()['username'] === $article['username'])): ?>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-gear"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="../author/edit_article.php?id=<?= $article['id_article'] ?>">
                                            <i class="bi bi-pencil me-2"></i>Éditer
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <p class="card-text">
                            <?= substr(strip_tags($article['contenu']), 0, 200) ?>...
                        </p>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted">
                                    <i class="bi bi-person me-1"></i>
                                    <?= htmlspecialchars($article['nom']) ?>
                                </small>
                                <small class="text-muted ms-3">
                                    <i class="bi bi-calendar me-1"></i>
                                    <?= formatDate($article['date_creation']) ?>
                                </small>
                            </div>
                            <div>
                                <span class="badge bg-light text-dark">
                                    <i class="bi bi-eye me-1"></i><?= $article['view_count'] ?>
                                </span>
                                <a href="../public/article.php?id=<?= $article['id_article'] ?>" class="btn btn-sm btn-outline-primary ms-2">
                                    Lire la suite
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav aria-label="Navigation des articles">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Recherche -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-search me-2"></i>Recherche</h5>
                </div>
                <div class="card-body">
                    <form action="../public/search.php" method="GET">
                        <div class="input-group">
                            <input type="text" class="form-control" name="q" placeholder="Mot-clé...">
                            <button class="btn btn-primary" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Catégories -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-tags me-2"></i>Catégories</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <?php foreach ($categories as $cat): ?>
                        <a href="../public/category.php?id=<?= $cat['id_categorie'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <?= htmlspecialchars($cat['nom_categorie']) ?>
                            <span class="badge bg-primary rounded-pill"><?= rand(1, 20) ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Articles populaires -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-fire me-2"></i>Populaires</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <?php foreach ($popularArticles as $article): ?>
                        <a href="../public/article.php?id=<?= $article['id_article'] ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><?= htmlspecialchars($article['titre']) ?></h6>
                                <small><?= $article['view_count'] ?> vues</small>
                            </div>
                            <small class="text-muted"><?= htmlspecialchars($article['nom']) ?></small>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>