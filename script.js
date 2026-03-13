document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.delete-confirm').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('Вы уверены, что хотите удалить?')) {
                e.preventDefault();
            }
        });
    });
});