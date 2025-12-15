<?php
$pageTitle = "Dashboard Auteur";
require_once '../includes/header.php';

$auth->requireRole('auteur');

$username = $_SESSION['username'];

// Statistiques de l'auteur
$stats = [
    'total' => $pdo->prepare("SELECT COUNT(*) FROM Article WHERE username = ?")->execute([$username]) ? 
               $pdo->fetchColumn() : 0,
    'published' => $pdo->prepare("SELECT COUNT(*) FROM Article WHERE username = ? AND status = 'published'")->execute([$username]) ? 
                  $pdo->fetchColumn() : 0,
    'draft' => $pdo->prepare("SELECT COUNT(*) FROM Article WHERE username = ? AND status = 'draft'")->execute([$username]) ? 
               $pdo->fetchColumn() : 0,
    'views' => $pdo->prepare("SELECT SUM(view_count) FROM Article WHERE username = ?")->execute([$username]) ? 
              $pdo->fetchColumn() : 0
];

// Articles récents de l'auteur
$recentArticles = $pdo->prepare("
    SELECT a.*, c.nom_categorie 
    FROM Article a 
    LEFT JOIN Categorie c ON a.id_categorie = c.id_categorie 
    WHERE a.username = ? 
    ORDER BY a.date_creation DESC 
    LIMIT 5
");
$recentArticles->execute([$username]);
$articles = $recentArticles->fetchAll();

// Commentaires sur les articles de l'auteur
$recentComments = $pdo->prepare("
    SELECT cm.*, a.titre, u.nom as commenter_name 
    FROM Commentaire cm 
    LEFT JOIN Article a ON cm.id_article = a.id_article 
    LEFT JOIN Utilisateur u ON cm.username = u.username 
    WHERE a.username = ? 
    ORDER BY cm.date_commentaire DESC 
    LIMIT 5
");
$recentComments->execute([$username]);
$comments = $recentComments->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar Auteur -->
        <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
            <div class="position-sticky pt-3">
                <div class="text-center mb-4">
                    <h5><i class="bi bi-pencil"></i> Espace Auteur</h5>
                    <small><?= $_SESSION['nom'] ?></small>
                </div>
                
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="bi bi-speedometer2 me-2"></i>Tableau de bord
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="articles.php">
                            <i class="bi bi-file-text me-2"></i>Mes articles
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="add_article.php">
                            <i class="bi bi-plus-circle me-2"></i>Nouvel article
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../public/profile.php">
                            <i class="bi bi-person me-2"></i>Mon profil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../core/index.php">
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
                <h1 class="h2">Tableau de bord Auteur</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="add_article.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Nouvel article
                    </a>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row">
                <div class="col-md-3 mb-4">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total articles</h6>
                                    <h2 class="card-text"><?= $stats['total'] ?></h2>
                                </div>
                                <i class="bi bi-file-text display-4 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Publiés</h6>
                                    <h2 class="card-text"><?= $stats['published'] ?></h2>
                                </div>
                                <i class="bi bi-check-circle display-4 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Brouillons</h6>
                                    <h2 class="card-text"><?= $stats['draft'] ?></h2>
                                </div>
                                <i class="bi bi-pencil display-4 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Vues totales</h6>
                                    <h2 class="card-text"><?= $stats['views'] ?></h2>
                                </div>
                                <i class="bi bi-eye display-4 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenu principal -->
            <div class="row">
                <!-- Articles récents -->
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-newspaper me-2"></i>Mes derniers articles</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($articles)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-file-text display-1 text-muted mb-3"></i>
                                <h4>Aucun article</h4>
                                <p class="text-muted">Vous n'avez pas encore créé d'articles</p>
                                <a href="add_article.php" class="btn btn-primary">Créer mon premier article</a>
                            </div>
                            <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($articles as $article): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1">
                                            <a href="../public/article.php?id=<?= $article['id_article'] ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($article['titre']) ?>
                                            </a>
                                        </h5>
                                        <div>
                                            <span class="badge bg-<?= 
                                                $article['status'] === 'published' ? 'success' : 'warning'
                                            ?> me-2">
                                                <?= $article['status'] ?>
                                            </span>
                                            <span class="badge bg-light text-dark">
                                                <i class="bi bi-eye"></i> <?= $article['view_count'] ?>
                                            </span>
                                        </div>
                                    </div>
                                    <p class="mb-1">
                                        <small class="text-muted">
                                            Catégorie: <?= htmlspecialchars($article['nom_categorie'] ?? 'Non catégorisé') ?> | 
                                            Créé le: <?= formatDate($article['date_creation']) ?>
                                        </small>
                                    </p>
                                    <div class="btn-group btn-group-sm mt-2">
                                        <a href="edit_article.php?id=<?= $article['id_article'] ?>" 
                                           class="btn btn-outline-primary">
                                            <i class="bi bi-pencil me-1"></i>Éditer
                                        </a>
                                        <a href="../public/article.php?id=<?= $article['id_article'] ?>" 
                                           class="btn btn-outline-secondary">
                                            <i class="bi bi-eye me-1"></i>Voir
                                        </a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="text-center mt-3">
                                <a href="articles.php" class="btn btn-outline-primary">Voir tous mes articles</a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Commentaires récents -->
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-chat-left-text me-2"></i>Derniers commentaires</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($comments)): ?>
                            <div class="text-center py-3">
                                <i class="bi bi-chat-text display-1 text-muted mb-2"></i>
                                <p class="text-muted">Aucun commentaire sur vos articles</p>
                            </div>
                            <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($comments as $comment): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <strong><?= htmlspecialchars($comment['commenter_name'] ?? substr($comment['email'], 0, strpos($comment['email'], '@'))) ?></strong>
                                        <span class="badge bg-<?= 
                                            $comment['status'] === 'approved' ? 'success' : 
                                            ($comment['status'] === 'pending' ? 'warning' : 'secondary')
                                        ?>">
                                            <?= $comment['status'] ?>
                                        </span>
                                    </div>
                                    <p class="mb-1 small"><?= htmlspecialchars(substr($comment['contenu'], 0, 80)) ?>...</p>
                                    <small class="text-muted">Sur: <?= htmlspecialchars($comment['titre']) ?></small>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Actions rapides -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-lightning me-2"></i>Actions rapides</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="add_article.php" class="btn btn-primary">
                                    <i class="bi bi-plus-circle me-2"></i>Nouvel article
                                </a>
                                <a href="articles.php" class="btn btn-outline-primary">
                                    <i class="bi bi-list-ul me-2"></i>Gérer mes articles
                                </a>
                                <a href="../public/profile.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-gear me-2"></i>Mon profil
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>