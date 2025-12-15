    <!-- Footer -->
    <footer class="bg-dark text-white mt-5">
        <div class="container py-5">
            <div class="row">
                <div class="col-md-4">
                    <h5><?= SITE_NAME ?></h5>
                    <p>Plateforme de blogging et de partage de connaissances.</p>
                </div>
                <div class="col-md-4">
                    <h5>Liens rapides</h5>
                    <ul class="list-unstyled">
                        <li><a href="../core/index.php" class="text-white-50 text-decoration-none">Accueil</a></li>
                        <li><a href="../public/search.php" class="text-white-50 text-decoration-none">Recherche</a></li>
                        <li><a href="../core/login.php" class="text-white-50 text-decoration-none">Connexion</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact</h5>
                    <p><i class="bi bi-envelope me-2"></i> <?= ADMIN_EMAIL ?></p>
                    <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. Tous droits réservés.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
    
    <!-- Scripts additionnels -->
    <?php if (isset($additionalScripts)) echo $additionalScripts; ?>
    
</body>
</html>