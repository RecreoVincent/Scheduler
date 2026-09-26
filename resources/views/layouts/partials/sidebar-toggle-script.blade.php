<script>
    (() => {
        const toggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('portalSidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        const moreButton = document.getElementById('mobileMoreNavigation');
        const mobileViewport = window.matchMedia('(max-width: 950px)');
        let lastTrigger = toggle;

        if (!toggle || !sidebar || !backdrop) return;

        function updateAccessibility() {
            const mobile = mobileViewport.matches;
            const open = !mobile || document.body.classList.contains('sidebar-open');
            toggle.setAttribute('aria-expanded', String(open));
            toggle.setAttribute('aria-label', open ? 'Close navigation menu' : 'Open navigation menu');
            moreButton?.setAttribute('aria-expanded', String(mobile && open));
            sidebar.setAttribute('aria-hidden', String(!open));
            sidebar.inert = mobile && !open;
        }

        function openSidebar(trigger = toggle) {
            if (!mobileViewport.matches) return;
            lastTrigger = trigger;
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
            lastTrigger?.focus({ preventScroll:true });
        }

        toggle.addEventListener('click', () => openSidebar(toggle));
        moreButton?.addEventListener('click', () => openSidebar(moreButton));
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
