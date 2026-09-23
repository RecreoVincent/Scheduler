<!DOCTYPE html>
<html lang="en">
<head>
    @include('layouts.partials.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#2d045f">
    <title>{{ $isLogout ? 'Signing Out' : 'Signing In' }} | MCC Scheduler</title>
    <style>
        :root { color-scheme:dark; }
        * { box-sizing:border-box; }
        html,body { width:100%; min-height:100%; margin:0; }
        body {
            display:grid;
            min-height:100vh;
            min-height:100dvh;
            place-items:center;
            overflow:hidden;
            color:#fff;
            background:linear-gradient(160deg,#1c0330 0%,#2d045f 55%,#3a0a72 100%);
            font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
        }
        body::before {
            position:fixed;
            z-index:-2;
            inset:0;
            content:"";
            background:radial-gradient(circle at 52% 34%,rgba(151,79,219,.2),transparent 42%);
            animation:ambient-glow 7s ease-in-out infinite alternate;
            pointer-events:none;
        }
        .transition-background::before,
        .transition-background::after {
            position:fixed;
            z-index:-1;
            width:44vmax;
            height:44vmax;
            content:"";
            border-radius:50%;
            filter:blur(4px);
            pointer-events:none;
        }
        .transition-background::before { top:-25vmax; left:-14vmax; background:rgba(112,34,184,.34); }
        .transition-background::after { right:-22vmax; bottom:-25vmax; background:rgba(90,18,153,.38); }
        .loading-card {
            display:grid;
            justify-items:center;
            width:min(420px,calc(100% - 40px));
            padding:clamp(36px,7vw,58px) 28px;
            text-align:center;
            background:linear-gradient(145deg,rgba(255,255,255,.16),rgba(255,255,255,.06));
            border:1px solid rgba(255,255,255,.22);
            border-radius:28px;
            box-shadow:0 28px 70px rgba(17,1,34,.42),inset 0 1px 0 rgba(255,255,255,.18);
            backdrop-filter:blur(16px);
            -webkit-backdrop-filter:blur(16px);
            opacity:0;
            transform:translateY(14px) scale(.975);
            will-change:opacity,transform,filter;
            animation:card-enter .72s cubic-bezier(.22,1,.36,1) .08s forwards;
            transition:opacity .46s cubic-bezier(.4,0,.2,1),transform .46s cubic-bezier(.4,0,.2,1),filter .46s ease;
        }
        .loading-card.is-fading { animation:none; opacity:0; filter:blur(4px); transform:translateY(-10px) scale(.975); }
        .logo-shell {
            display:grid;
            width:112px;
            height:112px;
            margin-bottom:25px;
            place-items:center;
            background:rgba(255,255,255,.13);
            border:1px solid rgba(255,255,255,.27);
            border-radius:28px;
            box-shadow:0 18px 35px rgba(20,1,44,.3);
        }
        .logo-shell img { display:block; width:88px; height:88px; object-fit:contain; }
        h1 { margin:0; font-size:clamp(25px,5vw,33px); letter-spacing:-.8px; }
        p { margin:10px 0 0; color:rgba(255,255,255,.8); font-size:14px; line-height:1.55; }
        .progress { width:min(230px,100%); height:6px; margin-top:29px; overflow:hidden; background:rgba(255,255,255,.18); border-radius:999px; }
        .progress::after { display:block; width:34%; height:100%; content:""; background:linear-gradient(90deg,#fff,#dcb7ff); border-radius:inherit; animation:loading 1s ease-in-out infinite; }
        .loading-copy { margin-top:13px; color:rgba(255,255,255,.7); font-size:12px; font-weight:700; letter-spacing:.45px; text-transform:uppercase; }
        .success-overlay {
            position:fixed;
            inset:0;
            display:grid;
            padding:20px;
            pointer-events:none;
            place-items:center;
            opacity:0;
            visibility:hidden;
            will-change:opacity;
            transition:opacity .4s cubic-bezier(.4,0,.2,1),visibility 0s linear .4s;
        }
        .success-overlay.is-visible { visibility:visible; opacity:1; transition-delay:0s; }
        .success-modal {
            display:grid;
            justify-items:center;
            width:min(390px,100%);
            padding:34px 28px;
            text-align:center;
            color:#28113e;
            background:linear-gradient(145deg,#fff,#f7efff);
            border:1px solid rgba(255,255,255,.9);
            border-radius:24px;
            box-shadow:0 30px 76px rgba(15,1,35,.48);
            opacity:0;
            transform:translateY(18px) scale(.96);
            will-change:opacity,transform;
            transition:opacity .38s ease .05s,transform .5s cubic-bezier(.22,1,.36,1) .05s;
        }
        .success-overlay.is-visible .success-modal { opacity:1; transform:translateY(0) scale(1); }
        .success-icon {
            display:grid;
            width:62px;
            height:62px;
            margin-bottom:17px;
            color:#fff;
            background:#450693;
            border:5px solid #ead9f9;
            border-radius:50%;
            place-items:center;
        }
        .success-icon svg { width:28px; height:28px; fill:none; stroke:currentColor; stroke-linecap:round; stroke-linejoin:round; stroke-width:2.8; }
        .success-modal h2 { margin:0; font-size:23px; letter-spacing:-.35px; }
        .success-modal p { margin-top:8px; color:#685a70; }
        @keyframes card-enter { to { opacity:1; transform:translateY(0) scale(1); } }
        @keyframes ambient-glow { from { opacity:.52; transform:scale(.92); } to { opacity:1; transform:scale(1.08); } }
        @keyframes loading { from { transform:translateX(-145%); } to { transform:translateX(365%); } }
        @media (prefers-reduced-motion:reduce) { *,*::before,*::after { scroll-behavior:auto!important; animation-duration:.01ms!important; animation-iteration-count:1!important; transition-duration:.01ms!important; } }
    </style>
</head>
<body>
    <div class="transition-background" aria-hidden="true"></div>
    <main id="loadingCard" class="loading-card" aria-live="polite">
        <div class="logo-shell"><img src="{{ asset('images/mcc-scheduler-logo-transparent.png') }}" alt="MCC Scheduler"></div>
        <h1>{{ $isLogout ? 'Signing out securely' : 'Welcome to MCC Scheduler' }}</h1>
        <p>{{ $isLogout ? "Completing logout from your {$portalLabel} Portal." : "Preparing your {$portalLabel} Portal workspace." }}</p>
        <div class="progress" aria-hidden="true"></div>
        <div class="loading-copy">{{ $isLogout ? 'Signing you out' : 'Signing you in' }}</div>
    </main>

    <div id="successOverlay" class="success-overlay" aria-hidden="true">
        <section class="success-modal" role="status" aria-live="assertive" aria-label="{{ $isLogout ? 'Logout successful' : 'Login successful' }}">
            <div class="success-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4.2 4.2L19.5 6.5"/></svg></div>
            <h2>{{ $isLogout ? 'Logout successful' : 'Login successful' }}</h2>
            <p>{{ $isLogout ? "You have been safely signed out of the {$portalLabel} Portal." : "Welcome to your {$portalLabel} Portal." }}</p>
        </section>
    </div>

    <script>
        (() => {
            const loadingCard = document.getElementById('loadingCard');
            const successOverlay = document.getElementById('successOverlay');
            const destinationUrl = @json($destinationUrl);

            window.setTimeout(() => {
                loadingCard.classList.add('is-fading');
                successOverlay.classList.add('is-visible');
                successOverlay.setAttribute('aria-hidden', 'false');

                window.setTimeout(() => window.location.replace(destinationUrl), 1000);
            }, 2000);
        })();
    </script>
</body>
</html>
