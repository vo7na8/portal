/**
 * ПОРТАЛ v2.0 — Общие скрипты
 */

document.addEventListener('DOMContentLoaded', function () {

    // ==========================================
    // TOAST авто-скрытие
    // ==========================================
    const toasts = document.querySelectorAll('.toast');
    toasts.forEach(function (toast) {
        setTimeout(function () {
            dismissToast(toast);
        }, 4500);
    });

    function dismissToast(toast) {
        toast.classList.add('hide');
        setTimeout(function () { toast.remove(); }, 280);
    }

    // ==========================================
    // Подтверждение удаления
    // ==========================================
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-confirm]');
        if (!btn) return;
        var message = btn.getAttribute('data-confirm') || 'Вы уверены?';
        if (!confirm(message)) {
            e.preventDefault();
            e.stopPropagation();
        }
    });

    // ==========================================
    // Боковая панель: hover на узких экранах
    // ==========================================
    var sidebar = document.getElementById('sidebar');
    if (sidebar && window.innerWidth <= 768) {
        sidebar.addEventListener('mouseenter', function () {
            sidebar.style.width = '220px';
            sidebar.querySelectorAll('.nav-item span, .user-details, .sidebar-brand-text, .logout-btn span')
                .forEach(function (el) { el.style.display = ''; });
        });
        sidebar.addEventListener('mouseleave', function () {
            sidebar.style.width = '';
            sidebar.querySelectorAll('.nav-item span, .user-details, .sidebar-brand-text, .logout-btn span')
                .forEach(function (el) { el.style.display = 'none'; });
        });
    }

    // ==========================================
    // Авто-скрытие форм добавления (toggle)
    // ==========================================
    document.querySelectorAll('[data-toggle-form]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var target = document.getElementById(btn.getAttribute('data-toggle-form'));
            if (!target) return;
            var isHidden = target.style.display === 'none' || target.style.display === '';
            target.style.display = isHidden ? 'block' : 'none';
            // Прокрутить к форме
            if (isHidden) target.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
    });

    // ==========================================
    // Авто-скрытие комментариев устройств (toggle)
    // ==========================================
    document.querySelectorAll('[data-toggle-comments]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var target = document.getElementById(btn.getAttribute('data-toggle-comments'));
            if (!target) return;
            var isHidden = target.style.display === 'none' || target.style.display === '';
            target.style.display = isHidden ? 'block' : 'none';
            btn.textContent = isHidden ? 'Скрыть комментарии' : 'Комментарии';
        });
    });

    // ==========================================
    // Drag-and-drop для зон загрузки
    // ==========================================
    document.querySelectorAll('.upload-area').forEach(function (area) {
        area.addEventListener('dragover', function (e) {
            e.preventDefault();
            area.style.borderColor = 'var(--accent)';
            area.style.background  = 'var(--card-hover)';
        });
        area.addEventListener('dragleave', function () {
            area.style.borderColor = '';
            area.style.background  = '';
        });
        area.addEventListener('drop', function (e) {
            e.preventDefault();
            area.style.borderColor = '';
            area.style.background  = '';
            var input = area.querySelector('input[type=file]');
            if (input && e.dataTransfer.files.length) {
                input.files = e.dataTransfer.files;
                // Показать имя файла
                var nameEl = area.querySelector('.upload-filename');
                if (nameEl) nameEl.textContent = e.dataTransfer.files[0].name;
            }
        });
        // Клик по зоне открывает диалог файла
        area.addEventListener('click', function () {
            var input = area.querySelector('input[type=file]');
            if (input) input.click();
        });
        var fileInput = area.querySelector('input[type=file]');
        if (fileInput) {
            fileInput.addEventListener('change', function () {
                var nameEl = area.querySelector('.upload-filename');
                if (nameEl && fileInput.files.length) {
                    nameEl.textContent = fileInput.files[0].name;
                }
            });
        }
    });

});

/**
 * Программное создание toast
 * Использование: showToast('success', 'Сохранено!')
 */
function showToast(type, message) {
    var icons = { success: 'fa-circle-check', error: 'fa-circle-xmark', warning: 'fa-triangle-exclamation', info: 'fa-circle-info' };
    var container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    var toast = document.createElement('div');
    toast.className = 'toast toast-' + type;
    toast.setAttribute('role', 'alert');
    toast.innerHTML =
        '<i class="fas ' + (icons[type] || 'fa-circle-info') + '"></i>' +
        '<span>' + message + '</span>' +
        '<button class="toast-close" onclick="this.parentElement.remove()">&times;</button>';
    container.appendChild(toast);
    setTimeout(function () {
        toast.classList.add('hide');
        setTimeout(function () { toast.remove(); }, 280);
    }, 4500);
}
