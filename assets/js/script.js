// BlogCMS - Scripts JavaScript

// Initialisation quand le DOM est chargé
document.addEventListener('DOMContentLoaded', function() {
    // Tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Validation des formulaires
    initFormValidation();
    
    // Gestion des messages flash
    initFlashMessages();
    
    // Gestion des commentaires
    initComments();
    
    // Gestion de la recherche
    initSearch();
});

// Validation des formulaires
function initFormValidation() {
    // Validation des formulaires avec required
    const forms = document.querySelectorAll('form[novalidate]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
    
    // Validation en temps réel
    const inputs = document.querySelectorAll('input[required], textarea[required], select[required]');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
    });
}

function validateField(field) {
    const isValid = field.checkValidity();
    const feedback = field.nextElementSibling;
    
    if (feedback && feedback.classList.contains('invalid-feedback')) {
        if (!isValid) {
            field.classList.add('is-invalid');
            field.classList.remove('is-valid');
        } else {
            field.classList.add('is-valid');
            field.classList.remove('is-invalid');
        }
    }
}

// Gestion des messages flash
function initFlashMessages() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
}

// Gestion des commentaires
function initComments() {
    // Répondre à un commentaire
    const replyButtons = document.querySelectorAll('.reply-btn');
    replyButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const commentId = this.dataset.commentId;
            const replyForm = document.querySelector(`#reply-form-${commentId}`);
            
            if (replyForm) {
                replyForm.classList.toggle('d-none');
                if (!replyForm.classList.contains('d-none')) {
                    replyForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    replyForm.querySelector('textarea').focus();
                }
            }
        });
    });
    
    // Éditer un commentaire
    const editButtons = document.querySelectorAll('.edit-comment-btn');
    editButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const commentId = this.dataset.commentId;
            const commentContent = document.querySelector(`#comment-content-${commentId}`);
            const editForm = document.querySelector(`#edit-form-${commentId}`);
            
            if (commentContent && editForm) {
                commentContent.classList.add('d-none');
                editForm.classList.remove('d-none');
                editForm.querySelector('textarea').focus();
            }
        });
    });
}

// Gestion de la recherche
function initSearch() {
    const searchInput = document.querySelector('input[name="q"]');
    if (searchInput) {
        // Auto-complétion
        searchInput.addEventListener('input', debounce(function(e) {
            const query = e.target.value.trim();
            if (query.length >= 2) {
                fetchSearchSuggestions(query);
            }
        }, 300));
        
        // Recherche instantanée
        const instantSearchCheckbox = document.querySelector('#instant-search');
        if (instantSearchCheckbox) {
            instantSearchCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    searchInput.addEventListener('input', debounce(function(e) {
                        if (e.target.value.trim().length >= 2) {
                            document.querySelector('#search-form').submit();
                        }
                    }, 500));
                }
            });
        }
    }
}

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Fetch search suggestions
function fetchSearchSuggestions(query) {
    fetch(`/api/search-suggestions?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            if (data.suggestions && data.suggestions.length > 0) {
                showSearchSuggestions(data.suggestions);
            }
        })
        .catch(error => console.error('Error fetching suggestions:', error));
}

// Show search suggestions
function showSearchSuggestions(suggestions) {
    // Implementation des suggestions de recherche
    console.log('Suggestions:', suggestions);
}

// Copier le lien
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('Lien copié dans le presse-papier !');
    }).catch(err => {
        console.error('Erreur lors de la copie:', err);
    });
}

// Afficher un toast
function showToast(message, type = 'success') {
    const toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        createToastContainer();
    }
    
    const toastId = 'toast-' + Date.now();
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-bg-${type} border-0`;
    toast.id = toastId;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', function () {
        toast.remove();
    });
}

// Créer le conteneur de toasts
function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
    container.style.zIndex = '1055';
    document.body.appendChild(container);
}

// Confirm avant suppression
function confirmDelete(message = 'Êtes-vous sûr de vouloir supprimer cet élément ?') {
    return confirm(message);
}

// Toggle sidebar mobile
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.classList.toggle('d-none');
    }
}

// Dark mode toggle
function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    const isDarkMode = document.body.classList.contains('dark-mode');
    localStorage.setItem('darkMode', isDarkMode);
    
    // Mettre à jour l'icône
    const icon = document.querySelector('#dark-mode-toggle i');
    if (icon) {
        if (isDarkMode) {
            icon.classList.remove('bi-moon');
            icon.classList.add('bi-sun');
        } else {
            icon.classList.remove('bi-sun');
            icon.classList.add('bi-moon');
        }
    }
}

// Initialiser le dark mode
function initDarkMode() {
    const darkMode = localStorage.getItem('darkMode') === 'true';
    if (darkMode) {
        document.body.classList.add('dark-mode');
        const icon = document.querySelector('#dark-mode-toggle i');
        if (icon) {
            icon.classList.remove('bi-moon');
            icon.classList.add('bi-sun');
        }
    }
}

// Formater la date
function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;
    
    const minute = 60 * 1000;
    const hour = 60 * minute;
    const day = 24 * hour;
    const week = 7 * day;
    
    if (diff < minute) {
        return 'À l\'instant';
    } else if (diff < hour) {
        const minutes = Math.floor(diff / minute);
        return `Il y a ${minutes} minute${minutes > 1 ? 's' : ''}`;
    } else if (diff < day) {
        const hours = Math.floor(diff / hour);
        return `Il y a ${hours} heure${hours > 1 ? 's' : ''}`;
    } else if (diff < week) {
        const days = Math.floor(diff / day);
        return `Il y a ${days} jour${days > 1 ? 's' : ''}`;
    } else {
        return date.toLocaleDateString('fr-FR', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
    }
}

// Initialiser le dark mode au chargement
initDarkMode();

// Exporter les fonctions globales
window.copyToClipboard = copyToClipboard;
window.confirmDelete = confirmDelete;
window.toggleSidebar = toggleSidebar;
window.toggleDarkMode = toggleDarkMode;
window.formatDate = formatDate;