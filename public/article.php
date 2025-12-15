<?php
$pageTitle = "Article";
require_once '../includes/header.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Récupérer l'article
$stmt = $pdo->prepare("
    SELECT a.*, u.nom, u.username, u.role, c.nom_categorie 
    FROM Article a 
    LEFT JOIN Utilisateur u ON a.username = u.username 
    LEFT JOIN Categorie c ON a.id_categorie = c.id_categorie 
    WHERE a.id_article = ? AND (a.status = 'published' OR ? = 'admin' OR ? = 'editor' OR a.username = ?)
");
$stmt->execute([$id, $auth->getUserRole(), $auth->getUserRole(), $_SESSION['username'] ?? '']);
$article = $stmt->fetch();

if (!$article) {
    addFlashMessage('danger', 'Article non trouvé ou non publié');
    header('Location: ../core/index.php');
    exit();
}

// Incrémenter le compteur de vues
if ($auth->getUserInfo()['username'] !== $article['username']) {
    incrementViews($pdo, $id);
}

// Récupérer les commentaires
$stmt = $pdo->prepare("
    SELECT c.*, u.nom, u.username 
    FROM Commentaire c 
    LEFT JOIN Utilisateur u ON c.username = u.username 
    WHERE c.id_article = ? AND c.status = 'approved' 
    ORDER BY c.date_commentaire ASC
");
$stmt->execute([$id]);
$comments = $stmt->fetchAll();

// Ajouter un commentaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $contenu = sanitize($_POST['contenu'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $username = $_SESSION['username'] ?? null;
    
    if (empty($contenu)) {
        addFlashMessage('danger', 'Le commentaire ne peut pas être vide');
    } elseif (!$auth->isLoggedIn() && empty($email)) {
        addFlashMessage('danger', 'L\'email est obligatoire pour les visiteurs');
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO Commentaire (contenu, username, email, id_article, status) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $status = $auth->isLoggedIn() ? 'approved' : 'pending';
        
        if ($stmt->execute([$contenu, $username, $email, $id, $status])) {
            addFlashMessage('success', $auth->isLoggedIn() ? 
                'Commentaire ajouté avec succès' : 
                'Commentaire en attente de modération');
            header("Location: article.php?id=$id");
            exit();
        } else {
            addFlashMessage('danger', 'Erreur lors de l\'ajout du commentaire');
        }
    }
}

// Récupérer les articles similaires
$similarArticles = [];
if ($article['id_categorie']) {
    $stmt = $pdo->prepare("
        SELECT a.*, u.nom 
        FROM Article a 
        LEFT JOIN Utilisateur u ON a.username = u.username 
        WHERE a.id_categorie = ? AND a.status = 'published' AND a.id_article != ? 
        ORDER BY a.date_creation DESC 
        LIMIT 3
    ");
    $stmt->execute([$article['id_categorie'], $id]);
    $similarArticles = $stmt->fetchAll();
}
?>

<div class="container mt-4">
    <!-- Navigation -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../core/index.php">Accueil</a></li>
            <?php if ($article['nom_categorie']): ?>
            <li class="breadcrumb-item">
                <a href="category.php?id=<?= $article['id_categorie'] ?>">
                    <?= htmlspecialchars($article['nom_categorie']) ?>
                </a>
            </li>
            <?php endif; ?>
            <li class="breadcrumb-item active" aria-current="page">Article</li>
        </ol>
    </nav>
    
    <!-- Article -->
    <article class="blog-post">
        <!-- En-tête -->
        <div class="mb-4">
            <?php if ($article['image_url']): ?>
            <img src="../assets/uploads/<?= htmlspecialchars($article['image_url']) ?>" 
                 class="img-fluid rounded mb-3" alt="<?= htmlspecialchars($article['titre']) ?>">
            <?php endif; ?>
            
            <h1 class="blog-post-title"><?= htmlspecialchars($article['titre']) ?></h1>
            
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <span class="badge bg-secondary me-2">
                        <?= htmlspecialchars($article['nom_categorie'] ?? 'Non catégorisé') ?>
                    </span>
                    <span class="badge bg-<?= $article['status'] === 'published' ? 'success' : 'warning' ?>">
                        <?= $article['status'] ?>
                    </span>
                </div>
                
                <?php if ($auth->isAuthor() && ($auth->getUserInfo()['username'] === $article['username'] || $auth->isAdmin() || $auth->isEditor())): ?>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-gear"></i>
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="../author/edit_article.php?id=<?= $article['id_article'] ?>">
                                <i class="bi bi-pencil me-2"></i>Éditer
                            </a>
                        </li>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="blog-post-meta mb-4">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" 
                             style="width: 40px; height: 40px;">
                            <?= strtoupper(substr($article['nom'], 0, 1)) ?>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <strong><?= htmlspecialchars($article['nom']) ?></strong>
                        <?php if ($article['role'] === 'admin'): ?>
                        <span class="badge bg-danger ms-2">Admin</span>
                        <?php elseif ($article['role'] === 'editor'): ?>
                        <span class="badge bg-warning ms-2">Éditeur</span>
                        <?php endif; ?>
                        <div class="text-muted small">
                            Publié le <?= formatDate($article['date_creation']) ?>
                            <?php if ($article['date_modification'] !== $article['date_creation']): ?>
                            • Mis à jour le <?= formatDate($article['date_modification']) ?>
                            <?php endif; ?>
                            • <i class="bi bi-eye me-1"></i><?= $article['view_count'] ?> vues
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Contenu -->
        <div class="blog-post-content mb-5">
            <?= nl2br(htmlspecialchars($article['contenu'])) ?>
        </div>
        
        <!-- Partage -->
        <div class="card mb-5">
            <div class="card-body text-center">
                <h5>Partager cet article</h5>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-outline-primary">
                        <i class="bi bi-facebook"></i>
                    </button>
                    <button type="button" class="btn btn-outline-info">
                        <i class="bi bi-twitter"></i>
                    </button>
                    <button type="button" class="btn btn-outline-success">
                        <i class="bi bi-whatsapp"></i>
                    </button>
                    <button type="button" class="btn btn-outline-dark">
                        <i class="bi bi-link-45deg"></i> Copier le lien
                    </button>
                </div>
            </div>
        </div>
    </article>
    
    <!-- Articles similaires -->
    <?php if (!empty($similarArticles)): ?>
    <div class="mb-5">
        <h3 class="mb-4">Articles similaires</h3>
        <div class="row">
            <?php foreach ($similarArticles as $similar): ?>
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">
                            <a href="article.php?id=<?= $similar['id_article'] ?>" class="text-decoration-none">
                                <?= htmlspecialchars($similar['titre']) ?>
                            </a>
                        </h5>
                        <p class="card-text small">
                            <?= substr(strip_tags($similar['contenu']), 0, 100) ?>...
                        </p>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">Par <?= htmlspecialchars($similar['nom']) ?></small>
                            <span class="badge bg-light text-dark">
                                <i class="bi bi-eye"></i> <?= $similar['view_count'] ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Commentaires -->
    <div class="mb-5">
        <h3 class="mb-4">Commentaires (<?= count($comments) ?>)</h3>
        
        <!-- Liste des commentaires -->
        <?php if (empty($comments)): ?>
        <div class="alert alert-info">
            Soyez le premier à commenter cet article !
        </div>
        <?php else: ?>
        <div class="comments-list">
            <?php foreach ($comments as $comment): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" 
                                 style="width: 40px; height: 40px;">
                                <?= $comment['username'] ? 
                                    strtoupper(substr($comment['nom'], 0, 1)) : 
                                    strtoupper(substr($comment['email'], 0, 1)) ?>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong>
                                    <?= $comment['username'] ? 
                                        htmlspecialchars($comment['nom']) : 
                                        htmlspecialchars($comment['email']) ?>
                                </strong>
                                <small class="text-muted">
                                    <?= formatDate($comment['date_commentaire'], 'relative') ?>
                                </small>
                            </div>
                            <p class="mt-2 mb-0"><?= nl2br(htmlspecialchars($comment['contenu'])) ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Formulaire d'ajout de commentaire -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Ajouter un commentaire</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="contenu" class="form-label">Votre commentaire *</label>
                        <textarea class="form-control" id="contenu" name="contenu" rows="4" 
                                  placeholder="Partagez vos pensées..." required></textarea>
                    </div>
                    
                    <?php if (!$auth->isLoggedIn()): ?>
                    <div class="mb-3">
                        <label for="email" class="form-label">Votre email *</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?= $_POST['email'] ?? '' ?>" required>
                        <div class="form-text">Votre email ne sera pas publié</div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="d-grid">
                        <button type="submit" name="add_comment" class="btn btn-primary">
                            <i class="bi bi-send me-2"></i>Poster le commentaire
                        </button>
                    </div>
                    
                    <?php if (!$auth->isLoggedIn()): ?>
                    <div class="mt-3 text-center">
                        <small class="text-muted">
                            Les commentaires des visiteurs sont soumis à modération.
                            <a href="../core/login.php" class="text-decoration-none">Connectez-vous</a> pour des commentaires instantanés.
                        </small>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>