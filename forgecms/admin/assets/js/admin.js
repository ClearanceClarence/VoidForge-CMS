/**
 * Forge CMS Admin JavaScript v1.0.3
 */

// Global toggle functions
function toggleSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    const body = document.body;
    if (sidebar) {
        sidebar.classList.toggle('open');
        body.classList.toggle('sidebar-open');
    }
}

function toggleUserDropdown() {
    const dropdown = document.getElementById('userDropdown');
    if (dropdown) {
        dropdown.classList.toggle('active');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Sidebar toggle (mobile)
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.admin-sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
        });

        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 1024 && 
                !sidebar.contains(e.target) && 
                !sidebarToggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        document.querySelectorAll('.dropdown.active, .user-dropdown.active').forEach(function(dropdown) {
            if (!dropdown.contains(e.target)) {
                dropdown.classList.remove('active');
            }
        });
    });

    // Confirm delete actions
    document.querySelectorAll('[data-confirm]').forEach(function(el) {
        el.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });

    // Auto-generate slug from title
    const titleInput = document.getElementById('title');
    const slugInput = document.getElementById('slug');
    
    if (titleInput && slugInput && !slugInput.value) {
        titleInput.addEventListener('input', function() {
            slugInput.value = slugify(this.value);
        });
    }

    // Initialize editor
    initEditor();
    
    // Initialize modals
    initModals();
});

/**
 * Slugify text
 */
function slugify(text) {
    return text
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .substring(0, 200);
}

/**
 * Initialize simple WYSIWYG editor
 */
function initEditor() {
    const editor = document.getElementById('editor');
    const contentInput = document.getElementById('content');
    
    if (!editor || !contentInput) return;

    // Set initial content
    editor.innerHTML = contentInput.value;

    // Toolbar buttons
    document.querySelectorAll('.editor-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const command = this.dataset.command;
            const value = this.dataset.value || null;
            
            if (command === 'createLink') {
                const url = prompt('Enter URL:');
                if (url) {
                    document.execCommand(command, false, url);
                }
            } else if (command === 'insertImage') {
                openModal('mediaModal');
            } else {
                document.execCommand(command, false, value);
            }
            
            editor.focus();
            updateToolbarState();
        });
    });

    // Update hidden input on change
    editor.addEventListener('input', function() {
        contentInput.value = this.innerHTML;
    });
    
    // Track selection changes
    document.addEventListener('selectionchange', function() {
        if (document.activeElement === editor) {
            updateToolbarState();
        }
    });
}

/**
 * Update toolbar button states
 */
function updateToolbarState() {
    document.querySelectorAll('.editor-btn[data-command]').forEach(function(btn) {
        const command = btn.dataset.command;
        try {
            if (document.queryCommandState(command)) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        } catch (e) {
            // Some commands don't support queryCommandState
        }
    });
}

/**
 * Initialize modals
 */
function initModals() {
    // Close modal on backdrop click
    document.querySelectorAll('.modal-backdrop').forEach(function(backdrop) {
        backdrop.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal(this.id);
            }
        });
    });

    // Close modal on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-backdrop.active').forEach(function(modal) {
                closeModal(modal.id);
            });
        }
    });
    
    // Close buttons
    document.querySelectorAll('.modal-close, [data-close-modal]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal-backdrop');
            if (modal) {
                closeModal(modal.id);
            }
        });
    });
}

/**
 * Open modal
 */
function openModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

/**
 * Close modal
 */
function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

/**
 * Format file size
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

/**
 * Show notification
 */
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.style.cssText = 'position: fixed; top: 1rem; right: 1rem; z-index: 9999; max-width: 400px; animation: slideIn 0.3s ease;';
    notification.innerHTML = message;
    
    document.body.appendChild(notification);
    
    setTimeout(function() {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(function() {
            notification.remove();
        }, 300);
    }, 3000);
}

// Add animation styles
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);
