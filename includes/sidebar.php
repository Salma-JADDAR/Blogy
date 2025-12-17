<?php
// Récupérer les catégories
$categories = getCategories();

// Récupérer les articles récents
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

<!-- Search Widget -->
<div class="search-widget widget-item">
    <h3 class="widget-title">Recherche</h3>
    <form method="GET" action="search.php">
        <input type="text" name="q" placeholder="Rechercher...">
        <button type="submit" title="Rechercher"><i class="bi bi-search"></i></button>
    </form>
</div>

<!-- Categories Widget -->
<div class="categories-widget widget-item">
    <h3 class="widget-title">Catégories</h3>
    <ul class="mt-3">
        <?php foreach($categories as $cat): ?>
            <li>
                <a href="category.php?id=<?php echo $cat['id_categorie']; ?>">
                    <?php echo htmlspecialchars($cat['nom_categorie']); ?> 
                    <span>(<?php echo $cat['nb_articles']; ?>)</span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

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
</div>

<!-- Tags Widget -->
<div class="tags-widget widget-item">
    <h3 class="widget-title">Tags</h3>
    <ul>
        <?php 
        $tags = ['PHP', 'MySQL', 'Web', 'Design', 'Bootstrap', 
                'JavaScript', 'CSS', 'HTML', 'Blog', 'Tutoriel'];
        shuffle($tags);
        foreach(array_slice($tags, 0, 10) as $tag): ?>
            <li><a href="search.php?q=<?php echo urlencode($tag); ?>"><?php echo $tag; ?></a></li>
        <?php endforeach; ?>
    </ul>
</div>