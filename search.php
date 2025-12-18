<?php
$page_title = "Recherche";
$body_class = "search-page";
require_once 'includes/header.php';

// الحصول على كلمة البحث
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$results = [];

if (!empty($search_query)) {
    try {
        $sql = "SELECT a.*, c.nom_categorie, u.nom as auteur_nom 
                FROM Article a 
                JOIN Categorie c ON a.id_categorie = c.id_categorie 
                JOIN Utilisateur u ON a.username = u.username 
                WHERE (a.titre LIKE :query OR a.contenu LIKE :query) 
                AND a.status = 'published' 
                ORDER BY a.date_creation DESC";
        $stmt = $pdo->prepare($sql);
        $search_term = "%$search_query%";
        $stmt->bindValue(':query', $search_term);
        $stmt->execute();
        $results = $stmt->fetchAll();
    } catch(PDOException $e) {
        $results = [];
    }
}
?>

<!-- Page Title -->
<div class="page-title position-relative">
    <div class="breadcrumbs">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php"><i class="bi bi-house"></i> Home</a></li>
                <li class="breadcrumb-item active">Recherche</li>
            </ol>
        </nav>
    </div>

    <div class="title-wrapper">
        <h1>Résultats de recherche</h1>
        <p>
            <?php if(empty($search_query)): ?>
                Entrez un terme de recherche
            <?php else: ?>
                Recherche pour: "<?php echo htmlspecialchars($search_query); ?>"
            <?php endif; ?>
        </p>
    </div>
</div>

<div class="container">
    <div class="row">
        <div class="col-lg-8">
            <!-- Search Results Section -->
            <section id="search-results" class="search-results section">
                <div class="container" data-aos="fade-up" data-aos-delay="100">
                    <?php if(empty($search_query)): ?>
                        <div class="alert alert-info text-center">
                            <h4>Entrez un terme de recherche</h4>
                            <p>Utilisez le formulaire pour rechercher des articles.</p>
                        </div>
                    <?php elseif(count($results) > 0): ?>
                        <div class="row gy-4">
                            <?php foreach($results as $index => $article): ?>
                                <?php 
                                // كل سطر يحتوي على بطاقتين
                                if ($index % 2 == 0 && $index > 0) {
                                    echo '</div><div class="row gy-4 mt-4">';
                                }
                                ?>
                                
                                <div class="col-lg-6 col-md-6">
                                    <div class="blog-post-item">
                                        <?php 
                                        $image_number = ($article['id_article'] % 6) + 1;
                                        ?>
                                        <img src="assets/img/blog/blog-post-<?php echo $image_number; ?>.webp" 
                                             alt="<?php echo htmlspecialchars($article['titre']); ?>"
                                             class="img-fluid">
                                        <div class="blog-post-content">
                                            <div class="post-meta">
                                                <span><i class="bi bi-person"></i> <?php echo htmlspecialchars($article['auteur_nom']); ?></span>
                                                <span><i class="bi bi-clock"></i> <?php echo date('M d, Y', strtotime($article['date_creation'])); ?></span>
                                                <span><i class="bi bi-eye"></i> <?php echo $article['view_count']; ?> vues</span>
                                            </div>
                                            <h2>
                                                <a href="article.php?id=<?php echo $article['id_article']; ?>">
                                                    <?php echo htmlspecialchars($article['titre']); ?>
                                                </a>
                                            </h2>
                                            <p>
                                                <?php 
                                                // Prendre les premiers 120 caractères du contenu
                                                $contenu = strip_tags($article['contenu']);
                                                // Mettre en évidence le terme de recherche
                                                if (!empty($search_query)) {
                                                    $contenu = preg_replace("/(" . preg_quote($search_query) . ")/i", "<mark>$1</mark>", $contenu);
                                                }
                                                if (strlen($contenu) > 120) {
                                                    echo substr($contenu, 0, 120) . '...';
                                                } else {
                                                    echo $contenu;
                                                }
                                                ?>
                                            </p>
                                            <div class="d-flex justify-content-between align-items-center mt-3">
                                                <a href="article.php?id=<?php echo $article['id_article']; ?>" class="read-more">
                                                    Lire plus <i class="bi bi-arrow-right"></i>
                                                </a>
                                                <span class="post-category-badge">
                                                    <?php echo htmlspecialchars($article['nom_categorie']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div><!-- End item -->
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning text-center">
                            <h4>Aucun résultat trouvé</h4>
                            <p>Aucun article ne correspond à votre recherche pour "<?php echo htmlspecialchars($search_query); ?>"</p>
                            <a href="category.php" class="btn btn-primary mt-2">Voir tous les articles</a>
                        </div>
                    <?php endif; ?>
                </div>
            </section><!-- /Search Results Section -->
        </div>
        
        <div class="col-lg-4 sidebar">
            <?php include 'includes/sidebar.php'; ?>
        </div>
    </div>
</div>

<style>

.blog-post-item {
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    height: 100%;
    margin-bottom: 30px;
}

.blog-post-item:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.15);
}

.blog-post-item img {
    width: 100%;
    height: 220px;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.blog-post-item:hover img {
    transform: scale(1.05);
}

.blog-post-content {
    padding: 25px;
}

.blog-post-content .post-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 15px;
    font-size: 14px;
    color: #666;
}

.blog-post-content .post-meta span {
    display: flex;
    align-items: center;
    gap: 5px;
}

.blog-post-content .post-meta i {
    color: #007bff;
    font-size: 12px;
}

.blog-post-content h2 {
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 15px;
    line-height: 1.4;
}

.blog-post-content h2 a {
    color: #333;
    text-decoration: none;
    transition: color 0.3s ease;
}

.blog-post-content h2 a:hover {
    color: #007bff;
}

.blog-post-content p {
    color: #666;
    line-height: 1.6;
    margin-bottom: 20px;
    font-size: 15px;
}

.read-more {
    color: #007bff;
    text-decoration: none;
    font-weight: 500;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: all 0.3s ease;
}

.read-more:hover {
    color: #0056b3;
    gap: 10px;
}

.read-more i {
    font-size: 12px;
    transition: transform 0.3s ease;
}

.read-more:hover i {
    transform: translateX(5px);
}

.post-category-badge {
    background: #f8f9fa;
    color: #007bff;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Highlight pour les résultats de recherche */
mark {
    background-color: #ffeb3b;
    padding: 2px 4px;
    border-radius: 3px;
    font-weight: bold;
}
</style>

<?php require_once 'includes/footer.php'; ?>