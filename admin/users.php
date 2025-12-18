<?php
require_once '../includes/init.php';

// Vérifier que l'utilisateur est admin
requireRole('admin');

$page_title = "Gestion des utilisateurs";
$body_class = "admin-users";
$message = '';
$message_type = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $username = $_POST['username'] ?? '';
    
    try {
        switch ($action) {
            case 'edit':
                $nom = trim($_POST['nom'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $role = $_POST['role'] ?? 'subscriber';
                $etat = $_POST['etat'] ?? 'actif';
                
                if (empty($nom) || empty($email)) {
                    $message = "Tous les champs sont requis";
                    $message_type = "danger";
                } else {
                    $sql = "UPDATE Utilisateur SET nom = :nom, email = :email, role = :role, etat = :etat 
                            WHERE username = :username";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':nom' => $nom,
                        ':email' => $email,
                        ':role' => $role,
                        ':etat' => $etat,
                        ':username' => $username
                    ]);
                    
                    $message = "Utilisateur mis à jour avec succès";
                    $message_type = "success";
                }
                break;
                
            case 'delete':
                if ($username === $_SESSION['user']['username']) {
                    $message = "Vous ne pouvez pas supprimer votre propre compte";
                    $message_type = "danger";
                } else {
                    $sql = "DELETE FROM Utilisateur WHERE username = :username";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([':username' => $username]);
                    
                    $message = "Utilisateur supprimé avec succès";
                    $message_type = "success";
                }
                break;
        }
    } catch(PDOException $e) {
        $message = "Erreur: " . $e->getMessage();
        $message_type = "danger";
    }
}

