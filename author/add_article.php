<?php
$pageTitle = "Nouvel article";
require_once '../includes/header.php';

$auth->requireRole('auteur');

// Charger TinyMCE
$loadTinyMCE = true;

// Récupérer les catégories
$categories = getCategories($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = sanitize($_POST['titre'] ?? '');
    $contenu = $_POST['contenu'] ?? '';
    $id_categorie = isset($_POST['id_categorie']) ? intval($_POST['id_categorie']) : null;
    $status = sanitize($_POST['status'] ?? 'draft');
    $username = $_SESSION['username'];
    
    if (empty($titre) || empty($contenu)) {
        addFlashMessage('danger', 'Le titre et le contenu sont obligatoires');
    } else {
        // Upload d'image (optionnel)
        $image_url = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadImage($_FILES['image']);
            if ($uploadResult['success']) {
                $image_url = $uploadResult['filename'];
            } else {
                addFlashMessage('warning', $uploadResult['message']);
            }
        }
        
        // Insérer l'article
        $stmt = $pdo->prepare("
            INSERT INTO Article (titre, contenu, image_url, username, id_categorie, status) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$titre, $contenu, $image_url, $username, $id_categorie, $status])) {
            $articleId = $pdo->lastInsertId();
            addFlashMessage('success', 'Article créé avec succès');
            
            // Redirection selon le statut
            if ($status === 'published') {
                header("Location: ../public/article.php?id=$articleId");
            } else {
                header("Location: articles.php");
            }
            exit();
        } else {
            addFlashMessage('danger', 'Erreur lors de la création de l\'article');
        }
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Créer un nouvel article</h1>
                <a href="articles.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Retour
                </a>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="titre" class="form-label">Titre de l'article *</label>
                            <input type="text" class="form-control form-control-lg" id="titre" name="titre" 
                                   value="<?= $_POST['titre'] ?? '' ?>" required>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="id_categorie" class="form-label">Catégorie</label>
                                <select class="form-select" id="id_categorie" name="id_categorie">
                                    <option value="">Sélectionner une catégorie</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id_categorie'] ?>" 
                                        <?= ($_POST['id_categorie'] ?? '') == $cat['id_categorie'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['nom_categorie']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="status" class="form-label">Statut</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="draft" <?= ($_POST['status'] ?? 'draft') == 'draft' ? 'selected' : '' ?>>Brouillon</option>
                                    <option value="published" <?= ($_POST['status'] ?? '') == 'published' ? 'selected' : '' ?>>Publié</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="image" class="form-label">Image principale (optionnel)</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                            <div class="form-text">Formats acceptés: JPG, PNG, GIF, WEBP (max 5MB)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="contenu" class="form-label">Contenu de l'article *</label>
                            <textarea class="form-control" id="contenu" name="contenu" rows="20" required><?= $_POST['contenu'] ?? '' ?></textarea>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="articles.php" class="btn btn-secondary me-md-2">Annuler</a>
                            <button type="submit" name="action" value="save_draft" class="btn btn-warning me-md-2">
                                <i class="bi bi-save me-2"></i>Enregistrer comme brouillon
                            </button>
                            <button type="submit" name="action" value="publish" class="btn btn-success">
                                <i class="bi bi-send me-2"></i>Publier maintenant
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Changer le statut selon le bouton cliqué
document.querySelector('button[name="action"][value="save_draft"]').addEventListener('click', function(e) {
    document.getElementById('status').value = 'draft';
});

document.querySelector('button[name="action"][value="publish"]').addEventListener('click', function(e) {
    document.getElementById('status').value = 'published';
    
    // Validation avant publication
    const titre = document.getElementById('titre').value.trim();
    const contenu = tinyMCE.get('contenu').getContent().trim();
    
    if (!titre) {
        e.preventDefault();
        alert('Le titre est obligatoire pour publier');
        document.getElementById('titre').focus();
        return false;
    }
    
    if (!contenu) {
        e.preventDefault();
        alert('Le contenu est obligatoire pour publier');
        return false;
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>