<script>
    (() => {
        document.querySelectorAll('form[data-auto-filter]').forEach((form) => {
            let typingTimer;
            const submit = () => {
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                    return;
                }

                form.submit();
            };

            form.querySelectorAll('select').forEach((select) => {
                select.addEventListener('change', submit);
            });

            form.querySelectorAll('input[type="text"], input[type="search"]').forEach((input) => {
                input.addEventListener('input', () => {
                    window.clearTimeout(typingTimer);
                    typingTimer = window.setTimeout(submit, 550);
                });
            });
        });
    })();
</script>
<script>
    (() => {
        const storageKey = 'mccscheduler.table-scroll-position';

        const rememberTablePosition = () => {
            if (!document.querySelector('.portal-pagination-bar')) {
                return;
            }

            try {
                sessionStorage.setItem(storageKey, JSON.stringify({
                    path: window.location.pathname,
                    top: window.scrollY,
                }));
            } catch (_) {
                // Browsers that block session storage can still use the tables normally.
            }
        };

        document.addEventListener('submit', event => {
            const form = event.target;

            if (!(form instanceof HTMLFormElement)) {
                return;
            }

            const method = (form.getAttribute('method') || 'GET').toUpperCase();
            const updatesTable = form.matches('[data-auto-filter], .portal-page-size')
                || form.querySelector('[name="search"], [name="per_page"]');

            const isEditingTableRecord = new URLSearchParams(window.location.search).has('edit');
            const updateMethod = form.querySelector('input[name="_method"]')?.value?.toUpperCase();
            const isRecordUpdate = ['PATCH', 'PUT'].includes(updateMethod);

            if ((method === 'GET' && updatesTable) || (method !== 'GET' && (isEditingTableRecord || isRecordUpdate))) {
                rememberTablePosition();
            }
        });

        document.addEventListener('click', event => {
            const link = event.target.closest('a[href]');

            if (!link || link.target === '_blank') {
                return;
            }

            const url = new URL(link.href, window.location.href);
            const staysOnCurrentPage = url.origin === window.location.origin
                && url.pathname === window.location.pathname;
            const updatesTable = link.matches('.portal-page-button') || url.searchParams.has('edit');

            if (staysOnCurrentPage && updatesTable) {
                rememberTablePosition();
            }
        });

        window.addEventListener('pageshow', () => {
            let savedPosition;

            try {
                savedPosition = JSON.parse(sessionStorage.getItem(storageKey) || 'null');
            } catch (_) {
                return;
            }

            if (!savedPosition || savedPosition.path !== window.location.pathname) {
                return;
            }

            sessionStorage.removeItem(storageKey);
            window.requestAnimationFrame(() => {
                window.scrollTo(0, Math.max(0, Number(savedPosition.top) || 0));
            });
        });
    })();
</script>
