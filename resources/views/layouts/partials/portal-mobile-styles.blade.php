<style>
    .mobile-bottom-nav { display:none; }

    @media(max-width:700px) {
        html,body { max-width:100%; overflow-x:hidden; }
        .app,.main { min-width:0; }
        .main { background:#f7f4fa; }
        .content {
            min-width:0;
            padding:18px 14px calc(104px + env(safe-area-inset-bottom, 0px)) !important;
            background:#f7f4fa;
        }
        .topbar { min-width:0; padding:12px 14px !important; }
        .topbar h1 { font-size:clamp(18px,5vw,23px); line-height:1.2; }
        .topbar-start { min-width:0; }
        .topbar-start > div { min-width:0; }
        .topbar-label { font-size:10px; }
        .content .page-header { gap:12px; margin-bottom:17px; }
        .content .page-header h2 { font-size:clamp(22px,6vw,28px); line-height:1.15; }
        .content .page-header :is(.actions,.page-header-actions,.roster-page-actions) {
            display:grid;
            grid-template-columns:repeat(2,minmax(0,1fr));
            width:100%;
        }
        .content .page-header :is(.actions,.page-header-actions,.roster-page-actions) > :only-child { grid-column:1/-1; }
        .content .page-header :is(.actions,.page-header-actions,.roster-page-actions) .button { width:100%; white-space:normal; text-align:center; }
        .content :is(.card,.welcome,.welcome-card,.stat-card) { min-width:0; border-radius:18px; }
        .content .card { margin-bottom:14px; padding:18px !important; }
        .content :is(.filters,.form-grid) > * { min-width:0; }
        .content :is(.filters,.form-grid) :is(input,select,textarea) { min-width:0; max-width:100%; }
        .content .form-actions { flex-wrap:wrap; }
        .content .form-actions .button { min-height:44px; }
        .mobile-bottom-nav {
            position:fixed;
            z-index:1050;
            right:0;
            bottom:0;
            left:0;
            display:grid;
            grid-template-columns:repeat(var(--mobile-nav-count),minmax(0,1fr));
            align-items:stretch;
            min-height:70px;
            padding:6px max(6px,env(safe-area-inset-right,0px)) calc(6px + env(safe-area-inset-bottom,0px)) max(6px,env(safe-area-inset-left,0px));
            background:rgba(255,255,255,.97);
            border-top:1px solid #e7ddec;
            box-shadow:0 -10px 30px rgba(45,4,95,.13);
            backdrop-filter:blur(14px);
            -webkit-backdrop-filter:blur(14px);
        }
        .mobile-bottom-nav-item {
            min-width:0;
            min-height:56px;
            display:flex;
            flex-direction:column;
            align-items:center;
            justify-content:center;
            gap:3px;
            padding:5px 2px;
            color:#6b5b78;
            background:transparent;
            border:0;
            border-radius:12px;
            font-size:10px;
            font-weight:750;
            line-height:1.1;
            text-align:center;
            cursor:pointer;
        }
        .mobile-bottom-nav-item svg { width:21px; height:21px; flex:none; }
        .mobile-bottom-nav-item span { overflow:hidden; max-width:100%; text-overflow:ellipsis; white-space:nowrap; }
        .mobile-bottom-nav-item:is(:hover,:focus-visible,.is-active) { color:var(--primary); background:#f3e9fa; }
        .mobile-bottom-nav-item:focus-visible { outline:2px solid var(--primary); outline-offset:-2px; }
        .content .table-wrap:has(> table.mobile-card-table),
        .content .table-wrapper:has(> table.mobile-card-table) { overflow:visible; background:transparent; border:0; box-shadow:none; }
        .content .table-wrap > table.mobile-card-table,
        .content .table-wrapper > table.mobile-card-table { display:block; width:100% !important; min-width:0 !important; border:0; background:transparent; }
        .content table.mobile-card-table thead {
            position:absolute;
            width:1px;
            height:1px;
            overflow:hidden;
            clip:rect(0,0,0,0);
            white-space:nowrap;
        }
        .content table.mobile-card-table tbody { display:grid; gap:12px; width:100%; }
        .content table.mobile-card-table tbody tr {
            display:block;
            width:100%;
            min-width:0;
            overflow:hidden;
            background:#fff;
            border:1px solid #e5dbea;
            border-radius:15px;
            box-shadow:0 5px 16px rgba(45,4,95,.06);
        }
        .content table.mobile-card-table tbody td {
            display:block;
            width:100% !important;
            min-width:0 !important;
            padding:11px 13px !important;
            border:0;
            border-bottom:1px solid #f0eaf3;
            text-align:left !important;
            white-space:normal !important;
            overflow-wrap:anywhere;
        }
        .content table.mobile-card-table tbody td:last-child { border-bottom:0; }
        .content table.mobile-card-table tbody td::before {
            content:attr(data-mobile-label);
            display:block;
            margin-bottom:4px;
            color:#695b73;
            font-size:10px;
            font-weight:850;
            letter-spacing:.04em;
            line-height:1.45;
            text-transform:uppercase;
        }
        .content table.mobile-card-table tbody td[data-mobile-actions] { display:block; }
        .content table.mobile-card-table tbody td[data-mobile-actions]::before { margin-bottom:9px; }
        .content table.mobile-card-table tbody td[data-mobile-actions] :is(.actions,.action-buttons,.table-actions) { flex-wrap:wrap; }
        .content table.mobile-card-table tbody td[colspan] { display:block; text-align:center !important; }
        .content table.mobile-card-table tbody td[colspan]::before { display:none; }
    }

    @media(max-width:380px) {
        .mobile-bottom-nav-item { font-size:9px; }
        .mobile-bottom-nav-item svg { width:19px; height:19px; }
    }

    @media print { .mobile-bottom-nav { display:none !important; } }
</style>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.content .table-wrap > table, .content .table-wrapper > table').forEach(table => {
            if (table.matches('.schedule-table,.room-usage-table,.archive-table') || table.querySelectorAll('thead tr').length !== 1) return;
            const headings = [...table.querySelectorAll('thead th')];
            if (headings.length < 2 || headings.some(heading => heading.hasAttribute('colspan') || heading.hasAttribute('rowspan'))) return;
            table.querySelectorAll('tbody tr').forEach(row => {
                [...row.children].forEach((cell, index) => {
                    if (cell.tagName !== 'TD' || cell.hasAttribute('colspan')) return;
                    const label = headings[index]?.textContent.trim().replace(/\s+/g, ' ') || '';
                    cell.dataset.mobileLabel = label;
                    if (/^actions?$/i.test(label)) cell.dataset.mobileActions = '';
                });
            });
            table.classList.add('mobile-card-table');
        });
    });
</script>
