<script>
    (() => {
        const toggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('portalSidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        const mobileViewport = window.matchMedia('(max-width: 950px)');

        if (!toggle || !sidebar || !backdrop) return;

        function updateAccessibility() {
            const mobile = mobileViewport.matches;
            const open = !mobile || document.body.classList.contains('sidebar-open');
            toggle.setAttribute('aria-expanded', String(open));
            toggle.setAttribute('aria-label', open ? 'Close navigation menu' : 'Open navigation menu');
            sidebar.setAttribute('aria-hidden', String(!open));
            sidebar.inert = mobile && !open;
        }

        function openSidebar() {
            if (!mobileViewport.matches) return;
            document.body.classList.add('sidebar-open');
            updateAccessibility();
            sidebar.querySelector('a, button')?.focus({ preventScroll:true });
        }

        function closeSidebar() {
            if (!mobileViewport.matches) {
                document.body.classList.remove('sidebar-open');
                updateAccessibility();
                return;
            }
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
        mobileViewport.addEventListener('change', () => {
            document.body.classList.remove('sidebar-open');
            updateAccessibility();
        });

        updateAccessibility();
    })();
</script>
