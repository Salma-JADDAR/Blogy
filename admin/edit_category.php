<?php
$pageTitle = "Modifier une catégorie";
require_once '../includes/header.php';

$auth->requireRole('admin');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Récupérer la catégorie
$stmt = $pdo->prepare("SELECT * FROM Categorie WHERE id_categorie = ?");
$stmt->execute([$id]);
$categorie = $stmt->fetch();

if (!$categorie) {
    addFlashMessage('danger', 'Catégorie non trouvée');
    header('Location: categories.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = sanitize($_POST['nom_categorie'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    
    if (empty($nom)) {
        addFlashMessage('danger', 'Le nom de la catégorie est obligatoire');
    } else {
        // Vérifier si le nom existe déjà pour une autre catégorie
        $stmt = $pdo->prepare("SELECT id_categorie FROM Categorie WHERE nom_categorie = ? AND id_categorie != ?");
        $stmt->execute([$nom, $id]);
        
        if ($stmt->fetch()) {
            addFlashMessage('warning', 'Ce nom de catégorie est déjà utilisé');
        } else {
            // Mettre à jour la catégorie
            $stmt = $pdo->prepare("UPDATE Categorie SET nom_categorie = ?, description = ? WHERE id_categorie = ?");
            if ($stmt->execute([$nom, $description, $id])) {
                addFlashMessage('success', 'Catégorie mise à jour avec succès');
                header('Location: categories.php');
                exit();
            } else {
                addFlashMessage('danger', 'Erreur lors de la mise à jour');
            }
        }
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Modifier la catégorie</h1>
                <a href="categories.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Retour
                </a>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="nom_categorie" class="form-label">Nom de la catégorie *</label>
                            <input type="text" class="form-control" id="nom_categorie" name="nom_categorie" 
                                   value="<?= htmlspecialchars($categorie['nom_categorie']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?= htmlspecialchars($categorie['description'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="categories.php" class="btn btn-secondary me-md-2">Annuler</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-2"></i>Mettre à jour
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>