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

    html { overscroll-behavior-y:none; }

    body {
        font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
        color:var(--text);
        background:#fff;
        overscroll-behavior-y:none;
    }

    button,input,select,textarea { font:inherit; }
    .app,.main,.content { background:#fff; }
    .main { padding-top:68px; }

    .sidebar {
        width:220px;
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
    .brand-icon--scheduler {
        width:56px;
        height:56px;
        flex:0 0 56px;
        padding:0;
        overflow:hidden;
        background:transparent;
        border:0;
        border-radius:50%;
        box-shadow:0 10px 25px rgba(25,2,47,.24);
    }
    .brand-icon--scheduler img { width:100%; height:100%; object-fit:contain; border-radius:50%; }
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
        position:fixed !important;
        z-index:900 !important;
        top:0;
        right:0;
        left:220px;
        width:auto;
        min-height:68px;
        gap:24px;
        padding:8px clamp(20px,3vw,38px);
        background-color:#450693;
        background-image:
            linear-gradient(165deg,rgba(45,4,95,.98),rgba(69,6,147,.95) 62%,rgba(97,21,163,.93)),
            url("{{ asset('images/landing-background.png') }}");
        background-position:center;
        background-repeat:no-repeat;
        background-size:cover;
        border-bottom:1px solid #2d045f;
        box-shadow:0 5px 24px rgba(48,16,69,.2);
    }

    .topbar-start { display:flex; align-items:center; gap:14px; min-width:0; }
    .topbar-label { display:block; margin-bottom:2px; color:rgba(255,255,255,.68); font-size:7px; font-weight:850; letter-spacing:1.1px; text-transform:uppercase; }
    .topbar h1 { color:#fff; font-size:19px; font-weight:800; letter-spacing:-.45px; }

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

    .portal-profile-menu{position:relative}.portal-profile-menu summary{list-style:none;cursor:pointer;user-select:none}.portal-profile-menu summary::-webkit-details-marker{display:none}.portal-profile-menu>summary.profile{display:block;padding:0;background:transparent;border:0;border-radius:0}
    .portal-profile-trigger{min-width:190px;display:flex;align-items:center;gap:10px;padding:7px 12px 7px 8px;color:#fff;background:linear-gradient(135deg,rgba(255,255,255,.16),rgba(255,255,255,.08));border:1px solid rgba(255,255,255,.25);border-radius:13px;box-shadow:0 10px 28px rgba(24,3,48,.2),inset 0 1px 0 rgba(255,255,255,.12);backdrop-filter:blur(10px);transition:.2s}
    .portal-profile-trigger:hover,.portal-profile-menu[open] .portal-profile-trigger{background:linear-gradient(135deg,rgba(255,255,255,.22),rgba(255,255,255,.12));border-color:rgba(255,255,255,.38);transform:translateY(-1px)}
    .portal-profile-trigger::after{width:7px;height:7px;margin:0 2px 4px auto;content:'';border-right:2px solid rgba(255,255,255,.82);border-bottom:2px solid rgba(255,255,255,.82);transform:rotate(45deg);transition:.2s}.portal-profile-menu[open] .portal-profile-trigger::after{margin-bottom:-2px;transform:rotate(225deg)}
    .portal-profile-avatar{width:37px;height:37px;display:grid;place-items:center;flex:0 0 37px;overflow:hidden;color:#450693;background:rgba(255,255,255,.94);border-radius:9px;box-shadow:0 6px 16px rgba(26,2,45,.18);font-size:12px;font-weight:850}.portal-profile-avatar img{width:100%;height:100%;object-fit:cover}
    .portal-profile-copy{min-width:0}.portal-profile-copy strong{display:block;max-width:145px;overflow:hidden;color:#fff;font-size:12px;white-space:nowrap;text-overflow:ellipsis}.portal-profile-copy small{display:block;margin-top:2px;max-width:145px;overflow:hidden;color:rgba(255,255,255,.68);font-size:9px;white-space:nowrap;text-overflow:ellipsis}
    .portal-profile-dropdown{position:absolute;z-index:1300;top:calc(100% + 10px);right:0;width:190px;padding:8px;background:linear-gradient(155deg,rgba(45,4,95,.98),rgba(69,6,147,.96));border:1px solid rgba(255,255,255,.2);border-radius:12px;box-shadow:0 18px 45px rgba(31,5,57,.3);backdrop-filter:blur(12px)}.portal-profile-dropdown form{margin:0}.portal-profile-action{width:100%;min-height:40px;display:flex;align-items:center;padding:10px 12px;color:rgba(255,255,255,.88);text-align:left;background:transparent;border:0;border-radius:8px;font-size:12px;font-weight:750;cursor:pointer}.portal-profile-action:hover{color:#fff;background:rgba(255,255,255,.14)}
    .portal-profile-modal[hidden]{display:none}.portal-profile-modal{position:fixed;z-index:2100;inset:0;display:grid;place-items:center;padding:20px;background:rgba(31,5,57,.62);backdrop-filter:blur(5px)}.portal-profile-dialog{width:min(640px,100%);max-height:calc(100vh - 40px);padding:26px;overflow-y:auto;color:#24152f;background:rgba(255,255,255,.76);border:1px solid rgba(69,6,147,.2);border-radius:18px;box-shadow:0 28px 80px rgba(24,3,48,.3);backdrop-filter:blur(12px)}
    .portal-profile-header{display:flex;align-items:flex-start;justify-content:space-between;gap:20px;margin-bottom:22px}.portal-profile-header h2{margin-bottom:5px;color:#2d045f;font-size:24px}.portal-profile-header p{color:#55465f;font-size:13px}.portal-profile-close{width:38px;height:38px;display:grid;place-items:center;flex:0 0 38px;color:#64748b;background:rgba(255,255,255,.78);border:1px solid rgba(69,6,147,.14);border-radius:9px;font-size:24px;cursor:pointer}
    .portal-profile-grid{display:grid;grid-template-columns:1fr 1fr;gap:15px}.portal-profile-photo{grid-column:1/-1;display:flex;align-items:center;gap:16px;padding:14px;background:rgba(255,255,255,.45);border:1px solid rgba(69,6,147,.12);border-radius:12px}.portal-profile-photo-preview{width:78px;height:78px;display:grid;place-items:center;flex:0 0 78px;overflow:hidden;color:#fff;background:#450693;border:3px solid rgba(255,255,255,.8);border-radius:50%;font-size:25px;font-weight:850}.portal-profile-photo-preview img{width:100%;height:100%;object-fit:cover}.portal-profile-photo strong{display:block;margin-bottom:4px}.portal-profile-photo p{margin-bottom:9px;color:#675b70;font-size:11px}.portal-profile-photo-button{display:inline-flex;padding:7px 11px;color:#450693;background:rgba(255,255,255,.8);border:1px solid rgba(69,6,147,.18);border-radius:8px;font-size:11px;font-weight:800;cursor:pointer}.portal-profile-photo-button input{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)}
    .portal-profile-field label{display:block;margin-bottom:6px;color:#302638;font-size:11px;font-weight:800}.portal-profile-field .input{min-height:45px;color:#271d2e;background:rgba(255,255,255,.82);border-color:rgba(69,6,147,.18)}.portal-profile-field .input[readonly]{color:#675b70;background:rgba(243,238,246,.75)}.portal-profile-error{display:block;margin-top:5px;color:#b42318;font-size:11px}.portal-profile-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:22px;padding-top:18px;border-top:1px solid rgba(69,6,147,.12)}
    @media(max-width:600px){.portal-profile-modal{padding:12px}.portal-profile-dialog{padding:18px}.portal-profile-grid{grid-template-columns:1fr}.portal-profile-photo{grid-column:auto}.portal-profile-actions{flex-direction:column-reverse}.portal-profile-actions .button{width:100%}}

    .admin-profile-modal[hidden]{display:none}
    .admin-profile-modal{position:fixed;z-index:2100;inset:0;display:grid;place-items:center;padding:20px;background:rgba(31,5,57,.62);backdrop-filter:blur(5px)}
    .admin-profile-dialog{width:min(640px,100%);max-height:calc(100vh - 40px);padding:26px;overflow-y:auto;color:#24152f;background-color:#d9bfea;background-image:var(--portal-panel-image);background-position:center;background-repeat:no-repeat;background-size:cover;border:1px solid rgba(69,6,147,.2);border-radius:18px;box-shadow:0 28px 80px rgba(24,3,48,.3)}
    .admin-profile-header{display:flex;align-items:flex-start;justify-content:space-between;gap:20px;margin-bottom:22px}
    .admin-profile-header h2{margin-bottom:5px;color:#2d045f;font-size:24px}
    .admin-profile-header p{color:#55465f;font-size:13px;line-height:1.5}
    .admin-profile-close{width:38px;height:38px;display:grid;place-items:center;flex:0 0 38px;color:#64748b;background:rgba(255,255,255,.78);border:1px solid rgba(69,6,147,.14);border-radius:9px;font-size:24px;cursor:pointer}
    .admin-profile-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:15px}
    .admin-profile-photo{grid-column:1/-1;display:flex;align-items:center;gap:16px;padding:14px;background:rgba(255,255,255,.45);border:1px solid rgba(69,6,147,.12);border-radius:12px}
    .admin-profile-photo-preview{width:78px;height:78px;display:grid;place-items:center;flex:0 0 78px;overflow:hidden;color:#fff;background:#450693;border:3px solid rgba(255,255,255,.8);border-radius:50%;box-shadow:0 8px 22px rgba(45,4,95,.18);font-size:25px;font-weight:850}
    .admin-profile-photo-preview img{width:100%;height:100%;display:block;object-fit:cover}
    .admin-profile-photo-copy{min-width:0}.admin-profile-photo-copy strong{display:block;margin-bottom:4px;color:#302638;font-size:13px}.admin-profile-photo-copy p{margin-bottom:9px;color:#675b70;font-size:11px;line-height:1.4}
    .admin-profile-photo-input{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap}
    .admin-profile-photo-button{display:inline-flex;align-items:center;min-height:34px;padding:7px 11px;color:#450693;background:rgba(255,255,255,.8);border:1px solid rgba(69,6,147,.18);border-radius:8px;font-size:11px;font-weight:800;cursor:pointer}
    .admin-profile-field.full{grid-column:1/-1}.admin-profile-field label{display:block;margin-bottom:6px;color:#302638;font-size:11px;font-weight:800}
    .admin-profile-field .input{width:100%;min-height:45px;padding:11px 12px;color:#271d2e;background:rgba(255,255,255,.82);border:1px solid rgba(69,6,147,.18);border-radius:9px;outline:none}
    .admin-profile-field .input:focus{border-color:#7022b8;box-shadow:0 0 0 3px rgba(112,34,184,.1)}
    .admin-profile-field .input[readonly]{color:#675b70;background:rgba(243,238,246,.75);cursor:not-allowed}
    .admin-profile-error{display:block;margin-top:5px;color:#b42318;font-size:11px}
    .admin-profile-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:22px;padding-top:18px;border-top:1px solid rgba(69,6,147,.12)}
    @media(max-width:600px){.admin-profile-modal{padding:12px}.admin-profile-dialog{padding:18px}.admin-profile-form-grid{grid-template-columns:1fr}.admin-profile-field.full{grid-column:auto}.admin-profile-actions{align-items:stretch;flex-direction:column-reverse}.admin-profile-actions .button{width:100%}}

    .content { width:100%; max-width:1440px; margin-right:auto; margin-left:auto; padding:clamp(23px,3vw,36px) clamp(18px,3vw,38px) 46px; }
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

    body.dean-department-portal .main,
    body.instructor-department-portal .main,
    body.student-department-portal .main {
        background-color:#8f64bd;
        background-image:url("{{ asset('images/admin-portal-background.png') }}");
        background-position:center;
        background-repeat:no-repeat;
        background-size:cover;
        background-attachment:fixed;
    }

    body.dean-department-portal .content,
    body.instructor-department-portal .content,
    body.student-department-portal .content { background-color:transparent; }

    body.dean-department-portal .content :is(.card,.welcome,.welcome-card,.stat-card),
    body.instructor-department-portal .content :is(.card,.welcome,.welcome-card,.stat-card),
    body.student-department-portal .content :is(.card,.welcome,.welcome-card,.stat-card) {
        color:#24152f;
        background-color:rgba(255,255,255,.68) !important;
        background-image:none !important;
        border-color:rgba(255,255,255,.48) !important;
    }

    body.dean-department-portal .content :is(.card,.welcome,.welcome-card,.stat-card) :is(h1,h2,h3,h4,p,span,strong,label,td,th),
    body.instructor-department-portal .content :is(.card,.welcome,.welcome-card,.stat-card) :is(h1,h2,h3,h4,p,span,strong,label,td,th),
    body.student-department-portal .content :is(.card,.welcome,.welcome-card,.stat-card) :is(h1,h2,h3,h4,p,span,strong,label,td,th) { color:#24152f !important; }

    body.dean-department-portal .content .card .filters,
    body.instructor-department-portal .content .card .filters,
    body.student-department-portal .content .card .filters {
        background-color:rgba(255,255,255,.72);
        background-image:none;
    }

    body.dean-department-portal .content .card :is(.table-wrap,.table-wrapper),
    body.instructor-department-portal .content .card :is(.table-wrap,.table-wrapper),
    body.student-department-portal .content .card :is(.table-wrap,.table-wrapper) { background:rgba(255,255,255,.75); }

    body.dean-department-portal .content .card table,
    body.instructor-department-portal .content .card table,
    body.student-department-portal .content .card table { background:transparent; }

    body.dean-department-portal .content .card th,
    body.instructor-department-portal .content .card th,
    body.student-department-portal .content .card th {
        color:#302039 !important;
        background:rgba(248,243,251,.85);
    }

    body.dean-department-portal .content .card :is(.badge,.role-badge),
    body.instructor-department-portal .content .card :is(.badge,.role-badge),
    body.student-department-portal .content .card :is(.badge,.role-badge) { color:#450693 !important; }

    body.dean-department-portal .content .card .portal-page-button.is-active,
    body.instructor-department-portal .content .card .portal-page-button.is-active,
    body.student-department-portal .content .card .portal-page-button.is-active { color:#fff !important; }

    /* Instructor and Student portals use the Dean portal's white canvas and department watermark. */
    body:is(.instructor-department-portal,.student-department-portal) .main {
        position:relative;
        isolation:isolate;
        background:#fff !important;
        background-image:none !important;
    }

    body:is(.instructor-department-portal,.student-department-portal) .main::before {
        content:'';
        position:fixed;
        z-index:0;
        top:68px;
        right:0;
        bottom:0;
        left:220px;
        background:#fff;
        pointer-events:none;
    }

    body:is(.instructor-department-portal,.student-department-portal) .main::after {
        content:'';
        position:fixed;
        z-index:1;
        top:68px;
        right:0;
        bottom:0;
        left:220px;
        background-image:var(--user-department-logo);
        background-repeat:no-repeat;
        background-position:50% 50%;
        background-size:min(66vmin,720px) min(66vmin,720px);
        opacity:.5;
        mix-blend-mode:multiply;
        pointer-events:none;
    }

    body:is(.instructor-department-portal,.student-department-portal) :is(.topbar,.content) {
        position:relative;
        z-index:2;
    }

    body:is(.instructor-department-portal,.student-department-portal) .content {
        background:transparent !important;
    }

    body:is(.instructor-department-portal,.student-department-portal) .content :is(.card,.welcome,.welcome-card,.stat-card,.stat) {
        color:#180d20;
        background-color:rgba(255,255,255,.68) !important;
        background-image:none !important;
        border-color:rgba(69,6,147,.28) !important;
        box-shadow:0 14px 34px rgba(48,16,69,.085);
        backdrop-filter:blur(2px);
    }

    body:is(.instructor-department-portal,.student-department-portal) .content :is(.card,.welcome,.welcome-card,.stat-card,.stat) :is(h1,h2,h3,h4,p,span,strong,label,td,th) {
        color:#180d20 !important;
    }

    body:is(.instructor-department-portal,.student-department-portal) .content .filters {
        color:#180d20;
        background:rgba(255,255,255,.72) !important;
        border-color:rgba(69,6,147,.22) !important;
    }

    body:is(.instructor-department-portal,.student-department-portal) .content .filters .input {
        color:#180d20;
        background:rgba(255,255,255,.88);
    }

    body:is(.instructor-department-portal,.student-department-portal) .content .filters .input::placeholder {
        color:#3b2944;
        opacity:1;
    }

    body:is(.instructor-department-portal,.student-department-portal) .content .card :is(.table-wrap,.table-wrapper) {
        background:rgba(255,255,255,.75);
    }

    body:is(.instructor-department-portal,.student-department-portal) .content .card th {
        color:#302039 !important;
        background:rgba(248,243,251,.85);
    }

    @media(max-width:950px) {
        body:is(.instructor-department-portal,.student-department-portal) .main::before,
        body:is(.instructor-department-portal,.student-department-portal) .main::after { left:0; }

        body:is(.instructor-department-portal,.student-department-portal) .main::after {
            background-size:min(66vmin,610px) min(66vmin,610px);
        }
    }

    @media(max-width:600px) {
        body:is(.instructor-department-portal,.student-department-portal) .main::before,
        body:is(.instructor-department-portal,.student-department-portal) .main::after { top:110px; }

        body:is(.instructor-department-portal,.student-department-portal) .main::after {
            background-size:min(74vmin,420px) min(74vmin,420px);
        }
    }

    @media(max-width:950px) {
        .topbar { left:0; }
    }

    @media(max-width:700px) {
        .topbar { align-items:flex-start; flex-direction:row; }
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
