<?php
require_once '../includes/init.php';
requireAnyRole(['admin', 'editor', 'auteur']);

$page_title = "Éditer l'article";
$body_class = "author-article-edit";
$message = '';
$message_type = '';

// Récupérer l'ID de l'article
$article_id = $_GET['id'] ?? 0;

// Récupérer l'article
try {
    $sql = "SELECT a.*, u.nom as auteur_nom 
            FROM Article a 
            JOIN Utilisateur u ON a.username = u.username 
            WHERE a.id_article = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $article_id]);
    $article = $stmt->fetch();
    
    if (!$article) {
        header('Location: articles.php');
        exit();
    }
    
    // Vérifier les permissions
    if ($_SESSION['user']['role'] == 'auteur' && $article['username'] != $_SESSION['user']['username']) {
        $_SESSION['error'] = "Vous ne pouvez éditer que vos propres articles";
        header('Location: articles.php');
        exit();
    }
} catch(PDOException $e) {
    die("Erreur: " . $e->getMessage());
}

// Récupérer les catégories
$categories = getCategories();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titre = trim($_POST['titre'] ?? '');
    $contenu = $_POST['contenu'] ?? '';
    $id_categorie = intval($_POST['id_categorie'] ?? 0);
    $status = $_POST['status'] ?? 'draft';
    
    // Validation
    $errors = [];
    
    if (empty($titre)) {
        $errors[] = "Le titre est obligatoire";
    }
    
    if (empty($contenu)) {
        $errors[] = "Le contenu est obligatoire";
    }
    
    if ($id_categorie <= 0) {
        $errors[] = "Veuillez sélectionner une catégorie";
    }
    
    // Les auteurs ne peuvent pas publier directement
    if ($_SESSION['user']['role'] == 'auteur' && $status == 'published') {
        $errors[] = "Les auteurs doivent soumettre leurs articles pour publication";
        $status = 'draft';
    }
    
    if (empty($errors)) {
        try {
            // Mettre à jour l'article
            $sql = "UPDATE Article 
                    SET titre = :titre, 
                        contenu = :contenu, 
                        id_categorie = :id_categorie, 
                        status = :status,
                        date_modification = NOW()
                    WHERE id_article = :id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':titre' => $titre,
                ':contenu' => $contenu,
                ':id_categorie' => $id_categorie,
                ':status' => $status,
                ':id' => $article_id
            ]);
            
            $message = "Article mis à jour avec succès !";
            $message_type = "success";
            
            // Rafraîchir les données
            $stmt->execute([':id' => $article_id]);
            $article = $stmt->fetch();
            
        } catch(PDOException $e) {
            $message = "Erreur lors de la mise à jour: " . $e->getMessage();
            $message_type = "danger";
        }
    } else {
        $message = implode("<br>", $errors);
        $message_type = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <?php require_once '../includes/header.php'; ?>
    <style>
        /* Styles similaires à article_new.php */
        .editor-container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .article-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body class="<?php echo $body_class; ?>">
    <?php require_once '../includes/header.php'; ?>
    
    <div class="main">
        <div class="container py-5">
            <!-- Page Header -->
            <div class="mb-4">
                <h1 class="mb-2">Éditer l'article</h1>
                <p class="text-muted">Modifiez les champs ci-dessous pour mettre à jour votre article</p>
            </div>
            
            <!-- Message d'alerte -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Informations de l'article -->
            <div class="article-info">
                <div class="row">
                    <div class="col-md-4">
                        <strong>ID:</strong> <?php echo $article['id_article']; ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Créé le:</strong> <?php echo formatDate($article['date_creation']); ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Modifié le:</strong> <?php echo formatDate($article['date_modification']); ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Vues:</strong> <?php echo $article['view_count']; ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Statut:</strong> 
                        <span class="badge bg-<?php 
                            echo $article['status'] == 'published' ? 'success' : 
                                  ($article['status'] == 'draft' ? 'warning' : 'secondary'); 
                        ?>">
                            <?php echo $article['status']; ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Formulaire -->
            <div class="editor-container">
                <form method="POST" action="" id="articleForm">
                    <div class="row">
                        <div class="col-lg-8">
                            <!-- Titre -->
                            <div class="mb-4">
                                <label for="titre" class="form-label">Titre de l'article *</label>
                                <input type="text" 
                                       class="form-control form-control-lg" 
                                       id="titre" 
                                       name="titre" 
                                       value="<?php echo htmlspecialchars($article['titre']); ?>"
                                       required>
                            </div>
                            
                            <!-- Contenu -->
                            <div class="mb-4">
                                <label for="contenu" class="form-label">Contenu *</label>
                                <textarea id="contenu" name="contenu" rows="15" required><?php echo htmlspecialchars($article['contenu']); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <!-- Paramètres -->
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0"><i class="bi bi-gear"></i> Paramètres</h5>
                                </div>
                                <div class="card-body">
                                    <!-- Catégorie -->
                                    <div class="mb-3">
                                        <label for="id_categorie" class="form-label">Catégorie *</label>
                                        <select class="form-select" id="id_categorie" name="id_categorie" required>
                                            <option value="">Sélectionner une catégorie</option>
                                            <?php foreach($categories as $cat): ?>
                                                <option value="<?php echo $cat['id_categorie']; ?>" 
                                                    <?php echo $article['id_categorie'] == $cat['id_categorie'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($cat['nom_categorie']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <!-- Statut -->
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Statut</label>
                                        <select class="form-select" id="status" name="status" 
                                                <?php echo ($_SESSION['user']['role'] == 'auteur') ? 'disabled' : ''; ?>>
                                            <option value="draft" <?php echo $article['status'] == 'draft' ? 'selected' : ''; ?>>
                                                Brouillon
                                            </option>
                                            <?php if ($_SESSION['user']['role'] != 'auteur'): ?>
                                            <option value="published" <?php echo $article['status'] == 'published' ? 'selected' : ''; ?>>
                                                Publié
                                            </option>
                                            <?php endif; ?>
                                            <option value="archived" <?php echo $article['status'] == 'archived' ? 'selected' : ''; ?>>
                                                Archivé
                                            </option>
                                        </select>
                                        <?php if ($_SESSION['user']['role'] == 'auteur'): ?>
                                            <input type="hidden" name="status" value="draft">
                                            <small class="text-muted">Les articles des auteurs doivent être approuvés par un éditeur</small>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Boutons -->
                                    <div class="d-grid gap-2">
                                        <button type="submit" name="save" value="save" class="btn btn-primary">
                                            <i class="bi bi-save"></i> Enregistrer les modifications
                                        </button>
                                        
                                        <a href="articles.php" class="btn btn-outline-secondary">
                                            <i class="bi bi-arrow-left"></i> Retour à la liste
                                        </a>
                                        
                                        <a href="../article.php?id=<?php echo $article_id; ?>" 
                                           class="btn btn-outline-info" target="_blank">
                                            <i class="bi bi-eye"></i> Voir l'article
                                        </a>
                                        
                                        <?php if(canDeleteArticle($article['username'])): ?>
                                            <button type="button" class="btn btn-outline-danger" 
                                                    data-bs-toggle="modal" data-bs-target="#deleteModal">
                                                <i class="bi bi-trash"></i> Supprimer l'article
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal de suppression -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmer la suppression</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir supprimer cet article ?</p>
                    <p class="text-danger"><small>Cette action est irréversible !</small></p>
                    <p class="text-warning"><small>Tous les commentaires associés seront également supprimés.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <form method="POST" action="articles.php" style="display: inline;">
                        <input type="hidden" name="article_id" value="<?php echo $article_id; ?>">
                        <button type="submit" name="delete_article" class="btn btn-danger">Supprimer</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <?php require_once '../includes/footer.php'; ?>
    
    <script>
        // Validation du formulaire
        document.getElementById('articleForm').addEventListener('submit', function(e) {
            const titre = document.getElementById('titre').value.trim();
            const categorie = document.getElementById('id_categorie').value;
            const contenu = document.getElementById('contenu').value.trim();
            
            if (!titre) {
                e.preventDefault();
                alert('Veuillez saisir un titre');
                document.getElementById('titre').focus();
                return;
            }
            
            if (!categorie) {
                e.preventDefault();
                alert('Veuillez sélectionner une catégorie');
                document.getElementById('id_categorie').focus();
                return;
            }
            
            if (!contenu) {
                e.preventDefault();
                alert('Veuillez saisir le contenu de l\'article');
                document.getElementById('contenu').focus();
                return;
            }
        });
        
        // Confirmation avant suppression
        document.querySelector('button[name="delete_article"]')?.addEventListener('click', function(e) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cet article ?')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>