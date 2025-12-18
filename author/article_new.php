<?php
require_once '../includes/init.php';

// Vérifier que l'utilisateur peut créer des articles
requireAnyRole(['admin', 'editor', 'auteur']);

$page_title = "Nouvel article";
$body_class = "author-article-new";
$message = '';
$message_type = '';

// Récupérer les catégories
$categories = getCategories();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titre = trim($_POST['titre'] ?? '');
    $contenu = $_POST['contenu'] ?? '';
    $id_categorie = intval($_POST['id_categorie'] ?? 0);
    $status = $_POST['status'] ?? 'draft';
    $excerpt = trim($_POST['excerpt'] ?? '');
    
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
            // Insérer l'article
            $sql = "INSERT INTO Article (titre, contenu, id_categorie, status, username, view_count) 
                    VALUES (:titre, :contenu, :id_categorie, :status, :username, 0)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':titre' => $titre,
                ':contenu' => $contenu,
                ':id_categorie' => $id_categorie,
                ':status' => $status,
                ':username' => $_SESSION['user']['username']
            ]);
            
            $article_id = $pdo->lastInsertId();
            
            $message = "Article créé avec succès !";
            $message_type = "success";
            
            // Rediriger selon le statut
            if ($status == 'published') {
                header("Location: ../article.php?id=" . $article_id);
                exit();
            } else {
                header("Location: article_edit.php?id=" . $article_id);
                exit();
            }
            
        } catch(PDOException $e) {
            $message = "Erreur lors de la création: " . $e->getMessage();
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
        .editor-container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            padding: 12px;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        #contenu {
            min-height: 300px;
            border-radius: 8px;
            border: 2px solid #e9ecef;
            padding: 15px;
        }
        
        .author-info {
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
                <h1 class="mb-2">Créer un nouvel article</h1>
                <p class="text-muted">Remplissez les champs ci-dessous pour créer votre article</p>
            </div>
            
            <!-- Message d'alerte -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Informations de l'auteur -->
            <div class="author-info">
                <div class="row">
                    <div class="col-md-6">
                        <strong>Auteur:</strong> <?php echo htmlspecialchars($_SESSION['user']['nom']); ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Rôle:</strong> <?php echo $_SESSION['user']['role']; ?>
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
                                       value="<?php echo htmlspecialchars($_POST['titre'] ?? ''); ?>"
                                       required
                                       placeholder="Un titre accrocheur pour votre article">
                            </div>
                            
                            <!-- Contenu -->
                            <div class="mb-4">
                                <label for="contenu" class="form-label">Contenu *</label>
                                <textarea id="contenu" name="contenu" rows="15" required><?php echo htmlspecialchars($_POST['contenu'] ?? ''); ?></textarea>
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
                                                    <?php echo ($_POST['id_categorie'] ?? '') == $cat['id_categorie'] ? 'selected' : ''; ?>>
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
                                            <option value="draft" <?php echo ($_POST['status'] ?? 'draft') == 'draft' ? 'selected' : ''; ?>>
                                                Brouillon
                                            </option>
                                            <?php if ($_SESSION['user']['role'] != 'auteur'): ?>
                                            <option value="published" <?php echo ($_POST['status'] ?? '') == 'published' ? 'selected' : ''; ?>>
                                                Publié
                                            </option>
                                            <?php endif; ?>
                                            <option value="archived" <?php echo ($_POST['status'] ?? '') == 'archived' ? 'selected' : ''; ?>>
                                                Archivé
                                            </option>
                                        </select>
                                        <?php if ($_SESSION['user']['role'] == 'auteur'): ?>
                                            <input type="hidden" name="status" value="draft">
                                            <small class="text-muted">Les articles des auteurs doivent être approuvés par un éditeur</small>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Résumé -->
                                    <div class="mb-4">
                                        <label for="excerpt" class="form-label">Résumé (optionnel)</label>
                                        <textarea class="form-control" 
                                                  id="excerpt" 
                                                  name="excerpt" 
                                                  rows="3"
                                                  placeholder="Un court résumé qui apparaîtra dans les listes d'articles"><?php echo htmlspecialchars($_POST['excerpt'] ?? ''); ?></textarea>
                                        <small class="text-muted">Maximum 200 caractères</small>
                                    </div>
                                    
                                    <!-- Boutons -->
                                    <div class="d-grid gap-2">
                                        <button type="submit" name="save" value="draft" class="btn btn-outline-warning">
                                            <i class="bi bi-save"></i> Enregistrer comme brouillon
                                        </button>
                                        
                                        <?php if($_SESSION['user']['role'] != 'auteur'): ?>
                                            <button type="submit" name="save" value="publish" class="btn btn-success">
                                                <i class="bi bi-send"></i> Publier maintenant
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" name="save" value="submit" class="btn btn-primary">
                                                <i class="bi bi-send"></i> Soumettre pour publication
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
    
    <?php require_once '../includes/footer.php'; ?>
    
    <script>
        // Simple éditeur de texte (pourrait être remplacé par TinyMCE)
        document.getElementById('contenu').addEventListener('focus', function() {
            if (this.value === '') {
                this.value = '<p>Commencez à écrire votre article ici...</p>\n\n';
            }
        });
        
        // Gérer les boutons de soumission
        document.querySelectorAll('button[type="submit"]').forEach(button => {
            button.addEventListener('click', function(e) {
                const value = this.value;
                const statusSelect = document.getElementById('status');
                
                if (value === 'publish') {
                    statusSelect.value = 'published';
                } else if (value === 'submit') {
                    // Pour les auteurs, on soumet pour approbation
                    statusSelect.value = 'draft';
                    alert('Votre article a été soumis pour approbation par un éditeur.');
                }
            });
        });
        
        // Validation du formulaire
        document.getElementById('articleForm').addEventListener('submit', function(e) {
            const titre = document.getElementById('titre').value.trim();
            const categorie = document.getElementById('id_categorie').value;
            const contenu = document.getElementById('contenu').value.trim();
            const excerpt = document.getElementById('excerpt').value;
            
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
            
            if (!contenu || contenu === '<p>Commencez à écrire votre article ici...</p>\n\n') {
                e.preventDefault();
                alert('Veuillez saisir le contenu de l\'article');
                document.getElementById('contenu').focus();
                return;
            }
            
            if (excerpt.length > 200) {
                e.preventDefault();
                alert('Le résumé ne peut pas dépasser 200 caractères');
                document.getElementById('excerpt').focus();
                return;
            }
        });
        
        // Compteur de caractères pour le résumé
        document.getElementById('excerpt').addEventListener('input', function() {
            const length = this.value.length;
            const counter = this.nextElementSibling;
            
            if (length > 200) {
                counter.innerHTML = '<span class="text-danger">' + length + '/200 caractères</span>';
            } else {
                counter.innerHTML = '<span class="text-muted">' + length + '/200 caractères</span>';
            }
        });
    </script>
</body>
</html>