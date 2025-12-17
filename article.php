<?php
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Récupérer l'ID de l'article
$article_id = $_GET['id'] ?? 0;

// Récupérer l'article
$article = getArticleById($article_id);

if (!$article) {
    header('Location: index.php');
    exit;
}

// Incrémenter le compteur de vues
$sql = "UPDATE Article SET view_count = view_count + 1 WHERE id_article = :id";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':id', $article_id, PDO::PARAM_INT);
$stmt->execute();

// Récupérer les commentaires
$comments = getComments($article_id);

$page_title = $article['titre'];
$body_class = "blog-details-page";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <?php
    // Inclure les CSS depuis header.php
    $include_css_only = true;
    require_once 'includes/header.php';
    ?>
    
    <!-- Socket.IO pour le temps réel -->
    <script src="https://cdn.socket.io/4.7.4/socket.io.min.js"></script>
    
    <style>
    /* Styles pour les commentaires */
    .comment-card {
        background: #fff;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        border-left: 4px solid #f75815;
        transition: all 0.3s ease;
        animation: fadeIn 0.5s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .comment-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    .comment-card.new-comment {
        background: rgba(247, 88, 21, 0.05);
        border-left: 4px solid #28a745;
        animation: highlight 2s ease;
    }

    @keyframes highlight {
        0% { background-color: rgba(40, 167, 69, 0.2); }
        100% { background-color: rgba(247, 88, 21, 0.05); }
    }

    .comment-header {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .user-info img {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #f0f0f0;
    }

    .user-info .meta h4 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
        color: #333;
    }

    .user-info .meta .date {
        font-size: 13px;
        color: #6c757d;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .comment-content p {
        margin: 0;
        line-height: 1.6;
        color: #444;
        font-size: 15px;
        white-space: pre-wrap;
    }

    .comment-stats {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #6c757d;
        font-size: 14px;
    }

    .comment-stats i {
        color: #f75815;
    }

    /* Formulaire */
    #comment-form {
        background: #f8f9fa;
        padding: 25px;
        border-radius: 10px;
        border: 1px solid #e9ecef;
    }

    .btn-submit {
        background: #f75815;
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 8px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-submit:hover:not(:disabled) {
        background: #e04e12;
        transform: translateY(-2px);
    }

    .btn-submit:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }

    .char-counter {
        text-align: right;
        font-size: 13px;
        color: #6c757d;
    }

    #char-count {
        font-weight: bold;
    }

    .char-count-warning {
        color: #ffc107;
    }

    .char-count-danger {
        color: #dc3545;
    }

    /* Alertes */
    .comment-alert {
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
        border: 1px solid transparent;
    }

    .alert-success {
        background-color: #d4edda;
        border-color: #c3e6cb;
        color: #155724;
    }

    .alert-danger {
        background-color: #f8d7da;
        border-color: #f5c6cb;
        color: #721c24;
    }

    .alert-warning {
        background-color: #fff3cd;
        border-color: #ffeaa7;
        color: #856404;
    }

    /* Animation de chargement */
    .spinner {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Indicateur temps réel */
    .realtime-indicator {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 12px;
        color: #28a745;
        background: rgba(40, 167, 69, 0.1);
        padding: 3px 8px;
        border-radius: 12px;
        margin-left: 10px;
    }

    .realtime-dot {
        width: 8px;
        height: 8px;
        background: #28a745;
        border-radius: 50%;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% { opacity: 0.5; }
        50% { opacity: 1; }
        100% { opacity: 0.5; }
    }

    /* Responsive */
    @media (max-width: 768px) {
        .comment-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .user-info {
            margin-bottom: 10px;
        }
        
        #comment-form {
            padding: 15px;
        }
    }
    </style>
</head>
<body class="<?php echo $body_class; ?>">

<?php 
// Ré-inclure header.php pour la structure
$include_css_only = false;
require_once 'includes/header.php'; 
?>

