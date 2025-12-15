<?php
// Configuration générale du site
require_once 'database.php';

// Constantes
define('SITE_TITLE', 'BlogCMS - Votre Blog Personnel');
define('SITE_DESCRIPTION', 'Système de gestion de contenu pour blog');
define('SITE_KEYWORDS', 'blog, cms, articles, publication');
define('ADMIN_EMAIL', 'admin@blogcms.com');
define('ITEMS_PER_PAGE', 6);
define('MAX_UPLOAD_SIZE', 5242880); // 5MB

// États possibles
define('ARTICLE_STATUS', ['draft', 'published', 'archived']);
define('COMMENT_STATUS', ['pending', 'approved', 'rejected']);
define('USER_ROLES', ['admin', 'editor', 'auteur', 'subscriber']);
define('USER_STATES', ['actif', 'inactif']);

// Initialisation des messages
if (!isset($_SESSION['messages'])) {
    $_SESSION['messages'] = [];
}

// Fonction pour ajouter un message flash
function addFlashMessage($type, $message) {
    $_SESSION['messages'][] = ['type' => $type, 'message' => $message];
}

// Fonction pour afficher les messages flash
function displayFlashMessages() {
    if (isset($_SESSION['messages']) && !empty($_SESSION['messages'])) {
        foreach ($_SESSION['messages'] as $msg) {
            echo '<div class="alert alert-' . $msg['type'] . ' alert-dismissible fade show" role="alert">
                    ' . $msg['message'] . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                  </div>';
        }
        // Vider les messages après affichage
        $_SESSION['messages'] = [];
    }
}
?>