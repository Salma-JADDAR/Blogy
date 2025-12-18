<?php
/**
 * Fonctions pour les articles
 */
function getArticles($limit = 10, $offset = 0) {
    global $pdo;
    
    try {
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
    } catch(PDOException $e) {
        error_log("Erreur dans getArticles(): " . $e->getMessage());
        return [];
    }
}

function getArticleById($id) {
    global $pdo;
    
    try {
        $sql = "SELECT a.*, u.nom as auteur_nom, c.nom_categorie 
                FROM Article a 
                JOIN Utilisateur u ON a.username = u.username 
                JOIN Categorie c ON a.id_categorie = c.id_categorie 
                WHERE a.id_article = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    } catch(PDOException $e) {
        error_log("Erreur getArticleById: " . $e->getMessage());
        return null;
    }
}

function getArticlesByCategory($category_id, $limit = 10) {
    global $pdo;
    
    try {
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
    } catch(PDOException $e) {
        error_log("Erreur getArticlesByCategory: " . $e->getMessage());
        return [];
    }
}

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

function getCategoryById($id) {
    global $pdo;
    
    try {
        $sql = "SELECT * FROM Categorie WHERE id_categorie = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    } catch(PDOException $e) {
        error_log("Erreur getCategoryById: " . $e->getMessage());
        return null;
    }
}

/**
 * Fonctions pour les commentaires
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

function addComment($article_id, $data) {
    global $pdo;
    
    try {
        // Validation
        if (empty(trim($data['contenu']))) {
            throw new Exception("Le commentaire ne peut pas être vide");
        }
        
        if (strlen(trim($data['contenu'])) > 1000) {
            throw new Exception("Le commentaire est trop long (max 1000 caractères)");
        }
        
        // Pour les utilisateurs non connectés, valider l'email
        if (!isset($data['username']) && empty($data['email'])) {
            throw new Exception("Email est requis pour les utilisateurs non connectés");
        }
        
        if (!isset($data['username']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Email invalide");
        }
        
        // Statut : 'approved' pour les connectés, 'pending' pour les autres
        $status = isset($data['username']) ? 'approved' : 'pending';
        
        $sql = "INSERT INTO Commentaire (contenu, username, email, id_article, status) 
                VALUES (:contenu, :username, :email, :article_id, :status)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':contenu', trim($data['contenu']));
        $stmt->bindValue(':username', $data['username'] ?? null);
        $stmt->bindValue(':email', trim($data['email'] ?? ''));
        $stmt->bindValue(':article_id', $article_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', $status);
        
        if ($stmt->execute()) {
            $comment_id = $pdo->lastInsertId();
            
            // Récupérer le commentaire avec les infos formatées
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
            
            if ($comment) {
                $date = new DateTime($comment['date_commentaire']);
                $comment['date_formatted'] = $date->format('d M, Y à H:i');
            }
            
            return $comment;
        }
        
        return false;
    } catch(Exception $e) {
        error_log("Erreur d'ajout de commentaire: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Fonctions pour les utilisateurs
 */
function getAllUsers() {
    global $pdo;
    
    try {
        $sql = "SELECT * FROM Utilisateur ORDER BY date_creation DESC";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Erreur getAllUsers: " . $e->getMessage());
        return [];
    }
}

function getUserByUsername($username) {
    global $pdo;
    
    try {
        $sql = "SELECT * FROM Utilisateur WHERE username = :username";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':username' => $username]);
        return $stmt->fetch();
    } catch(PDOException $e) {
        error_log("Erreur getUserByUsername: " . $e->getMessage());
        return null;
    }
}

function updateUser($username, $data) {
    global $pdo;
    
    try {
        $sql = "UPDATE Utilisateur SET nom = :nom, email = :email, role = :role, etat = :etat 
                WHERE username = :username";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nom' => $data['nom'],
            ':email' => $data['email'],
            ':role' => $data['role'],
            ':etat' => $data['etat'],
            ':username' => $username
        ]);
        
        return true;
    } catch(PDOException $e) {
        error_log("Erreur updateUser: " . $e->getMessage());
        return false;
    }
}

function deleteUser($username) {
    global $pdo;
    
    try {
        // D'abord, supprimer les articles de l'utilisateur
        $sql = "DELETE FROM Article WHERE username = :username";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':username' => $username]);
        
        // Puis supprimer l'utilisateur
        $sql = "DELETE FROM Utilisateur WHERE username = :username";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':username' => $username]);
        
        return true;
    } catch(PDOException $e) {
        error_log("Erreur deleteUser: " . $e->getMessage());
        return false;
    }
}

/**
 * Fonction getStats() CRITIQUE pour le dashboard
 */
