<div id="{{ $analyticsModalId }}" class="portal-data-modal" hidden>
    <section class="portal-data-dialog" role="dialog" aria-modal="true" aria-labelledby="{{ $analyticsModalId }}Title">
        <div class="portal-data-header">
            <div><h2 id="{{ $analyticsModalId }}Title">Dashboard Analytics</h2><p>{{ $analyticsModalDescription }}</p></div>
            <div class="portal-data-actions">
                <div class="portal-data-toolbar" role="group" aria-label="Chart type">
                    <button type="button" class="portal-data-type active" data-chart-type="bar">Bar</button>
                    <button type="button" class="portal-data-type" data-chart-type="pie">Pie</button>
                    <button type="button" class="portal-data-type" data-chart-type="doughnut">Doughnut</button>
                </div>
                <button type="button" class="portal-data-close" aria-label="Close analytics">&times;</button>
            </div>
        </div>
        <div class="portal-data-layout"></div>
    </section>
</div>

<script>
(() => {
    const modal = document.getElementById(@js($analyticsModalId));
    const items = @js($analyticsModalData);
    const cards = [...document.querySelectorAll(@js($analyticsCardSelector))];
    const canvas = modal.querySelector('.portal-data-layout');
    const title = modal.querySelector('h2');
    const closeButton = modal.querySelector('.portal-data-close');
    const typeButtons = [...modal.querySelectorAll('.portal-data-type')];
    let chartType = 'bar';
    let lastTrigger = null;

    const escapeHtml = value => String(value).replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;');
    const legend = () => `<div class="portal-data-legend">${items.map(item => `<div class="portal-data-legend-item"><span class="portal-data-legend-label"><i class="portal-data-legend-color" style="background:${item.color}"></i>${escapeHtml(item.label)}</span><strong>${item.value}</strong></div>`).join('')}</div>`;

    function renderBar() {
        const maximum = Math.max(1, ...items.map(item => Number(item.value)));
        canvas.innerHTML = `<div class="portal-data-bars" role="img" aria-label="Dashboard analytics bar chart">${items.map(item => {
            const height = Number(item.value) === 0 ? 2 : Math.max(9, Number(item.value) / maximum * 210);
            return `<div class="portal-data-column"><span class="portal-data-value">${item.value}</span><div class="portal-data-fill" style="height:${height}px;background:${item.color}"></div><span class="portal-data-label">${escapeHtml(item.label)}</span></div>`;
        }).join('')}</div>`;
    }

    function renderCircle() {
        const total = items.reduce((sum,item) => sum + Number(item.value), 0);
        if (!total) { canvas.innerHTML = '<p class="portal-data-empty">No analytics are available yet.</p>'; return; }
        let current = 0;
        const segments = items.map(item => { const start=current; current += Number(item.value)/total*100; return `${item.color} ${start}% ${current}%`; }).join(',');
        canvas.innerHTML = `<div class="portal-data-circle ${chartType === 'doughnut' ? 'doughnut' : ''}" style="background:conic-gradient(${segments})" role="img" aria-label="Dashboard ${chartType} chart"></div>${legend()}`;
    }

    function render() { chartType === 'bar' ? renderBar() : renderCircle(); }
    function closeModal() { modal.hidden=true; document.body.classList.remove('portal-analytics-open'); cards.forEach(card=>card.classList.remove('active')); lastTrigger?.focus(); }
    cards.forEach(card => card.addEventListener('click', () => { lastTrigger=card;cards.forEach(item=>item.classList.toggle('active',item===card));title.textContent=`${card.dataset.label} Analytics`;modal.hidden=false;document.body.classList.add('portal-analytics-open');render();closeButton.focus(); }));
    typeButtons.forEach(button => button.addEventListener('click', () => { chartType=button.dataset.chartType;typeButtons.forEach(item=>item.classList.toggle('active',item===button));render(); }));
    closeButton.addEventListener('click',closeModal);
    modal.addEventListener('click',event=>{if(event.target===modal)closeModal()});
    document.addEventListener('keydown',event=>{if(event.key==='Escape'&&!modal.hidden)closeModal()});
})();
</script>
