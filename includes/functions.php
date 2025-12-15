<?php
// Fonctions utilitaires pour le projet

/**
 * Upload une image
 */
function uploadImage($file, $targetDir = UPLOAD_DIR) {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Erreur lors du téléchargement'];
    }
    
    // Vérifier le type MIME
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Type de fichier non autorisé'];
    }
    
    // Vérifier la taille (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'message' => 'Le fichier est trop volumineux (max 5MB)'];
    }
    
    // Générer un nom unique
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = uniqid() . '_' . date('Ymd') . '.' . $extension;
    $targetPath = $targetDir . $fileName;
    
    // Créer le dossier s'il n'existe pas
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    // Déplacer le fichier
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'filename' => $fileName];
    }
    
    return ['success' => false, 'message' => 'Erreur lors du déplacement du fichier'];
}

/**
 * Nettoyer les données d'entrée
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Générer un slug
 */
function generateSlug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    $string = trim($string, '-');
    return $string;
}

/**
 * Formater la date
 */
function formatDate($dateString, $format = 'd/m/Y') {
    return date($format, strtotime($dateString));
}

/**
 * Obtenir les catégories
 */
function getCategories($pdo) {
    $stmt = $pdo->query("SELECT * FROM Categorie ORDER BY nom_categorie");
    return $stmt->fetchAll();
}

/**
 * Obtenir les articles populaires
 */
function getPopularArticles($pdo, $limit = 5) {
    $stmt = $pdo->prepare("
        SELECT a.*, u.nom, c.nom_categorie 
        FROM Article a 
        LEFT JOIN Utilisateur u ON a.username = u.username 
        LEFT JOIN Categorie c ON a.id_categorie = c.id_categorie 
        WHERE a.status = 'published' 
        ORDER BY a.view_count DESC 
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

/**
 * Incrémenter le compteur de vues
 */
function incrementViewCount($pdo, $articleId) {
    $stmt = $pdo->prepare("
        UPDATE Article SET view_count = view_count + 1 
        WHERE id_article = ?
    ");
    return $stmt->execute([$articleId]);
}

/**
 * Vérifier si l'utilisateur peut éditer un article
 */
function canEditArticle($articleAuthor, $userRole, $username) {
    if ($userRole === 'admin' || $userRole === 'editor') {
        return true;
    }
    return $articleAuthor === $username;
}
?>