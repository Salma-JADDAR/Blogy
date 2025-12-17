</main>

<footer id="footer" class="footer">
    <div class="container footer-top">
        <div class="row gy-4 justify-content-center">
            <div class="col-lg-4 col-md-6 footer-about text-center">
                <a href="index.php" class="logo d-flex align-items-center justify-content-center">
                    <span class="sitename"><?php echo SITE_NAME; ?></span>
                </a>
                <div class="footer-contact pt-3">
                    <p>Blog professionnel</p>
                    <p class="mt-3"><strong>Email:</strong> <span><?php echo SITE_EMAIL; ?></span></p>
                </div>
                <div class="social-links d-flex mt-4 justify-content-center">
                    <a href=""><i class="bi bi-twitter-x"></i></a>
                    <a href=""><i class="bi bi-facebook"></i></a>
                    <a href=""><i class="bi bi-instagram"></i></a>
                    <a href=""><i class="bi bi-linkedin"></i></a>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-3 footer-links text-center">
                <h4>Navigation</h4>
                <ul class="list-unstyled">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="about.php">About</a></li>
                    <li><a href="category.php">Category</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
            </div>
            
            <div class="col-lg-2 col-md-3 footer-links text-center">
                <h4>Catégories</h4>
                <ul class="list-unstyled">
                    <?php 
                    $categories = getCategories();
                    foreach(array_slice($categories, 0, 5) as $cat): ?>
                        <li><a href="category.php?id=<?php echo $cat['id_categorie']; ?>">
                            <?php echo htmlspecialchars($cat['nom_categorie']); ?>
                        </a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="container copyright text-center mt-4">
        <p>© <span>Copyright</span> <strong class="px-1 sitename"><?php echo SITE_NAME; ?></strong> 
           <span>All Rights Reserved</span></p>
        <div class="credits">
            <?php echo date('Y'); ?> - Développé avec PHP & MySQL
        </div>
    </div>
</footer>