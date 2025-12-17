<?php
/**
 * Récupère tous les articles publiés
 */
function getArticles($limit = 10, $offset = 0) {
    global $pdo;
    
    $sql = "SELECT a.*, u.nom as auteur_nom, c.nom_categorie 
            FROM Article a 
            JOIN Utilisateur u ON a.username = u.username 
            JOIN Categorie c ON a.id_categorie = c.id_categorie 
            WHERE a.status = 'published' 
            ORDER BY a.date_creation DESC 
            LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

/**
 * Récupère un article par son ID
 */
function getArticleById($id) {
    global $pdo;
    
    $sql = "SELECT a.*, u.nom as auteur_nom, c.nom_categorie 
            FROM Article a 
            JOIN Utilisateur u ON a.username = u.username 
            JOIN Categorie c ON a.id_categorie = c.id_categorie 
            WHERE a.id_article = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetch();
}

/**
 * Récupère les articles par catégorie
 */
function getArticlesByCategory($category_id, $limit = 10) {
    global $pdo;
    
    $sql = "SELECT a.*, u.nom as auteur_nom, c.nom_categorie 
            FROM Article a 
            JOIN Utilisateur u ON a.username = u.username 
            JOIN Categorie c ON a.id_categorie = c.id_categorie 
            WHERE a.id_categorie = :category_id 
            AND a.status = 'published' 
            ORDER BY a.date_creation DESC 
            LIMIT :limit";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':category_id', $category_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

/**
 * Récupère toutes les catégories
 */
/**
 * Récupère toutes les catégories
 */
function getCategories() {
    global $pdo;
    
    try {
        $sql = "SELECT c.*, 
                COALESCE(
                    (SELECT COUNT(*) 
                     FROM Article a 
                     WHERE a.id_categorie = c.id_categorie 
                     AND a.status = 'published'
                    ), 0
                ) as nb_articles 
                FROM Categorie c 
                ORDER BY c.nom_categorie";
        
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Erreur dans getCategories(): " . $e->getMessage());
        return [];
    }
}

/**
 * Récupère les commentaires d'un article
 */
function getComments($article_id) {
    global $pdo;
    
    try {
        $sql = "SELECT c.*, COALESCE(u.nom, 'Anonyme') as username_display 
                FROM Commentaire c 
                LEFT JOIN Utilisateur u ON c.username = u.username 
                WHERE c.id_article = :article_id 
                AND c.status = 'approved' 
                ORDER BY c.date_commentaire DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':article_id', $article_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Erreur récupération commentaires: " . $e->getMessage());
        return [];
    }
}

/**
 * Ajoute un commentaire et retourne les données
 */
function addComment($article_id, $data) {
    global $pdo;
    
    try {
        // Valider l'email pour les utilisateurs non connectés
        if (!isset($data['username']) && empty($data['email'])) {
            throw new Exception("Email est requis pour les utilisateurs non connectés");
        }
        
        if (!isset($data['username']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Email invalide");
        }
        
        // Valider le contenu
        if (empty(trim($data['contenu']))) {
            throw new Exception("Le commentaire ne peut pas être vide");
        }
        
        if (strlen(trim($data['contenu'])) > 1000) {
            throw new Exception("Le commentaire ne peut pas dépasser 1000 caractères");
        }
        
        // Préparer la requête SQL
        $sql = "INSERT INTO Commentaire (contenu, username, email, id_article, status) 
                VALUES (:contenu, :username, :email, :article_id, 'approved')";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':contenu', trim($data['contenu']));
        $stmt->bindValue(':username', $data['username'] ?? null);
        $stmt->bindValue(':email', trim($data['email'] ?? ''));
        $stmt->bindValue(':article_id', $article_id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            // Récupérer l'ID du commentaire inséré
            $comment_id = $pdo->lastInsertId();
            
            // Récupérer les données complètes du commentaire
            $sql = "SELECT c.*, 
                    COALESCE(u.nom, 
                    CASE 
                        WHEN c.username IS NOT NULL THEN c.username
                        ELSE SUBSTRING_INDEX(c.email, '@', 1)
                    END) as username_display 
                    FROM Commentaire c 
                    LEFT JOIN Utilisateur u ON c.username = u.username 
                    WHERE c.id_commentaire = :id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':id', $comment_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $comment = $stmt->fetch();
            
            // Formater la date pour l'affichage
            if ($comment) {
                $date = new DateTime($comment['date_commentaire']);
                $comment['date_formatted'] = $date->format('d M, Y à H:i');
            }
            
            return $comment;
        }
        
        return false;
    } catch(PDOException $e) {
        error_log("Erreur d'ajout de commentaire: " . $e->getMessage());
        throw new Exception("Erreur de base de données: " . $e->getMessage());
    } catch(Exception $e) {
        error_log("Erreur validation commentaire: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Vérifie si l'utilisateur est connecté
 */
function isLoggedIn() {
    return isset($_SESSION['user']);
}

/**
 * Vérifie si l'utilisateur est admin
 */
function isAdmin() {
    return isset($_SESSION['user']) && $_SESSION['user']['role'] == 'admin';
}

/**
 * Formatte la date
 */
function formatDate($date_string) {
    $date = new DateTime($date_string);
    return $date->format('d M, Y');
}

/**
 * Génère un slug à partir d'un titre
 */
function generateSlug($string) {
    $slug = strtolower($string);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}


/**
 * Generate pagination links
 */
function generatePagination($current_page, $total_pages, $base_url, $additional_params = '') {
    if ($total_pages <= 1) {
        return '';
    }
    
    $html = '<div class="pagination-section">';
    $html .= '<nav aria-label="Page navigation">';
    $html .= '<ul class="pagination-list">';
    
    // Previous button
    if ($current_page > 1) {
        $html .= '<li><a href="' . $base_url . '?page=' . ($current_page - 1) . $additional_params . '"><i class="bi bi-chevron-left"></i></a></li>';
    } else {
        $html .= '<li class="disabled"><span><i class="bi bi-chevron-left"></i></span></li>';
    }
    
    // Calculate page range
    $start_page = max(1, $current_page - 2);
    $end_page = min($total_pages, $start_page + 4);
    
    if ($end_page - $start_page < 4 && $start_page > 1) {
        $start_page = max(1, $end_page - 4);
    }
    
    // First page
    if ($start_page > 1) {
        $html .= '<li><a href="' . $base_url . '?page=1' . $additional_params . '">1</a></li>';
        if ($start_page > 2) {
            $html .= '<li class="dots"><span>...</span></li>';
        }
    }
    
    // Page numbers
    for ($i = $start_page; $i <= $end_page; $i++) {
        if ($i == $current_page) {
            $html .= '<li class="active"><span>' . $i . '</span></li>';
        } else {
            $html .= '<li><a href="' . $base_url . '?page=' . $i . $additional_params . '">' . $i . '</a></li>';
        }
    }
    
    // Last page
    if ($end_page < $total_pages) {
        if ($end_page < $total_pages - 1) {
            $html .= '<li class="dots"><span>...</span></li>';
        }
        $html .= '<li><a href="' . $base_url . '?page=' . $total_pages . $additional_params . '">' . $total_pages . '</a></li>';
    }
    
    // Next button
    if ($current_page < $total_pages) {
        $html .= '<li><a href="' . $base_url . '?page=' . ($current_page + 1) . $additional_params . '"><i class="bi bi-chevron-right"></i></a></li>';
    } else {
        $html .= '<li class="disabled"><span><i class="bi bi-chevron-right"></i></span></li>';
    }
    
    $html .= '</ul>';
    $html .= '</nav>';
    $html .= '</div>';
    
    return $html;
}
?>