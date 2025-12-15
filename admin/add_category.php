<?php
$pageTitle = "Ajouter une catégorie";
require_once '../includes/header.php';

$auth->requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = sanitize($_POST['nom_categorie'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    
    if (empty($nom)) {
        addFlashMessage('danger', 'Le nom de la catégorie est obligatoire');
    } else {
        // Vérifier si la catégorie existe déjà
        $stmt = $pdo->prepare("SELECT id_categorie FROM Categorie WHERE nom_categorie = ?");
        $stmt->execute([$nom]);
        
        if ($stmt->fetch()) {
            addFlashMessage('warning', 'Cette catégorie existe déjà');
        } else {
            // Insérer la nouvelle catégorie
            $stmt = $pdo->prepare("INSERT INTO Categorie (nom_categorie, description) VALUES (?, ?)");
            if ($stmt->execute([$nom, $description])) {
                addFlashMessage('success', 'Catégorie ajoutée avec succès');
                header('Location: categories.php');
                exit();
            } else {
                addFlashMessage('danger', 'Erreur lors de l\'ajout de la catégorie');
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
                <h1 class="h2">Ajouter une catégorie</h1>
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
                                   value="<?= $_POST['nom_categorie'] ?? '' ?>" required>
                            <div class="form-text">Le nom qui sera affiché sur le site</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="4"><?= $_POST['description'] ?? '' ?></textarea>
                            <div class="form-text">Description optionnelle de la catégorie</div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="categories.php" class="btn btn-secondary me-md-2">Annuler</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-2"></i>Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>