<!-- Page Title -->
<div class="page-title">
    <div class="breadcrumbs">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php"><i class="bi bi-house"></i> Home</a></li>
                <li class="breadcrumb-item"><a href="category.php?id=<?php echo $article['id_categorie']; ?>">
                    <?php echo htmlspecialchars($article['nom_categorie']); ?>
                </a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($article['titre']); ?></li>
            </ol>
        </nav>
    </div>
</div>

<div class="container">
    <div class="row">
        <div class="col-lg-8">
            <!-- Article Content -->
            <section id="blog-details" class="blog-details section">
                <article class="article">
                    <div class="hero-img" data-aos="zoom-in">
                        <img src="assets/img/blog/blog-post-3.webp" 
                             alt="<?php echo htmlspecialchars($article['titre']); ?>" 
                             class="img-fluid" loading="lazy">
                        <div class="meta-overlay">
                            <div class="meta-categories">
                                <a href="category.php?id=<?php echo $article['id_categorie']; ?>" class="category">
                                    <?php echo htmlspecialchars($article['nom_categorie']); ?>
                                </a>
                                <span class="divider">•</span>
                                <span class="reading-time">
                                    <i class="bi bi-clock"></i> <?php echo $article['view_count'] + 1; ?> vues
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="article-content" data-aos="fade-up" data-aos-delay="100">
                        <div class="content-header">
                            <h1 class="title"><?php echo htmlspecialchars($article['titre']); ?></h1>
                            
                            <div class="author-info">
                                <div class="author-details">
                                    <img src="assets/img/person/person-f-8.webp" alt="Author" class="author-img">
                                    <div class="info">
                                        <h4><?php echo htmlspecialchars($article['auteur_nom']); ?></h4>
                                        <span class="role">Auteur</span>
                                    </div>
                                </div>
                                <div class="post-meta">
                                    <span class="date">
                                        <i class="bi bi-calendar3"></i> 
                                        <?php echo formatDate($article['date_creation']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="content">
                            <?php echo nl2br(htmlspecialchars($article['contenu'])); ?>
                        </div>
                    </div>
                </article>
            </section>
            
            <!-- Commentaires Section -->
            <section id="blog-comments" class="blog-comments section">
                <div class="container" data-aos="fade-up">
                    <div class="blog-comments-3">
                        <div class="section-header d-flex justify-content-between align-items-center">
                            <div>
                                <h3>Commentaires 
                                    <span id="comment-count" class="comment-count">(<?php echo count($comments); ?>)</span>
                                    <span class="realtime-indicator" id="realtime-indicator" style="display: none;">
                                        <span class="realtime-dot"></span>
                                        Temps réel
                                    </span>
                                </h3>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-primary" id="refresh-comments">
                                    <i class="bi bi-arrow-clockwise" id="refresh-icon"></i> Actualiser
                                </button>
                                <div class="comment-stats">
                                    <i class="bi bi-chat"></i> 
                                    <span id="total-comments"><?php echo count($comments); ?></span> commentaire(s)
                                </div>
                            </div>
                        </div>
                        
                        <div id="comments-wrapper" class="comments-wrapper">
                            <?php if (!empty($comments)): ?>
                                <?php foreach($comments as $comment): ?>
                                    <article class="comment-card" id="comment-<?php echo $comment['id_commentaire']; ?>">
                                        <div class="comment-header">
                                            <div class="user-info">
                                                <img src="assets/img/person/person-f-9.webp" alt="User avatar" loading="lazy">
                                                <div class="meta">
                                                    <h4 class="name">
                                                        <?php echo htmlspecialchars($comment['username_display'] ?? 'Anonyme'); ?>
                                                    </h4>
                                                    <span class="date">
                                                        <i class="bi bi-calendar3"></i> 
                                                        <?php 
                                                        $date = new DateTime($comment['date_commentaire']);
                                                        echo $date->format('d M, Y à H:i');
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="comment-content">
                                            <p><?php echo nl2br(htmlspecialchars($comment['contenu'])); ?></p>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div id="no-comments" class="text-center py-5">
                                    <i class="bi bi-chat" style="font-size: 3rem; color: #dee2e6;"></i>
                                    <h5 class="mt-3">Aucun commentaire pour le moment</h5>
                                    <p class="text-muted">Soyez le premier à commenter cet article!</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Formulaire de commentaire -->
            <section id="blog-comment-form" class="blog-comment-form section">
                <div class="container" data-aos="fade-up" data-aos-delay="100">
                    <!-- Messages d'alerte -->
                    <div id="comment-alert" class="comment-alert" style="display: none;"></div>
                    
                    <form id="comment-form" method="post" role="form">
                        <div class="section-header">
                            <h3>Ajouter un commentaire</h3>
                            <p class="text-muted">Tous les utilisateurs peuvent commenter. Votre email ne sera pas publié.</p>
                        </div>
                        
                        <div class="row gy-3">
                            <?php if(!isset($_SESSION['user'])): ?>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email" class="form-label">Email *</label>
                                        <input type="email" name="email" class="form-control" id="email" 
                                               placeholder="votre@email.com" required>
                                        <div class="form-text">Votre email ne sera pas affiché publiquement</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name" class="form-label">Nom (optionnel)</label>
                                        <input type="text" name="name" class="form-control" id="name" 
                                               placeholder="Votre nom">
                                        <div class="form-text">Si vide, votre pseudo email sera utilisé</div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <i class="bi bi-person"></i> Vous commentez en tant que: 
                                        <strong><?php echo htmlspecialchars($_SESSION['user']['nom']); ?></strong>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="contenu" class="form-label">Votre commentaire *</label>
                                    <textarea class="form-control" name="contenu" id="contenu" 
                                              rows="5" placeholder="Partagez vos pensées..." 
                                              maxlength="1000" required></textarea>
                                    <div class="d-flex justify-content-between mt-2">
                                        <div class="form-text">Maximum 1000 caractères</div>
                                        <div class="char-counter">
                                            <small><span id="char-count">0</span>/1000</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <button type="submit" name="submit_comment" class="btn-submit" id="submit-comment">
                                        <span id="submit-text">Poster le commentaire</span>
                                        <span id="loading-spinner" class="spinner-border spinner-border-sm" 
                                              role="status" style="display: none;"></span>
                                    </button>
                                    
                                    <small class="text-muted">
                                        <i class="bi bi-clock"></i> Les commentaires sont affichés en temps réel
                                    </small>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </section>
        </div>
        
        <div class="col-lg-4 sidebar">
            <?php include 'includes/sidebar.php'; ?>
        </div>
    </div>
</div>

<script>
// Variables globales
let socket = null;
const articleId = <?php echo $article_id; ?>;

document.addEventListener('DOMContentLoaded', function() {
    const commentForm = document.getElementById('comment-form');
    const commentContent = document.getElementById('contenu');
    const charCount = document.getElementById('char-count');
    const submitBtn = document.getElementById('submit-comment');
    const submitText = document.getElementById('submit-text');
    const loadingSpinner = document.getElementById('loading-spinner');
    const commentAlert = document.getElementById('comment-alert');
    const commentsWrapper = document.getElementById('comments-wrapper');
    const noCommentsDiv = document.getElementById('no-comments');
    const commentCountSpan = document.getElementById('comment-count');
    const totalCommentsSpan = document.getElementById('total-comments');
    const refreshBtn = document.getElementById('refresh-comments');
    const refreshIcon = document.getElementById('refresh-icon');
    const realtimeIndicator = document.getElementById('realtime-indicator');
    
    // Compteur de caractères
    if (commentContent) {
        commentContent.addEventListener('input', function() {
            const length = this.value.length;
            charCount.textContent = length;
            
            // Changer la couleur selon la longueur
            if (length > 900) {
                charCount.className = 'char-count-danger';
                submitBtn.disabled = true;
            } else if (length > 800) {
                charCount.className = 'char-count-warning';
                submitBtn.disabled = false;
            } else {
                charCount.className = '';
                submitBtn.disabled = false;
            }
        });
    }
    
    // Soumission du formulaire avec AJAX
    if (commentForm) {
        commentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Valider le formulaire
            const email = document.getElementById('email');
            if (email && !email.value.trim()) {
                showAlert('danger', 'Veuillez saisir votre email');
                email.focus();
                return;
            }
            
            if (email && !validateEmail(email.value)) {
                showAlert('danger', 'Veuillez saisir un email valide');
                email.focus();
                return;
            }
            
            if (!commentContent.value.trim()) {
                showAlert('danger', 'Veuillez saisir un commentaire');
                commentContent.focus();
                return;
            }
            
            if (commentContent.value.trim().length > 1000) {
                showAlert('danger', 'Le commentaire ne peut pas dépasser 1000 caractères');
                return;
            }
            
            // Désactiver le bouton et montrer le chargement
            submitBtn.disabled = true;
            submitText.style.display = 'none';
            loadingSpinner.style.display = 'inline-block';
            
            // Récupérer les données du formulaire
            const formData = new FormData(this);
            const nameInput = document.getElementById('name');
            if (nameInput && nameInput.value.trim()) {
                formData.append('name', nameInput.value.trim());
            }
            
            // Ajouter l'ID de l'article et le flag AJAX
            formData.append('article_id', articleId);
            formData.append('ajax', 'true');
            
            // Envoyer la requête AJAX
            fetch('ajax/add_comment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur réseau');
                }
                return response.json();
            })
            .then(data => {
                // Réactiver le bouton
                submitBtn.disabled = false;
                submitText.style.display = 'inline';
                loadingSpinner.style.display = 'none';
                
                if (data.success) {
                    // Afficher le message de succès
                    showAlert('success', data.message);
                    
                    // Ajouter le nouveau commentaire à la liste
                    addNewComment(data.comment);
                    
                    // Mettre à jour les compteurs
                    updateCommentCount(1);
                    
                    // Réinitialiser le formulaire
                    commentForm.reset();
                    charCount.textContent = '0';
                    charCount.className = '';
                    
                    // Masquer le message "Aucun commentaire"
                    if (noCommentsDiv) {
                        noCommentsDiv.style.display = 'none';
                    }
                    
                    // Émettre l'événement Socket.IO pour les autres utilisateurs
                    if (socket && socket.connected) {
                        socket.emit('new_comment', {
                            article_id: articleId,
                            comment: data.comment
                        });
                    }
                    
                    // Masquer l'alerte après 3 secondes
                    setTimeout(() => {
                        commentAlert.style.display = 'none';
                    }, 3000);
                } else {
                    // Afficher le message d'erreur
                    showAlert('danger', data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                submitBtn.disabled = false;
                submitText.style.display = 'inline';
                loadingSpinner.style.display = 'none';
                showAlert('danger', 'Une erreur est survenue. Veuillez réessayer.');
            });
        });
    }
    
    // Fonction pour afficher les alertes
    function showAlert(type, message) {
        commentAlert.className = `comment-alert alert-${type}`;
        commentAlert.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                <div>${message}</div>
            </div>
        `;
        commentAlert.style.display = 'block';
    }
    
    // Fonction pour valider l'email
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    // Fonction pour ajouter un nouveau commentaire
    function addNewComment(comment) {
        // Créer l'élément HTML du commentaire
        const commentHTML = `
            <article class="comment-card new-comment" id="comment-${comment.id_commentaire}">
                <div class="comment-header">
                    <div class="user-info">
                        <img src="assets/img/person/person-f-9.webp" alt="User avatar" loading="lazy">
                        <div class="meta">
                            <h4 class="name">${escapeHtml(comment.username_display || 'Anonyme')}</h4>
                            <span class="date">
                                <i class="bi bi-calendar3"></i> ${comment.date_formatted}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="comment-content">
                    <p>${escapeHtml(comment.contenu).replace(/\n/g, '<br>')}</p>
                </div>
            </article>
        `;
        
        // Ajouter au début de la liste
        if (commentsWrapper.firstChild) {
            commentsWrapper.insertAdjacentHTML('afterbegin', commentHTML);
        } else {
            commentsWrapper.innerHTML = commentHTML;
        }
        
        // Supprimer l'animation "new" après 3 secondes
        setTimeout(() => {
            const newComment = document.getElementById(`comment-${comment.id_commentaire}`);
            if (newComment) {
                newComment.classList.remove('new-comment');
            }
        }, 3000);
    }
    
    // Fonction pour échapper le HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Fonction pour mettre à jour le compteur de commentaires
    function updateCommentCount(increment = 0) {
        const currentCount = parseInt(commentCountSpan.textContent.replace(/[()]/g, '')) || 0;
        const newCount = currentCount + increment;
        
        commentCountSpan.textContent = `(${newCount})`;
        totalCommentsSpan.textContent = newCount;
    }
    
    // Bouton d'actualisation des commentaires
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            refreshBtn.disabled = true;
            refreshIcon.classList.add('spinner');
            
            // Rafraîchir les commentaires via AJAX
            fetch(`ajax/get_comments.php?article_id=${articleId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateCommentsList(data.comments);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    showAlert('danger', 'Erreur lors du rafraîchissement');
                })
                .finally(() => {
                    setTimeout(() => {
                        refreshBtn.disabled = false;
                        refreshIcon.classList.remove('spinner');
                    }, 1000);
                });
        });
    }
    
    // Fonction pour mettre à jour la liste des commentaires
    function updateCommentsList(comments) {
        if (comments.length === 0) {
            commentsWrapper.innerHTML = `
                <div id="no-comments" class="text-center py-5">
                    <i class="bi bi-chat" style="font-size: 3rem; color: #dee2e6;"></i>
                    <h5 class="mt-3">Aucun commentaire pour le moment</h5>
                    <p class="text-muted">Soyez le premier à commenter cet article!</p>
                </div>
            `;
        } else {
            let html = '';
            comments.forEach(comment => {
                html += `
                    <article class="comment-card" id="comment-${comment.id_commentaire}">
                        <div class="comment-header">
                            <div class="user-info">
                                <img src="assets/img/person/person-f-9.webp" alt="User avatar" loading="lazy">
                                <div class="meta">
                                    <h4 class="name">${escapeHtml(comment.username_display || 'Anonyme')}</h4>
                                    <span class="date">
                                        <i class="bi bi-calendar3"></i> ${comment.date_formatted}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="comment-content">
                            <p>${escapeHtml(comment.contenu).replace(/\n/g, '<br>')}</p>
                        </div>
                    </article>
                `;
            });
            commentsWrapper.innerHTML = html;
        }
        
        updateCommentCount(0);
        showAlert('success', 'Commentaires rafraîchis!');
    }
    
    // Initialiser Socket.IO pour le temps réel
    initSocketIO();
    
    // Fonction d'initialisation de Socket.IO
    function initSocketIO() {
        try {
            socket = io('http://localhost:3000', {
                transports: ['websocket', 'polling']
            });
            
            socket.on('connect', () => {
                console.log('Connecté au serveur temps réel');
                realtimeIndicator.style.display = 'inline-flex';
                
                // Rejoindre la salle de l'article
                socket.emit('join_article', articleId);
            });
            
            socket.on('disconnect', () => {
                console.log('Déconnecté du serveur temps réel');
                realtimeIndicator.style.display = 'none';
            });
            
            socket.on('new_comment', (data) => {
                if (data.article_id == articleId) {
                    console.log('Nouveau commentaire reçu:', data.comment);
                    
                    // Ajouter le nouveau commentaire
                    addNewComment(data.comment);
                    
                    // Mettre à jour les compteurs
                    updateCommentCount(1);
                    
                    // Masquer le message "Aucun commentaire"
                    if (noCommentsDiv) {
                        noCommentsDiv.style.display = 'none';
                    }
                    
                    // Afficher une notification
                    showAlert('info', `Nouveau commentaire de ${data.comment.username_display}`);
                    
                    // Mascher l'alerte après 3 secondes
                    setTimeout(() => {
                        commentAlert.style.display = 'none';
                    }, 3000);
                }
            });
            
            socket.on('connect_error', (error) => {
                console.log('Erreur de connexion Socket.IO:', error);
                realtimeIndicator.style.display = 'none';
            });
            
        } catch (error) {
            console.log('Socket.IO non disponible:', error);
            realtimeIndicator.style.display = 'none';
        }
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>