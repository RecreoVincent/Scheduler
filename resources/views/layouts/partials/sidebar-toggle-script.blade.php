<script>
    (() => {
        const toggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('portalSidebar');
        const backdrop = document.getElementById('sidebarBackdrop');

        if (!toggle || !sidebar || !backdrop) return;

        function updateAccessibility() {
            const open = document.body.classList.contains('sidebar-open');
            toggle.setAttribute('aria-expanded', String(open));
            toggle.setAttribute('aria-label', open ? 'Close navigation menu' : 'Open navigation menu');
            sidebar.setAttribute('aria-hidden', String(!open));
            sidebar.inert = !open;
        }

        function openSidebar() {
            document.body.classList.add('sidebar-open');
            updateAccessibility();
            sidebar.querySelector('a, button')?.focus({ preventScroll:true });
        }

        function closeSidebar() {
            document.body.classList.remove('sidebar-open');
            updateAccessibility();
            toggle.focus({ preventScroll:true });
        }

        toggle.addEventListener('click', openSidebar);
        backdrop.addEventListener('click', closeSidebar);
        sidebar.querySelectorAll('a').forEach(link => link.addEventListener('click', closeSidebar));
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && document.body.classList.contains('sidebar-open')) {
                closeSidebar();
            }
        });

        updateAccessibility();
    })();
</script>
