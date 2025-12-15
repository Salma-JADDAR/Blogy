<?php
// Fonctions utilitaires

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
        return ['success' => false, 'message' => 'Type de fichier non autorisé. Formats acceptés: JPG, PNG, GIF, WEBP'];
    }
    
    // Vérifier la taille
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['success' => false, 'message' => 'Le fichier est trop volumineux (max 5MB)'];
    }
    
    // Générer un nom unique
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $fileName = uniqid() . '_' . date('Ymd_His') . '.' . $extension;
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
 * Nettoyer les données
 */
function sanitize($data) {
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
function formatDate($dateString, $format = 'd/m/Y à H:i') {
    $timestamp = strtotime($dateString);
    $formats = [
        'd/m/Y' => date('d/m/Y', $timestamp),
        'd/m/Y H:i' => date('d/m/Y H:i', $timestamp),
        'd/m/Y à H:i' => date('d/m/Y', $timestamp) . ' à ' . date('H:i', $timestamp),
        'relative' => timeAgo($timestamp)
    ];
    
    return $formats[$format] ?? date($format, $timestamp);
}

/**
 * Temps relatif (il y a...)
 */
function timeAgo($timestamp) {
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'il y a ' . $diff . ' sec';
    } elseif ($diff < 3600) {
        return 'il y a ' . floor($diff / 60) . ' min';
    } elseif ($diff < 86400) {
        return 'il y a ' . floor($diff / 3600) . ' h';
    } elseif ($diff < 2592000) {
        return 'il y a ' . floor($diff / 86400) . ' jours';
    } elseif ($diff < 31536000) {
        return 'il y a ' . floor($diff / 2592000) . ' mois';
    } else {
        return 'il y a ' . floor($diff / 31536000) . ' ans';
    }
}

/**
 * Récupérer les catégories
 */
function getCategories($pdo) {
    $stmt = $pdo->query("SELECT * FROM Categorie ORDER BY nom_categorie");
    return $stmt->fetchAll();
}

/**
 * Récupérer une catégorie
 */
function getCategory($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM Categorie WHERE id_categorie = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Récupérer les articles populaires
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
 * Incrémenter les vues
 */
function incrementViews($pdo, $articleId) {
    $stmt = $pdo->prepare("UPDATE Article SET view_count = view_count + 1 WHERE id_article = ?");
    return $stmt->execute([$articleId]);
}

/**
 * Pagination
 */
function getPagination($pdo, $query, $params = [], $perPage = ITEMS_PER_PAGE) {
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $offset = ($page - 1) * $perPage;
    
    // Compter le total
    $countQuery = "SELECT COUNT(*) as total FROM ($query) as count_table";
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];
    $totalPages = ceil($total / $perPage);
    
    // Récupérer les données avec LIMIT
    $dataQuery = $query . " LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($dataQuery);
    $stmt->execute(array_merge($params, [$perPage, $offset]));
    $data = $stmt->fetchAll();
    
    return [
        'data' => $data,
        'page' => $page,
        'totalPages' => $totalPages,
        'total' => $total,
        'perPage' => $perPage
    ];
}

/**
 * Vérifier les permissions
 */
function canEditArticle($articleAuthor, $userRole, $username) {
    if ($userRole === 'admin' || $userRole === 'editor') {
        return true;
    }
    return $articleAuthor === $username;
}
?>