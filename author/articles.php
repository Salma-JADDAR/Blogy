<?php
$pageTitle = "Mes articles";
require_once '../includes/header.php';

$auth->requireRole('auteur');

$username = $_SESSION['username'];
$filter = $_GET['filter'] ?? 'all';

// Construire la requête avec filtre
$whereClause = "WHERE username = ?";
$params = [$username];

switch ($filter) {
    case 'published':
        $whereClause .= " AND status = 'published'";
        break;
    case 'draft':
        $whereClause .= " AND status = 'draft'";
        break;
    case 'archived':
        $whereClause .= " AND status = 'archived'";
        break;
}

// Récupérer les articles avec pagination
$query = "
    SELECT a.*, c.nom_categorie 
    FROM Article a 
    LEFT JOIN Categorie c ON a.id_categorie = c.id_categorie 
    $whereClause 
    ORDER BY a.date_creation DESC
";

$result = getPagination($pdo, $query, $params, 10);
$articles = $result['data'];
$pagination = $result;

// Supprimer un article
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Vérifier que l'article appartient bien à l'utilisateur
    $stmt = $pdo->prepare("SELECT username FROM Article WHERE id_article = ?");
    $stmt->execute([$id]);
    $article = $stmt->fetch();
    
    if ($article && ($article['username'] === $username || $auth->isAdmin())) {
        $stmt = $pdo->prepare("DELETE FROM Article WHERE id_article = ?");
        if ($stmt->execute([$id])) {
            addFlashMessage('success', 'Article supprimé avec succès');
        } else {
            addFlashMessage('danger', 'Erreur lors de la suppression');
        }
    } else {
        addFlashMessage('danger', 'Vous n\'êtes pas autorisé à supprimer cet article');
    }
    
    header('Location: articles.php' . ($filter != 'all' ? "?filter=$filter" : ''));
    exit();
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Mes articles</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="add_article.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Nouvel article
                    </a>
                </div>
            </div>
            
            <!-- Filtres -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="btn-group" role="group">
                                <a href="articles.php?filter=all" 
                                   class="btn btn-outline-primary <?= $filter == 'all' ? 'active' : '' ?>">
                                    Tous (<?= $pagination['total'] ?>)
                                </a>
                                <a href="articles.php?filter=published" 
                                   class="btn btn-outline-success <?= $filter == 'published' ? 'active' : '' ?>">
                                    Publiés
                                </a>
                                <a href="articles.php?filter=draft" 
                                   class="btn btn-outline-warning <?= $filter == 'draft' ? 'active' : '' ?>">
                                    Brouillons
                                </a>
                                <a href="articles.php?filter=archived" 
                                   class="btn btn-outline-secondary <?= $filter == 'archived' ? 'active' : '' ?>">
                                    Archivés
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tableau des articles -->
            <div class="card">
                <div class="card-body">
                    <?php if (empty($articles)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-file-text display-1 text-muted mb-3"></i>
                        <h4>Aucun article trouvé</h4>
                        <p class="text-muted">Vous n'avez pas encore créé d'articles</p>
                        <a href="add_article.php" class="btn btn-primary">Créer mon premier article</a>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Titre</th>
                                    <th>Catégorie</th>
                                    <th>Statut</th>
                                    <th>Vues</th>
                                    <th>Création</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($articles as $article): ?>
                                <tr>
                                    <td>
                                        <a href="../public/article.php?id=<?= $article['id_article'] ?>" 
                                           class="text-decoration-none">
                                            <?= htmlspecialchars($article['titre']) ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($article['nom_categorie'] ?? 'Non catégorisé') ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $article['status'] === 'published' ? 'success' : 
                                            ($article['status'] === 'draft' ? 'warning' : 'secondary')
                                        ?>">
                                            <?= $article['status'] ?>
                                        </span>
                                    </td>
                                    <td><?= $article['view_count'] ?></td>
                                    <td><?= formatDate($article['date_creation'], 'd/m/Y') ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="../public/article.php?id=<?= $article['id_article'] ?>" 
                                               class="btn btn-outline-primary" title="Voir">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="edit_article.php?id=<?= $article['id_article'] ?>" 
                                               class="btn btn-outline-warning" title="Éditer">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="articles.php?delete=<?= $article['id_article'] ?><?= $filter != 'all' ? "&filter=$filter" : '' ?>" 
                                               class="btn btn-outline-danger" 
                                               onclick="return confirm('Supprimer définitivement cet article ?')" title="Supprimer">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($pagination['totalPages'] > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $pagination['totalPages']; $i++): ?>
                            <li class="page-item <?= $i == $pagination['page'] ? 'active' : '' ?>">
                                <a class="page-link" 
                                   href="?page=<?= $i ?><?= $filter != 'all' ? "&filter=$filter" : '' ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>