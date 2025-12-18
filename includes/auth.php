<?php
/**
 * Vérifie si l'utilisateur est connecté
 * Redirige vers la page de login si non connecté
 */
function requireLogin() {
    if (!isset($_SESSION['user'])) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: login.php');
        exit();
    }
}

/**
 * Vérifie si l'utilisateur a un rôle spécifique
 */
function hasRole($role) {
    return isset($_SESSION['user']) && $_SESSION['user']['role'] == $role;
}

/**
 * Vérifie si l'utilisateur a au moins un des rôles spécifiés
 */
function hasAnyRole($roles) {
    if (!isset($_SESSION['user'])) {
        return false;
    }
    
    return in_array($_SESSION['user']['role'], $roles);
}

/**
 * Vérifie si l'utilisateur est admin
 */
function isAdmin() {
    return hasRole('admin');
}

/**
 * Vérifie si l'utilisateur est éditeur
 */
function isEditor() {
    return hasRole('editor');
}

/**
 * Vérifie si l'utilisateur est auteur
 */
function isAuthor() {
    return hasRole('auteur');
}

/**
 * Vérifie si l'utilisateur est subscriber
 */
function isSubscriber() {
    return hasRole('subscriber');
}

/**
 * Redirige l'utilisateur si il n'a pas le bon rôle
 */
function requireRole($role) {
    requireLogin();
    
    if (!hasRole($role)) {
        $_SESSION['error'] = "Accès refusé. Permission insuffisante.";
        header('Location: index.php');
        exit();
    }
}

/**
 * Redirige l'utilisateur si il n'a aucun des rôles spécifiés
 */
function requireAnyRole($roles) {
    requireLogin();
    
    if (!hasAnyRole($roles)) {
        $_SESSION['error'] = "Accès refusé. Permission insuffisante.";
        header('Location: index.php');
        exit();
    }
}

/**
 * Vérifie si l'utilisateur peut modifier un article
 */
function canEditArticle($article_username = null) {
    if (!isset($_SESSION['user'])) {
        return false;
    }
    
    $user = $_SESSION['user'];
    
    // Les admins et éditeurs peuvent tout modifier
    if (hasAnyRole(['admin', 'editor'])) {
        return true;
    }
    
    // Les auteurs ne peuvent modifier que leurs propres articles
    if (isAuthor() && $article_username && $article_username === $user['username']) {
        return true;
    }
    
    return false;
}

/**
 * Vérifie si l'utilisateur peut supprimer un article
 */
function canDeleteArticle($article_username = null) {
    if (!isset($_SESSION['user'])) {
        return false;
    }
    
    // Seuls les admins peuvent supprimer des articles
    if (isAdmin()) {
        return true;
    }
    
    // Les éditeurs peuvent supprimer leurs propres articles
    if (isEditor() && $article_username && $article_username === $_SESSION['user']['username']) {
        return true;
    }
    
    // Les auteurs peuvent supprimer leurs propres articles
    if (isAuthor() && $article_username && $article_username === $_SESSION['user']['username']) {
        return true;
    }
    
    return false;
}

/**
 * Vérifie si l'utilisateur peut modérer les commentaires
 */
function canModerateComments() {
    return hasAnyRole(['admin', 'editor']);
}

/**
 * Vérifie si l'utilisateur peut gérer les utilisateurs
 */
function canManageUsers() {
    return isAdmin();
}

/**
 * Vérifie si l'utilisateur peut gérer les catégories
 */
function canManageCategories() {
    return hasAnyRole(['admin', 'editor']);
}

/**
 * Vérifie si l'utilisateur peut créer des articles
 */
function canCreateArticle() {
    return hasAnyRole(['admin', 'editor', 'auteur']);
}

/**
 * Vérifie si l'utilisateur peut commenter
 */
function canComment() {
    // Tout le monde peut commenter (visiteurs et utilisateurs)
    return true;
}
?>