<?php
$pageTitle = "Recherche";
require_once '../includes/header.php';

$searchQuery = isset($_GET['q']) ? sanitize($_GET['q']) : '';
$category = isset($_GET['category']) ? intval($_GET['category']) : 0;
$author = isset($_GET['author']) ? sanitize($_GET['author']) : '';

// Construire la requête de recherche
$whereClause = "WHERE a.status = 'published'";
$params = [];

if (!empty($searchQuery)) {
    $whereClause .= " AND (a.titre LIKE ? OR a.contenu LIKE ?)";
    $searchParam = "%$searchQuery%";
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($category > 0) {
    $whereClause .= " AND a.id_categorie = ?";
    $params[] = $category;
}

if (!empty($author)) {
    $whereClause .= " AND u.username = ?";
    $params[] = $author;
}

// Récupérer les résultats avec pagination
$query = "
    SELECT a.*, u.nom, u.username, c.nom_categorie 
    FROM Article a 
    LEFT JOIN Utilisateur u ON a.username = u.username 
    LEFT JOIN Categorie c ON a.id_categorie = c.id_categorie 
    $whereClause 
    ORDER BY a.date_creation DESC
";

$result = getPagination($pdo, $query, $params);
$articles = $result['data'];
$pagination = $result;

// Récupérer les catégories pour le filtre
$categories = getCategories($pdo);

// Récupérer les auteurs
$authors = $pdo->query("
    SELECT DISTINCT u.username, u.nom 
    FROM Article a 
    LEFT JOIN Utilisateur u ON a.username = u.username 
    WHERE a.status = 'published' 
    ORDER BY u.nom
")->fetchAll();
?>

<div class="container mt-4">
    <!-- En-tête de recherche -->
    <div class="mb-5">
        <h1 class="mb-3">Recherche d'articles</h1>
        
        <!-- Formulaire de recherche -->
        <div class="card">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-6">
                        <label for="q" class="form-label">Mot-clé</label>
                        <input type="text" class="form-control" id="q" name="q" 
                               value="<?= htmlspecialchars($searchQuery) ?>" 
                               placeholder="Rechercher des articles...">
                    </div>
                    
                    <div class="col-md-3">
                        <label for="category" class="form-label">Catégorie</label>
                        <select class="form-select" id="category" name="category">
                            <option value="">Toutes les catégories</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id_categorie'] ?>" 
                                <?= $category == $cat['id_categorie'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nom_categorie']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="author" class="form-label">Auteur</label>
                        <select class="form-select" id="author" name="author">
                            <option value="">Tous les auteurs</option>
                            <?php foreach ($authors as $authorItem): ?>
                            <option value="<?= htmlspecialchars($authorItem['username']) ?>" 
                                <?= $author == $authorItem['username'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($authorItem['nom']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-12">
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="search.php" class="btn btn-secondary me-md-2">Effacer</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search me-2"></i>Rechercher
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Résultats -->
    <div class="mb-4">
        <h2 class="mb-4">
            <?php if (!empty($searchQuery) || $category > 0 || !empty($author)): ?>
            Résultats de recherche
            <small class="text-muted">(<?= $pagination['total'] ?> article<?= $pagination['total'] > 1 ? 's' : '' ?>)</small>
            <?php else: ?>
            Tous les articles
            <?php endif; ?>
        </h2>
        
        <?php if (empty($articles)): ?>
        <div class="text-center py-5">
            <i class="bi bi-search display-1 text-muted mb-3"></i>
            <h4>Aucun résultat trouvé</h4>
            <p class="text-muted">
                <?php if (!empty($searchQuery)): ?>
                Aucun article ne correspond à votre recherche "<?= htmlspecialchars($searchQuery) ?>"
                <?php else: ?>
                Aucun article ne correspond à vos critères
                <?php endif; ?>
            </p>
            <a href="search.php" class="btn btn-primary">Réinitialiser la recherche</a>
        </div>
        <?php else: ?>
        <div class="row">
            <?php foreach ($articles as $article): ?>
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <?php if ($article['image_url']): ?>
                    <img src="../assets/uploads/<?= htmlspecialchars($article['image_url']) ?>" 
                         class="card-img-top" alt="<?= htmlspecialchars($article['titre']) ?>"
                         style="height: 200px; object-fit: cover;">
                    <?php endif; ?>
                    <div class="card-body">
                        <span class="badge bg-secondary mb-2">
                            <?= htmlspecialchars($article['nom_categorie'] ?? 'Non catégorisé') ?>
                        </span>
                        <h5 class="card-title">
                            <a href="article.php?id=<?= $article['id_article'] ?>" class="text-decoration-none">
                                <?= htmlspecialchars($article['titre']) ?>
                            </a>
                        </h5>
                        <p class="card-text">
                            <?= substr(strip_tags($article['contenu']), 0, 150) ?>...
                        </p>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="bi bi-person me-1"></i><?= htmlspecialchars($article['nom']) ?>
                            </small>
                            <small class="text-muted">
                                <i class="bi bi-calendar me-1"></i><?= formatDate($article['date_creation'], 'd/m/Y') ?>
                            </small>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        <a href="article.php?id=<?= $article['id_article'] ?>" class="btn btn-sm btn-outline-primary">
                            Lire la suite
                        </a>
                        <span class="float-end text-muted small">
                            <i class="bi bi-eye"></i> <?= $article['view_count'] ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($pagination['totalPages'] > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <?php 
                // Construction des paramètres GET pour la pagination
                $queryParams = [];
                if (!empty($searchQuery)) $queryParams[] = "q=" . urlencode($searchQuery);
                if ($category > 0) $queryParams[] = "category=$category";
                if (!empty($author)) $queryParams[] = "author=" . urlencode($author);
                $queryString = !empty($queryParams) ? '&' . implode('&', $queryParams) : '';
                ?>
                
                <?php for ($i = 1; $i <= $pagination['totalPages']; $i++): ?>
                <li class="page-item <?= $i == $pagination['page'] ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?><?= $queryString ?>"><?= $i ?></a>
                </li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>