function getStats() {
    global $pdo;
    
    $stats = [
        'total_articles' => 0,
        'total_users' => 0,
        'total_comments' => 0,
        'total_categories' => 0,
        'pending_comments' => 0,
        'published_articles' => 0,
        'draft_articles' => 0,
        'popular_articles' => []
    ];
    
    try {
        // 1. Nombre total d'articles
        $sql = "SELECT COUNT(*) as count FROM Article";
        $stmt = $pdo->query($sql);
        $stats['total_articles'] = $stmt->fetch()['count'];
        
        // 2. Articles publiés
        $sql = "SELECT COUNT(*) as count FROM Article WHERE status = 'published'";
        $stmt = $pdo->query($sql);
        $stats['published_articles'] = $stmt->fetch()['count'];
        
        // 3. Articles brouillons
        $sql = "SELECT COUNT(*) as count FROM Article WHERE status = 'draft'";
        $stmt = $pdo->query($sql);
        $stats['draft_articles'] = $stmt->fetch()['count'];
        
        // 4. Nombre total d'utilisateurs
        $sql = "SELECT COUNT(*) as count FROM Utilisateur";
        $stmt = $pdo->query($sql);
        $stats['total_users'] = $stmt->fetch()['count'];
        
        // 5. Nombre total de commentaires
        $sql = "SELECT COUNT(*) as count FROM Commentaire";
        $stmt = $pdo->query($sql);
        $stats['total_comments'] = $stmt->fetch()['count'];
        
        // 6. Commentaires en attente
        $sql = "SELECT COUNT(*) as count FROM Commentaire WHERE status = 'pending'";
        $stmt = $pdo->query($sql);
        $stats['pending_comments'] = $stmt->fetch()['count'];
        
        // 7. Nombre de catégories
        $sql = "SELECT COUNT(*) as count FROM Categorie";
        $stmt = $pdo->query($sql);
        $stats['total_categories'] = $stmt->fetch()['count'];
        
        // 8. Articles populaires (plus de vues)
        $sql = "SELECT a.*, u.nom as auteur_nom 
                FROM Article a 
                JOIN Utilisateur u ON a.username = u.username 
                WHERE a.status = 'published' 
                ORDER BY a.view_count DESC 
                LIMIT 5";
        $stmt = $pdo->query($sql);
        $stats['popular_articles'] = $stmt->fetchAll();
        
    } catch(PDOException $e) {
        error_log("Erreur dans getStats(): " . $e->getMessage());
    }
    
    return $stats;
}

/**
 * Fonctions pour les auteurs
 */
function getAuthorArticles($username, $status = null) {
    global $pdo;
    
    try {
        $sql = "SELECT a.*, c.nom_categorie 
                FROM Article a 
                JOIN Categorie c ON a.id_categorie = c.id_categorie 
                WHERE a.username = :username";
        
        if ($status) {
            $sql .= " AND a.status = :status";
        }
        
        $sql .= " ORDER BY a.date_creation DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':username', $username);
        
        if ($status) {
            $stmt->bindValue(':status', $status);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Erreur getAuthorArticles: " . $e->getMessage());
        return [];
    }
}

function getAuthorStats($username) {
    global $pdo;
    
    $stats = [
        'total' => 0,
        'published' => 0,
        'draft' => 0,
        'archived' => 0,
        'total_views' => 0,
        'avg_views' => 0
    ];
    
    try {
        // Nombre total d'articles
        $sql = "SELECT COUNT(*) as count FROM Article WHERE username = :username";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':username' => $username]);
        $stats['total'] = $stmt->fetch()['count'];
        
        // Articles par statut
        $sql = "SELECT status, COUNT(*) as count FROM Article 
                WHERE username = :username 
                GROUP BY status";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':username' => $username]);
        
        while($row = $stmt->fetch()) {
            if (isset($stats[$row['status']])) {
                $stats[$row['status']] = $row['count'];
            }
        }
        
        // Vues totales et moyennes
        $sql = "SELECT SUM(view_count) as total, AVG(view_count) as average 
                FROM Article 
                WHERE username = :username AND status = 'published'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':username' => $username]);
        $views = $stmt->fetch();
        
        $stats['total_views'] = $views['total'] ?? 0;
        $stats['avg_views'] = round($views['average'] ?? 0);
        
    } catch(PDOException $e) {
        error_log("Erreur getAuthorStats: " . $e->getMessage());
    }
    
    return $stats;
}

/**
 * Fonctions utilitaires
 */
function formatDate($date_string) {
    if (empty($date_string)) {
        return 'Date inconnue';
    }
    
    try {
        $date = new DateTime($date_string);
        $now = new DateTime();
        $diff = $now->diff($date);
        
        // Si moins d'une semaine, afficher "Il y a X jours/heures"
        if ($diff->days == 0) {
            if ($diff->h == 0) {
                if ($diff->i < 1) return 'À l\'instant';
                return 'Il y a ' . $diff->i . ' min';
            }
            return 'Il y a ' . $diff->h . ' h';
        } elseif ($diff->days == 1) {
            return 'Hier';
        } elseif ($diff->days < 7) {
            return 'Il y a ' . $diff->days . ' jours';
        } else {
            return $date->format('d M, Y');
        }
    } catch(Exception $e) {
        return $date_string;
    }
}

function escapeHtml($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function truncateText($text, $length = 100) {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}

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
    
    // Page numbers
    $start_page = max(1, $current_page - 2);
    $end_page = min($total_pages, $start_page + 4);
    
    if ($end_page - $start_page < 4 && $start_page > 1) {
        $start_page = max(1, $end_page - 4);
    }
    
    if ($start_page > 1) {
        $html .= '<li><a href="' . $base_url . '?page=1' . $additional_params . '">1</a></li>';
        if ($start_page > 2) {
            $html .= '<li class="dots"><span>...</span></li>';
        }
    }
    
    for ($i = $start_page; $i <= $end_page; $i++) {
        if ($i == $current_page) {
            $html .= '<li class="active"><span>' . $i . '</span></li>';
        } else {
            $html .= '<li><a href="' . $base_url . '?page=' . $i . $additional_params . '">' . $i . '</a></li>';
        }
    }
    
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

/**
 * Fonctions de validation
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function isValidUsername($username) {
    return preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username);
}

function sanitizeInput($input) {
    return trim(strip_tags($input));
}

/**
 * Fonctions pour les permissions (complémentaires à auth.php)
 */



?>