<?php
$page_title = "Accueil";
$body_class = "index-page";
require_once 'includes/header.php';

// Récupérer les articles récents (5 articles pour la grille hero)
$hero_articles = getArticles(5);
// Récupérer les articles pour la section Featured Posts
$featured_articles = getArticles(6); // On prend 6 articles pour 3 colonnes
// Récupérer les articles pour la section Latest Posts
$latest_articles = getArticles(6);
// Récupérer toutes les catégories
$categories = getCategories();
?>

<!-- Blog Hero Section -->
<section id="blog-hero" class="blog-hero section">
    <div class="container" data-aos="fade-up" data-aos-delay="100">
        <div class="blog-grid">
            <?php if (!empty($hero_articles)): ?>
                <!-- Featured Post (Large) -->
                <article class="blog-item featured" data-aos="fade-up">
                    <img src="assets/img/blog/blog-post-3.webp" alt="Blog Image" class="img-fluid">
                    <div class="blog-content">
                        <div class="post-meta">
                            <?php if(isset($hero_articles[0])): ?>
                                <span class="date">
                                    <?php echo date('M. d, Y', strtotime($hero_articles[0]['date_creation'])); ?>
                                </span>
                                <span class="category">
                                    <?php echo htmlspecialchars($hero_articles[0]['nom_categorie']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <h2 class="post-title">
                            <?php if(isset($hero_articles[0])): ?>
                                <a href="article.php?id=<?php echo $hero_articles[0]['id_article']; ?>" 
                                   title="<?php echo htmlspecialchars($hero_articles[0]['titre']); ?>">
                                    <?php echo htmlspecialchars($hero_articles[0]['titre']); ?>
                                </a>
                            <?php endif; ?>
                        </h2>
                    </div>
                </article><!-- End Featured Post -->

                <!-- Regular Posts -->
                <?php for($i = 1; $i < min(5, count($hero_articles)); $i++): ?>
                    <article class="blog-item" data-aos="fade-up" data-aos-delay="<?php echo $i * 100; ?>">
                        <img src="assets/img/blog/blog-post-portrait-<?php echo $i; ?>.webp" alt="Blog Image" class="img-fluid">
                        <div class="blog-content">
                            <div class="post-meta">
                                <span class="date">
                                    <?php echo date('M. d, Y', strtotime($hero_articles[$i]['date_creation'])); ?>
                                </span>
                                <span class="category">
                                    <?php echo htmlspecialchars($hero_articles[$i]['nom_categorie']); ?>
                                </span>
                            </div>
                            <h3 class="post-title">
                                <a href="article.php?id=<?php echo $hero_articles[$i]['id_article']; ?>" 
                                   title="<?php echo htmlspecialchars($hero_articles[$i]['titre']); ?>">
                                    <?php echo htmlspecialchars($hero_articles[$i]['titre']); ?>
                                </a>
                            </h3>
                        </div>
                    </article>
                <?php endfor; ?>
            <?php else: ?>
                <p class="text-center">Aucun article publié pour le moment.</p>
            <?php endif; ?>
        </div>
    </div>
</section><!-- /Blog Hero Section -->

<!-- Featured Posts Section -->
<section id="featured-posts" class="featured-posts section">
    <!-- Section Title -->
    <div class="container section-title" data-aos="fade-up">
        <h2>Featured Posts</h2>
        <div><span>Check Our</span> <span class="description-title">Featured Posts</span></div>
    </div><!-- End Section Title -->

    <div class="container" data-aos="fade-up" data-aos-delay="100">
        <div class="row gy-4">
            <?php if (!empty($featured_articles)): ?>
                <?php foreach($featured_articles as $index => $article): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="blog-post-item">
                            <img src="assets/img/blog/blog-post-portrait-<?php echo ($index % 5) + 1; ?>.webp" alt="Blog Image">
                            <div class="blog-post-content">
                                <div class="post-meta">
                                    <span><i class="bi bi-person"></i> <?php echo htmlspecialchars($article['auteur_nom']); ?></span>
                                    <span><i class="bi bi-clock"></i> <?php echo date('M d, Y', strtotime($article['date_creation'])); ?></span>
                                    <span><i class="bi bi-chat-dots"></i> <?php echo $article['view_count']; ?> views</span>
                                </div>
                                <h2>
                                    <a href="article.php?id=<?php echo $article['id_article']; ?>">
                                        <?php echo htmlspecialchars($article['titre']); ?>
                                    </a>
                                </h2>
                                <p>
                                    <?php 
                                    // Prendre les premiers 150 caractères du contenu
                                    $contenu = strip_tags($article['contenu']);
                                    if (strlen($contenu) > 150) {
                                        echo substr($contenu, 0, 150) . '...';
                                    } else {
                                        echo $contenu;
                                    }
                                    ?>
                                </p>
                                <a href="article.php?id=<?php echo $article['id_article']; ?>" class="read-more">
                                    Read More <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div><!-- End item -->
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <p>Aucun article en vedette pour le moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section><!-- /Featured Posts Section -->
<!-- Category Section Section -->
<section id="category-section" class="category-section section">
    <!-- Section Title -->
    <div class="container section-title" data-aos="fade-up">
        <h2>Category Section</h2>
        <div> <span class="description-title">Category Section</span></div>
    </div><!-- End Section Title -->

    <div class="container" data-aos="fade-up" data-aos-delay="100">
        <!-- Featured Posts -->
        <div class="row gy-4 mb-4">
            <?php foreach($categories as $index => $category): ?>
                <?php if($index < 3): ?>
                    <div class="col-lg-4">
                        <article class="featured-post">
                            <div class="post-img">
                                <img src="assets/img/blog/blog-post-<?php echo $index + 5; ?>.webp" alt="" class="img-fluid" loading="lazy">
                            </div>
                            <div class="post-content">
                                <div class="category-meta">
                                    <span class="post-category"><?php echo htmlspecialchars($category['nom_categorie']); ?></span>
                                    <div class="author-meta">
                                       
                                        <span class="post-date"><?php echo date('d M Y'); ?></span>
                                    </div>
                                </div>
                                <h2 class="title">
                                    <a href="category.php?id=<?php echo $category['id_categorie']; ?>">
                                        Découvrez la catégorie <?php echo htmlspecialchars($category['nom_categorie']); ?>
                                    </a>
                                </h2>
                                <p class="mt-2"><?php echo htmlspecialchars(substr($category['description'] ?? 'Explorez nos articles dans cette catégorie', 0, 100)); ?>...</p>
                            </div>
                        </article>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <!-- List Posts -->
        <div class="row">
            <?php foreach($categories as $index => $category): ?>
                <?php if($index < 6): ?>
                    <div class="col-xl-4 col-lg-6">
                        <article class="list-post">
                            <div class="post-img">
                                <img src="assets/img/blog/blog-post-<?php echo ($index % 6) + 1; ?>.webp" alt="" class="img-fluid" loading="lazy">
                            </div>
                            <div class="post-content">
                                <div class="category-meta">
                                    <span class="post-category"><?php echo htmlspecialchars($category['nom_categorie']); ?></span>
                                </div>
                                <h3 class="title">
                                    <a href="category.php?id=<?php echo $category['id_categorie']; ?>">
                                        Catégorie: <?php echo htmlspecialchars($category['nom_categorie']); ?>
                                    </a>
                                </h3>
                                <div class="post-meta">
                                    <span class="read-time"><?php echo $category['nb_articles']; ?> article<?php echo $category['nb_articles'] > 1 ? 's' : ''; ?></span>
                                    <span class="post-date"><?php echo date('d M Y'); ?></span>
                                </div>
                            </div>
                        </article>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section><!-- /Category Section Section -->

<!-- Latest Posts Section -->
<section id="latest-posts" class="latest-posts section">
    <!-- Section Title -->
    <div class="container section-title" data-aos="fade-up">
        <h2>Latest Posts</h2>
        <div><span>Check Our</span> <span class="description-title">Latest Posts</span></div>
    </div><!-- End Section Title -->

    <div class="container" data-aos="fade-up" data-aos-delay="100">
        <div class="row gy-4">
            <?php foreach($latest_articles as $index => $article): ?>
                <div class="col-lg-4">
                    <article>
                        <div class="post-img">
                            <img src="assets/img/blog/blog-post-<?php echo ($index % 6) + 1; ?>.webp" alt="" class="img-fluid">
                        </div>
                        <p class="post-category"><?php echo htmlspecialchars($article['nom_categorie']); ?></p>
                        <h2 class="title">
                            <a href="article.php?id=<?php echo $article['id_article']; ?>">
                                <?php echo htmlspecialchars($article['titre']); ?>
                            </a>
                        </h2>
                        <div class="d-flex align-items-center">
                            <img src="assets/img/person/person-f-12.webp" alt="" class="img-fluid post-author-img flex-shrink-0">
                            <div class="post-meta">
                                <p class="post-author"><?php echo htmlspecialchars($article['auteur_nom']); ?></p>
                                <p class="post-date">
                                    <time datetime="<?php echo $article['date_creation']; ?>">
                                        <?php echo date('M d, Y', strtotime($article['date_creation'])); ?>
                                    </time>
                                </p>
                            </div>
                        </div>
                    </article>
                </div><!-- End post list item -->
            <?php endforeach; ?>
        </div>
    </div>
</section><!-- /Latest Posts Section -->

<?php require_once 'includes/footer.php'; ?>