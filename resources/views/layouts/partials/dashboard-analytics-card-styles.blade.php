<style>
    .portal-analytics-grid {
        display:grid;
        grid-template-columns:repeat(auto-fit,minmax(210px,1fr));
        gap:14px;
        margin-bottom:24px;
    }
    .portal-analytics-card {
        --analytics-accent:#450693;
        --analytics-progress:100%;
        width:100%;
        min-height:170px;
        display:block;
        padding:16px;
        overflow:hidden;
        color:#24152f;
        font:inherit;
        text-align:left;
        background:rgba(255,255,255,.68);
        border:1px solid rgba(69,6,147,.22);
        border-radius:16px;
        transition:transform .2s ease,border-color .2s ease,box-shadow .2s ease;
    }
    button.portal-analytics-card { cursor:pointer; }
    button.portal-analytics-card:hover,
    button.portal-analytics-card:focus-visible,
    button.portal-analytics-card.active {
        transform:translateY(-3px);
        border-color:var(--analytics-accent);
        box-shadow:0 12px 25px rgba(69,6,147,.12);
        outline:none;
    }
    .portal-analytics-header {
        display:flex;
        align-items:center;
        gap:9px;
        padding-bottom:11px;
        border-bottom:1px solid rgba(69,6,147,.14);
    }
    .portal-analytics-icon {
        width:29px;
        height:29px;
        display:grid;
        flex:0 0 29px;
        place-items:center;
        color:var(--analytics-accent) !important;
        background:rgba(255,255,255,.68);
        border-radius:9px;
    }
    .portal-analytics-icon svg { width:17px;height:17px;fill:none;stroke:currentColor;stroke-width:2; }
    .portal-analytics-title { margin:0 !important;font-size:15px !important;font-weight:800 !important;color:#24152f !important;text-transform:none !important; }
    .portal-analytics-metric { display:flex;align-items:center;justify-content:space-between;gap:12px;padding:13px 0 11px; }
    .portal-analytics-label { margin:0 !important;font-size:12px !important;font-weight:650 !important;color:#4b3d55 !important;text-transform:none !important; }
    .portal-analytics-value { margin:0 !important;font-size:23px !important;font-weight:850 !important;line-height:1;color:#24152f !important; }
    .portal-analytics-progress { width:100%;height:6px;display:block;margin:0;overflow:hidden;background:rgba(148,163,184,.24);border-radius:999px; }
    .portal-analytics-progress-fill { width:var(--analytics-progress);height:6px;display:block;margin:0;background:var(--analytics-accent);border-radius:inherit; }
    .portal-analytics-footer { width:100%;display:block;margin:9px 0 0 !important;padding:7px 9px;font-size:10px !important;font-weight:700 !important;color:#554a65 !important;background:rgba(248,250,252,.72);border-radius:9px;text-transform:none !important;white-space:nowrap; }
    body.portal-analytics-open { overflow:hidden; }
    .portal-data-modal[hidden] { display:none; }
    .portal-data-modal {
        position:fixed;
        z-index:1000;
        top:68px;
        right:0;
        bottom:0;
        left:220px;
        display:grid;
        place-items:center;
        padding:24px;
        background:rgba(15,23,42,.58);
        backdrop-filter:blur(3px);
    }
    .portal-data-dialog {
        width:min(1100px,100%);
        max-height:calc(100vh - 132px);
        padding:26px;
        overflow-y:auto;
        color:#24152f;
        background-color:#d9bfea !important;
        background-image:var(--portal-panel-image) !important;
        background-position:center !important;
        background-repeat:no-repeat !important;
        background-size:cover !important;
        border:1px solid rgba(69,6,147,.32);
        border-radius:18px;
        box-shadow:0 24px 65px rgba(15,23,42,.25);
        backdrop-filter:blur(8px);
    }
    .portal-data-header,.portal-data-actions,.portal-data-layout,.portal-data-legend-item { display:flex;align-items:center; }
    .portal-data-header { justify-content:space-between;gap:18px;margin-bottom:24px; }
    .portal-data-header h2 { margin:0 0 5px;color:#2d045f !important; }
    .portal-data-header p { margin:0;color:#4b3d55 !important; }
    .portal-data-actions { gap:12px; }
    .portal-data-toolbar { display:flex;gap:6px;padding:5px;background:rgba(243,233,250,.9);border-radius:10px; }
    .portal-data-type { padding:8px 12px;font-size:12px;font-weight:750;color:#554a65;background:transparent;border:0;border-radius:7px;cursor:pointer; }
    .portal-data-type.active { color:#fff;background:#450693; }
    .portal-data-close { width:40px;height:40px;font-size:25px;line-height:1;color:#64748b;background:#f8fafc;border:1px solid #e2e8f0;border-radius:9px;cursor:pointer; }
    .portal-data-layout { justify-content:center;gap:38px;min-height:280px; }
    .portal-data-bars { width:100%;height:280px;display:flex;align-items:flex-end;justify-content:space-around;gap:12px;padding:28px 8px 0;border-bottom:1px solid #cbd5e1; }
    .portal-data-column { height:100%;min-width:55px;flex:1;display:flex;flex-direction:column;align-items:center;justify-content:flex-end; }
    .portal-data-value { margin-bottom:7px;font-size:13px;font-weight:800;color:#24152f; }
    .portal-data-fill { width:min(64px,75%);min-height:4px;border-radius:9px 9px 0 0; }
    .portal-data-label { margin-top:8px;font-size:11px;font-weight:650;color:#554a65;text-align:center; }
    .portal-data-circle { position:relative;flex:0 0 230px;width:230px;height:230px;border-radius:50%; }
    .portal-data-circle.doughnut::after { position:absolute;inset:57px;content:'';background:rgba(255,255,255,.96);border-radius:50%;box-shadow:0 0 0 1px #e2e8f0; }
    .portal-data-legend { min-width:220px;display:grid;gap:11px; }
    .portal-data-legend-item { justify-content:space-between;gap:20px;font-size:13px; }
    .portal-data-legend-label { display:flex;align-items:center;gap:8px; }
    .portal-data-legend-color { width:11px;height:11px;border-radius:3px; }
    .portal-data-empty { color:#64748b; }
    @media(max-width:950px){.portal-data-modal{left:0}.portal-data-header,.portal-data-layout{align-items:stretch;flex-direction:column}.portal-data-circle{margin:auto}}
    @media(max-width:520px){.portal-data-modal{padding:12px}.portal-data-dialog{padding:18px}.portal-data-actions{align-items:stretch;flex-direction:column}.portal-data-toolbar{flex-wrap:wrap}.portal-data-bars{overflow-x:auto}}
    @media(max-width:520px){.portal-analytics-grid{grid-template-columns:1fr}}
</style>