// Récupérer tous les utilisateurs
$users = getAllUsers();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <?php require_once '../includes/header.php'; ?>
    <style>
        .user-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            border-left: 4px solid #667eea;
        }
        
        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
            margin-right: 15px;
        }
        
        .user-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .user-info {
            flex-grow: 1;
        }
        
        .user-actions {
            display: flex;
            gap: 10px;
        }
        
        .user-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .meta-item {
            font-size: 14px;
            color: #6c757d;
        }
        
        .meta-item i {
            margin-right: 5px;
            width: 16px;
        }
        
        .role-badge {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .role-admin { background: #667eea; color: white; }
        .role-editor { background: #f093fb; color: white; }
        .role-auteur { background: #43e97b; color: white; }
        .role-subscriber { background: #4facfe; color: white; }
        
        .state-active { color: #28a745; }
        .state-inactive { color: #dc3545; }
    </style>
</head>
<body class="<?php echo $body_class; ?>">
    <?php require_once '../includes/header.php'; ?>
    
    <div class="main">
        <div class="container py-5">
            <!-- Page Header -->
            <div class="mb-5">
                <h1 class="mb-3">Gestion des utilisateurs</h1>
                <p class="text-muted mb-0">Gérez les comptes et permissions des utilisateurs</p>
            </div>
            
            <!-- Message d'alerte -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Liste des utilisateurs -->
            <?php if (!empty($users)): ?>
                <div class="users-list">
                    <?php foreach($users as $user): ?>
                        <div class="user-card">
                            <div class="user-header">
                                <div class="user-avatar">
                                    <?php echo strtoupper(substr($user['nom'], 0, 1)); ?>
                                </div>
                                
                                <div class="user-info">
                                    <div class="d-flex align-items-center mb-1">
                                        <h5 class="mb-0"><?php echo htmlspecialchars($user['nom']); ?></h5>
                                        <span class="role-badge role-<?php echo $user['role']; ?> ms-2">
                                            <?php echo $user['role']; ?>
                                        </span>
                                        <span class="ms-2 <?php echo $user['etat'] == 'actif' ? 'state-active' : 'state-inactive'; ?>">
                                            <i class="bi bi-circle-fill"></i>
                                            <?php echo $user['etat']; ?>
                                        </span>
                                    </div>
                                    <p class="text-muted mb-0">
                                        @<?php echo htmlspecialchars($user['username']); ?> • 
                                        <?php echo htmlspecialchars($user['email']); ?>
                                    </p>
                                </div>
                                
                                <div class="user-actions">
                                    <button class="btn btn-sm btn-outline-primary edit-user"
                                            data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                            data-nom="<?php echo htmlspecialchars($user['nom']); ?>"
                                            data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                            data-role="<?php echo htmlspecialchars($user['role']); ?>"
                                            data-etat="<?php echo htmlspecialchars($user['etat']); ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    
                                    <?php if($user['username'] !== $_SESSION['user']['username']): ?>
                                        <button class="btn btn-sm btn-outline-danger delete-user"
                                                data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                                data-nom="<?php echo htmlspecialchars($user['nom']); ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="user-meta">
                                <div class="meta-item">
                                    <i class="bi bi-calendar3"></i>
                                    Inscrit le <?php echo date('d/m/Y', strtotime($user['date_creation'])); ?>
                                </div>
                                
                                <!-- Compter les articles de l'utilisateur -->
                                <?php
                                try {
                                    $sql = "SELECT COUNT(*) as count FROM Article WHERE username = :username";
                                    $stmt = $pdo->prepare($sql);
                                    $stmt->execute([':username' => $user['username']]);
                                    $article_count = $stmt->fetch()['count'];
                                } catch(PDOException $e) {
                                    $article_count = 0;
                                }
                                ?>
                                
                                <div class="meta-item">
                                    <i class="bi bi-file-earmark-text"></i>
                                    <?php echo $article_count; ?> article(s)
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state text-center py-5">
                    <i class="bi bi-people" style="font-size: 50px; color: #dee2e6;"></i>
                    <h4 class="mt-3">Aucun utilisateur</h4>
                    <p class="text-muted">Aucun utilisateur inscrit sur le site</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal Édition -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier l'utilisateur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="username" id="edit_username">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_nom" class="form-label">Nom complet *</label>
                            <input type="text" class="form-control" id="edit_nom" name="nom" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_role" class="form-label">Rôle</label>
                                    <select class="form-select" id="edit_role" name="role">
                                        <option value="subscriber">Subscriber</option>
                                        <option value="auteur">Auteur</option>
                                        <option value="editor">Éditeur</option>
                                        <option value="admin">Administrateur</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_etat" class="form-label">État</label>
                                    <select class="form-select" id="edit_etat" name="etat">
                                        <option value="actif">Actif</option>
                                        <option value="inactif">Inactif</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" name="action" value="edit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Suppression -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmer la suppression</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="username" id="delete_username">
                    <div class="modal-body">
                        <p>Êtes-vous sûr de vouloir supprimer l'utilisateur <strong id="delete_nom"></strong> ?</p>
                        <p class="text-danger"><small>Cette action est irréversible !</small></p>
                        <p class="text-warning"><small>Tous les articles et commentaires de cet utilisateur seront également supprimés.</small></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" name="action" value="delete" class="btn btn-danger">Supprimer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php require_once '../includes/footer.php'; ?>
    
    <script>
        // Gérer l'édition des utilisateurs
        document.querySelectorAll('.edit-user').forEach(button => {
            button.addEventListener('click', function() {
                const username = this.getAttribute('data-username');
                const nom = this.getAttribute('data-nom');
                const email = this.getAttribute('data-email');
                const role = this.getAttribute('data-role');
                const etat = this.getAttribute('data-etat');
                
                document.getElementById('edit_username').value = username;
                document.getElementById('edit_nom').value = nom;
                document.getElementById('edit_email').value = email;
                document.getElementById('edit_role').value = role;
                document.getElementById('edit_etat').value = etat;
                
                const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
                modal.show();
            });
        });
        
        // Gérer la suppression des utilisateurs
        document.querySelectorAll('.delete-user').forEach(button => {
            button.addEventListener('click', function() {
                const username = this.getAttribute('data-username');
                const nom = this.getAttribute('data-nom');
                
                document.getElementById('delete_username').value = username;
                document.getElementById('delete_nom').textContent = nom;
                
                const modal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
                modal.show();
            });
        });
    </script>
</body>
</html>