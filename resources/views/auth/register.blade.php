@php
    $departmentBrands = [
        'BSIT' => ['name' => 'Information Technology Department', 'logo' => 'images/bsit-department-logo.jpg'],
        'BSBA' => ['name' => 'Business Administration Department', 'logo' => 'images/bsba-department-logo.jpg'],
        'BSHM' => ['name' => 'Hospitality Management Department', 'logo' => 'images/bshm-department-logo.jpg'],
        'BSED' => ['name' => 'Secondary Education Department', 'logo' => 'images/education-department-logo.jpg'],
        'BEED' => ['name' => 'Elementary Education Department', 'logo' => 'images/education-department-logo.jpg'],
    ];
    $departmentBrand = $selectedRole === 'dean' ? ($departmentBrands[$selectedCourse] ?? null) : null;
    $registrationBrand = $departmentBrand
        ? ['logo' => $departmentBrand['logo'], 'logo_alt' => $departmentBrand['name'].' logo', 'label' => $departmentBrand['name'], 'title' => 'Join your department workspace.', 'summary' => 'Create your Dean account for secure, department-specific scheduling and academic resource management.']
        : ['logo' => 'images/mcc-college-logo.png', 'logo_alt' => 'Madridejos Community College logo', 'label' => 'MCC '.ucfirst($selectedRole).' Portal', 'title' => 'Join your academic workspace.', 'summary' => $selectedRole === 'student' ? 'Create your account to access your Study Load, class schedule, assigned rooms, and academic information.' : 'Create your account to access teaching schedules, workloads, assigned classes, and room information.'];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    @include('layouts.partials.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create {{ ucfirst($selectedRole) }} Account | Scheduler</title>
    <style>
        :root{--primary:#450693;--primary-dark:#2d045f;--primary-light:#7022b8;--gold:#e8b84a;--gold-dark:#815000;--gold-soft:#fff6df;--ink:#1e1724;--text:#554b5e;--muted:#817789;--line:#e3dbe8;--soft:#faf8fb;--danger:#b42318;--success:#067647}
        *{box-sizing:border-box;margin:0;padding:0}body{min-height:100vh;font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:var(--ink);background:white}a{color:inherit;text-decoration:none}button,input,select{font:inherit}.auth-layout{min-height:100vh;display:grid;grid-template-columns:.86fr 1.14fr;align-items:start}
        .form-side{order:2;min-height:100vh;display:flex;justify-content:center;padding:34px clamp(32px,5vw,80px) 45px;background:#fff}.form-content{width:100%;max-width:670px}.brand{display:inline-flex;align-items:center;gap:10px;margin-bottom:27px;font-size:20px;font-weight:850;color:var(--primary-dark);letter-spacing:-.3px}.brand-mark{width:42px;height:42px;display:grid;place-items:center;color:white;background:var(--primary);border-radius:11px;box-shadow:0 8px 20px rgba(69,6,147,.2)}.brand-mark svg{width:22px;height:22px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
        .heading{margin-bottom:18px}.heading-kicker{display:flex;align-items:center;gap:8px;margin-bottom:9px;font-size:10px;font-weight:850;letter-spacing:1.2px;color:var(--gold-dark);text-transform:uppercase}.heading-kicker::before{content:'';width:25px;height:3px;background:var(--gold);border-radius:10px}.heading h1{margin-bottom:7px;font-size:29px;letter-spacing:-1px;color:var(--primary-dark)}.heading p{font-size:12px;line-height:1.6;color:var(--muted)}
        .portal-card{display:flex;align-items:center;gap:12px;margin-bottom:20px;padding:11px 13px;background:var(--gold-soft);border:1px solid #eed181;border-radius:11px}.portal-symbol{width:37px;height:37px;display:grid;place-items:center;flex:0 0 37px;font-size:13px;font-weight:900;color:#2f1d00;background:var(--gold);border-radius:9px}.portal-copy span,.portal-copy strong{display:block}.portal-copy span{margin-bottom:2px;font-size:8px;font-weight:850;letter-spacing:.8px;color:var(--gold-dark);text-transform:uppercase}.portal-copy strong{font-size:13px;color:#3a290e}.course-name{color:var(--gold-dark)}.change-link{margin-left:auto;font-size:9px;font-weight:850;color:var(--primary);text-transform:uppercase}.change-link:hover{text-decoration:underline}
        .alert{margin-bottom:17px;padding:11px 13px;font-size:11px;line-height:1.5;border-radius:9px}.alert-error{color:var(--danger);background:#fef3f2;border:1px solid #fecdca}.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:15px 16px}.form-group.full{grid-column:1/-1}.form-group label{display:block;margin-bottom:6px;font-size:10px;font-weight:800;color:#3c3344}.optional{font-weight:600;color:var(--muted)}.form-control{width:100%;height:46px;padding:0 13px;font-size:12px;color:var(--ink);background:var(--soft);border:1px solid #ddd3e3;border-radius:9px;outline:none;transition:.2s}.form-control::placeholder{color:#a79dab}.form-control:focus{background:white;border-color:var(--primary-light);box-shadow:0 0 0 4px rgba(69,6,147,.08)}select.form-control{appearance:none;padding-right:38px;background-image:linear-gradient(45deg,transparent 50%,#756b7d 50%),linear-gradient(135deg,#756b7d 50%,transparent 50%);background-position:calc(100% - 18px) 20px,calc(100% - 13px) 20px;background-size:5px 5px,5px 5px;background-repeat:no-repeat}.form-control:disabled{color:#9a919f;background:#f2eef4;cursor:not-allowed}.password-wrap{position:relative}.password-wrap .form-control{padding-right:52px}.toggle-password{position:absolute;top:50%;right:8px;transform:translateY(-50%);padding:6px;font-size:8px;font-weight:850;color:var(--primary);background:transparent;border:0;cursor:pointer;text-transform:uppercase}.field-error{margin-top:5px;font-size:9px;color:var(--danger)}.field-help{margin-top:5px;font-size:9px;line-height:1.45;color:var(--muted)}
        .form-actions{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-top:22px}.login-link{font-size:10px;color:var(--muted)}.login-link strong{color:var(--primary)}.register-button{min-width:190px;height:48px;display:flex;align-items:center;justify-content:center;gap:9px;padding:0 20px;font-size:11px;font-weight:850;color:white;background:var(--primary);border:0;border-radius:9px;box-shadow:0 11px 24px rgba(69,6,147,.19);cursor:pointer;transition:.2s}.register-button:hover{background:var(--primary-light);transform:translateY(-1px);box-shadow:0 14px 28px rgba(69,6,147,.25)}.register-button svg{width:16px;height:16px;fill:none;stroke:var(--gold);stroke-width:2.3;stroke-linecap:round;stroke-linejoin:round}
        .form-step[hidden]{display:none}.step-progress{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:18px}.step-progress-item{display:flex;align-items:center;gap:8px;padding:9px 11px;color:var(--muted);background:rgba(245,239,251,.72);border:1px solid #e2d4ed;border-radius:10px;font-size:9px;font-weight:800}.step-progress-item span{width:23px;height:23px;display:grid;place-items:center;color:var(--primary);background:white;border:1px solid #d6c0e8;border-radius:7px}.step-progress-item.active{color:var(--primary-dark);background:#eee2f8;border-color:#b98edb}.step-progress-item.active span{color:white;background:var(--primary);border-color:var(--primary)}.step-actions{display:flex;align-items:center;justify-content:flex-end;gap:10px;margin-top:22px}.secondary-button{height:48px;padding:0 19px;color:var(--primary);background:#f7f2fb;border:1px solid #d9c5e9;border-radius:9px;font-size:11px;font-weight:850;cursor:pointer}.secondary-button:hover{background:#eee2f8}.next-button{min-width:160px}.account-step-note{grid-column:1/-1;padding:12px 14px;color:var(--text);background:#f7f2fb;border:1px solid #e1d4eb;border-radius:10px;font-size:10px;line-height:1.55}
        .visual-side{position:sticky;top:0;order:1;height:100vh;display:flex;align-items:center;justify-content:center;overflow:hidden;padding:clamp(42px,5vw,72px);color:white;background:linear-gradient(145deg,var(--primary-dark),var(--primary) 58%,#6716aa)}.visual-side::before{content:'';position:absolute;width:560px;height:560px;top:-330px;right:-220px;border:1px solid rgba(255,255,255,.12);border-radius:50%;box-shadow:0 0 0 90px rgba(255,255,255,.025),0 0 0 180px rgba(255,255,255,.018)}.visual-side::after{content:'';position:absolute;width:250px;height:250px;left:-145px;bottom:-155px;background:var(--gold);border-radius:50%;opacity:.15}.visual-content{position:relative;z-index:1;width:min(500px,100%)}.visual-label{display:inline-flex;align-items:center;gap:8px;margin-bottom:20px;padding:7px 11px;font-size:9px;font-weight:850;letter-spacing:.8px;color:#fff1c6;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);border-radius:20px;text-transform:uppercase}.visual-label::before{content:'';width:7px;height:7px;background:var(--gold);border-radius:50%;box-shadow:0 0 0 4px rgba(232,184,74,.12)}.visual-content h2{max-width:470px;margin-bottom:15px;font-size:clamp(31px,3.3vw,47px);line-height:1.08;letter-spacing:-1.7px}.visual-content>p{max-width:455px;font-size:13px;line-height:1.7;color:rgba(255,255,255,.7)}
        .registration-preview{margin-top:31px;padding:17px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.17);border-radius:15px;backdrop-filter:blur(8px)}.preview-card{padding:20px;color:var(--ink);background:#fbf9fc;border-radius:11px;box-shadow:0 20px 45px rgba(25,2,49,.2)}.preview-head{display:flex;align-items:center;gap:12px;margin-bottom:18px}.preview-avatar{width:42px;height:42px;display:grid;place-items:center;color:white;background:var(--primary);border-radius:11px;font-size:14px;font-weight:900}.preview-head span,.preview-head strong{display:block}.preview-head span{margin-bottom:3px;font-size:7px;font-weight:850;letter-spacing:.8px;color:var(--gold-dark);text-transform:uppercase}.preview-head strong{font-size:12px;color:var(--primary-dark)}.preview-steps{display:grid;gap:10px}.preview-step{display:flex;align-items:center;gap:10px;padding:10px 11px;background:white;border:1px solid #eee7f1;border-radius:8px}.step-number{width:24px;height:24px;display:grid;place-items:center;flex:0 0 24px;color:#2f1d00;background:var(--gold);border-radius:7px;font-size:8px;font-weight:900}.preview-step div strong,.preview-step div span{display:block}.preview-step div strong{font-size:9px;color:#372c3f}.preview-step div span{margin-top:2px;font-size:7px;color:#918797}.visual-points{display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-top:18px}.visual-point{display:flex;align-items:center;gap:8px;font-size:9px;font-weight:700;color:rgba(255,255,255,.72)}.check{width:21px;height:21px;display:grid;place-items:center;flex:0 0 21px;color:#302000;background:var(--gold);border-radius:6px}.check svg{width:12px;height:12px;fill:none;stroke:currentColor;stroke-width:2.4;stroke-linecap:round;stroke-linejoin:round}
        @media(max-width:980px){.auth-layout{grid-template-columns:1fr}.visual-side{display:none}.form-side{padding:35px 24px}.form-content{max-width:720px}}
        @media(max-width:600px){.form-side{padding:25px 17px 36px}.brand{margin-bottom:22px}.heading h1{font-size:26px}.form-grid{grid-template-columns:1fr}.form-group.full{grid-column:auto}.form-actions{align-items:stretch;flex-direction:column-reverse}.register-button{width:100%}.login-link{text-align:center}.change-link{display:none}}
        body.branded-registration{height:100vh;height:100dvh;display:flex;align-items:center;justify-content:center;overflow:hidden;padding:clamp(12px,2vw,28px);background-image:linear-gradient(rgba(0,0,0,.2),rgba(0,0,0,.2)),url("{{ asset('images/landing-background.png') }}");background-position:center;background-repeat:no-repeat;background-size:cover}
        .branded-registration .auth-layout{width:min(1180px,100%);height:calc(100dvh - clamp(24px,4vw,56px));min-height:0;margin:0;grid-template-columns:minmax(0,1.05fr) minmax(420px,.95fr);gap:clamp(28px,4vw,62px);padding:0}
        .branded-registration .form-side{height:100%;min-height:0;align-items:center;padding:clamp(22px,3vw,38px) clamp(28px,5vw,58px);background:rgba(255,255,255,.96);border:1px solid rgba(255,255,255,.7);border-radius:24px;box-shadow:0 28px 75px rgba(35,4,61,.24);backdrop-filter:blur(14px)}
        .branded-registration .form-content{max-width:440px}.branded-registration .brand{margin-bottom:22px}.branded-registration .heading-kicker{color:var(--primary)}.branded-registration .heading-kicker::before{background:var(--primary)}
        .branded-registration .portal-card{background:#f5effb;border-color:#ddcceb}.branded-registration .portal-symbol{width:48px;height:48px;flex-basis:48px;overflow:hidden;padding:0;background:transparent;border:0;border-radius:50%}.branded-registration .portal-symbol img{width:100%;height:100%;display:block;object-fit:contain;border-radius:50%}.branded-registration .portal-symbol.mcc-symbol{border-radius:0}.branded-registration .portal-symbol.mcc-symbol img{border-radius:0}.branded-registration .portal-copy span,.branded-registration .course-name{color:var(--primary)}.branded-registration .portal-copy strong{color:var(--primary-dark)}
        .branded-registration .visual-side{position:sticky;padding:clamp(24px,3vw,48px);background:transparent}.branded-registration .visual-side::before,.branded-registration .visual-side::after{display:none}
        .registration-identity{position:relative;z-index:1;width:min(520px,100%);color:#fff}.registration-logo{width:clamp(150px,16vw,205px);height:clamp(145px,15vw,195px);display:grid;place-items:center;margin-bottom:27px;filter:drop-shadow(0 20px 30px rgba(30,3,53,.28))}.registration-logo img{width:100%;height:100%;display:block;object-fit:contain}.registration-logo.department-logo{width:clamp(145px,15vw,195px);height:clamp(145px,15vw,195px);overflow:hidden;clip-path:circle(50% at 50% 50%)}.registration-identity .visual-label{margin-bottom:18px;color:#fff;background:rgba(69,6,147,.32);border-color:rgba(255,255,255,.24)}.registration-identity .visual-label::before{background:#450693;box-shadow:0 0 0 4px rgba(69,6,147,.2)}.registration-identity h2{max-width:510px;margin-bottom:16px;font-size:clamp(37px,4.2vw,59px);line-height:1.02;letter-spacing:-2px;text-shadow:0 5px 22px rgba(26,2,45,.28)}.registration-identity>p{max-width:490px;font-size:14px;line-height:1.75;color:rgba(255,255,255,.84);text-shadow:0 3px 14px rgba(26,2,45,.25)}.registration-benefits{display:grid;gap:10px;margin-top:28px}.registration-benefit{display:flex;align-items:center;gap:11px;font-size:11px;font-weight:750;color:rgba(255,255,255,.9)}.registration-benefit span{width:25px;height:25px;display:grid;place-items:center;flex:0 0 25px;color:#fff;background:#450693;border:1px solid rgba(255,255,255,.3);border-radius:7px}
        @media(max-width:980px){.branded-registration .auth-layout{width:100%;display:block;padding:0}.branded-registration .visual-side{display:none}.branded-registration .form-side{min-height:100vh;padding:30px 22px;border:0;border-radius:0}.branded-registration .form-content{max-width:720px}}
        @media(min-width:981px){
            .branded-registration .auth-layout{align-items:stretch}
            .branded-registration .form-side{overflow:hidden}
            .branded-registration .form-content{margin:0}
            .branded-registration .brand{margin-bottom:12px;font-size:17px}.branded-registration .brand-mark{width:36px;height:36px}
            .branded-registration .heading{margin-bottom:11px}.branded-registration .heading-kicker{margin-bottom:5px}.branded-registration .heading h1{margin-bottom:3px;font-size:25px}.branded-registration .heading p{line-height:1.4}
            .branded-registration .portal-card{margin-bottom:11px;padding:7px 11px}.branded-registration .portal-symbol{width:39px;height:39px;flex-basis:39px}
            .branded-registration .step-progress{margin-bottom:11px}.branded-registration .step-progress-item{padding:7px 10px}.branded-registration .step-progress-item span{width:21px;height:21px}
            .branded-registration .form-grid{gap:9px 14px}.branded-registration .form-group label{margin-bottom:4px}.branded-registration .form-control{height:40px}
            .branded-registration select.form-control{background-position:calc(100% - 18px) 17px,calc(100% - 13px) 17px}
            .branded-registration .field-help,.branded-registration .field-error{margin-top:3px;line-height:1.25}
            .branded-registration .step-actions{margin-top:13px}.branded-registration .form-actions{margin-top:13px}.branded-registration .form-actions .step-actions{margin-top:0}
            .branded-registration .register-button,.branded-registration .secondary-button{height:42px}
        }
        @media(min-width:981px) and (max-height:760px){
            .branded-registration .form-side{padding-top:12px;padding-bottom:12px}
            .branded-registration .brand{margin-bottom:6px;font-size:16px}.branded-registration .brand-mark{width:32px;height:32px}.branded-registration .brand-mark svg{width:18px;height:18px}
            .branded-registration .heading{margin-bottom:6px}.branded-registration .heading-kicker{margin-bottom:2px;font-size:8px}.branded-registration .heading h1{margin-bottom:2px;font-size:21px}.branded-registration .heading p{font-size:9px;line-height:1.25}
            .branded-registration .portal-card{margin-bottom:6px;padding:5px 9px}.branded-registration .portal-symbol{width:30px;height:30px;flex-basis:30px}
            .branded-registration .step-progress{margin-bottom:6px;gap:6px}.branded-registration .step-progress-item{padding:4px 8px}.branded-registration .step-progress-item span{width:19px;height:19px}
            .branded-registration .form-grid{gap:5px 12px}.branded-registration .form-group label{margin-bottom:2px}.branded-registration .form-control{height:33px}.branded-registration select.form-control{background-position:calc(100% - 18px) 13px,calc(100% - 13px) 13px}
            .branded-registration .field-help,.branded-registration .field-error{margin-top:2px;font-size:8px}
            .branded-registration .step-actions,.branded-registration .form-actions{margin-top:8px}.branded-registration .register-button,.branded-registration .secondary-button{height:38px}
        }
        @media(min-width:981px) and (max-height:900px){
            .branded-registration .visual-side{height:calc(100vh - 36px);padding:18px 34px;align-items:center}
            .branded-registration .registration-identity{width:min(520px,100%)}
            .branded-registration .registration-logo{width:clamp(150px,16vw,205px);height:clamp(145px,15vw,195px);margin-bottom:14px}
            .branded-registration .registration-logo.department-logo{width:clamp(145px,15vw,195px);height:clamp(145px,15vw,195px)}
            .branded-registration .registration-identity .visual-label{margin-bottom:11px}
            .branded-registration .registration-identity h2{max-width:510px;margin-bottom:10px;font-size:36px;line-height:1.02;letter-spacing:-2px}
            .branded-registration .registration-identity>p{max-width:490px;font-size:14px;line-height:1.5}
            .branded-registration .registration-benefits{gap:7px;margin-top:16px}
            .branded-registration .registration-benefit{font-size:11px}.branded-registration .registration-benefit span{width:25px;height:25px;flex-basis:25px}
        }
        @media(min-width:981px) and (max-height:700px){
            .branded-registration .registration-logo{margin-bottom:9px}
            .branded-registration .registration-identity .visual-label{margin-bottom:8px}
            .branded-registration .registration-benefits{margin-top:10px}
        }
        /* Keep the registration identity panel identical to the login identity panel. */
        .branded-registration .visual-side{position:relative;top:auto;height:100%;padding:clamp(24px,3vw,48px)}
        .branded-registration .portal-identity{position:relative;z-index:1;width:min(520px,100%);color:#fff}
        .branded-registration .portal-logo-wrap{width:clamp(120px,13vw,170px);height:clamp(120px,13vw,170px);display:grid;place-items:center;margin-bottom:27px;padding:8px;background:rgba(255,255,255,.96);border:1px solid rgba(255,255,255,.82);border-radius:50%;box-shadow:0 22px 50px rgba(30,3,53,.27)}
        .branded-registration .portal-logo-wrap img{width:100%;height:100%;object-fit:contain;border-radius:50%}
        .branded-registration .portal-logo-wrap.mcc-logo-wrap{width:clamp(150px,16vw,205px);height:clamp(145px,15vw,195px);padding:0;background:transparent;border:0;border-radius:0;box-shadow:none;filter:drop-shadow(0 20px 30px rgba(30,3,53,.28))}
        .branded-registration .portal-logo-wrap.mcc-logo-wrap img{border-radius:0}
        .branded-registration .portal-logo-wrap.department-logo-wrap{width:clamp(145px,15vw,195px);height:clamp(145px,15vw,195px);padding:0;overflow:hidden;background:transparent;border:0}
        .branded-registration .portal-logo-wrap.department-logo-wrap img{width:100%;height:100%;display:block;object-fit:contain;object-position:50% 50%;clip-path:circle(50% at 50% 50%)}
        .branded-registration .portal-identity .visual-label{margin-bottom:18px;color:#fff;background:rgba(69,6,147,.32);border-color:rgba(255,255,255,.24)}
        .branded-registration .portal-identity .visual-label::before{background:#450693;box-shadow:0 0 0 4px rgba(69,6,147,.2)}
        .branded-registration .portal-identity h2{max-width:510px;margin-bottom:16px;font-size:clamp(37px,4.2vw,59px);line-height:1.02;letter-spacing:-2px;text-shadow:0 5px 22px rgba(26,2,45,.28)}
        .branded-registration .portal-identity>p{max-width:490px;font-size:14px;line-height:1.75;color:rgba(255,255,255,.84);text-shadow:0 3px 14px rgba(26,2,45,.25)}
        .branded-registration .portal-access-list{display:grid;gap:10px;margin-top:28px}
        .branded-registration .portal-access-item{display:flex;align-items:center;gap:11px;font-size:11px;font-weight:750;color:rgba(255,255,255,.9)}
        .branded-registration .portal-access-item span{width:25px;height:25px;display:grid;place-items:center;flex:0 0 25px;color:#fff;background:#450693;border:1px solid rgba(255,255,255,.3);border-radius:7px}
        .branded-registration .portal-access-item svg{width:13px;height:13px;fill:none;stroke:currentColor;stroke-width:2.5;stroke-linecap:round;stroke-linejoin:round}
        @media(min-width:981px) and (max-height:850px){
            .branded-registration .portal-logo-wrap{width:110px;height:110px;margin-bottom:14px}
            .branded-registration .portal-identity .visual-label{margin-bottom:11px}
            .branded-registration .portal-identity h2{margin-bottom:10px;font-size:36px}
            .branded-registration .portal-identity>p{line-height:1.5}
            .branded-registration .portal-access-list{margin-top:16px;gap:7px}
        }
    </style>
</head>
<body class="branded-registration">
@php($roleSymbols = ['dean' => 'D', 'instructor' => 'I', 'student' => 'S'])
<main class="auth-layout">
    <section class="form-side">
        <div class="form-content">
            <a class="brand" href="{{ route('home') }}"><span class="brand-mark"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18M8 14h3M13 14h3M8 18h3"/></svg></span><span>MCC | Scheduler</span></a>
            <header class="heading"><span class="heading-kicker">Create your portal account</span><h1>Join your academic workspace</h1><p>{{ $selectedRole === 'student' ? 'Complete your student and section information, then sign in to your portal.' : 'Complete your information below. Your account requires approval before portal access is enabled.' }}</p></header>

            <div class="portal-card"><span class="portal-symbol {{ $departmentBrand ? '' : 'mcc-symbol' }}"><img src="{{ asset($registrationBrand['logo']) }}" alt="{{ $registrationBrand['logo_alt'] }}"></span><div class="portal-copy"><span>Selected portal</span><strong>{{ ucfirst($selectedRole) }} Portal @if($selectedCourse)<em class="course-name">· {{ $selectedCourse }}</em>@endif</strong></div><a class="change-link" href="{{ route('home') }}#portals">Change</a></div>
            @if($errors->any())<div class="alert alert-error" role="alert">Please review the highlighted registration fields and try again.</div>@endif

            @php($openAccountStep = $errors->hasAny(['email', 'password', 'password_confirmation']))
            <form id="registrationForm" method="POST" action="{{ route('register') }}" data-initial-step="{{ $openAccountStep ? 2 : 1 }}">
                @csrf
                <input type="hidden" name="role" value="{{ $selectedRole }}">
                <div class="step-progress" aria-label="Registration progress"><div class="step-progress-item" data-step-indicator="1"><span>1</span>Profile details</div><div class="step-progress-item" data-step-indicator="2"><span>2</span>Account security</div></div>
                <section class="form-step" data-form-step="1">
                <div class="form-grid">
                    <div class="form-group"><label for="first_name">First Name</label><input id="first_name" class="form-control" type="text" name="first_name" value="{{ old('first_name') }}" placeholder="First name" required autofocus autocomplete="given-name">@error('first_name')<p class="field-error">{{ $message }}</p>@enderror</div>
                    <div class="form-group"><label for="middle_name">Middle Name <span class="optional">(optional)</span></label><input id="middle_name" class="form-control" type="text" name="middle_name" value="{{ old('middle_name') }}" placeholder="Middle name" autocomplete="additional-name">@error('middle_name')<p class="field-error">{{ $message }}</p>@enderror</div>
                    <div class="form-group"><label for="last_name">Last Name</label><input id="last_name" class="form-control" type="text" name="last_name" value="{{ old('last_name') }}" placeholder="Last name" required autocomplete="family-name">@error('last_name')<p class="field-error">{{ $message }}</p>@enderror</div>
                    <div class="form-group"><label for="suffix">Suffix <span class="optional">(optional)</span></label><input id="suffix" class="form-control" type="text" name="suffix" value="{{ old('suffix') }}" placeholder="Jr., Sr., III">@error('suffix')<p class="field-error">{{ $message }}</p>@enderror</div>

                    <div class="form-group {{ $selectedRole === 'dean' ? 'full' : '' }}"><label for="course">Course / Department</label><select id="course" class="form-control" name="course" required><option value="">Select department</option>@foreach($courses as $course)<option value="{{ $course }}" @selected(old('course', $selectedCourse) === $course)>{{ $course }}</option>@endforeach</select>@error('course')<p class="field-error">{{ $message }}</p>@enderror</div>

                    @if($selectedRole === 'student')
                        <div class="form-group"><label for="year_level">Year Level</label><select id="year_level" class="form-control" name="year_level" required><option value="">Select year level</option>@for($level=1;$level<=4;$level++)<option value="{{ $level }}" @selected((string) old('year_level') === (string) $level)>Year {{ $level }}</option>@endfor</select>@error('year_level')<p class="field-error">{{ $message }}</p>@enderror</div>
                        <div class="form-group full"><label for="academic_section_id">Section</label><select id="academic_section_id" class="form-control" name="academic_section_id" required><option value="">Select department and year level first</option>@foreach($sections as $section)<option value="{{ $section->id }}" data-course="{{ $section->course }}" data-year="{{ $section->year_level }}" @selected((string) old('academic_section_id') === (string) $section->id)>{{ $section->name }} · {{ $section->academic_year }}</option>@endforeach</select><p id="sectionHelp" class="field-help">Only sections matching the selected department and year level will appear.</p>@error('academic_section_id')<p class="field-error">{{ $message }}</p>@enderror</div>
                    @endif

                    @if($selectedRole === 'instructor')
                        <div class="form-group"><label for="employment_type">Employment Type</label><select id="employment_type" class="form-control" name="employment_type" required><option value="">Select employment type</option><option value="full_time" @selected(old('employment_type') === 'full_time')>Full Time (30 units maximum)</option><option value="industry_part_time" @selected(old('employment_type') === 'industry_part_time')>Industry Part-Time (15 units maximum)</option><option value="flexible_part_time" @selected(old('employment_type') === 'flexible_part_time')>Flexible Part-Time (15 units maximum)</option></select>@error('employment_type')<p class="field-error">{{ $message }}</p>@enderror</div>
                        <div id="outsideWorkGroup" class="form-group"><label for="outside_work_end_time">Outside-Work End Time</label><input id="outside_work_end_time" class="form-control" type="time" name="outside_work_end_time" value="{{ old('outside_work_end_time', '17:00') }}"><p class="field-help">Required only for Industry Part-Time instructors.</p>@error('outside_work_end_time')<p class="field-error">{{ $message }}</p>@enderror</div>
                    @endif

                </div>
                <div class="step-actions"><button class="register-button next-button" type="button" id="nextRegistrationStep">Continue <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg></button></div>
                </section>

                <section class="form-step" data-form-step="2" hidden>
                <div class="form-grid">
                    <div class="account-step-note">@if($selectedRole === 'student')Use your school-issued MCC Microsoft 365 email. Only eligible accounts in the MS365 student registry can register.@else Use an active email address and create a secure password for your {{ ucfirst($selectedRole) }} Portal account.@endif</div>
                    <div class="form-group full"><label for="email">Email Address</label><input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required autocomplete="username">@error('email')<p class="field-error">{{ $message }}</p>@enderror</div>
                    <div class="form-group"><label for="password">Password</label><div class="password-wrap"><input id="password" class="form-control" type="password" name="password" placeholder="Create password" required autocomplete="new-password"><button class="toggle-password" type="button" data-password-target="password">Show</button></div>@error('password')<p class="field-error">{{ $message }}</p>@enderror</div>
                    <div class="form-group"><label for="password_confirmation">Confirm Password</label><div class="password-wrap"><input id="password_confirmation" class="form-control" type="password" name="password_confirmation" placeholder="Repeat password" required autocomplete="new-password"><button class="toggle-password" type="button" data-password-target="password_confirmation">Show</button></div></div>
                </div>

                <div class="form-actions"><a class="login-link" href="{{ route('login', ['role' => $selectedRole, 'course' => old('course', $selectedCourse)]) }}">Already registered? <strong>Sign in</strong></a><div class="step-actions"><button class="secondary-button" type="button" id="previousRegistrationStep">Back</button><button class="register-button" type="submit">Create {{ ucfirst($selectedRole) }} Account <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg></button></div></div>
                </section>
            </form>
        </div>
    </section>

    <section class="visual-side" aria-label="Scheduler account registration">
        <div class="portal-identity">
            <div class="portal-logo-wrap {{ $departmentBrand ? 'department-logo-wrap' : 'mcc-logo-wrap' }}"><img src="{{ asset($registrationBrand['logo']) }}" alt="{{ $registrationBrand['logo_alt'] }}"></div>
            <span class="visual-label">{{ $registrationBrand['label'] }}</span>
            <h2>{{ $registrationBrand['title'] }}</h2>
            <p>{{ $registrationBrand['summary'] }}</p>
            <div class="portal-access-list">
                <div class="portal-access-item"><span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg></span>Secure role-based registration</div>
                <div class="portal-access-item"><span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg></span>Department-connected academic access</div>
                <div class="portal-access-item"><span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg></span>{{ $selectedRole === 'student' ? 'Study Load and schedule access' : 'Protected account approval' }}</div>
            </div>
        </div>
    </section>
</main>

<script>
(() => {
    const registrationForm=document.getElementById('registrationForm'),steps=[...document.querySelectorAll('[data-form-step]')],indicators=[...document.querySelectorAll('[data-step-indicator]')];
    const showStep=number=>{steps.forEach(step=>step.hidden=Number(step.dataset.formStep)!==number);indicators.forEach(item=>item.classList.toggle('active',Number(item.dataset.stepIndicator)===number));};
    document.getElementById('nextRegistrationStep')?.addEventListener('click',()=>{const firstStep=steps.find(step=>step.dataset.formStep==='1');const fields=[...firstStep.querySelectorAll('input,select')].filter(field=>!field.disabled);for(const field of fields){if(!field.checkValidity()){field.reportValidity();return;}}showStep(2);document.querySelector('.form-side')?.scrollTo({top:0,behavior:'smooth'});});
    document.getElementById('previousRegistrationStep')?.addEventListener('click',()=>showStep(1));
    showStep(Number(registrationForm?.dataset.initialStep||1));
    document.querySelectorAll('[data-password-target]').forEach(button=>button.addEventListener('click',()=>{const input=document.getElementById(button.dataset.passwordTarget),visible=input.type==='text';input.type=visible?'password':'text';button.textContent=visible?'Show':'Hide'}));
    const course=document.getElementById('course'),year=document.getElementById('year_level'),section=document.getElementById('academic_section_id'),help=document.getElementById('sectionHelp');
    if(course&&year&&section){const options=[...section.querySelectorAll('option[data-course]')];const update=()=>{const selectedCourse=course.value,selectedYear=year.value;let available=0;options.forEach(option=>{const matches=option.dataset.course===selectedCourse&&option.dataset.year===selectedYear;option.hidden=!matches;option.disabled=!matches;if(matches)available++});const current=section.selectedOptions[0];if(current?.dataset.course&&(current.disabled||current.hidden))section.value='';section.disabled=!selectedCourse||!selectedYear||available===0;section.options[0].textContent=!selectedCourse||!selectedYear?'Select department and year level first':available?'Select section':'No sections available';help.textContent=available?`${available} matching ${available===1?'section':'sections'} available.`:'No section is available for the selected department and year level.'};course.addEventListener('change',update);year.addEventListener('change',update);update()}
    const employment=document.getElementById('employment_type'),outsideGroup=document.getElementById('outsideWorkGroup'),outsideTime=document.getElementById('outside_work_end_time');if(employment&&outsideGroup&&outsideTime){const update=()=>{const industry=employment.value==='industry_part_time';outsideGroup.hidden=!industry;outsideTime.disabled=!industry;outsideTime.required=industry};employment.addEventListener('change',update);update()}
})();
</script>
</body>
</html>
