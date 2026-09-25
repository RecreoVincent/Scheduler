<style>
    :root {
        --primary:#450693;
        --primary-dark:#2d045f;
        --primary-light:#7022b8;
        --gold:#e8b84a;
        --gold-dark:#805000;
        --gold-soft:#fff6df;
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

    .student-department-chip { display:grid; grid-template-columns:8px minmax(0,1fr); column-gap:9px; row-gap:2px; }
    .student-department-chip .department-dot { grid-row:1 / span 2; align-self:center; }
    .student-department-line,.student-section-line { min-width:0; }
    .student-section-line { color:rgba(255,255,255,.64); font-size:9px; font-weight:650; }

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

    .personal-semester-menu { position:relative; }
    .personal-semester-menu summary { list-style:none; cursor:pointer; user-select:none; }
    .personal-semester-menu summary::-webkit-details-marker { display:none; }
    .personal-semester-trigger { width:42px; height:42px; display:grid; place-items:center; color:var(--primary); background:rgba(255,255,255,.9); border:1px solid rgba(255,255,255,.62); border-radius:11px; font-size:18px; box-shadow:0 7px 18px rgba(25,2,47,.16); transition:.2s; }
    .personal-semester-trigger svg { width:19px; height:19px; }
    .personal-semester-menu[open] .personal-semester-trigger,.personal-semester-trigger:hover { color:#fff; background:var(--primary); }
    .personal-semester-dropdown { position:absolute; z-index:1300; top:calc(100% + 10px); right:0; width:290px; padding:18px; color:#302638; background:#fff; border:1px solid rgba(69,6,147,.16); border-radius:14px; box-shadow:0 18px 45px rgba(31,5,57,.24); }
    .personal-semester-dropdown strong { display:block; margin-bottom:5px; color:var(--navy); font-size:13px; }
    .personal-semester-dropdown p { margin:0 0 12px; color:var(--muted); font-size:11px; line-height:1.5; }
    .personal-semester-row { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:9px 0; color:#302638; font-size:12px; font-weight:700; border-top:1px solid #f0eaf4; cursor:pointer; }
    .personal-semester-row:first-of-type { border-top:0; }
    .personal-semester-switch { position:relative; display:inline-block; width:38px; height:22px; flex:0 0 38px; }
    .personal-semester-switch input { position:absolute; inset:0; width:100%; height:100%; margin:0; opacity:0; cursor:pointer; z-index:1; }
    .personal-semester-track { position:absolute; inset:0; background:#d8cedf; border-radius:999px; transition:.2s; }
    .personal-semester-track::before { content:''; position:absolute; width:16px; height:16px; top:3px; left:3px; background:#fff; border-radius:50%; box-shadow:0 1px 3px rgba(0,0,0,.25); transition:.2s; }
    .personal-semester-switch input:checked + .personal-semester-track { background:var(--primary); }
    .personal-semester-switch input:checked + .personal-semester-track::before { transform:translateX(16px); }
    .personal-semester-switch input:focus-visible + .personal-semester-track { box-shadow:0 0 0 3px rgba(69,6,147,.25); }

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

    .portal-sidebar{display:flex;flex-direction:column;overflow:hidden !important}.portal-sidebar-navigation{width:calc(100% + 36px);min-height:0;flex:1 1 auto;margin:0 -18px;padding:0 18px;overflow-y:auto;overflow-x:hidden}.portal-sidebar-logout{position:relative;z-index:1;flex:0 0 auto;margin-top:auto;padding:16px 2px 2px;border-top:1px solid rgba(255,255,255,.16)}.portal-sidebar-logout-button{width:100%;min-height:44px;display:flex;align-items:center;gap:10px;padding:10px 12px;color:rgba(255,255,255,.92);background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);border-radius:10px;font-size:12px;font-weight:800;cursor:pointer;transition:.2s}.portal-sidebar-logout-button svg{width:17px;height:17px;flex:0 0 17px}.portal-sidebar-logout-button:hover{color:#fff;background:rgba(255,255,255,.18);transform:translateY(-1px)}
    .portal-profile-menu{position:relative}.portal-profile-menu>.profile{display:block;padding:0;background:transparent;border:0;border-radius:0}
    .portal-profile-trigger{min-width:190px;display:flex;align-items:center;gap:10px;padding:7px 12px 7px 8px;color:#fff;background:linear-gradient(135deg,rgba(255,255,255,.16),rgba(255,255,255,.08));border:1px solid rgba(255,255,255,.25);border-radius:13px;box-shadow:0 10px 28px rgba(24,3,48,.2),inset 0 1px 0 rgba(255,255,255,.12);backdrop-filter:blur(10px);transition:.2s}
    .portal-profile-avatar{width:37px;height:37px;display:grid;place-items:center;flex:0 0 37px;overflow:hidden;color:#450693;background:rgba(255,255,255,.94);border-radius:9px;box-shadow:0 6px 16px rgba(26,2,45,.18);font-size:12px;font-weight:850}.portal-profile-avatar img{width:100%;height:100%;object-fit:cover}
    .portal-profile-copy{min-width:0}.portal-profile-copy strong{display:block;max-width:145px;overflow:hidden;color:#fff;font-size:12px;white-space:nowrap;text-overflow:ellipsis}.portal-profile-copy small{display:block;margin-top:2px;max-width:145px;overflow:hidden;color:rgba(255,255,255,.68);font-size:9px;white-space:nowrap;text-overflow:ellipsis}
    .portal-profile-dropdown{position:absolute;z-index:1300;top:calc(100% + 10px);right:0;width:190px;padding:8px;background:linear-gradient(155deg,rgba(45,4,95,.98),rgba(69,6,147,.96));border:1px solid rgba(255,255,255,.2);border-radius:12px;box-shadow:0 18px 45px rgba(31,5,57,.3);backdrop-filter:blur(12px)}.portal-profile-dropdown form{margin:0}.portal-profile-action{width:100%;min-height:40px;display:flex;align-items:center;gap:9px;padding:10px 12px;color:rgba(255,255,255,.88);text-align:left;background:transparent;border:0;border-radius:8px;font-size:12px;font-weight:750;cursor:pointer}.portal-profile-action-icon{width:16px;height:16px;flex:0 0 16px}.portal-profile-action:hover{color:#fff;background:rgba(255,255,255,.14)}
    .portal-profile-modal[hidden]{display:none}.portal-profile-modal{position:fixed;z-index:2100;inset:0;display:grid;place-items:center;padding:20px;background:rgba(31,5,57,.62);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px)}.portal-profile-dialog{width:min(640px,100%);max-height:calc(100vh - 40px);padding:26px;overflow-y:auto;color:#24152f;background:rgba(255,255,255,.76);border:1px solid rgba(69,6,147,.2);border-radius:18px;box-shadow:0 28px 80px rgba(24,3,48,.3);backdrop-filter:blur(12px)}
    .portal-profile-header{display:flex;align-items:flex-start;justify-content:space-between;gap:20px;margin-bottom:22px}.portal-profile-header h2{margin-bottom:5px;color:#2d045f;font-size:24px}.portal-profile-header p{color:#55465f;font-size:13px}.portal-profile-close{width:38px;height:38px;display:grid;place-items:center;flex:0 0 38px;color:#64748b;background:rgba(255,255,255,.78);border:1px solid rgba(69,6,147,.14);border-radius:9px;font-size:24px;cursor:pointer}
    .portal-profile-grid{display:grid;grid-template-columns:1fr 1fr;gap:15px}.portal-profile-photo{grid-column:1/-1;display:flex;align-items:center;gap:16px;padding:14px;background:rgba(255,255,255,.45);border:1px solid rgba(69,6,147,.12);border-radius:12px}.portal-profile-photo-preview{width:78px;height:78px;display:grid;place-items:center;flex:0 0 78px;overflow:hidden;color:#fff;background:#450693;border:3px solid rgba(255,255,255,.8);border-radius:50%;font-size:25px;font-weight:850}.portal-profile-photo-preview img{width:100%;height:100%;object-fit:cover}.portal-profile-photo strong{display:block;margin-bottom:4px}.portal-profile-photo p{margin-bottom:9px;color:#675b70;font-size:11px}.portal-profile-photo-button{display:inline-flex;padding:7px 11px;color:#450693;background:rgba(255,255,255,.8);border:1px solid rgba(69,6,147,.18);border-radius:8px;font-size:11px;font-weight:800;cursor:pointer}.portal-profile-photo-button input{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)}
    .portal-profile-field label{display:block;margin-bottom:6px;color:#302638;font-size:11px;font-weight:800}.portal-profile-field .input{min-height:45px;color:#271d2e;background:rgba(255,255,255,.82);border-color:rgba(69,6,147,.18)}.portal-profile-field .input[readonly]{color:#675b70;background:rgba(243,238,246,.75)}.portal-profile-error{display:block;margin-top:5px;color:#b42318;font-size:11px}.portal-profile-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:22px;padding-top:18px;border-top:1px solid rgba(69,6,147,.12)}
    @media(max-width:600px){.portal-profile-modal{padding:12px}.portal-profile-dialog{padding:18px}.portal-profile-grid{grid-template-columns:1fr}.portal-profile-photo{grid-column:auto}.portal-profile-actions{flex-direction:column-reverse}.portal-profile-actions .button{width:100%}}

    .admin-profile-modal[hidden]{display:none}
    .admin-profile-modal{position:fixed;z-index:2100;inset:0;display:grid;place-items:center;padding:20px;background:rgba(31,5,57,.62);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px)}
    .admin-profile-dialog{width:min(640px,100%);max-height:calc(100vh - 40px);padding:32px;overflow-y:auto;color:#24152f;background-color:#d9bfea;background-image:var(--portal-panel-image);background-position:center;background-repeat:no-repeat;background-size:cover;border:1px solid rgba(69,6,147,.2);border-radius:26px;box-shadow:0 6px 14px rgba(24,3,48,.18),0 36px 90px rgba(24,3,48,.32)}
    .admin-profile-header{display:flex;align-items:flex-start;justify-content:space-between;gap:20px;margin-bottom:24px}
    .admin-profile-header h2{margin-bottom:5px;color:#2d045f;font-size:26px;font-weight:850}
    .admin-profile-header p{color:#55465f;font-size:13px;line-height:1.5}
    .admin-profile-close{width:38px;height:38px;display:grid;place-items:center;flex:0 0 38px;color:#64748b;background:rgba(255,255,255,.78);border:1px solid rgba(69,6,147,.14);border-radius:10px;font-size:22px;cursor:pointer;transition:background .16s ease,color .16s ease}
    .admin-profile-close:hover{color:var(--primary);background:#fff}
    .admin-profile-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
    .admin-profile-photo{grid-column:1/-1;display:flex;align-items:center;gap:16px;padding:14px;background:rgba(255,255,255,.45);border:1px solid rgba(69,6,147,.12);border-radius:13px}
    .admin-profile-photo-preview{width:78px;height:78px;display:grid;place-items:center;flex:0 0 78px;overflow:hidden;color:#fff;background:#450693;border:3px solid rgba(255,255,255,.8);border-radius:50%;box-shadow:0 8px 22px rgba(45,4,95,.18);font-size:25px;font-weight:850}
    .admin-profile-photo-preview img{width:100%;height:100%;display:block;object-fit:cover}
    .admin-profile-photo-copy{min-width:0}.admin-profile-photo-copy strong{display:block;margin-bottom:4px;color:#302638;font-size:13px}.admin-profile-photo-copy p{margin-bottom:9px;color:#675b70;font-size:11px;line-height:1.4}
    .admin-profile-photo-input{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap}
    .admin-profile-photo-button{display:inline-flex;align-items:center;min-height:34px;padding:7px 11px;color:#450693;background:rgba(255,255,255,.8);border:1px solid rgba(69,6,147,.18);border-radius:9px;font-size:11px;font-weight:800;cursor:pointer;transition:background .16s ease}
    .admin-profile-photo-button:hover{background:#fff}
    .admin-profile-field.full{grid-column:1/-1}.admin-profile-field label{display:block;margin-bottom:6px;color:#302638;font-size:11px;font-weight:800}
    .admin-profile-field .input{width:100%;min-height:45px;padding:11px 12px;color:#271d2e;background:rgba(255,255,255,.82);border:1.5px solid rgba(69,6,147,.4);border-radius:10px;outline:none;transition:border-color .16s ease,box-shadow .16s ease,background .16s ease}
    .admin-profile-field .input:focus{border-color:var(--primary);box-shadow:0 0 0 4px rgba(69,6,147,.22);background:#fff}
    .admin-profile-field .input[readonly]{color:#675b70;background:rgba(243,238,246,.75);cursor:not-allowed}
    .admin-profile-error{display:block;margin-top:5px;color:#b42318;font-size:11px}
    .admin-profile-divider{padding-top:14px;border-top:1px solid rgba(69,6,147,.12)}
    .admin-profile-divider strong{display:block;margin-bottom:4px;color:#302638;font-size:13px}
    .admin-profile-divider p{margin:0;color:#675b70;font-size:11px;line-height:1.4}
    .admin-profile-password-wrap{position:relative}
    .admin-profile-password-wrap .input{padding-right:42px}
    .admin-profile-password-toggle{position:absolute;top:50%;right:6px;transform:translateY(-50%);width:32px;height:32px;display:grid;place-items:center;color:#675b70;background:transparent;border:0;border-radius:8px;cursor:pointer}
    .admin-profile-password-toggle:hover{color:var(--primary);background:rgba(69,6,147,.08)}
    .admin-profile-password-toggle svg{width:18px;height:18px}
    .admin-profile-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:24px;padding-top:18px;border-top:1px solid rgba(69,6,147,.12)}
    @media(max-width:600px){.admin-profile-modal{padding:12px}.admin-profile-dialog{padding:20px}.admin-profile-form-grid{grid-template-columns:1fr}.admin-profile-field.full{grid-column:auto}.admin-profile-actions{align-items:stretch;flex-direction:column-reverse}.admin-profile-actions .button{width:100%}}

    .content { width:100%; max-width:1440px; margin-right:auto; margin-left:auto; padding:clamp(26px,3vw,40px) clamp(18px,3vw,40px) 50px; }
    /*
       Shared portal page standard
       ---------------------------
       The Student List is the visual reference for every portal: the page
       purpose stays on the left, related controls stay together on the right,
       filters occupy one predictable toolbar, and data tables use the available
       width evenly. Individual scheduling/print tables keep their deliberate
       column definitions below.
    */
    .content .page-header {
        align-items:flex-end;
        gap:16px;
        margin-bottom:24px;
    }
    .content .page-header > :first-child { min-width:0; }
    .content .page-header :is(.actions,.page-header-actions,.roster-page-actions) {
        display:flex;
        align-items:center;
        justify-content:flex-end;
        flex-wrap:wrap;
        gap:9px;
    }
    .content .page-header :is(.actions,.page-header-actions,.roster-page-actions) .button {
        min-width:0;
        white-space:nowrap;
    }
    .page-header { margin-bottom:32px; }
    .page-header h2 { margin-bottom:7px; color:var(--navy); font-size:28px; font-weight:850; letter-spacing:-.9px; }
    .page-header p { color:var(--navy); font-size:12.5px; line-height:1.6; opacity:.8; }

    .welcome,.welcome-card {
        margin-bottom:28px;
        padding:clamp(24px,3.4vw,34px);
        color:#302638;
        background-color:#d9bfea;
        background-image:var(--portal-panel-image);
        background-position:center;
        background-repeat:no-repeat;
        background-size:cover;
        border:1px solid rgba(69,6,147,.18);
        border-radius:24px;
        box-shadow:0 2px 6px rgba(48,16,69,.08),0 24px 50px rgba(48,16,69,.14);
    }

    .welcome h2,.welcome-card h2 { color:var(--navy); font-size:22px; font-weight:850; }
    .welcome p,.welcome-card p { color:#55465f; }

    .card,.stat-card {
        color:#302638;
        background-color:#d9bfea;
        background-image:var(--portal-panel-image);
        background-position:center;
        background-repeat:no-repeat;
        background-size:cover;
        border:1px solid rgba(69,6,147,.18);
        border-radius:20px;
        box-shadow:0 2px 5px rgba(48,16,69,.07),0 22px 44px rgba(48,16,69,.13);
        transition:box-shadow .22s ease,border-color .22s ease,transform .22s ease;
    }

    .card { padding:28px; }
    .card h3 { color:var(--navy); font-size:15px; font-weight:800; }
    .card p,.stat-card span { color:#55465f; }
    .stat-card { cursor:pointer; }
    .stat-card::after { background:rgba(232,184,74,.22) !important; }
    .stat-card strong { color:var(--navy) !important; font-size:26px !important; }
    .stat-card:hover,.stat-card:focus-visible,.stat-card.active { border-color:var(--primary) !important; box-shadow:0 4px 10px rgba(69,6,147,.1),0 26px 52px rgba(69,6,147,.2) !important; transform:translateY(-3px); }

    .button {
        min-height:44px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:8px;
        padding:11px 22px;
        color:#fff;
        background:var(--primary);
        border:1px solid var(--primary);
        border-radius:13px;
        box-shadow:0 2px 4px rgba(69,6,147,.1),0 12px 26px rgba(69,6,147,.22);
        font-size:11.5px;
        font-weight:850;
        letter-spacing:.3px;
        cursor:pointer;
        transition:transform .16s ease,background .16s ease,border-color .16s ease,box-shadow .16s ease;
    }

    .button:hover { color:#fff; background:var(--primary-light); border-color:var(--primary-light); box-shadow:0 4px 8px rgba(69,6,147,.14),0 16px 32px rgba(69,6,147,.26); transform:translateY(-2px); }
    .button:active { transform:translateY(0); box-shadow:0 1px 2px rgba(69,6,147,.1); }
    .button:focus-visible,.button-secondary:focus-visible,.button-danger:focus-visible { outline:2px solid var(--primary-light); outline-offset:2px; }
    .button-secondary { color:var(--primary); background:#f5eefb; border-color:#dac6e9; box-shadow:none; }
    .button-secondary:hover { color:#fff; background:var(--primary); border-color:var(--primary); box-shadow:0 8px 20px rgba(69,6,147,.2); transform:translateY(-2px); }
    .button-danger { color:var(--danger); background:#fff5f4; border-color:#fecaca; box-shadow:none; }
    .button-danger:hover { color:#fff; background:var(--danger); border-color:var(--danger); box-shadow:0 8px 20px rgba(198,40,40,.22); transform:translateY(-2px); }

    .input,input.input,select.input,textarea.input {
        min-height:46px;
        padding:11px 14px;
        color:#322939;
        background:rgba(255,255,255,.75);
        border:1.5px solid var(--primary-light);
        border-radius:13px;
        font-size:12.5px;
        outline:none;
        transition:border-color .18s ease,box-shadow .18s ease,background .18s ease;
    }

    .input:hover,input.input:hover,select.input:hover,textarea.input:hover { background:rgba(255,255,255,.88); border-color:var(--primary); }
    .input:focus,input.input:focus,select.input:focus,textarea.input:focus { border-color:var(--primary); box-shadow:0 0 0 4px rgba(69,6,147,.22); background:#fff; }
    label { color:#413649; font-size:11.5px; font-weight:800; letter-spacing:.1px; }
    .content .filters {
        display:grid;
        grid-template-columns:repeat(auto-fit,minmax(min(100%,180px),1fr));
        align-items:end;
        gap:12px;
        margin-bottom:18px;
        padding:16px;
        color:#302638;
        background-color:#d9bfea;
        background-image:var(--portal-panel-image);
        background-position:center;
        background-repeat:no-repeat;
        background-size:cover;
        border:1px solid rgba(69,6,147,.18);
        border-radius:16px;
    }
    .content .filters > * { min-width:0; }
    .content .filters > :is(input,select,button,.button) { align-self:end; }
    .content .filters :is(label,input,select) { width:100%; }
    .form-actions { flex-wrap:wrap; padding-top:20px; border-top:1px solid #eee8f1; }

    .table-wrap { overflow-x:auto; background:rgba(255,255,255,.68); border:1px solid rgba(69,6,147,.14); border-radius:16px; }
    table { background:rgba(255,255,255,.42); border-collapse:separate; border-spacing:0; }
    th,td { padding:15px 17px; border-bottom:1px solid #eee8f1; }
    th { color:#5c4f66; background:rgba(248,243,251,.85); font-size:9.5px; font-weight:850; letter-spacing:.85px; text-transform:uppercase; }
    td { color:#4e4557; font-size:11.5px; }
    tbody tr { transition:background .14s ease; }
    tbody tr:last-child td { border-bottom:0; }
    tbody tr:hover { background:rgba(255,255,255,.62); }
    table td:has(> .actions) { width:1%; white-space:nowrap; text-align:right; }
    table td:has(> .actions) > .actions { justify-content:flex-end; flex-wrap:nowrap; }
    .badge { display:inline-block; padding:5px 12px; color:var(--primary); background:#f2e7fa; border:1px solid #e4d0f1; border-radius:999px; font-size:9.5px; font-weight:850; letter-spacing:.3px; }
    .schedule-time { color:var(--primary) !important; }

    /* Standard data table: shared by every interactive portal. Print views
       intentionally do not use the portal layout, so their document tables
       retain their dedicated print dimensions. */
    .content :is(.table-wrap,.table-wrapper) {
        position:relative;
        max-width:100%;
        overscroll-behavior-inline:contain;
        scrollbar-color:rgba(69,6,147,.56) rgba(69,6,147,.09);
        scrollbar-width:thin;
    }
    .content :is(.table-wrap,.table-wrapper) > table {
        width:100%;
        margin:0;
    }
    /* Regular data tables distribute their usable space consistently. Tables
       with a schedule-specific colgroup retain their intentional proportions. */
    .content :is(.table-wrap,.table-wrapper) > table:not(.schedule-table):not(.room-usage-table) {
        table-layout:fixed;
    }
    .content :is(.table-wrap,.table-wrapper) > table :is(th,td) {
        vertical-align:middle;
        padding:15px 18px;
    }
    .content :is(.table-wrap,.table-wrapper) > table th {
        line-height:1.28;
        white-space:normal;
    }
    .content :is(.table-wrap,.table-wrapper) > table td {
        line-height:1.45;
        overflow-wrap:break-word;
    }
    .content :is(.table-wrap,.table-wrapper) > table td[colspan] {
        padding:34px 20px;
        color:var(--muted);
        text-align:center;
    }
    .content :is(.table-wrap,.table-wrapper) > table .actions {
        align-items:center;
        justify-content:flex-end;
        flex-wrap:nowrap;
        gap:7px;
    }
    .content :is(.table-wrap,.table-wrapper) > table :is(th,td):has(> .actions) {
        width:184px;
        padding-right:14px;
        padding-left:14px;
        white-space:nowrap;
    }
    .content :is(.table-wrap,.table-wrapper)::-webkit-scrollbar { height:9px; }
    .content :is(.table-wrap,.table-wrapper)::-webkit-scrollbar-track { background:rgba(69,6,147,.09); border-radius:999px; }
    .content :is(.table-wrap,.table-wrapper)::-webkit-scrollbar-thumb { background:rgba(69,6,147,.56); border-radius:999px; }
    .content :is(.table-wrap,.table-wrapper)::-webkit-scrollbar-thumb:hover { background:var(--primary); }

    /* Summary controls sit at the upper-left of a table, while page navigation
       remains centered beneath it, matching the Student List. */
    .content .portal-pagination-meta { margin:0 0 14px; }
    .content .portal-pagination { margin:18px auto 0; }

    /* One full-viewport backdrop for every portal modal.  Some dashboard
       overlays used to begin below the top bar and beside the sidebar; this
       rule deliberately covers both so every open modal has the same focused
       full-screen blur as the student import dialog. */
    body :is(
        .portal-profile-modal,
        .admin-profile-modal,
        .notification-modal,
        .notice-modal,
        .delete-modal,
        .delete-confirmation-modal,
        .account-confirmation,
        .archive-modal,
        .analytics-modal,
        .chart-modal,
        .portal-data-modal
    ) {
        position:fixed;
        z-index:3000;
        inset:0;
        display:grid;
        place-items:center;
        padding:20px;
        overflow:auto;
        overscroll-behavior:contain;
        background:rgba(31,5,57,.60);
        backdrop-filter:blur(16px) saturate(.86);
        -webkit-backdrop-filter:blur(16px) saturate(.86);
    }
    body :is(
        .portal-profile-modal,
        .admin-profile-modal,
        .notification-modal,
        .notice-modal,
        .delete-modal,
        .delete-confirmation-modal,
        .account-confirmation,
        .archive-modal,
        .analytics-modal,
        .chart-modal,
        .portal-data-modal
    )[hidden] { display:none !important; }
    .chart-modal,.delete-modal { background:rgba(31,5,57,.62); backdrop-filter:blur(10px); -webkit-backdrop-filter:blur(10px); }
    .chart-panel,.delete-dialog {
        color:#302638;
        background-color:#d9bfea;
        background-image:var(--portal-panel-image);
        background-position:center;
        background-repeat:no-repeat;
        background-size:cover;
        border-top:4px solid var(--gold);
        border-radius:24px;
        box-shadow:0 6px 14px rgba(24,3,48,.18),0 36px 90px rgba(24,3,48,.3);
    }
    .chart-close:hover,.chart-close:focus-visible { color:var(--primary); background:#f4edfa; outline:2px solid #d8c0e8; }
    .chart-toolbar { background:#f4edfa; }
    .chart-type-button.active { color:#fff; background:var(--primary); }
    .card-title,.delete-dialog h2 { color:var(--navy); }
    .role-badge { color:var(--primary); background:#f2e7fa; border-radius:999px; }
    .quick-link { color:var(--gold-dark); background:var(--gold-soft); border-color:#f0dca3; transition:background .16s ease,transform .16s ease,box-shadow .16s ease; }
    .quick-link:hover { background:#ffecb3; box-shadow:0 8px 18px rgba(232,184,74,.3); transform:translateY(-2px); }
    .pagination-link:hover { color:var(--primary); background:#f5eefb; border-color:#caa8df; }
    .pagination-link.active { color:#fff; background:var(--primary); border-color:var(--primary); }
    .portal-pagination-bar {
        display:flex;
        align-items:center;
        justify-content:center;
        flex-wrap:wrap;
        gap:12px;
        margin:18px 0 0;
    }
    .portal-pagination-meta {
        display:flex;
        align-items:center;
        justify-content:flex-start;
        gap:12px;
        min-width:0;
        flex-wrap:wrap;
        margin:0 0 14px;
    }
    .portal-page-size label {
        display:inline-flex;
        align-items:center;
        gap:7px;
        color:#766b7f;
        font-size:12px;
        font-weight:750;
        white-space:nowrap;
    }
    .portal-page-size select {
        height:34px;
        min-width:58px;
        padding:0 25px 0 10px;
        color:#3e2454;
        background-color:#fff;
        border:1px solid #d8c0e8;
        border-radius:8px;
        font:800 12px/1 inherit;
        cursor:pointer;
    }
    .portal-page-size select:focus-visible { outline:3px solid rgba(112,34,184,.22); outline-offset:2px; }
    .portal-pagination-summary {
        margin:0;
        padding-left:14px;
        color:#766b7f;
        border-left:1px solid #e5dbea;
        font-size:12px;
        font-weight:700;
        line-height:1.4;
    }
    .portal-pagination-summary strong { color:#3e2454; font-weight:850; }
    .portal-pagination-separator { margin:0 5px; color:#9b8ca7; }
    .portal-pagination {
        display:flex;
        justify-content:center;
        align-items:center;
        flex-wrap:wrap;
        gap:8px;
        margin:18px 0 0;
    }
    .portal-page-button {
        width:46px;
        height:46px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        flex:0 0 46px;
        padding:0;
        color:#465268;
        background:rgba(255,255,255,.74);
        border:1.5px solid rgba(255,255,255,.9);
        border-radius:13px;
        box-shadow:0 8px 20px rgba(47,18,68,.08);
        font-size:14.5px;
        font-weight:850;
        line-height:1;
        text-decoration:none;
        transition:color .16s ease,background .16s ease,border-color .16s ease,box-shadow .16s ease,transform .16s ease;
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
        box-shadow:0 10px 24px rgba(69,6,147,.16);
        outline:none;
        transform:translateY(-2px);
    }
    .portal-page-button.is-active {
        color:#fff;
        background:var(--primary);
        border-color:var(--primary);
        box-shadow:0 10px 24px rgba(69,6,147,.26);
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
    .table-wrapper { overflow-x:auto; background:rgba(255,255,255,.68); border:1px solid rgba(69,6,147,.14); border-radius:12px; }
    .scan-frame { border-color:var(--primary) !important; }

    .notice-modal,.notification-modal { background:rgba(31,5,57,.65); backdrop-filter:blur(10px); -webkit-backdrop-filter:blur(10px); }
    .notice-dialog,.notification-dialog {
        color:#302638;
        background-color:#d9bfea;
        background-image:var(--portal-panel-image);
        background-position:center;
        background-repeat:no-repeat;
        background-size:cover;
        border-top:4px solid var(--gold);
        border-radius:24px;
        box-shadow:0 6px 14px rgba(24,3,48,.2),0 36px 90px rgba(24,3,48,.32);
    }
    .notice-dialog h2,.notification-dialog h2 { color:var(--navy); }
    .assignment-notice { color:#5c2978; background:#f8f0fc; border-color:#dfc9ec; border-radius:13px; }

    .stat,.analytics-dialog,.archive-dialog,.confirmation-dialog,.delete-confirmation-dialog,.section-delete-dialog,.room-qr-dialog,.section-item,.section-empty,.instructor-option {
        color:#302638;
        background-color:#d9bfea !important;
        background-image:var(--portal-panel-image) !important;
        background-position:center !important;
        background-repeat:no-repeat !important;
        background-size:cover !important;
        border-color:rgba(69,6,147,.18) !important;
        box-shadow:0 3px 7px rgba(48,16,69,.07),0 20px 42px rgba(48,16,69,.12);
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

    /* Instructor and Student portals use the Dean portal's white canvas. */
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
        content:none;
        display:none;
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
        .topbar {
            position:relative !important;
            align-items:flex-start;
            flex-direction:column;
            gap:12px;
        }
        .topbar-start,.topbar-actions { width:100%; }
        .topbar-actions { flex-wrap:wrap; justify-content:flex-end; }
        .profile { max-width:100%; }
        .main { padding-top:0; }
        .content { padding-top:24px; }
        .page-header { align-items:flex-start; flex-direction:column; }
        .content .page-header { align-items:flex-start; flex-direction:column; }
        .content .page-header :is(.actions,.page-header-actions,.roster-page-actions) { width:100%; justify-content:flex-start; }
        .page-header .button { width:100%; }
        .content .filters { grid-template-columns:1fr; }
        .content :is(.table-wrap,.table-wrapper) > table:not(.schedule-table):not(.room-usage-table) { min-width:680px; }
        .card { padding:20px; }
        .portal-pagination-bar { justify-content:center; gap:12px; }
        .portal-pagination-meta { justify-content:flex-start; }
        .portal-pagination-summary { padding-left:0; border-left:0; text-align:left; }
        .portal-pagination-separator { display:none; }
        .portal-pagination { justify-content:center; }

        /* These menus anchor to their trigger via right:0, which can push
           them off-screen once the trigger no longer sits at the true
           right edge of a wrapped topbar row. Break them out to the
           viewport instead, matching the notification panel's own
           mobile pattern (schedule-notification-styles.blade.php). */
        .topbar-settings-dropdown,
        .portal-profile-dropdown,
        .profile-dropdown {
            position:fixed;
            top:130px;
            left:15px;
            right:15px;
            width:auto;
        }
    }

    @media(max-width:520px) {
        .topbar { gap:12px; }
        .profile-copy strong,.profile-name,.profile strong { max-width:110px; }
        .welcome,.welcome-card { padding:22px; }
        .portal-pagination { gap:6px; }
        .portal-page-button { width:40px; height:40px; flex-basis:40px; border-radius:10px; font-size:13.5px; }
        .portal-page-button svg { width:17px; height:17px; }
    }
</style>
<script>
    (() => {
        const modalSelector = [
            '.portal-profile-modal',
            '.admin-profile-modal',
            '.notification-modal',
            '.notice-modal',
            '.delete-modal',
            '.delete-confirmation-modal',
            '.account-confirmation',
            '.archive-modal',
            '.analytics-modal',
            '.chart-modal',
            '.portal-data-modal',
        ].join(',');

        const moveToViewportLayer = (modal) => {
            if (!(modal instanceof HTMLElement) || modal.parentElement === document.body) {
                return;
            }

            // A fixed child of .content remains trapped below the sidebar/topbar
            // when its portal creates a stacking context. Moving overlays to the
            // document body lets their shared full-screen backdrop cover it all.
            document.body.append(modal);
        };

        const moveAllModalsToViewportLayer = (root = document) => {
            if (root instanceof HTMLElement && root.matches(modalSelector)) {
                moveToViewportLayer(root);
            }

            root.querySelectorAll?.(modalSelector).forEach(moveToViewportLayer);
        };

        document.addEventListener('DOMContentLoaded', () => {
            moveAllModalsToViewportLayer();

            new MutationObserver((changes) => {
                changes.forEach((change) => {
                    change.addedNodes.forEach((node) => {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            moveAllModalsToViewportLayer(node);
                        }
                    });
                });
            }).observe(document.body, { childList:true, subtree:true });
        }, { once:true });
    })();
</script>
