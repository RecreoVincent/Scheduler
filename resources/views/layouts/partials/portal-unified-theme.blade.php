<style>
    :root {
        --primary:#450693;
        --primary-dark:#2d045f;
        --primary-light:#7022b8;
        --gold:#450693;
        --gold-dark:#450693;
        --gold-soft:#f4edfa;
        --navy:#2d045f;
        --text:#4e4557;
        --muted:#7b7183;
        --bg:#f6f2f8;
        --background:#f6f2f8;
        --surface:#ffffff;
        --border:#e5dbea;
        --sidebar:#450693;
        --danger:#c62828;
        --success:#16835f;
        --portal-panel-image:
            linear-gradient(rgba(255,255,255,.60),rgba(255,255,255,.60)),
            url("{{ asset('images/landing-background.png') }}");
    }

    body {
        font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
        color:var(--text);
        background:#fff;
    }

    button,input,select,textarea { font:inherit; }
    .app,.main,.content { background:#fff; }

    .sidebar {
        width:272px;
        padding:24px 18px 22px;
        overflow-x:hidden !important;
        overflow-y:auto;
        color:#fff;
        background-image:
            linear-gradient(165deg,rgba(45,4,95,.98),rgba(69,6,147,.95) 62%,rgba(97,21,163,.93)),
            url("{{ asset('images/landing-background.png') }}");
        background-position:center;
        background-size:cover;
        border:0;
        box-shadow:12px 0 38px rgba(39,4,73,.22);
    }

    .sidebar::before {
        content:'';
        position:absolute;
        width:245px;
        height:245px;
        top:-165px;
        right:-155px;
        border:1px solid rgba(255,255,255,.11);
        border-radius:50%;
        box-shadow:0 0 0 52px rgba(255,255,255,.025),0 0 0 104px rgba(255,255,255,.018);
        pointer-events:none;
    }

    .brand {
        position:relative;
        display:flex;
        align-items:center;
        gap:12px;
        margin:0 0 21px;
        padding:0 8px;
        color:#fff;
        font-size:18px;
        font-weight:850;
        letter-spacing:-.35px;
    }

    .brand-icon {
        width:48px;
        height:48px;
        display:grid;
        place-items:center;
        flex:0 0 48px;
        padding:4px;
        color:var(--primary);
        background:rgba(255,255,255,.96);
        border:1px solid rgba(255,255,255,.72);
        border-radius:13px;
        box-shadow:0 10px 25px rgba(25,2,47,.24);
    }

    .brand-icon img { width:100%; height:100%; object-fit:contain; }
    .brand-icon--mcc img { border-radius:0; }
    .brand-icon--department {
        width:82px!important;
        height:82px!important;
        flex:0 0 82px!important;
        padding:0;
        overflow:hidden;
        background:transparent;
        border:0;
        border-radius:50%;
        box-shadow:0 10px 25px rgba(25,2,47,.22);
    }
    .brand-icon--department img {
        width:82px!important;
        height:82px!important;
        object-fit:cover;
        border-radius:50%;
        clip-path:circle(50% at 50% 50%);
        transform:none!important;
    }
    .brand-copy { min-width:0; }
    .brand-copy strong { display:block; color:#fff; font-size:17px; }
    .brand-copy small { display:block; margin-top:3px; color:rgba(255,255,255,.58); font-size:8px; font-weight:750; letter-spacing:1px; text-transform:uppercase; }

    .department-chip,.portal-chip {
        position:relative;
        display:flex;
        align-items:center;
        gap:9px;
        margin:0 7px 23px;
        padding:10px 12px;
        color:rgba(255,255,255,.88);
        text-align:left;
        background:rgba(255,255,255,.085);
        border:1px solid rgba(255,255,255,.15);
        border-radius:10px;
        font-size:10px;
        font-weight:750;
    }

    .department-dot,.portal-chip::before {
        content:'';
        width:8px;
        height:8px;
        flex:0 0 8px;
        background:#fff;
        border-radius:50%;
        box-shadow:0 0 0 4px rgba(255,255,255,.12);
    }

    .menu-label {
        margin:19px 12px 8px;
        color:rgba(255,255,255,.4);
        font-size:9px;
        font-weight:850;
        letter-spacing:1.3px;
        text-transform:uppercase;
    }

    .menu-link,.logout-button {
        position:relative;
        width:100%;
        display:flex;
        align-items:center;
        gap:11px;
        margin-bottom:5px;
        padding:11px 13px;
        color:rgba(255,255,255,.72);
        text-align:left;
        background:transparent;
        border:0;
        border-radius:9px;
        font-size:12px;
        font-weight:680;
        cursor:pointer;
        transition:background .18s ease,color .18s ease,transform .18s ease;
    }

    .menu-link:hover,.menu-link.active,.logout-button:hover {
        color:#fff;
        background:rgba(255,255,255,.12);
        box-shadow:inset 0 0 0 1px rgba(255,255,255,.08);
    }

    .menu-link.active { font-weight:800; }
    .menu-link.active::after {
        content:'';
        position:absolute;
        width:3px;
        height:55%;
        top:50%;
        right:0;
        background:#fff;
        border-radius:3px 0 0 3px;
        transform:translateY(-50%);
    }

    .menu-icon,.menu-link:hover .menu-icon,.menu-link.active .menu-icon {
        width:20px;
        flex:0 0 20px;
        color:rgba(255,255,255,.78);
        font-size:17px;
        line-height:1;
        text-align:center;
    }

    .topbar {
        min-height:84px;
        gap:24px;
        padding:14px clamp(20px,3vw,38px);
        background-color:#d9bfea;
        background-image:var(--portal-panel-image);
        background-position:center;
        background-repeat:no-repeat;
        background-size:cover;
        border-bottom:1px solid rgba(69,6,147,.18);
        box-shadow:0 5px 24px rgba(48,16,69,.07);
    }

    .topbar-start { display:flex; align-items:center; gap:14px; min-width:0; }
    .topbar-label { display:block; margin-bottom:3px; color:var(--primary); font-size:8px; font-weight:850; letter-spacing:1.15px; text-transform:uppercase; }
    .topbar h1 { color:var(--navy); font-size:21px; font-weight:800; letter-spacing:-.5px; }

    .profile {
        display:flex;
        align-items:center;
        gap:10px;
        padding:6px 10px 6px 7px;
        text-align:left;
        background:rgba(255,255,255,.72);
        border:1px solid rgba(69,6,147,.14);
        border-radius:11px;
    }

    .profile-avatar {
        width:37px;
        height:37px;
        display:grid;
        place-items:center;
        flex:0 0 37px;
        color:#fff;
        background:var(--primary);
        border-radius:9px;
        font-size:12px;
        font-weight:850;
    }

    .profile .profile-avatar { color:#fff; font-size:12px; margin-top:0; }

    .profile-copy,.profile > div { min-width:0; text-align:left; }
    .profile-copy strong,.profile-name,.profile strong { display:block; max-width:200px; overflow:hidden; color:#302638; font-size:11px; font-weight:800; white-space:nowrap; text-overflow:ellipsis; }
    .profile-copy span,.profile-role { display:block; margin-top:2px; color:var(--muted); font-size:9px; }

    .content { width:100%; padding:clamp(23px,3vw,36px) clamp(18px,3vw,38px) 46px; }
    .page-header { margin-bottom:23px; }
    .page-header h2 { margin-bottom:5px; color:var(--navy); font-size:23px; letter-spacing:-.6px; }
    .page-header p { color:var(--navy); font-size:12px; line-height:1.55; }

    .welcome,.welcome-card {
        margin-bottom:24px;
        padding:clamp(22px,3vw,30px);
        color:#302638;
        background-color:#d9bfea;
        background-image:var(--portal-panel-image);
        background-position:center;
        background-repeat:no-repeat;
        background-size:cover;
        border:1px solid rgba(69,6,147,.18);
        border-radius:18px;
        box-shadow:0 14px 34px rgba(48,16,69,.09);
    }

    .welcome h2,.welcome-card h2 { color:var(--navy); }
    .welcome p,.welcome-card p { color:#55465f; }

    .card,.stat-card {
        color:#302638;
        background-color:#d9bfea;
        background-image:var(--portal-panel-image);
        background-position:center;
        background-repeat:no-repeat;
        background-size:cover;
        border:1px solid rgba(69,6,147,.18);
        border-radius:15px;
        box-shadow:0 13px 32px rgba(48,16,69,.085);
    }

    .card { padding:24px; }
    .card h3 { color:var(--navy); }
    .card p,.stat-card span { color:#55465f; }
    .stat-card::after { background:rgba(69,6,147,.10) !important; }
    .stat-card strong { color:var(--navy) !important; }
    .stat-card:hover,.stat-card:focus-visible,.stat-card.active { border-color:var(--primary) !important; box-shadow:0 14px 30px rgba(69,6,147,.11) !important; }

    .button {
        min-height:40px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:7px;
        padding:9px 16px;
        color:#fff;
        background:var(--primary);
        border:1px solid var(--primary);
        border-radius:9px;
        box-shadow:0 7px 17px rgba(69,6,147,.14);
        font-size:11px;
        font-weight:800;
        cursor:pointer;
        transition:transform .18s ease,background .18s ease,border-color .18s ease;
    }

    .button:hover { color:#fff; background:var(--primary-light); border-color:var(--primary-light); transform:translateY(-1px); }
    .button-secondary { color:var(--primary); background:#f5eefb; border-color:#dac6e9; box-shadow:none; }
    .button-secondary:hover { color:#fff; background:var(--primary); border-color:var(--primary); }
    .button-danger { color:var(--danger); background:#fff5f4; border-color:#fecaca; box-shadow:none; }
    .button-danger:hover { color:#fff; background:var(--danger); border-color:var(--danger); }

    .input,input.input,select.input,textarea.input {
        min-height:43px;
        padding:10px 12px;
        color:#322939;
        background:rgba(255,255,255,.72);
        border:1px solid rgba(255,255,255,.8);
        border-radius:9px;
        font-size:12px;
        outline:none;
        transition:border-color .18s ease,box-shadow .18s ease;
    }

    .input:focus,input.input:focus,select.input:focus,textarea.input:focus { border-color:var(--primary-light); box-shadow:0 0 0 3px rgba(69,6,147,.09); }
    label { color:#413649; font-size:11px; font-weight:750; }
    .filters {
        padding:15px;
        color:#302638;
        background-color:#d9bfea;
        background-image:var(--portal-panel-image);
        background-position:center;
        background-repeat:no-repeat;
        background-size:cover;
        border:1px solid rgba(69,6,147,.18);
        border-radius:11px;
    }
    .form-actions { flex-wrap:wrap; padding-top:18px; border-top:1px solid #eee8f1; }

    .table-wrap { overflow-x:auto; background:rgba(255,255,255,.68); border:1px solid rgba(69,6,147,.14); border-radius:11px; }
    table { background:rgba(255,255,255,.42); }
    th,td { padding:13px; border-bottom:1px solid #eee8f1; }
    th { color:#665c6f; background:rgba(248,243,251,.72); font-size:9px; font-weight:850; letter-spacing:.65px; text-transform:uppercase; }
    td { color:#4e4557; font-size:11px; }
    tbody tr:hover { background:rgba(255,255,255,.58); }
    .badge { color:var(--primary); background:#f2e7fa; border:1px solid #e4d0f1; font-size:9px; font-weight:800; }
    .schedule-time { color:var(--primary) !important; }

    .chart-modal,.delete-modal { background:rgba(31,5,57,.62); backdrop-filter:blur(5px); }
    .chart-panel,.delete-dialog {
        color:#302638;
        background-color:#d9bfea;
        background-image:var(--portal-panel-image);
        background-position:center;
        background-repeat:no-repeat;
        background-size:cover;
        border-top:4px solid var(--primary);
        border-radius:15px;
        box-shadow:0 28px 80px rgba(24,3,48,.28);
    }
    .chart-close:hover,.chart-close:focus-visible { color:var(--primary); background:#f4edfa; outline:2px solid #d8c0e8; }
    .chart-toolbar { background:#f4edfa; }
    .chart-type-button.active { color:#fff; background:var(--primary); }
    .card-title,.delete-dialog h2 { color:var(--navy); }
    .role-badge { color:var(--primary); background:#f2e7fa; }
    .quick-link { color:var(--primary); background:#f5eefb; border-color:#dfceeb; }
    .quick-link:hover { background:#eadcf4; }
    .pagination-link:hover { color:var(--primary); background:#f5eefb; border-color:#caa8df; }
    .pagination-link.active { color:#fff; background:var(--primary); border-color:var(--primary); }
    .portal-pagination {
        display:flex;
        justify-content:center;
        align-items:center;
        flex-wrap:wrap;
        gap:6px;
        margin-top:18px;
    }
    .portal-page-button {
        width:42px;
        height:42px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        flex:0 0 42px;
        padding:0;
        color:#465268;
        background:rgba(255,255,255,.74);
        border:1px solid rgba(255,255,255,.9);
        border-radius:9px;
        box-shadow:0 6px 16px rgba(47,18,68,.06);
        font-size:14px;
        font-weight:800;
        line-height:1;
        text-decoration:none;
        transition:color .18s ease,background .18s ease,border-color .18s ease,box-shadow .18s ease,transform .18s ease;
    }
    .portal-page-button svg {
        width:18px;
        height:18px;
        fill:none;
        stroke:currentColor;
        stroke-width:2.25;
        stroke-linecap:round;
        stroke-linejoin:round;
    }
    .portal-page-button:hover,
    .portal-page-button:focus-visible {
        color:var(--primary);
        background:#fff;
        border-color:#d7bee7;
        box-shadow:0 8px 19px rgba(69,6,147,.13);
        outline:none;
        transform:translateY(-1px);
    }
    .portal-page-button.is-active {
        color:#fff;
        background:var(--primary);
        border-color:var(--primary);
        box-shadow:0 8px 20px rgba(69,6,147,.22);
        pointer-events:none;
    }
    .portal-page-button.is-disabled {
        color:#9aa9bb;
        background:rgba(255,255,255,.62);
        border-color:rgba(255,255,255,.76);
        box-shadow:none;
        cursor:not-allowed;
        pointer-events:none;
    }
    .portal-page-ellipsis {
        min-width:22px;
        color:#766b7f;
        text-align:center;
        font-size:18px;
        font-weight:800;
    }
    .table-wrapper { overflow-x:auto; background:rgba(255,255,255,.68); border:1px solid rgba(69,6,147,.14); border-radius:11px; }
    .scan-frame { border-color:var(--primary) !important; }

    .notice-modal,.notification-modal { background:rgba(31,5,57,.65); backdrop-filter:blur(5px); }
    .notice-dialog,.notification-dialog {
        color:#302638;
        background-color:#d9bfea;
        background-image:var(--portal-panel-image);
        background-position:center;
        background-repeat:no-repeat;
        background-size:cover;
        border-top:4px solid var(--primary);
        border-radius:15px;
        box-shadow:0 28px 80px rgba(24,3,48,.3);
    }
    .notice-dialog h2,.notification-dialog h2 { color:var(--navy); }
    .assignment-notice { color:#5c2978; background:#f8f0fc; border-color:#dfc9ec; }

    .stat,.analytics-dialog,.archive-dialog,.confirmation-dialog,.delete-confirmation-dialog,.section-delete-dialog,.room-qr-dialog,.section-item,.section-empty,.instructor-option {
        color:#302638;
        background-color:#d9bfea !important;
        background-image:var(--portal-panel-image) !important;
        background-position:center !important;
        background-repeat:no-repeat !important;
        background-size:cover !important;
        border-color:rgba(69,6,147,.18) !important;
        box-shadow:0 14px 34px rgba(48,16,69,.085);
    }

    .stat span,
    .analytics-dialog p,
    .archive-dialog p,
    .confirmation-dialog p,
    .delete-confirmation-dialog p,
    .section-delete-dialog p,
    .room-qr-dialog p { color:#55465f; }

    .section-page-link,.pagination-link { background:rgba(255,255,255,.66); border-color:rgba(255,255,255,.75); }

    .sidebar-toggle { color:var(--primary); border-color:var(--border); border-radius:10px; box-shadow:0 7px 18px rgba(69,6,147,.08); }
    .sidebar-backdrop { background:rgba(31,5,57,.58); }

    @media(max-width:700px) {
        .topbar { align-items:flex-start; }
        .profile { max-width:52%; }
        .content { padding-top:22px; }
        .page-header { align-items:flex-start; flex-direction:column; }
        .page-header .button { width:100%; }
        .card { padding:18px; }
    }

    @media(max-width:520px) {
        .topbar { gap:12px; }
        .profile-copy strong,.profile-name,.profile strong { max-width:110px; }
        .welcome,.welcome-card { padding:20px; }
        .portal-pagination { gap:5px; }
        .portal-page-button { width:38px; height:38px; flex-basis:38px; border-radius:8px; font-size:13px; }
        .portal-page-button svg { width:17px; height:17px; }
    }
</style>
