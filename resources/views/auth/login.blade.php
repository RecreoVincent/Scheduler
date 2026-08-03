@php
    $allowedRoles=['admin','dean','instructor','student'];
    $allowedCourses=['BSIT','BSBA','BSHM','BSED','BEED'];
    $selectedRole=strtolower(request('role',old('role','user')));
    $selectedCourse=strtoupper(request('course',old('course','')));
    if(!in_array($selectedRole,$allowedRoles,true)){$selectedRole='user';}
    if(!in_array($selectedCourse,$allowedCourses,true)){$selectedCourse='';}
    $roleSymbols=['admin'=>'A','dean'=>'D','instructor'=>'I','student'=>'S','user'=>'U'];
    $departmentLogins=[
        'BSIT'=>['name'=>'Information Technology Department','logo'=>'images/bsit-department-logo.jpg'],
        'BSBA'=>['name'=>'Business Administration Department','logo'=>'images/bsba-department-logo.jpg'],
        'BSHM'=>['name'=>'Hospitality Management Department','logo'=>'images/bshm-department-logo.jpg'],
        'BSED'=>['name'=>'Secondary Education Department','logo'=>'images/education-department-logo.jpg'],
        'BEED'=>['name'=>'Elementary Education Department','logo'=>'images/education-department-logo.jpg'],
    ];
    $departmentLogin=$departmentLogins[$selectedCourse]??null;
    $isDepartmentDean=$selectedRole==='dean'&&$departmentLogin!==null;
    $roleLoginBrands=[
        'admin'=>[
            'welcome'=>'Welcome, Administrator',
            'label'=>'MCC Administration Portal',
            'description'=>'Sign in to manage institutional accounts, access, and academic analytics.',
            'title'=>'Lead the campus with clarity.',
            'summary'=>'A secure workspace for account administration, institutional oversight, and reliable academic information.',
            'features'=>['Secure administrative access','Centralized account management','Clear institutional analytics'],
        ],
        'instructor'=>[
            'welcome'=>'Welcome, Instructor',
            'label'=>'MCC Instructor Portal',
            'description'=>'Sign in to access your teaching schedule, workload, assigned sections, and room information.',
            'title'=>'Keep every class within reach.',
            'summary'=>'A focused workspace for teaching schedules, workloads, assigned classes, room scanning, and printable records.',
            'features'=>['Secure instructor access','Organized teaching workload','Real-time room information'],
        ],
        'student'=>[
            'welcome'=>'Welcome, Student',
            'label'=>'MCC Student Portal',
            'description'=>'Sign in to view your Study Load, class schedule, assigned rooms, and academic information.',
            'title'=>'Stay ready for every class.',
            'summary'=>'A clear student workspace for Study Loads, schedules, room scanning, profile information, and printable records.',
            'features'=>['Secure student access','Clear Study Load and schedule','Quick room information'],
        ],
    ];
    if($isDepartmentDean){
        $portalBrand=[
            'welcome'=>'Welcome, '.$selectedCourse.' Dean',
            'label'=>$departmentLogin['name'],
            'description'=>'Sign in to manage the '.$departmentLogin['name'].' schedules and academic resources.',
            'title'=>'Plan the department with clarity.',
            'summary'=>'A focused workspace for class scheduling, instructor workloads, sections, subjects, rooms, timetables, and printable academic reports.',
            'features'=>['Secure department-specific access','Organized scheduling and resource management','Conflict-aware academic planning'],
            'logo'=>$departmentLogin['logo'],
            'logoAlt'=>$departmentLogin['name'].' logo',
        ];
    }else{
        $portalBrand=$roleLoginBrands[$selectedRole]??null;
        if($portalBrand!==null){
            $portalBrand['logo']='images/mcc-college-logo.png';
            $portalBrand['logoAlt']='Madridejos Community College logo';
        }
    }
    $isBrandedPortal=$portalBrand!==null;
    $usesMccLogo=$isBrandedPortal&&!$isDepartmentDean;
    $usesDepartmentLogo=$isDepartmentDean;
    $usesBsitLogo=$isDepartmentDean&&$selectedCourse==='BSIT';
    $departmentLogoClass=$isDepartmentDean?'department-logo-'.strtolower($selectedCourse):'';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign In | Scheduler</title>
    <style>
        :root {
            --primary:#450693;
            --primary-dark:#2d045f;
            --primary-light:#7022b8;
            --gold:#e8b84a;
            --gold-dark:#815000;
            --gold-soft:#fff6df;
            --ink:#1e1724;
            --text:#554b5e;
            --muted:#817789;
            --line:#e3dbe8;
            --soft:#faf8fb;
            --danger:#b42318;
            --success:#067647;
        }

        *{box-sizing:border-box;margin:0;padding:0}
        html,body{width:100%;height:100%;overflow:hidden}
        body{height:100vh;height:100dvh;font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:var(--ink);background:white}
        a{color:inherit;text-decoration:none}button,input{font:inherit}
        .auth-layout{width:100%;height:100vh;height:100dvh;min-height:0;display:grid;grid-template-columns:.96fr 1.04fr;overflow:hidden}

        .form-side{height:100%;order:2;display:flex;align-items:center;justify-content:center;overflow:hidden;padding:45px clamp(35px,7vw,105px);background:#fff}
        .form-content{width:100%;max-width:430px}
        .brand{display:inline-flex;align-items:center;gap:10px;margin-bottom:45px;font-size:20px;font-weight:850;color:var(--primary-dark);letter-spacing:-.3px}
        .brand-mark{width:42px;height:42px;display:grid;place-items:center;color:white;background:var(--primary);border-radius:11px;box-shadow:0 8px 20px rgba(69,6,147,.2)}
        .brand-mark svg{width:22px;height:22px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
        .brand-mark::after{content:'';position:absolute}
        .heading{margin-bottom:24px}
        .heading-kicker{display:flex;align-items:center;gap:8px;margin-bottom:12px;font-size:10px;font-weight:850;letter-spacing:1.2px;color:var(--gold-dark);text-transform:uppercase}
        .heading-kicker::before{content:'';width:25px;height:3px;background:var(--gold);border-radius:10px}
        .heading h1{margin-bottom:9px;font-size:31px;letter-spacing:-1px;color:var(--primary-dark)}
        .heading p{font-size:13px;line-height:1.65;color:var(--muted)}

        .portal-card{display:flex;align-items:center;gap:12px;margin-bottom:24px;padding:12px 14px;background:var(--gold-soft);border:1px solid #eed181;border-radius:11px}
        .portal-symbol{width:37px;height:37px;display:grid;place-items:center;flex:0 0 37px;font-size:13px;font-weight:900;color:#2f1d00;background:var(--gold);border-radius:9px}
        .portal-copy{min-width:0}.portal-copy span,.portal-copy strong{display:block}.portal-copy span{margin-bottom:2px;font-size:8px;font-weight:850;letter-spacing:.8px;color:var(--gold-dark);text-transform:uppercase}.portal-copy strong{font-size:13px;color:#3a290e}.course-name{color:var(--gold-dark)}
        .change-link{margin-left:auto;font-size:9px;font-weight:850;color:var(--primary);text-transform:uppercase}.change-link:hover{text-decoration:underline}

        .alert{margin-bottom:17px;padding:11px 13px;font-size:11px;line-height:1.5;border-radius:9px}.alert-error{color:var(--danger);background:#fef3f2;border:1px solid #fecdca}.alert-success{color:var(--success);background:#ecfdf3;border:1px solid #abefc6}
        .form-group{margin-bottom:17px}.form-group label{display:block;margin-bottom:7px;font-size:11px;font-weight:750;color:#3c3344}
        .input-wrap{position:relative}.field-icon{position:absolute;top:50%;left:13px;width:17px;height:17px;transform:translateY(-50%);fill:none;stroke:#93889b;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;pointer-events:none}
        .form-control{width:100%;height:48px;padding:0 45px 0 41px;font-size:13px;color:var(--ink);background:var(--soft);border:1px solid #ddd3e3;border-radius:9px;outline:none;transition:.2s}.form-control::placeholder{color:#a79dab}.form-control:focus{background:white;border-color:var(--primary-light);box-shadow:0 0 0 4px rgba(69,6,147,.08)}
        .toggle-password{position:absolute;top:50%;right:10px;transform:translateY(-50%);padding:6px;font-size:9px;font-weight:850;color:var(--primary);background:transparent;border:0;cursor:pointer;text-transform:uppercase}.field-error{margin-top:6px;font-size:10px;color:var(--danger)}
        .form-options{display:flex;align-items:center;justify-content:space-between;gap:14px;margin:3px 0 21px;font-size:10px}.remember{display:flex;align-items:center;gap:7px;color:var(--text);cursor:pointer}.remember input{width:14px;height:14px;accent-color:var(--primary)}.forgot{font-weight:800;color:var(--primary)}.forgot:hover{text-decoration:underline}
        .login-button{width:100%;height:49px;display:flex;align-items:center;justify-content:center;gap:9px;font-size:12px;font-weight:850;color:white;background:var(--primary);border:0;border-radius:9px;box-shadow:0 11px 24px rgba(69,6,147,.19);cursor:pointer;transition:.2s}.login-button:hover{background:var(--primary-light);transform:translateY(-1px);box-shadow:0 14px 28px rgba(69,6,147,.25)}.login-button svg{width:16px;height:16px;fill:none;stroke:var(--gold);stroke-width:2.3;stroke-linecap:round;stroke-linejoin:round}
        .register-prompt{margin-top:19px;text-align:center;font-size:10px;color:var(--muted)}.register-prompt a{font-weight:850;color:var(--primary)}.register-prompt a:hover{text-decoration:underline}

        .visual-side{position:relative;height:100%;order:1;display:flex;align-items:center;justify-content:center;overflow:hidden;padding:clamp(42px,6vw,80px);color:white;background:linear-gradient(145deg,var(--primary-dark),var(--primary) 58%,#6716aa)}
        .visual-side::before{content:'';position:absolute;width:560px;height:560px;top:-330px;right:-220px;border:1px solid rgba(255,255,255,.12);border-radius:50%;box-shadow:0 0 0 90px rgba(255,255,255,.025),0 0 0 180px rgba(255,255,255,.018)}
        .visual-side::after{content:'';position:absolute;width:250px;height:250px;left:-145px;bottom:-155px;background:var(--gold);border-radius:50%;opacity:.15}
        .visual-content{position:relative;z-index:1;width:min(500px,100%)}
        .visual-label{display:inline-flex;align-items:center;gap:8px;margin-bottom:20px;padding:7px 11px;font-size:9px;font-weight:850;letter-spacing:.8px;color:#fff1c6;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);border-radius:20px;text-transform:uppercase}.visual-label::before{content:'';width:7px;height:7px;background:var(--gold);border-radius:50%;box-shadow:0 0 0 4px rgba(232,184,74,.12)}
        .visual-content h2{max-width:470px;margin-bottom:15px;font-size:clamp(31px,3.4vw,47px);line-height:1.08;letter-spacing:-1.7px}.visual-content>p{max-width:455px;font-size:13px;line-height:1.7;color:rgba(255,255,255,.7)}

        .schedule-preview{margin-top:32px;padding:17px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.17);border-radius:15px;backdrop-filter:blur(8px)}
        .preview-window{overflow:hidden;color:var(--ink);background:#fbf9fc;border-radius:10px;box-shadow:0 20px 45px rgba(25,2,49,.2)}
        .preview-head{height:42px;display:flex;align-items:center;justify-content:space-between;padding:0 14px;background:white;border-bottom:1px solid #eee7f1}.preview-head strong{font-size:10px;color:var(--primary-dark)}.preview-status{display:flex;align-items:center;gap:6px;font-size:7px;font-weight:800;color:#08724d}.preview-status::before{content:'';width:6px;height:6px;background:#18a66f;border-radius:50%}
        .preview-body{padding:14px}.preview-days{display:grid;grid-template-columns:repeat(5,1fr);gap:7px}.preview-day>span{display:block;margin-bottom:6px;font-size:7px;font-weight:800;color:#968c9d;text-align:center;text-transform:uppercase}.preview-slot{height:34px;margin-bottom:5px;background:#eee8f1;border-radius:4px}.preview-slot.purple{padding:6px;color:white;background:var(--primary)}.preview-slot.gold{padding:6px;color:#372300;background:var(--gold)}.preview-slot.light{padding:6px;color:var(--primary-dark);background:#dfcbed}.preview-slot small,.preview-slot b{display:block;overflow:hidden;white-space:nowrap;text-overflow:ellipsis}.preview-slot small{font-size:5px;opacity:.72}.preview-slot b{margin-top:2px;font-size:6px}
        .visual-points{display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-top:18px}.visual-point{display:flex;align-items:center;gap:8px;font-size:9px;font-weight:700;color:rgba(255,255,255,.72)}.check{width:21px;height:21px;display:grid;place-items:center;flex:0 0 21px;color:#302000;background:var(--gold);border-radius:6px}.check svg{width:12px;height:12px;fill:none;stroke:currentColor;stroke-width:2.4;stroke-linecap:round;stroke-linejoin:round}

        .branded-portal-login{height:100vh;height:100dvh;display:flex;align-items:center;justify-content:center;overflow:hidden;padding:clamp(12px,2vw,28px);background-image:linear-gradient(rgba(0,0,0,.2),rgba(0,0,0,.2)),url("{{ asset('images/landing-background.png') }}");background-position:center;background-repeat:no-repeat;background-size:cover}
        .branded-portal-login .auth-layout{width:min(1180px,100%);height:calc(100dvh - clamp(24px,4vw,56px));min-height:0;grid-template-columns:minmax(0,1.05fr) minmax(420px,.95fr);gap:clamp(28px,4vw,62px)}
        .branded-portal-login .form-side{padding:clamp(22px,3vw,38px) clamp(28px,5vw,58px);background:rgba(255,255,255,.96);border:1px solid rgba(255,255,255,.7);border-radius:24px;box-shadow:0 28px 75px rgba(35,4,61,.24);backdrop-filter:blur(14px)}
        .branded-portal-login .form-content{max-width:400px}
        .branded-portal-login .brand{margin-bottom:22px}
        .branded-portal-login .heading{margin-bottom:18px}
        .branded-portal-login .heading-kicker{color:var(--primary)}
        .branded-portal-login .heading-kicker::before{background:var(--primary)}
        .branded-portal-login .portal-card{margin-bottom:18px;background:#f5effb;border-color:#ddcceb}
        .branded-portal-login .portal-symbol{width:44px;height:44px;flex-basis:44px;padding:2px;background:#fff;border:1px solid #d9c9e7;border-radius:50%}
        .branded-portal-login .portal-symbol img{width:100%;height:100%;object-fit:contain;border-radius:50%}
        .branded-portal-login .portal-symbol.mcc-portal-symbol{padding:0;background:transparent;border:0;border-radius:0}
        .branded-portal-login .mcc-portal-symbol img{border-radius:0}
        .branded-portal-login .portal-symbol.department-portal-symbol{width:56px!important;height:56px!important;flex-basis:56px!important;padding:0;overflow:hidden;background:transparent;border:0}
        .branded-portal-login .portal-symbol.department-portal-symbol img{width:56px!important;height:56px!important;object-fit:cover;clip-path:circle(50% at 50% 50%);transform:none!important}
        .branded-portal-login .portal-symbol.bsit-portal-symbol{overflow:hidden}
        .branded-portal-login .bsit-portal-symbol img{transform:none}
        .branded-portal-login .portal-copy span,.branded-portal-login .course-name{color:var(--primary)}
        .branded-portal-login .portal-copy strong{color:var(--primary-dark)}
        .branded-portal-login .login-button svg{stroke:#fff}
        .branded-portal-login .visual-side{padding:clamp(24px,3vw,48px);background:transparent}
        .branded-portal-login .visual-side::before,.branded-portal-login .visual-side::after{display:none}
        .portal-identity{position:relative;z-index:1;width:min(520px,100%);color:#fff}
        .portal-logo-wrap{width:clamp(120px,13vw,170px);height:clamp(120px,13vw,170px);display:grid;place-items:center;margin-bottom:27px;padding:8px;background:rgba(255,255,255,.96);border:1px solid rgba(255,255,255,.82);border-radius:50%;box-shadow:0 22px 50px rgba(30,3,53,.27)}
        .portal-logo-wrap img{width:100%;height:100%;object-fit:contain;border-radius:50%}
        .portal-logo-wrap.mcc-logo-wrap{width:clamp(150px,16vw,205px);height:clamp(145px,15vw,195px);padding:0;background:transparent;border:0;border-radius:0;box-shadow:none;filter:drop-shadow(0 20px 30px rgba(30,3,53,.28))}
        .portal-logo-wrap.mcc-logo-wrap img{border-radius:0}
        .portal-logo-wrap.department-logo-wrap{width:clamp(145px,15vw,195px)!important;height:clamp(145px,15vw,195px)!important;padding:0;overflow:hidden;background:transparent;border:0}
        .portal-logo-wrap.department-logo-wrap img{width:100%!important;height:100%!important;display:block;object-fit:contain;object-position:50% 50%;clip-path:circle(50% at 50% 50%);transform:none!important}
        .portal-logo-wrap.bsit-logo-wrap{overflow:hidden}
        .portal-logo-wrap.bsit-logo-wrap img{transform:none}
        .department-logo-bsit img,.department-logo-bshm img,.department-logo-bsba img,.department-logo-bsed img,.department-logo-beed img{transform:none}
        .portal-identity .visual-label{margin-bottom:18px;color:#fff;background:rgba(69,6,147,.32);border-color:rgba(255,255,255,.24)}
        .portal-identity .visual-label::before{background:#450693;box-shadow:0 0 0 4px rgba(69,6,147,.2)}
        .portal-identity h2{max-width:510px;margin-bottom:16px;font-size:clamp(37px,4.2vw,59px);line-height:1.02;letter-spacing:-2px;text-shadow:0 5px 22px rgba(26,2,45,.28)}
        .portal-identity>p{max-width:490px;font-size:14px;line-height:1.75;color:rgba(255,255,255,.84);text-shadow:0 3px 14px rgba(26,2,45,.25)}
        .portal-access-list{display:grid;gap:10px;margin-top:28px}
        .portal-access-item{display:flex;align-items:center;gap:11px;font-size:11px;font-weight:750;color:rgba(255,255,255,.9)}
        .portal-access-item span{width:25px;height:25px;display:grid;place-items:center;flex:0 0 25px;color:#fff;background:#450693;border:1px solid rgba(255,255,255,.3);border-radius:7px}
        .portal-access-item svg{width:13px;height:13px;fill:none;stroke:currentColor;stroke-width:2.5;stroke-linecap:round;stroke-linejoin:round}

        @media(max-width:900px){.auth-layout{grid-template-columns:1fr}.visual-side{display:none}.form-side{height:100dvh;min-height:0;padding:24px 22px}.brand{margin-bottom:24px}}
        @media(max-width:480px){.form-side{align-items:center;padding:18px 17px}.heading h1{font-size:26px}.form-options{gap:10px}.change-link{display:none}}
        @media(max-width:900px){.branded-portal-login{padding:0;background-position:62% center}.branded-portal-login .auth-layout{width:100%;height:100dvh;min-height:0;display:block}.branded-portal-login .form-side{height:100dvh;min-height:0;padding:24px 22px;border:0;border-radius:0;background:rgba(255,255,255,.97)}.branded-portal-login .brand{margin-bottom:20px}}
        @media(max-height:850px){
            .branded-portal-login .form-side{padding-top:12px;padding-bottom:12px}
            .branded-portal-login .brand{margin-bottom:10px;font-size:18px}
            .branded-portal-login .brand-mark{width:37px;height:37px;border-radius:10px}
            .branded-portal-login .brand-mark svg{width:20px;height:20px}
            .branded-portal-login .heading{margin-bottom:8px}
            .branded-portal-login .heading-kicker{margin-bottom:4px;font-size:9px}
            .branded-portal-login .heading h1{margin-bottom:5px;font-size:25px}
            .branded-portal-login .heading p{font-size:12px;line-height:1.4}
            .branded-portal-login .portal-card{margin-bottom:8px;padding:7px 10px}
            .branded-portal-login .portal-symbol{width:38px;height:38px;flex-basis:38px}
            .branded-portal-login .form-group{margin-bottom:8px}
            .branded-portal-login .form-group label{margin-bottom:4px}
            .branded-portal-login .form-control{height:40px}
            .branded-portal-login .form-options{margin:2px 0 9px}
            .branded-portal-login .login-button{height:41px}
            .branded-portal-login .register-prompt{margin-top:7px}
            .portal-logo-wrap{width:110px;height:110px;margin-bottom:14px}
            .portal-identity .visual-label{margin-bottom:11px}
            .portal-identity h2{margin-bottom:10px;font-size:36px}
            .portal-identity>p{line-height:1.5}
            .portal-access-list{margin-top:16px;gap:7px}
        }
    </style>
</head>
<body @class(['branded-portal-login'=>$isBrandedPortal])>

<main class="auth-layout">
    <section class="form-side">
        <div class="form-content">
            <a class="brand" href="{{ route('home') }}"><span class="brand-mark"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18M8 14h3M13 14h3M8 18h3"/></svg></span><span>{{ $isBrandedPortal ? 'MCC | Scheduler' : 'Scheduler' }}</span></a>
            <header class="heading"><span class="heading-kicker">Secure portal access</span><h1>{{ $isBrandedPortal ? $portalBrand['welcome'] : 'Welcome back' }}</h1><p>{{ $isBrandedPortal ? $portalBrand['description'] : 'Enter your account credentials to continue.' }}</p></header>

            <div class="portal-card"><span @class(['portal-symbol','mcc-portal-symbol'=>$usesMccLogo,'department-portal-symbol'=>$usesDepartmentLogo,'bsit-portal-symbol'=>$usesBsitLogo,$departmentLogoClass=>$usesDepartmentLogo])>@if($isBrandedPortal)<img src="{{ asset($portalBrand['logo']) }}" alt="{{ $portalBrand['logoAlt'] }}">@else{{ $roleSymbols[$selectedRole] }}@endif</span><div class="portal-copy"><span>Selected portal</span><strong>{{ ucfirst($selectedRole) }} Portal @if($selectedRole==='dean'&&$selectedCourse)<em class="course-name">· {{ $selectedCourse }}</em>@endif</strong></div><a class="change-link" href="{{ route('home') }}">Change</a></div>
            @if($errors->any())<div class="alert alert-error" role="alert">Please check your login information and try again.</div>@endif
            @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <input type="hidden" name="role" value="{{ $selectedRole }}"><input type="hidden" name="course" value="{{ $selectedCourse }}">
                <div class="form-group"><label for="email">Email address</label><div class="input-wrap"><svg class="field-icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg><input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required autofocus autocomplete="username"></div>@error('email')<p class="field-error">{{ $message }}</p>@enderror</div>
                <div class="form-group"><label for="password">Password</label><div class="input-wrap"><svg class="field-icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3M12 15v2"/></svg><input id="password" class="form-control" type="password" name="password" placeholder="Enter your password" required autocomplete="current-password"><button id="togglePassword" class="toggle-password" type="button">Show</button></div>@error('password')<p class="field-error">{{ $message }}</p>@enderror</div>
                <div class="form-options"><label class="remember"><input type="checkbox" name="remember" @checked(old('remember'))>Remember me</label>@if(Route::has('password.request'))<a class="forgot" href="{{ route('password.request') }}">Forgot password?</a>@endif</div>
                <button class="login-button" type="submit">Sign In to {{ ucfirst($selectedRole) }} Portal <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg></button>
            </form>
            @if(in_array($selectedRole,['dean','instructor','student'],true))<p class="register-prompt">Don't have an account? <a href="{{ route('register',['role'=>$selectedRole,'course'=>$selectedCourse]) }}">Register here</a></p>@endif
        </div>
    </section>

    <section class="visual-side" aria-label="Scheduler platform overview">
        @if($isBrandedPortal)
        <div class="portal-identity">
            <div @class(['portal-logo-wrap','mcc-logo-wrap'=>$usesMccLogo,'department-logo-wrap'=>$usesDepartmentLogo,'bsit-logo-wrap'=>$usesBsitLogo,$departmentLogoClass=>$usesDepartmentLogo])><img src="{{ asset($portalBrand['logo']) }}" alt="{{ $portalBrand['logoAlt'] }}"></div>
            <span class="visual-label">{{ $portalBrand['label'] }}</span>
            <h2>{{ $portalBrand['title'] }}</h2>
            <p>{{ $portalBrand['summary'] }}</p>
            <div class="portal-access-list">
                @foreach($portalBrand['features'] as $feature)
                    <div class="portal-access-item"><span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg></span>{{ $feature }}</div>
                @endforeach
            </div>
        </div>
        @else
        <div class="visual-content"><span class="visual-label">Academic scheduling platform</span><h2>Everything in its right place.</h2><p>Connected schedules, clear workloads, and real-time room visibility for every academic role.</p>
            <div class="schedule-preview"><div class="preview-window"><div class="preview-head"><strong>Weekly Schedule</strong><span class="preview-status">Conflict free</span></div><div class="preview-body"><div class="preview-days">
                <div class="preview-day"><span>Mon</span><div class="preview-slot purple"><small>08:00</small><b>IT 101</b></div><div class="preview-slot"></div><div class="preview-slot light"><small>13:00</small><b>Lab 2</b></div></div>
                <div class="preview-day"><span>Tue</span><div class="preview-slot"></div><div class="preview-slot gold"><small>10:00</small><b>Room 4</b></div><div class="preview-slot"></div></div>
                <div class="preview-day"><span>Wed</span><div class="preview-slot light"><small>08:30</small><b>IT 203</b></div><div class="preview-slot"></div><div class="preview-slot purple"><small>14:00</small><b>Lab 1</b></div></div>
                <div class="preview-day"><span>Thu</span><div class="preview-slot"></div><div class="preview-slot purple"><small>10:30</small><b>IT 304</b></div><div class="preview-slot"></div></div>
                <div class="preview-day"><span>Fri</span><div class="preview-slot gold"><small>09:00</small><b>Lab 5</b></div><div class="preview-slot"></div><div class="preview-slot light"><small>15:00</small><b>IT 401</b></div></div>
            </div></div></div></div>
            <div class="visual-points"><div class="visual-point"><span class="check"><svg viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></svg></span>Role-based access</div><div class="visual-point"><span class="check"><svg viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></svg></span>Real-time information</div></div>
        </div>
        @endif
    </section>
</main>

<script>(()=>{const input=document.getElementById('password'),button=document.getElementById('togglePassword');button.addEventListener('click',()=>{const visible=input.type==='text';input.type=visible?'password':'text';button.textContent=visible?'Show':'Hide'})})();</script>
</body>
</html>
