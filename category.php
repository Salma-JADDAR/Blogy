<?php
$page_title = "Catégories";
$body_class = "category-page";
require_once 'includes/header.php';

// الحصول على معرّف الفئة من URL
$category_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$current_category = null;
$category_name = "Toutes les catégories";
$category_description = "Parcourez tous nos articles";

// جلب معلومات الفئة إذا كان هناك معرّف
if ($category_id > 0) {
    try {
        $sql = "SELECT * FROM Categorie WHERE id_categorie = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$category_id]);
        $current_category = $stmt->fetch();
        
        if ($current_category) {
            $category_name = htmlspecialchars($current_category['nom_categorie']);
            $category_description = htmlspecialchars($current_category['description'] ?? 'Articles dans cette catégorie');
            $page_title = $category_name . " - Catégorie";
        } else {
            header('Location: category.php');
            exit();
        }
    } catch(PDOException $e) {
        $current_category = null;
    }
}

// إعدادات الترقيم
$articles_per_page = 4;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $articles_per_page;

// جلب العدد الإجمالي للمقالات
try {
    if ($category_id > 0) {
        $sql = "SELECT COUNT(*) as total FROM Article WHERE id_categorie = ? AND status = 'published'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$category_id]);
    } else {
        $sql = "SELECT COUNT(*) as total FROM Article WHERE status = 'published'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    }
    $total_articles = $stmt->fetch()['total'];
} catch(PDOException $e) {
    $total_articles = 0;
}

// حساب عدد الصفحات
$total_pages = ceil($total_articles / $articles_per_page);

// جلب المقالات للصفحة الحالية
try {
    if ($category_id > 0) {
        $sql = "SELECT a.*, c.nom_categorie, u.nom as auteur_nom 
                FROM Article a 
                JOIN Categorie c ON a.id_categorie = c.id_categorie 
                JOIN Utilisateur u ON a.username = u.username 
                WHERE a.id_categorie = :category_id AND a.status = 'published' 
                ORDER BY a.date_creation DESC 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':category_id', $category_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $articles_per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        $sql = "SELECT a.*, c.nom_categorie, u.nom as auteur_nom 
                FROM Article a 
                JOIN Categorie c ON a.id_categorie = c.id_categorie 
                JOIN Utilisateur u ON a.username = u.username 
                WHERE a.status = 'published' 
                ORDER BY a.date_creation DESC 
                LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $articles_per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
    }
    $articles = $stmt->fetchAll();
} catch(PDOException $e) {
    $articles = [];
}

// جلب جميع الفئات
$categories = getCategories();

// جلب المقالات الأخيرة
try {
    $sql_recent = "SELECT a.*, u.nom as auteur_nom 
                   FROM Article a 
                   JOIN Utilisateur u ON a.username = u.username 
                   WHERE a.status = 'published' 
                   ORDER BY a.date_creation DESC 
                   LIMIT 5";
    $stmt_recent = $pdo->prepare($sql_recent);
    $stmt_recent->execute();
    $recent_articles = $stmt_recent->fetchAll();
} catch(PDOException $e) {
    $recent_articles = [];
}
?>

<!-- Page Title -->
<div class="page-title position-relative">
    <div class="breadcrumbs">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php"><i class="bi bi-house"></i> Home</a></li>
                <li class="breadcrumb-item"><a href="category.php">Catégories</a></li>
                <?php if($current_category): ?>
                    <li class="breadcrumb-item active"><?php echo $category_name; ?></li>
                <?php endif; ?>
            </ol>
        </nav>
    </div>

    <div class="title-wrapper">
        <h1><?php echo $category_name; ?></h1>
        <p><?php echo $category_description; ?></p>
        <?php if($total_articles > 0): ?>
            <p class="text-muted">
                <?php echo $total_articles; ?> article<?php echo $total_articles > 1 ? 's' : ''; ?> 
                | Page <?php echo $current_page; ?> sur <?php echo $total_pages; ?>
            </p>
        <?php endif; ?>
    </div>
</div><!-- End Page Title -->

<div class="container">
    <div class="row">
        <div class="col-lg-8">
            <!-- Category Posts Section -->
            <section id="category-posts" class="category-posts section">
                <!-- Section Title -->
                <div class="container section-title" data-aos="fade-up">
                    <h2><?php echo $category_name; ?></h2>
                    <div><span>Découvrez nos</span> <span class="description-title">Articles</span></div>
                </div><!-- End Section Title -->

                <div class="container" data-aos="fade-up" data-aos-delay="100">
                    <?php if(count($articles) > 0): ?>
                        <div class="row gy-4">
                            <?php foreach($articles as $index => $article): ?>
                                <?php 
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
                                                $contenu = strip_tags($article['contenu']);
                                                if (strlen($contenu) > 120) {
                                                    echo htmlspecialchars(substr($contenu, 0, 120)) . '...';
                                                } else {
                                                    echo htmlspecialchars($contenu);
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
                        <div class="alert alert-info text-center">
                            <h4>Aucun article trouvé</h4>
                            <p>
                                <?php if($category_id > 0): ?>
                                    Il n'y a pas encore d'articles publiés dans la catégorie "<?php echo $category_name; ?>".
                                <?php else: ?>
                                    Il n'y a pas encore d'articles publiés sur le blog.
                                <?php endif; ?>
                            </p>
                            <a href="category.php" class="btn btn-primary mt-2">Voir toutes les catégories</a>
                        </div>
                    <?php endif; ?>
                </div>
            </section><!-- /Category Posts Section -->

            <!-- Pagination Section -->
            <?php if($total_pages > 1): ?>
            <section id="pagination" class="pagination section">
                <div class="container" data-aos="fade-up" data-aos-delay="200">
                    <div class="d-flex justify-content-center">
                        <ul class="pagination-list">
                            <!-- Previous Button -->
                            <li class="<?php echo $current_page == 1 ? 'disabled' : ''; ?>">
                                <a href="category.php?<?php 
                                    echo ($category_id > 0 ? "id=$category_id&" : '') . 
                                    "page=" . ($current_page - 1); 
                                ?>" 
                                <?php echo $current_page == 1 ? 'tabindex="-1"' : ''; ?>>
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                            
                            <!-- Page Numbers -->
                            <?php
                            $start_page = max(1, $current_page - 2);
                            $end_page = min($total_pages, $start_page + 4);
                            
                            if ($end_page - $start_page < 4 && $start_page > 1) {
                                $start_page = max(1, $end_page - 4);
                            }
                            
                            if ($start_page > 1): ?>
                                <li><a href="category.php?<?php echo ($category_id > 0 ? "id=$category_id&" : '') . "page=1"; ?>">1</a></li>
                                <?php if($start_page > 2): ?>
                                    <li class="dots">...</li>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php for($i = $start_page; $i <= $end_page; $i++): ?>
                                <li class="<?php echo $i == $current_page ? 'active' : ''; ?>">
                                    <a href="category.php?<?php echo ($category_id > 0 ? "id=$category_id&" : '') . "page=$i"; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($end_page < $total_pages): ?>
                                <?php if($end_page < $total_pages - 1): ?>
                                    <li class="dots">...</li>
                                <?php endif; ?>
                                <li><a href="category.php?<?php echo ($category_id > 0 ? "id=$category_id&" : '') . "page=$total_pages"; ?>">
                                    <?php echo $total_pages; ?>
                                </a></li>
                            <?php endif; ?>
                            
                            <!-- Next Button -->
                            <li class="<?php echo $current_page == $total_pages ? 'disabled' : ''; ?>">
                                <a href="category.php?<?php 
                                    echo ($category_id > 0 ? "id=$category_id&" : '') . 
                                    "page=" . ($current_page + 1); 
                                ?>" 
                                <?php echo $current_page == $total_pages ? 'tabindex="-1"' : ''; ?>>
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </section><!-- /Pagination Section -->
            <?php endif; ?>
        </div>

        <div class="col-lg-4 sidebar">
            <div class="widgets-container" data-aos="fade-up" data-aos-delay="200">
                <!-- Search Widget -->
                <div class="search-widget widget-item">
                    <h3 class="widget-title">Recherche</h3>
                    <form method="GET" action="search.php">
                        <input type="text" name="q" placeholder="Rechercher des articles...">
                        <button type="submit" title="Rechercher"><i class="bi bi-search"></i></button>
                    </form>
                </div><!--/Search Widget -->

                <!-- Categories Widget -->
                <div class="categories-widget widget-item">
                    <h3 class="widget-title">Catégories</h3>
                    <ul class="mt-3">
                        <li><a href="category.php" class="<?php echo ($category_id == 0) ? 'active' : ''; ?>">
                            Toutes les catégories
                        </a></li>
                        <?php foreach($categories as $cat): ?>
                            <li>
                                <a href="category.php?id=<?php echo $cat['id_categorie']; ?>" 
                                   class="<?php echo ($category_id == $cat['id_categorie']) ? 'active' : ''; ?>">
                                    <?php echo htmlspecialchars($cat['nom_categorie']); ?> 
                                    <span>(<?php echo $cat['nb_articles']; ?>)</span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div><!--/Categories Widget -->

                <!-- Recent Posts Widget -->
                <div class="recent-posts-widget widget-item">
                    <h3 class="widget-title">Articles récents</h3>
                    <?php foreach($recent_articles as $recent): ?>
                        <div class="post-item">
                            <?php 
                            $recent_img_number = ($recent['id_article'] % 5) + 1;
                            ?>
                            <img src="assets/img/blog/blog-post-square-<?php echo $recent_img_number; ?>.webp" 
                                 alt="<?php echo htmlspecialchars($recent['titre']); ?>" 
                                 class="flex-shrink-0">
                            <div>
                                <h4>
                                    <a href="article.php?id=<?php echo $recent['id_article']; ?>">
                                        <?php echo htmlspecialchars(substr($recent['titre'], 0, 40)); ?>
                                        <?php if(strlen($recent['titre']) > 40) echo '...'; ?>
                                    </a>
                                </h4>
                                <time datetime="<?php echo $recent['date_creation']; ?>">
                                    <?php echo date('d M, Y', strtotime($recent['date_creation'])); ?>
                                </time>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div><!--/Recent Posts Widget -->

                <!-- Tags Widget -->
                <div class="tags-widget widget-item">
                    <h3 class="widget-title">Tags populaires</h3>
                    <ul>
                        <?php 
                        $tags = ['Technologie', 'Business', 'Sports', 'Divertissement', 'Politique', 
                                'Santé', 'Éducation', 'Voyage', 'Nourriture', 'Mode de vie'];
                        shuffle($tags);
                        foreach(array_slice($tags, 0, 10) as $tag): ?>
                            <li>
                                <a href="search.php?q=<?php echo urlencode($tag); ?>">
                                    <?php echo $tag; ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div><!--/Tags Widget -->
            </div>
        </div>
    </div>
</div>

<style>
/* Style pour les cartes d'articles */
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
    color:  #f75815;
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
    color:  #f75815;
}

.blog-post-content p {
    color: #666;
    line-height: 1.6;
    margin-bottom: 20px;
    font-size: 15px;
}

.read-more {
    color: #f75815;
    text-decoration: none;
    font-weight: 500;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: all 0.3s ease;
}

.read-more:hover {
    color: #f75815;
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
    color:  #f75815;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Pagination Styles */
.pagination-list {
    display: flex;
    list-style: none;
    padding: 0;
    margin: 0;
    gap: 5px;
}

.pagination-list li a,
.pagination-list li span {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 8px;
    text-decoration: none;
    color: #333;
    font-weight: 500;
    transition: all 0.3s ease;
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
}

.pagination-list li a:hover {
    background-color: #f75815;
    color: white;
    border-color:  #f75815;
    transform: translateY(-2px);
}

.pagination-list li.active a {
    background-color: #f75815;
    color: white;
    border-color:  #f75815;
}

.pagination-list li.disabled a {
    opacity: 0.5;
    cursor: not-allowed;
    background-color: #e9ecef;
    color: #6c757d;
}

.pagination-list li.dots {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    color: #6c757d;
}

.pagination-list li a i {
    font-size: 14px;
}

/* Section Title Styles */
.section-title {
    text-align: center;
    margin-bottom: 50px;
}

.section-title h2 {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 15px;
    color: #333;
}

.section-title div {
    font-size: 18px;
    color: #666;
}

.section-title .description-title {
    color:  #f75815;
    font-weight: 600;
}

/* Responsive */
@media (max-width: 992px) {
    .blog-post-item img {
        height: 200px;
    }
}

@media (max-width: 768px) {
    .blog-post-content .post-meta {
        gap: 10px;
        font-size: 13px;
    }
    
    .blog-post-content h2 {
        font-size: 18px;
    }
    
    .section-title h2 {
        font-size: 28px;
    }
}

@media (max-width: 576px) {
    .blog-post-item {
        margin-bottom: 20px;
    }
    
    .blog-post-content {
        padding: 20px;
    }
    
    .pagination-list li a,
    .pagination-list li span {
        width: 35px;
        height: 35px;
        font-size: 14px;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>