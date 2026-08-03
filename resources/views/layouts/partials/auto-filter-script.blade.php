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
