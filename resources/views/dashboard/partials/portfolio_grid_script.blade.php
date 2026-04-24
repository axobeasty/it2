<script>
    (function () {
        var modal = document.getElementById('portfolioDetailModal');
        if (!modal || typeof bootstrap === 'undefined') return;
        modal.addEventListener('show.bs.modal', function (event) {
            var t = event.relatedTarget;
            if (!t || !t.dataset) return;
            var num = document.getElementById('pdNumber');
            var title = document.getElementById('pdTitle');
            var type = document.getElementById('pdType');
            var role = document.getElementById('pdRole');
            var author = document.getElementById('pdAuthor');
            var date = document.getElementById('pdDate');
            var status = document.getElementById('pdStatus');
            var link = document.getElementById('pdFileLink');
            var none = document.getElementById('pdFileNone');
            if (num) num.textContent = t.dataset.number || '—';
            if (title) title.textContent = t.dataset.title || '—';
            if (type) type.textContent = t.dataset.type || '—';
            if (role) role.textContent = t.dataset.role || '—';
            if (author) author.textContent = t.dataset.author || '—';
            if (date) date.textContent = t.dataset.date || '—';
            if (status) status.textContent = t.dataset.status || '—';
            var url = t.dataset.fileUrl || '';
            if (link && none) {
                if (url) {
                    link.href = url;
                    link.classList.remove('d-none');
                    none.classList.add('d-none');
                } else {
                    link.classList.add('d-none');
                    none.classList.remove('d-none');
                }
            }
            var label = document.getElementById('portfolioDetailModalLabel');
            if (label) label.textContent = t.dataset.title ? ('Портфолио: ' + t.dataset.title) : 'Портфолио';
        });
        document.querySelectorAll('.portfolio-detail-card').forEach(function (card) {
            card.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    card.click();
                }
            });
        });
    })();
</script>
