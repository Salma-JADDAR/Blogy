<?php
$pageTitle = "Dashboard Admin";
require_once '../includes/header.php';

$auth->requireRole('admin');

// Statistiques
$stats = [
    'users' => $pdo->query("SELECT COUNT(*) as total FROM Utilisateur WHERE etat = 'actif'")->fetch()['total'],
    'articles' => $pdo->query("SELECT COUNT(*) as total FROM Article WHERE status = 'published'")->fetch()['total'],
    'comments' => $pdo->query("SELECT COUNT(*) as total FROM Commentaire")->fetch()['total'],
    'pending_comments' => $pdo->query("SELECT COUNT(*) as total FROM Commentaire WHERE status = 'pending'")->fetch()['total'],
    'categories' => $pdo->query("SELECT COUNT(*) as total FROM Categorie")->fetch()['total']
];

// Derniers articles
$recentArticles = $pdo->query("
    SELECT a.*, u.nom, c.nom_categorie 
    FROM Article a 
    LEFT JOIN Utilisateur u ON a.username = u.username 
    LEFT JOIN Categorie c ON a.id_categorie = c.id_categorie 
    ORDER BY a.date_creation DESC 
    LIMIT 5
")->fetchAll();

// Derniers commentaires
$recentComments = $pdo->query("
    SELECT c.*, a.titre, u.nom as user_nom 
    FROM Commentaire c 
    LEFT JOIN Article a ON c.id_article = a.id_article 
    LEFT JOIN Utilisateur u ON c.username = u.username 
    ORDER BY c.date_commentaire DESC 
    LIMIT 5
")->fetchAll();

// Articles par catégorie
$categoriesStats = $pdo->query("
    SELECT c.nom_categorie, COUNT(a.id_article) as count
    FROM Categorie c 
    LEFT JOIN Article a ON c.id_categorie = a.id_categorie 
    GROUP BY c.id_categorie 
    ORDER BY count DESC
")->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar">
            <div class="position-sticky pt-3">
                <div class="text-center text-white mb-4">
                    <h5><i class="bi bi-shield-check"></i> Administration</h5>
                    <small><?= $_SESSION['nom'] ?></small>
                </div>
                
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active text-white" href="dashboard.php">
                            <i class="bi bi-speedometer2 me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="categories.php">
                            <i class="bi bi-tags me-2"></i>Catégories
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="articles.php">
                            <i class="bi bi-file-text me-2"></i>Articles
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="comments.php">
                            <i class="bi bi-chat-dots me-2"></i>Commentaires
                            <?php if ($stats['pending_comments'] > 0): ?>
                            <span class="badge bg-danger float-end"><?= $stats['pending_comments'] ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="users.php">
                            <i class="bi bi-people me-2"></i>Utilisateurs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="../core/index.php">
                            <i class="bi bi-house me-2"></i>Retour au site
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="../core/logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>Déconnexion
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Dashboard Administrateur</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="add_category.php" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-plus-circle me-1"></i>Nouvelle catégorie
                        </a>
                        <a href="../author/add_article.php" class="btn btn-sm btn-outline-success">
                            <i class="bi bi-file-earmark-plus me-1"></i>Nouvel article
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row">
                <div class="col-md-3 mb-4">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Utilisateurs</h6>
                                    <h2 class="card-text"><?= $stats['users'] ?></h2>
                                </div>
                                <i class="bi bi-people display-4 opacity-50"></i>
                            </div>
                            <a href="users.php" class="text-white text-decoration-none small">
                                Voir détails <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Articles</h6>
                                    <h2 class="card-text"><?= $stats['articles'] ?></h2>
                                </div>
                                <i class="bi bi-file-text display-4 opacity-50"></i>
                            </div>
                            <a href="articles.php" class="text-white text-decoration-none small">
                                Voir détails <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Commentaires</h6>
                                    <h2 class="card-text"><?= $stats['comments'] ?></h2>
                                </div>
                                <i class="bi bi-chat-dots display-4 opacity-50"></i>
                            </div>
                            <a href="comments.php" class="text-white text-decoration-none small">
                                Voir détails <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">En attente</h6>
                                    <h2 class="card-text"><?= $stats['pending_comments'] ?></h2>
                                </div>
                                <i class="bi bi-clock display-4 opacity-50"></i>
                            </div>
                            <a href="comments.php?filter=pending" class="text-white text-decoration-none small">
                                Modérer <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="row">
                <!-- Derniers articles -->
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-newspaper me-2"></i>Derniers articles</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Titre</th>
                                            <th>Auteur</th>
                                            <th>Catégorie</th>
                                            <th>Statut</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentArticles as $article): ?>
                                        <tr>
                                            <td>
                                                <a href="../public/article.php?id=<?= $article['id_article'] ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($article['titre']) ?>
                                                </a>
                                            </td>
                                            <td><?= htmlspecialchars($article['nom']) ?></td>
                                            <td><?= htmlspecialchars($article['nom_categorie']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= 
                                                    $article['status'] === 'published' ? 'success' : 
                                                    ($article['status'] === 'draft' ? 'warning' : 'secondary')
                                                ?>">
                                                    <?= $article['status'] ?>
                                                </span>
                                            </td>
                                            <td><?= formatDate($article['date_creation'], 'd/m/Y') ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <a href="articles.php" class="btn btn-sm btn-outline-primary">Voir tous les articles</a>
                        </div>
                    </div>
                </div>
                
                <!-- Derniers commentaires -->
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-chat-left-text me-2"></i>Derniers commentaires</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <?php foreach ($recentComments as $comment): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">
                                            <?= htmlspecialchars($comment['user_nom'] ?? substr($comment['email'], 0, strpos($comment['email'], '@'))) ?>
                                        </h6>
                                        <span class="badge bg-<?= 
                                            $comment['status'] === 'approved' ? 'success' : 
                                            ($comment['status'] === 'pending' ? 'warning' : 'danger')
                                        ?>">
                                            <?= $comment['status'] ?>
                                        </span>
                                    </div>
                                    <p class="mb-1 small"><?= htmlspecialchars(substr($comment['contenu'], 0, 50)) ?>...</p>
                                    <small class="text-muted">Sur: <?= htmlspecialchars($comment['titre']) ?></small>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="comments.php" class="btn btn-sm btn-outline-primary w-100">
                                Gérer les commentaires
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Articles par catégorie -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Articles par catégorie</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($categoriesStats as $stat): ?>
                                <div class="col-md-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-center p-3 border rounded">
                                        <div>
                                            <h6 class="mb-0"><?= htmlspecialchars($stat['nom_categorie']) ?></h6>
                                            <small class="text-muted"><?= $stat['count'] ?> articles</small>
                                        </div>
                                        <span class="badge bg-primary rounded-pill"><?= $stat['count'] ?></span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>