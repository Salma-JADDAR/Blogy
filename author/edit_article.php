<?php
$pageTitle = "Modifier l'article";
require_once '../includes/header.php';

$auth->requireRole('auteur');

// Charger TinyMCE
$loadTinyMCE = true;

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$username = $_SESSION['username'];

// Récupérer l'article
$stmt = $pdo->prepare("
    SELECT a.*, c.nom_categorie 
    FROM Article a 
    LEFT JOIN Categorie c ON a.id_categorie = c.id_categorie 
    WHERE a.id_article = ?
");
$stmt->execute([$id]);
$article = $stmt->fetch();

// Vérifier les permissions
if (!$article) {
    addFlashMessage('danger', 'Article non trouvé');
    header('Location: articles.php');
    exit();
}

if (!$auth->isAdmin() && !$auth->isEditor() && $article['username'] !== $username) {
    addFlashMessage('danger', 'Vous n\'êtes pas autorisé à modifier cet article');
    header('Location: articles.php');
    exit();
}

// Récupérer les catégories
$categories = getCategories($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = sanitize($_POST['titre'] ?? '');
    $contenu = $_POST['contenu'] ?? '';
    $id_categorie = isset($_POST['id_categorie']) ? intval($_POST['id_categorie']) : null;
    $status = sanitize($_POST['status'] ?? 'draft');
    
    if (empty($titre) || empty($contenu)) {
        addFlashMessage('danger', 'Le titre et le contenu sont obligatoires');
    } else {
        // Upload d'image (optionnel)
        $image_url = $article['image_url'];
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadImage($_FILES['image']);
            if ($uploadResult['success']) {
                // Supprimer l'ancienne image si elle existe
                if ($image_url && file_exists(UPLOAD_DIR . $image_url)) {
                    unlink(UPLOAD_DIR . $image_url);
                }
                $image_url = $uploadResult['filename'];
            } else {
                addFlashMessage('warning', $uploadResult['message']);
            }
        }
        
        // Supprimer l'image si demandé
        if (isset($_POST['delete_image']) && $image_url) {
            if (file_exists(UPLOAD_DIR . $image_url)) {
                unlink(UPLOAD_DIR . $image_url);
            }
            $image_url = null;
        }
        
        // Mettre à jour l'article
        $stmt = $pdo->prepare("
            UPDATE Article 
            SET titre = ?, contenu = ?, image_url = ?, id_categorie = ?, status = ?, 
                date_modification = CURRENT_TIMESTAMP 
            WHERE id_article = ?
        ");
        
        if ($stmt->execute([$titre, $contenu, $image_url, $id_categorie, $status, $id])) {
            addFlashMessage('success', 'Article mis à jour avec succès');
            
            // Redirection selon le statut
            if ($status === 'published') {
                header("Location: ../public/article.php?id=$id");
            } else {
                header("Location: articles.php");
            }
            exit();
        } else {
            addFlashMessage('danger', 'Erreur lors de la mise à jour de l\'article');
        }
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Modifier l'article</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="../public/article.php?id=<?= $id ?>" class="btn btn-outline-primary me-2">
                        <i class="bi bi-eye me-2"></i>Voir
                    </a>
                    <a href="articles.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Retour
                    </a>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="titre" class="form-label">Titre de l'article *</label>
                            <input type="text" class="form-control form-control-lg" id="titre" name="titre" 
                                   value="<?= htmlspecialchars($article['titre']) ?>" required>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="id_categorie" class="form-label">Catégorie</label>
                                <select class="form-select" id="id_categorie" name="id_categorie">
                                    <option value="">Sélectionner une catégorie</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id_categorie'] ?>" 
                                        <?= $article['id_categorie'] == $cat['id_categorie'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['nom_categorie']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="status" class="form-label">Statut</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="draft" <?= $article['status'] == 'draft' ? 'selected' : '' ?>>Brouillon</option>
                                    <option value="published" <?= $article['status'] == 'published' ? 'selected' : '' ?>>Publié</option>
                                    <option value="archived" <?= $article['status'] == 'archived' ? 'selected' : '' ?>>Archivé</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Image actuelle -->
                        <?php if ($article['image_url']): ?>
                        <div class="mb-3">
                            <label class="form-label">Image actuelle</label>
                            <div class="mb-2">
                                <img src="../assets/uploads/<?= htmlspecialchars($article['image_url']) ?>" 
                                     class="img-thumbnail" style="max-height: 200px;">
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="delete_image" name="delete_image">
                                <label class="form-check-label text-danger" for="delete_image">
                                    Supprimer cette image
                                </label>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="image" class="form-label">
                                <?= $article['image_url'] ? 'Remplacer l\'image' : 'Ajouter une image' ?> (optionnel)
                            </label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                            <div class="form-text">Formats acceptés: JPG, PNG, GIF, WEBP (max 5MB)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="contenu" class="form-label">Contenu de l'article *</label>
                            <textarea class="form-control" id="contenu" name="contenu" rows="20" required><?= htmlspecialchars($article['contenu']) ?></textarea>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="articles.php" class="btn btn-secondary me-md-2">Annuler</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-2"></i>Mettre à jour
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Informations de l'article -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Informations de l'article</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>ID:</strong> <?= $article['id_article'] ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Créé le:</strong> <?= formatDate($article['date_creation']) ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Modifié le:</strong> <?= formatDate($article['date_modification']) ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Vues:</strong> <?= $article['view_count'] ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>