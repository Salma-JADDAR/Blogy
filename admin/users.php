<?php
$pageTitle = "Gestion des utilisateurs";
require_once '../includes/header.php';

$auth->requireRole('admin');

// Récupérer tous les utilisateurs
$users = $pdo->query("SELECT * FROM Utilisateur ORDER BY date_creation DESC")->fetchAll();

// Actions sur les utilisateurs
if (isset($_GET['action']) && isset($_GET['user'])) {
    $username = $_GET['user'];
    $action = $_GET['action'];
    
    // Ne pas permettre de modifier/supprimer l'admin principal
    if ($username === 'admin_blog') {
        addFlashMessage('danger', 'Cet utilisateur ne peut pas être modifié');
        header('Location: users.php');
        exit();
    }
    
    if (in_array($action, ['activate', 'deactivate', 'delete'])) {
        switch ($action) {
            case 'activate':
                $stmt = $pdo->prepare("UPDATE Utilisateur SET etat = 'actif' WHERE username = ?");
                $message = "Utilisateur activé";
                break;
            case 'deactivate':
                $stmt = $pdo->prepare("UPDATE Utilisateur SET etat = 'inactif' WHERE username = ?");
                $message = "Utilisateur désactivé";
                break;
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM Utilisateur WHERE username = ?");
                $message = "Utilisateur supprimé";
                break;
        }
        
        if ($stmt->execute([$username])) {
            addFlashMessage('success', $message);
        } else {
            addFlashMessage('danger', 'Erreur lors de l\'opération');
        }
        
        header('Location: users.php');
        exit();
    }
    
    if ($action === 'changerole' && isset($_GET['role'])) {
        $newRole = $_GET['role'];
        if (in_array($newRole, USER_ROLES)) {
            $stmt = $pdo->prepare("UPDATE Utilisateur SET role = ? WHERE username = ?");
            if ($stmt->execute([$newRole, $username])) {
                addFlashMessage('success', "Rôle de l'utilisateur mis à jour");
            } else {
                addFlashMessage('danger', 'Erreur lors du changement de rôle');
            }
        }
        header('Location: users.php');
        exit();
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Gestion des utilisateurs</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="add_user.php" class="btn btn-primary">
                        <i class="bi bi-person-plus me-2"></i>Ajouter un utilisateur
                    </a>
                </div>
            </div>
            
            <!-- Statistiques -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h6 class="card-title">Total utilisateurs</h6>
                            <p class="card-text display-6"><?= count($users) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h6 class="card-title">Utilisateurs actifs</h6>
                            <p class="card-text display-6">
                                <?= count(array_filter($users, fn($u) => $u['etat'] === 'actif')) ?>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h6 class="card-title">Administrateurs</h6>
                            <p class="card-text display-6">
                                <?= count(array_filter($users, fn($u) => $u['role'] === 'admin')) ?>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h6 class="card-title">Auteurs</h6>
                            <p class="card-text display-6">
                                <?= count(array_filter($users, fn($u) => $u['role'] === 'auteur')) ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tableau des utilisateurs -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Nom</th>
                                    <th>Email</th>
                                    <th>Rôle</th>
                                    <th>État</th>
                                    <th>Inscription</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($user['username']) ?></strong>
                                        <?php if ($user['username'] === $_SESSION['username']): ?>
                                        <span class="badge bg-secondary">Vous</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($user['nom']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $user['role'] === 'admin' ? 'danger' : 
                                            ($user['role'] === 'editor' ? 'warning' : 
                                            ($user['role'] === 'auteur' ? 'success' : 'secondary'))
                                        ?>">
                                            <?= $user['role'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $user['etat'] === 'actif' ? 'success' : 'secondary' ?>">
                                            <?= $user['etat'] ?>
                                        </span>
                                    </td>
                                    <td><?= formatDate($user['date_creation'], 'd/m/Y') ?></td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                    type="button" data-bs-toggle="dropdown">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <a class="dropdown-item" href="edit_user.php?user=<?= $user['username'] ?>">
                                                        <i class="bi bi-pencil me-2"></i>Modifier
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                
                                                <!-- Changer le rôle -->
                                                <li><h6 class="dropdown-header">Changer le rôle</h6></li>
                                                <?php foreach (USER_ROLES as $role): 
                                                    if ($user['role'] !== $role && $user['username'] !== 'admin_blog'):
                                                ?>
                                                <li>
                                                    <a class="dropdown-item" href="users.php?action=changerole&user=<?= $user['username'] ?>&role=<?= $role ?>">
                                                        <?= $role === 'admin' ? '👑 ' : '' ?>
                                                        <?= ucfirst($role) ?>
                                                    </a>
                                                </li>
                                                <?php endif; endforeach; ?>
                                                
                                                <li><hr class="dropdown-divider"></li>
                                                
                                                <!-- Activer/Désactiver -->
                                                <?php if ($user['username'] !== 'admin_blog'): ?>
                                                <?php if ($user['etat'] === 'actif'): ?>
                                                <li>
                                                    <a class="dropdown-item text-warning" href="users.php?action=deactivate&user=<?= $user['username'] ?>">
                                                        <i class="bi bi-person-x me-2"></i>Désactiver
                                                    </a>
                                                </li>
                                                <?php else: ?>
                                                <li>
                                                    <a class="dropdown-item text-success" href="users.php?action=activate&user=<?= $user['username'] ?>">
                                                        <i class="bi bi-person-check me-2"></i>Activer
                                                    </a>
                                                </li>
                                                <?php endif; ?>
                                                
                                                <!-- Supprimer -->
                                                <li>
                                                    <a class="dropdown-item text-danger" 
                                                       href="users.php?action=delete&user=<?= $user['username'] ?>"
                                                       onclick="return confirm('Supprimer définitivement cet utilisateur ?')">
                                                        <i class="bi bi-trash me-2"></i>Supprimer
                                                    </a>
                                                </li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>