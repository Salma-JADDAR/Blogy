<?php
require_once '../includes/init.php';

// Vérifier que l'utilisateur est admin ou éditeur
requireAnyRole(['admin', 'editor']);

$page_title = "Gestion des catégories";
$body_class = "admin-categories";
$message = '';
$message_type = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add':
                $nom = trim($_POST['nom_categorie'] ?? '');
                $description = trim($_POST['description'] ?? '');
                
                if (empty($nom)) {
                    $message = "Le nom de la catégorie est requis";
                    $message_type = "danger";
                } else {
                    $sql = "INSERT INTO Categorie (nom_categorie, description) VALUES (:nom, :desc)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([':nom' => $nom, ':desc' => $description]);
                    $message = "Catégorie ajoutée avec succès";
                    $message_type = "success";
                }
                break;
                
            case 'edit':
                $id = intval($_POST['id_categorie']);
                $nom = trim($_POST['nom_categorie'] ?? '');
                $description = trim($_POST['description'] ?? '');
                
                if (empty($nom)) {
                    $message = "Le nom de la catégorie est requis";
                    $message_type = "danger";
                } else {
                    $sql = "UPDATE Categorie SET nom_categorie = :nom, description = :desc WHERE id_categorie = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([':nom' => $nom, ':desc' => $description, ':id' => $id]);
                    $message = "Catégorie mise à jour avec succès";
                    $message_type = "success";
                }
                break;
                
            case 'delete':
                $id = intval($_POST['id_categorie']);
                
                // Vérifier si la catégorie contient des articles
                $sql = "SELECT COUNT(*) as count FROM Article WHERE id_categorie = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':id' => $id]);
                $article_count = $stmt->fetch()['count'];
                
                if ($article_count > 0) {
                    $message = "Impossible de supprimer cette catégorie car elle contient $article_count article(s)";
                    $message_type = "danger";
                } else {
                    $sql = "DELETE FROM Categorie WHERE id_categorie = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([':id' => $id]);
                    $message = "Catégorie supprimée avec succès";
                    $message_type = "success";
                }
                break;
        }
    } catch(PDOException $e) {
        $message = "Erreur: " . $e->getMessage();
        $message_type = "danger";
    }
}

// Récupérer toutes les catégories
$categories = getCategories();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <?php require_once '../includes/header.php'; ?>
    <style>
        .category-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            border-left: 4px solid #667eea;
        }
        
        .category-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .category-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-action {
            padding: 5px 12px;
            font-size: 13px;
        }
        
        .article-count {
            background: #f8f9fa;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            color: #6c757d;
        }
        
        .modal-content {
            border-radius: 15px;
            border: none;
        }
        
        .modal-header {
            background: #f8f9fa;
            border-radius: 15px 15px 0 0;
            padding: 20px;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px 20px;
        }
        
        .empty-state i {
            font-size: 50px;
            color: #dee2e6;
            margin-bottom: 20px;
        }
    </style>
</head>
<body class="<?php echo $body_class; ?>">
    <?php require_once '../includes/header.php'; ?>
    
    <div class="main">
        <div class="container py-5">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-5">
                <div>
                    <h1 class="mb-2">Gestion des catégories</h1>
                    <p class="text-muted mb-0">Ajoutez, modifiez ou supprimez des catégories</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    <i class="bi bi-plus-lg me-1"></i> Nouvelle catégorie
                </button>
            </div>
            
            <!-- Message d'alerte -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Liste des catégories -->
            <?php if (!empty($categories)): ?>
                <div class="row">
                    <?php foreach($categories as $category): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="category-card">
                                <div class="category-header">
                                    <h5 class="mb-0"><?php echo htmlspecialchars($category['nom_categorie']); ?></h5>
                                    <div class="category-actions">
                                        <button class="btn btn-sm btn-outline-primary btn-action edit-category" 
                                                data-id="<?php echo $category['id_categorie']; ?>"
                                                data-nom="<?php echo htmlspecialchars($category['nom_categorie']); ?>"
                                                data-desc="<?php echo htmlspecialchars($category['description'] ?? ''); ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger btn-action delete-category"
                                                data-id="<?php echo $category['id_categorie']; ?>"
                                                data-nom="<?php echo htmlspecialchars($category['nom_categorie']); ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <?php if (!empty($category['description'])): ?>
                                    <p class="mb-3"><?php echo htmlspecialchars($category['description']); ?></p>
                                <?php endif; ?>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="article-count">
                                        <i class="bi bi-file-earmark-text"></i>
                                        <?php echo $category['nb_articles']; ?> article(s)
                                    </span>
                                    <small class="text-muted">
                                        ID: <?php echo $category['id_categorie']; ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-bookmarks"></i>
                    <h4 class="mt-3">Aucune catégorie</h4>
                    <p class="text-muted">Commencez par ajouter votre première catégorie</p>
                    <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="bi bi-plus-lg me-1"></i> Ajouter une catégorie
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modals -->
    
    <!-- Modal Ajout -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter une catégorie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nom_categorie" class="form-label">Nom de la catégorie *</label>
                            <input type="text" class="form-control" id="nom_categorie" name="nom_categorie" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description (optionnelle)</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" name="action" value="add" class="btn btn-primary">Ajouter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Édition -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier la catégorie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="id_categorie" id="edit_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_nom_categorie" class="form-label">Nom de la catégorie *</label>
                            <input type="text" class="form-control" id="edit_nom_categorie" name="nom_categorie" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description (optionnelle)</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" name="action" value="edit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Suppression -->
    <div class="modal fade" id="deleteCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmer la suppression</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="id_categorie" id="delete_id">
                    <div class="modal-body">
                        <p>Êtes-vous sûr de vouloir supprimer la catégorie <strong id="delete_nom"></strong> ?</p>
                        <p class="text-danger"><small>Cette action est irréversible !</small></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" name="action" value="delete" class="btn btn-danger">Supprimer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php require_once '../includes/footer.php'; ?>
    
    <script>
        // Gérer l'édition des catégories
        document.querySelectorAll('.edit-category').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const nom = this.getAttribute('data-nom');
                const desc = this.getAttribute('data-desc');
                
                document.getElementById('edit_id').value = id;
                document.getElementById('edit_nom_categorie').value = nom;
                document.getElementById('edit_description').value = desc;
                
                const modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
                modal.show();
            });
        });
        
        // Gérer la suppression des catégories
        document.querySelectorAll('.delete-category').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const nom = this.getAttribute('data-nom');
                
                document.getElementById('delete_id').value = id;
                document.getElementById('delete_nom').textContent = nom;
                
                const modal = new bootstrap.Modal(document.getElementById('deleteCategoryModal'));
                modal.show();
            });
        });
        
        // Focus sur le champ nom lors de l'ouverture du modal d'ajout
        document.getElementById('addCategoryModal').addEventListener('shown.bs.modal', function() {
            document.getElementById('nom_categorie').focus();
        });
    </script>
</body>
</html>