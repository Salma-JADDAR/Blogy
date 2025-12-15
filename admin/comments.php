<?php
$pageTitle = "Modération des commentaires";
require_once '../includes/header.php';

$auth->requireRole('admin');

// Filtrer les commentaires
$filter = $_GET['filter'] ?? 'all';
$filterWhere = '';
switch ($filter) {
    case 'pending': $filterWhere = "WHERE c.status = 'pending'"; break;
    case 'approved': $filterWhere = "WHERE c.status = 'approved'"; break;
    case 'rejected': $filterWhere = "WHERE c.status = 'rejected'"; break;
    default: $filterWhere = ''; break;
}

// Actions sur les commentaires
if (isset($_GET['action']) && isset($_GET['id'])) {
    $commentId = intval($_GET['id']);
    $action = $_GET['action'];
    
    if (in_array($action, ['approve', 'reject', 'delete'])) {
        switch ($action) {
            case 'approve':
                $stmt = $pdo->prepare("UPDATE Commentaire SET status = 'approved' WHERE id_commentaire = ?");
                $message = "Commentaire approuvé";
                break;
            case 'reject':
                $stmt = $pdo->prepare("UPDATE Commentaire SET status = 'rejected' WHERE id_commentaire = ?");
                $message = "Commentaire rejeté";
                break;
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM Commentaire WHERE id_commentaire = ?");
                $message = "Commentaire supprimé";
                break;
        }
        
        if ($stmt->execute([$commentId])) {
            addFlashMessage('success', $message);
        } else {
            addFlashMessage('danger', 'Erreur lors de l\'opération');
        }
        
        header('Location: comments.php' . ($filter != 'all' ? "?filter=$filter" : ''));
        exit();
    }
}

// Récupérer les commentaires
$query = "
    SELECT c.*, a.titre, a.id_article, u.nom as user_nom, u.username 
    FROM Commentaire c 
    LEFT JOIN Article a ON c.id_article = a.id_article 
    LEFT JOIN Utilisateur u ON c.username = u.username 
    $filterWhere 
    ORDER BY c.date_commentaire DESC
";
$comments = $pdo->query($query)->fetchAll();

// Statistiques
$stats = [
    'all' => $pdo->query("SELECT COUNT(*) as total FROM Commentaire")->fetch()['total'],
    'pending' => $pdo->query("SELECT COUNT(*) as total FROM Commentaire WHERE status = 'pending'")->fetch()['total'],
    'approved' => $pdo->query("SELECT COUNT(*) as total FROM Commentaire WHERE status = 'approved'")->fetch()['total'],
    'rejected' => $pdo->query("SELECT COUNT(*) as total FROM Commentaire WHERE status = 'rejected'")->fetch()['total']
];
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Modération des commentaires</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <span class="btn btn-sm btn-outline-primary disabled">
                            <i class="bi bi-chat-dots me-1"></i><?= $stats['all'] ?> total
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Filtres -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="btn-group" role="group">
                                <a href="comments.php?filter=all" 
                                   class="btn btn-outline-primary <?= $filter == 'all' ? 'active' : '' ?>">
                                    Tous (<?= $stats['all'] ?>)
                                </a>
                                <a href="comments.php?filter=pending" 
                                   class="btn btn-outline-warning <?= $filter == 'pending' ? 'active' : '' ?>">
                                    En attente (<?= $stats['pending'] ?>)
                                </a>
                                <a href="comments.php?filter=approved" 
                                   class="btn btn-outline-success <?= $filter == 'approved' ? 'active' : '' ?>">
                                    Approuvés (<?= $stats['approved'] ?>)
                                </a>
                                <a href="comments.php?filter=rejected" 
                                   class="btn btn-outline-danger <?= $filter == 'rejected' ? 'active' : '' ?>">
                                    Rejetés (<?= $stats['rejected'] ?>)
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Liste des commentaires -->
            <div class="card">
                <div class="card-body">
                    <?php if (empty($comments)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-chat-text display-1 text-muted mb-3"></i>
                        <h4>Aucun commentaire trouvé</h4>
                        <p class="text-muted">Aucun commentaire ne correspond à ce filtre</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Contenu</th>
                                    <th>Auteur</th>
                                    <th>Article</th>
                                    <th>Date</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($comments as $comment): ?>
                                <tr>
                                    <td style="max-width: 300px;">
                                        <div class="comment-preview">
                                            <?= htmlspecialchars(substr($comment['contenu'], 0, 100)) ?>
                                            <?php if (strlen($comment['contenu']) > 100): ?>...<?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($comment['username']): ?>
                                        <strong><?= htmlspecialchars($comment['user_nom']) ?></strong>
                                        <br><small class="text-muted">@<?= htmlspecialchars($comment['username']) ?></small>
                                        <?php else: ?>
                                        <em><?= htmlspecialchars($comment['email']) ?></em>
                                        <br><small class="text-muted">Visiteur</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="../public/article.php?id=<?= $comment['id_article'] ?>" 
                                           class="text-decoration-none">
                                            <?= htmlspecialchars($comment['titre']) ?>
                                        </a>
                                    </td>
                                    <td><?= formatDate($comment['date_commentaire']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $comment['status'] === 'approved' ? 'success' : 
                                            ($comment['status'] === 'pending' ? 'warning' : 'danger')
                                        ?>">
                                            <?= $comment['status'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <?php if ($comment['status'] !== 'approved'): ?>
                                            <a href="comments.php?action=approve&id=<?= $comment['id_commentaire'] ?><?= $filter != 'all' ? "&filter=$filter" : '' ?>" 
                                               class="btn btn-outline-success" title="Approuver">
                                                <i class="bi bi-check-lg"></i>
                                            </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($comment['status'] !== 'rejected'): ?>
                                            <a href="comments.php?action=reject&id=<?= $comment['id_commentaire'] ?><?= $filter != 'all' ? "&filter=$filter" : '' ?>" 
                                               class="btn btn-outline-warning" title="Rejeter">
                                                <i class="bi bi-x-lg"></i>
                                            </a>
                                            <?php endif; ?>
                                            
                                            <a href="comments.php?action=delete&id=<?= $comment['id_commentaire'] ?><?= $filter != 'all' ? "&filter=$filter" : '' ?>" 
                                               class="btn btn-outline-danger" 
                                               onclick="return confirm('Supprimer ce commentaire ?')" title="Supprimer">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>