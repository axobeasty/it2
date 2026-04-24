<div class="offcanvas-lg offcanvas-start app-sidebar-offcanvas border-0" tabindex="-1" id="appSidebarNav" aria-labelledby="appSidebarNavLabel">
    <div class="offcanvas-header app-sidebar-drawer-header d-lg-none align-items-center py-3 px-3">
        <span class="offcanvas-title mb-0" id="appSidebarNavLabel">Меню</span>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#appSidebarNav" aria-label="Закрыть"></button>
    </div>
    <div class="offcanvas-body p-0 d-flex flex-column flex-grow-1" style="min-height: 0;">
        @include('layout.sidebar')
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var root = document.getElementById('appSidebarNav');
        if (!root || typeof bootstrap === 'undefined') return;
        root.addEventListener('click', function (e) {
            var a = e.target.closest('a.nav-link');
            if (!a) return;
            var href = a.getAttribute('href');
            if (!href || href === '#' || href.indexOf('javascript:') === 0) return;
            if (window.matchMedia('(min-width: 992px)').matches) return;
            var inst = bootstrap.Offcanvas.getInstance(root);
            if (inst) inst.hide();
        });
    });
</script>
