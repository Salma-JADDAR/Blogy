<?php
$pageTitle = "Gestion des catégories";
require_once '../includes/header.php';

$auth->requireRole('admin');

// Récupérer toutes les catégories
$categories = $pdo->query("SELECT * FROM Categorie ORDER BY nom_categorie")->fetchAll();

// Supprimer une catégorie
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Vérifier si la catégorie a des articles
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM Article WHERE id_categorie = ?");
    $stmt->execute([$id]);
    $hasArticles = $stmt->fetch()['count'] > 0;
    
    if ($hasArticles) {
        addFlashMessage('danger', 'Impossible de supprimer cette catégorie car elle contient des articles');
    } else {
        $stmt = $pdo->prepare("DELETE FROM Categorie WHERE id_categorie = ?");
        if ($stmt->execute([$id])) {
            addFlashMessage('success', 'Catégorie supprimée avec succès');
        } else {
            addFlashMessage('danger', 'Erreur lors de la suppression');
        }
    }
    header('Location: categories.php');
    exit();
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Gestion des catégories</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="add_category.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Ajouter une catégorie
                    </a>
                </div>
            </div>
            
            <!-- Tableau des catégories -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nom</th>
                                    <th>Description</th>
                                    <th>Articles</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $cat): 
                                    // Compter les articles dans cette catégorie
                                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM Article WHERE id_categorie = ?");
                                    $stmt->execute([$cat['id_categorie']]);
                                    $articleCount = $stmt->fetch()['count'];
                                ?>
                                <tr>
                                    <td><?= $cat['id_categorie'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($cat['nom_categorie']) ?></strong>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars(substr($cat['description'] ?? 'Pas de description', 0, 100)) ?>
                                        <?php if (strlen($cat['description'] ?? '') > 100): ?>...<?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary"><?= $articleCount ?></span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="edit_category.php?id=<?= $cat['id_categorie'] ?>" 
                                               class="btn btn-outline-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="categories.php?delete=<?= $cat['id_categorie'] ?>" 
                                               class="btn btn-outline-danger"
                                               onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <?php if (empty($categories)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="bi bi-tags display-4 d-block mb-2"></i>
                                            Aucune catégorie trouvée
                                        </div>
                                        <a href="add_category.php" class="btn btn-primary mt-2">
                                            Créer la première catégorie
                                        </a>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Statistiques -->
            <div class="row mt-4">
                <div class="col-md-4">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <h5 class="card-title">Total des catégories</h5>
                            <p class="card-text display-4"><?= count($categories) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <h5 class="card-title">Catégories avec articles</h5>
                            <p class="card-text display-4">
                                <?php 
                                $stmt = $pdo->query("
                                    SELECT COUNT(DISTINCT id_categorie) as count 
                                    FROM Article WHERE id_categorie IS NOT NULL
                                ");
                                echo $stmt->fetch()['count'];
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <h5 class="card-title">Catégories vides</h5>
                            <p class="card-text display-4">
                                <?php 
                                $stmt = $pdo->query("
                                    SELECT c.id_categorie 
                                    FROM Categorie c 
                                    LEFT JOIN Article a ON c.id_categorie = a.id_categorie 
                                    WHERE a.id_article IS NULL
                                ");
                                $emptyCats = $stmt->fetchAll();
                                echo count($emptyCats);
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>