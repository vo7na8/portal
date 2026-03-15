/**
 * PORTAL v2.0 — Scripts
 */

document.addEventListener('DOMContentLoaded', function () {

    // Тоасты авто-скрытие
    document.querySelectorAll('.toast').forEach(function (toast) {
        setTimeout(function () { dismissToast(toast); }, 4500);
    });

    function dismissToast(toast) {
        toast.classList.add('hide');
        setTimeout(function () { toast.remove(); }, 280);
    }

    // Подтверждение удаления
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-confirm]');
        if (!btn) return;
        if (!confirm(btn.getAttribute('data-confirm') || 'Вы уверены?')) {
            e.preventDefault();
            e.stopPropagation();
        }
    });

    // Сайдбар: hover на мобильных
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

    // Toggle формы
    document.querySelectorAll('[data-toggle-form]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var target = document.getElementById(btn.getAttribute('data-toggle-form'));
            if (!target) return;
            var isHidden = target.style.display === 'none' || target.style.display === '';
            target.style.display = isHidden ? 'block' : 'none';
            if (isHidden) target.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
    });

    // FIX #2: Toggle комментариев — сохраняем оригинальную иконку и восстанавливаем при закрытии
    document.querySelectorAll('[data-toggle-comments]').forEach(function (btn) {
        var iconEl  = btn.querySelector('i');
        var origClass = iconEl ? iconEl.className : null; // запоминаем исходный класс
        btn.addEventListener('click', function () {
            var target  = document.getElementById(btn.getAttribute('data-toggle-comments'));
            if (!target) return;
            var isHidden = target.style.display === 'none' || target.style.display === '';
            target.style.display = isHidden ? 'block' : 'none';
            if (iconEl) {
                iconEl.className = isHidden ? 'fas fa-chevron-up' : origClass;
            }
            btn.title = isHidden ? 'Скрыть' : btn.getAttribute('title') || '';
        });
    });

    // Drag-and-drop загрузка
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
                var nameEl = area.querySelector('.upload-filename');
                if (nameEl) nameEl.textContent = e.dataTransfer.files[0].name;
            }
        });
        area.addEventListener('click', function (e) {
            if (e.target.tagName === 'BUTTON' || e.target.type === 'submit') return;
            var input = area.querySelector('input[type=file]');
            if (input) input.click();
        });
        var fileInput = area.querySelector('input[type=file]');
        if (fileInput) {
            fileInput.addEventListener('change', function () {
                var nameEl = area.querySelector('.upload-filename');
                if (nameEl && fileInput.files.length) nameEl.textContent = fileInput.files[0].name;
            });
        }
    });

    // Авто-резайз textarea
    document.querySelectorAll('textarea').forEach(function (ta) {
        ta.addEventListener('input', function () {
            ta.style.height = 'auto';
            ta.style.height = Math.min(ta.scrollHeight, 320) + 'px';
        });
    });

});

/**
 * Программное создание toast
 * showToast('success', 'Сохранено!')
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
    var icon = document.createElement('i');
    icon.className = 'fas ' + (icons[type] || 'fa-circle-info');
    var text = document.createElement('span');
    text.textContent = message;
    var closeBtn = document.createElement('button');
    closeBtn.className = 'toast-close';
    closeBtn.textContent = '\u00d7';
    closeBtn.addEventListener('click', function () { toast.remove(); });
    toast.append(icon, text, closeBtn);
    container.appendChild(toast);
    setTimeout(function () {
        toast.classList.add('hide');
        setTimeout(function () { toast.remove(); }, 280);
    }, 4500);
}
