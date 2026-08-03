<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scheduler</title>
    <style>
        * {
            box-sizing: border-box;
        }

        html, body {
            width: 100%;
            height: 100%;
            margin: 0;
            overflow: hidden;
            background-color: #ffffff;
        }

        body {
            height: 100vh;
            height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(28px, 5vw, 76px);
            background-image:
                linear-gradient(rgba(0, 0, 0, 0.20), rgba(0, 0, 0, 0.20)),
                url("{{ asset('images/landing-background.png') }}");
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
        }

        body.modal-open {
            overflow: hidden;
        }

        button {
            font: inherit;
        }

        .landing-shell {
            width: min(1180px, 100%);
            max-height: 100%;
        }

        .landing-intro {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: clamp(14px, 2vw, 24px);
            width: 100%;
            padding: 0 0 clamp(26px, 3vw, 38px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.22);
        }

        .institution-logos {
            width: min(900px, 100%);
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
            align-items: center;
            gap: clamp(18px, 3vw, 42px);
        }

        .department-logo-group {
            display: flex;
            align-items: center;
            gap: clamp(14px, 2vw, 27px);
        }

        .department-logo-group--left {
            justify-content: flex-end;
        }

        .department-logo-group--right {
            justify-content: flex-start;
        }

        .department-emblem {
            width: clamp(74px, 7vw, 102px);
            height: clamp(74px, 7vw, 102px);
            display: block;
            object-fit: cover;
            object-position: center;
            border-radius: 50%;
            clip-path: circle(50% at 50% 50%);
            filter: drop-shadow(0 12px 22px rgba(25, 3, 42, 0.28));
        }

        .department-emblem.department-emblem--featured {
            width: clamp(90px, 8.5vw, 122px);
            height: clamp(90px, 8.5vw, 122px);
        }

        .college-logo {
            width: clamp(190px, 18vw, 250px);
            max-height: 32vh;
            object-fit: contain;
            filter: drop-shadow(0 18px 30px rgba(25, 3, 42, 0.30));
        }

        .brand-copy {
            width: 100%;
            text-align: center;
        }

        .brand-name {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.22em;
            margin: 0;
            color: #ffffff;
            font-family: "Segoe UI", Inter, Arial, Helvetica, sans-serif;
            font-size: clamp(50px, 5.4vw, 78px);
            font-weight: 750;
            line-height: 1;
            letter-spacing: -1.8px;
            text-shadow: 0 4px 18px rgba(25, 3, 42, 0.28);
        }

        .brand-name b {
            color: #450693;
            font-weight: 300;
        }

        .brand-tagline {
            max-width: 720px;
            margin: 22px auto 0;
            padding-top: 19px;
            color: rgba(255, 255, 255, 0.86);
            border-top: 1px solid rgba(255, 255, 255, 0.25);
            font-family: "Segoe UI", Inter, Arial, Helvetica, sans-serif;
            font-size: clamp(19px, 1.75vw, 25px);
            font-weight: 450;
            line-height: 1.55;
            letter-spacing: 0.2px;
            text-shadow: 0 3px 14px rgba(25, 3, 42, 0.28);
        }

        .portal-area {
            padding-top: clamp(25px, 3vw, 36px);
        }

        .portal-heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 35px;
            margin-bottom: 20px;
        }

        .portal-heading span {
            display: block;
            margin-bottom: 7px;
            color: #450693;
            font-family: "Segoe UI", Inter, Arial, Helvetica, sans-serif;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.4px;
            text-transform: uppercase;
        }

        .portal-heading h2 {
            margin: 0 0 7px;
            color: #ffffff;
            font-family: "Segoe UI", Inter, Arial, Helvetica, sans-serif;
            font-size: clamp(25px, 2.4vw, 34px);
            line-height: 1.15;
            letter-spacing: -0.7px;
        }

        .portal-heading p {
            max-width: 380px;
            margin: 0 0 2px;
            color: rgba(255, 255, 255, 0.72);
            font-family: "Segoe UI", Inter, Arial, Helvetica, sans-serif;
            font-size: 13px;
            line-height: 1.55;
        }

        .portal-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 13px;
        }

        .portal-card {
            min-height: 148px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: space-between;
            padding: 19px;
            color: #ffffff;
            text-align: left;
            text-decoration: none;
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.18), rgba(255, 255, 255, 0.09));
            border: 1px solid rgba(255, 255, 255, 0.24);
            border-radius: 16px;
            cursor: pointer;
            transition: transform 0.2s ease, background 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        .portal-card:hover,
        .portal-card:focus-visible {
            transform: translateY(-4px);
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.27), rgba(255, 255, 255, 0.14));
            border-color: rgba(69, 6, 147, 0.78);
            box-shadow: 0 17px 35px rgba(26, 4, 43, 0.20);
            outline: none;
        }

        .portal-top {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        }

        .portal-symbol {
            width: 39px;
            height: 39px;
            display: grid;
            place-items: center;
            color: #3c075d;
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(255, 255, 255, 0.75);
            border-radius: 11px;
            font-family: "Segoe UI", Inter, Arial, Helvetica, sans-serif;
            font-size: 15px;
            font-weight: 850;
        }

        .portal-card:nth-child(2) .portal-symbol {
            color: #ffffff;
            background: #450693;
            border-color: rgba(255, 255, 255, 0.42);
        }

        .portal-arrow {
            color: rgba(255, 255, 255, 0.70);
            font-size: 20px;
            transition: transform 0.2s ease;
        }

        .portal-card:hover .portal-arrow {
            transform: translateX(4px);
        }

        .portal-card h3 {
            margin: 16px 0 4px;
            font-family: "Segoe UI", Inter, Arial, Helvetica, sans-serif;
            font-size: 16px;
            font-weight: 750;
        }

        .portal-card p {
            margin: 0;
            color: rgba(255, 255, 255, 0.68);
            font-family: "Segoe UI", Inter, Arial, Helvetica, sans-serif;
            font-size: 11px;
            line-height: 1.45;
        }

        .course-modal[hidden] {
            display: none;
        }

        .course-modal {
            position: fixed;
            z-index: 100;
            inset: 0;
            display: grid;
            place-items: center;
            padding: clamp(16px, 4vw, 54px);
            background: rgba(69, 6, 147, 0.14);
            backdrop-filter: blur(8px) saturate(110%);
            -webkit-backdrop-filter: blur(8px) saturate(110%);
        }

        .course-dialog {
            position: relative;
            isolation: isolate;
            width: min(1240px, 100%);
            overflow: hidden;
            padding: clamp(26px, 3.3vw, 44px);
            color: #ffffff;
            background:
                radial-gradient(circle at 92% 10%, rgba(241, 224, 255, 0.28), transparent 30%),
                linear-gradient(135deg, rgba(126, 82, 164, 0.44), rgba(76, 38, 108, 0.30));
            border: 1px solid rgba(245, 235, 255, 0.46);
            border-radius: 26px;
            box-shadow: 0 35px 90px rgba(41, 10, 66, 0.38);
            backdrop-filter: blur(20px) saturate(120%);
            -webkit-backdrop-filter: blur(20px) saturate(120%);
        }

        .course-dialog::before,
        .course-dialog::after {
            position: absolute;
            z-index: -1;
            content: "";
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 50%;
            pointer-events: none;
        }

        .course-dialog::before {
            width: 330px;
            height: 330px;
            top: -235px;
            right: 80px;
        }

        .course-dialog::after {
            width: 210px;
            height: 210px;
            right: -105px;
            bottom: -115px;
        }

        .dialog-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 9px;
        }

        .dialog-head h2 {
            margin: 0;
            color: #ffffff;
            font-family: "Segoe UI", Inter, Arial, Helvetica, sans-serif;
            font-size: clamp(26px, 2.8vw, 39px);
            line-height: 1.1;
            letter-spacing: -0.8px;
        }

        .close-modal {
            flex: 0 0 auto;
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            color: #ffffff;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.22);
            border-radius: 12px;
            font-size: 25px;
            cursor: pointer;
            transition: background 0.18s ease, transform 0.18s ease;
        }

        .close-modal:hover,
        .close-modal:focus-visible {
            background: rgba(255, 255, 255, 0.23);
            outline: none;
            transform: rotate(4deg);
        }

        .course-dialog > p {
            margin: 0 0 clamp(22px, 2.7vw, 32px);
            color: rgba(255, 255, 255, 0.76);
            font-family: "Segoe UI", Inter, Arial, Helvetica, sans-serif;
            font-size: 14px;
            line-height: 1.5;
        }

        .course-grid {
            position: relative;
            display: grid;
            grid-template-columns: repeat(5, minmax(124px, 152px));
            justify-content: space-between;
            gap: clamp(18px, 3vw, 42px);
            padding: 27px clamp(8px, 1.5vw, 20px) 24px;
        }

        .course-link {
            position: relative;
            min-width: 0;
            min-height: 408px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            color: #ffffff;
            text-align: center;
            text-decoration: none;
            font-family: "Segoe UI", Inter, Arial, Helvetica, sans-serif;
            font-size: 12px;
            font-weight: 800;
            transition: transform 0.22s ease;
        }

        .course-link::before {
            position: absolute;
            z-index: 0;
            top: -24px;
            bottom: -20px;
            left: 50%;
            width: 2px;
            content: "";
            background: linear-gradient(90deg, #8f8294, #f1edf2, #7a6d80);
            box-shadow: 1px 0 3px rgba(19, 2, 30, 0.25);
            transform: translateX(-50%);
        }

        .course-link::after {
            position: absolute;
            z-index: 2;
            top: -3px;
            left: 50%;
            width: calc(100% + 14px);
            height: 5px;
            content: "";
            background: linear-gradient(180deg, #ffffff, #bdb0c1 50%, #76697c 52%, #dbd2de);
            border-radius: 999px;
            box-shadow: 0 3px 6px rgba(15, 2, 25, 0.24);
            transform: translateX(-50%);
        }

        .course-flag {
            position: relative;
            z-index: 1;
            width: 100%;
            min-height: 400px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 13px;
            padding: 38px 10px 72px;
            overflow: hidden;
            color: #ffffff;
            background:
                linear-gradient(90deg, rgba(0, 0, 0, 0.12), transparent 18%, rgba(255, 255, 255, 0.16) 50%, transparent 77%, rgba(0, 0, 0, 0.17)),
                linear-gradient(155deg, var(--flag-light), var(--flag-color) 55%, var(--flag-dark));
            clip-path: polygon(0 0, 100% 0, 100% 84%, 50% 100%, 0 84%);
            filter: drop-shadow(0 14px 13px rgba(14, 2, 24, 0.27));
            transition: filter 0.22s ease, transform 0.22s ease;
        }

        .course-flag::before {
            position: absolute;
            inset: 0;
            content: "";
            background: linear-gradient(105deg, transparent 20%, rgba(255, 255, 255, 0.15) 42%, transparent 61%);
            pointer-events: none;
        }

        .course-logo {
            position: relative;
            z-index: 1;
            width: 82px !important;
            height: 82px !important;
            display: block;
            padding: 0;
            object-fit: cover;
            object-position: center;
            background: transparent;
            border: 0;
            border-radius: 50%;
            clip-path: circle(50% at 50% 50%);
            overflow: hidden;
            box-shadow: 0 9px 22px rgba(24, 2, 39, 0.28);
            transition: transform 0.22s ease, box-shadow 0.22s ease;
        }

        .course-name {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            min-width: 0;
            width: 100%;
        }

        .course-name strong {
            color: #ffffff;
            font-size: 20px;
            line-height: 1.2;
            letter-spacing: 0.4px;
            text-shadow: 0 2px 7px rgba(14, 2, 24, 0.35);
        }

        .course-name strong.course-code--vertical {
            writing-mode: vertical-rl;
            text-orientation: upright;
            line-height: 1;
            letter-spacing: 5px;
        }

        .course-name small {
            color: rgba(255, 255, 255, 0.83);
            font-size: 10px;
            font-weight: 650;
            line-height: 1.3;
            text-shadow: 0 1px 5px rgba(14, 2, 24, 0.30);
        }

        .course-arrow {
            position: absolute;
            z-index: 2;
            right: 50%;
            bottom: 49px;
            width: 30px;
            height: 30px;
            display: grid;
            place-items: center;
            color: var(--flag-color);
            background: rgba(255, 255, 255, 0.94);
            border-radius: 50%;
            font-size: 15px;
            box-shadow: 0 5px 12px rgba(20, 3, 34, 0.22);
            transform: translateX(50%);
            transition: color 0.2s ease, background 0.2s ease, transform 0.2s ease;
        }

        .course-link:hover,
        .course-link:focus-visible {
            color: #ffffff;
            outline: none;
            transform: translateY(-5px);
        }

        .course-link:hover .course-flag,
        .course-link:focus-visible .course-flag {
            filter: drop-shadow(0 20px 18px rgba(14, 2, 24, 0.40));
            transform: scale(1.025);
        }

        .course-link:hover .course-logo,
        .course-link:focus-visible .course-logo {
            box-shadow: 0 12px 27px rgba(19, 2, 31, 0.38);
            transform: none;
        }

        .course-link:hover .course-arrow,
        .course-link:focus-visible .course-arrow {
            color: var(--flag-dark);
            background: #ffffff;
            transform: translateX(50%) translateY(3px);
        }

        @media (max-width: 980px) {
            .portal-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .course-grid {
                grid-template-columns: repeat(5, minmax(100px, 132px));
                justify-content: space-between;
                gap: 12px;
            }

            .course-link {
                min-height: 324px;
            }

            .course-flag {
                min-height: 316px;
                padding-right: 7px;
                padding-left: 7px;
            }

            .course-logo {
                width: 70px;
                height: 70px;
            }
        }

        @media (max-width: 700px) {
            body {
                justify-content: center;
                padding: 12px;
            }

            .landing-intro {
                gap: 8px;
                padding-bottom: 12px;
                text-align: center;
            }

            .institution-logos {
                grid-template-columns: minmax(0, 1fr) 82px minmax(0, 1fr);
                gap: 8px;
            }

            .department-logo-group {
                gap: 6px;
            }

            .department-emblem {
                width: clamp(34px, 10vw, 44px);
                height: clamp(34px, 10vw, 44px);
            }

            .department-emblem.department-emblem--featured {
                width: clamp(40px, 12vw, 54px);
                height: clamp(40px, 12vw, 54px);
            }

            .college-logo {
                width: 82px;
                max-height: 86px;
                justify-self: center;
            }

            .brand-name {
                justify-content: center;
                font-size: clamp(22px, 7vw, 30px);
                letter-spacing: -0.8px;
            }

            .brand-tagline {
                max-width: none;
                margin: 8px 0 0;
                padding-top: 7px;
                font-size: clamp(10px, 3vw, 13px);
                line-height: 1.3;
            }

            .portal-area {
                padding-top: 12px;
            }

            .portal-heading {
                display: flex;
                align-items: flex-end;
                margin-bottom: 10px;
                text-align: left;
            }

            .portal-heading p {
                display: none;
            }

            .portal-heading span { margin-bottom: 4px; font-size: 8px; }
            .portal-heading h2 { margin-bottom: 0; font-size: 19px; }

            .portal-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
            }

            .portal-card {
                min-height: 88px;
                padding: 11px;
                border-radius: 12px;
            }

            .portal-symbol { width: 30px; height: 30px; border-radius: 8px; font-size: 12px; }
            .portal-arrow { font-size: 16px; }
            .portal-card h3 { margin: 8px 0 2px; font-size: 13px; }
            .portal-card p { font-size: 9px; line-height: 1.25; }

            .course-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .course-dialog {
                padding: 22px 18px;
                border-radius: 20px;
            }

            .dialog-head h2 {
                font-size: 25px;
            }

            .course-dialog > p {
                margin-bottom: 18px;
                font-size: 12px;
            }

            .course-link {
                min-height: 152px;
            }

            .course-flag {
                min-height: 146px;
                gap: 7px;
                padding: 13px 7px 34px;
            }

            .course-logo {
                width: 52px;
                height: 52px;
                padding: 3px;
            }

            .course-name strong {
                font-size: 15px;
            }

            .course-name small {
                font-size: 8px;
            }

            .course-arrow {
                bottom: 20px;
                width: 24px;
                height: 24px;
                font-size: 12px;
            }
        }

        @media (max-width: 430px) {
            .course-modal {
                padding: 10px;
            }

            .course-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .course-link {
                min-height: 145px;
            }

            .course-logo {
                width: 50px;
                height: 50px;
            }

            .course-flag {
                min-height: 139px;
                padding-top: 12px;
            }
        }

        @media (max-height: 820px) and (min-width: 701px) {
            body { padding: 18px 34px; }
            .college-logo { width: clamp(150px, 14vw, 185px); max-height: 185px; }
            .department-emblem { width:clamp(58px,6vw,76px); height:clamp(58px,6vw,76px); }
            .department-emblem.department-emblem--featured { width:clamp(78px,7.5vw,98px); height:clamp(78px,7.5vw,98px); }
            .institution-logos { width:min(760px,100%); gap:24px; }
            .landing-intro { gap: 10px; padding-bottom: 16px; }
            .brand-name { font-size: clamp(42px, 4.8vw, 58px); }
            .brand-tagline { margin-top: 11px; padding-top: 10px; font-size: 16px; line-height: 1.35; }
            .portal-area { padding-top: 15px; }
            .portal-heading { margin-bottom: 11px; }
            .portal-heading span { margin-bottom: 3px; font-size: 9px; }
            .portal-heading h2 { margin-bottom: 0; font-size: 23px; }
            .portal-heading p { font-size: 11px; }
            .portal-card { min-height: 104px; padding: 14px; }
            .portal-card h3 { margin-top: 9px; }
            .course-dialog { padding: 16px 28px; }
            .dialog-head h2 { font-size: 27px; }
            .course-dialog > p { margin-bottom: 7px; font-size: 12px; }
            .course-grid { gap: 12px; padding-top: 18px; padding-bottom: 13px; }
            .course-link { min-height: 408px; }
            .course-flag { min-height: 400px; }
        }

        @media (max-height: 560px) and (max-width: 700px) {
            .course-modal { padding: 8px; }
            .course-dialog { padding: 14px 18px; border-radius: 18px; }
            .dialog-head { margin-bottom: 4px; }
            .dialog-head h2 { font-size: 22px; }
            .close-modal { width: 34px; height: 34px; font-size: 20px; }
            .course-dialog > p { margin-bottom: 7px; font-size: 10px; }
            .course-grid { gap: 8px; padding-top: 18px; padding-bottom: 13px; }
            .course-link { min-height: 126px; }
            .course-flag { min-height: 120px; gap: 5px; padding: 10px 5px 29px; }
            .course-logo { width: 44px; height: 44px; border-width: 2px; }
            .course-name strong { font-size: 13px; }
            .course-name small { font-size: 7px; line-height: 1.2; }
            .course-arrow { bottom: 17px; width: 21px; height: 21px; font-size: 10px; }
        }
    </style>
</head>
<body>
    <main class="landing-shell">
        <section class="landing-intro" aria-labelledby="landingTitle">
            <div class="institution-logos" aria-label="Madridejos Community College and department logos">
                <div class="department-logo-group department-logo-group--left">
                    <img class="department-emblem" src="{{ asset('images/bsba-department-logo.jpg') }}" alt="Business Administration Department logo">
                    <img class="department-emblem department-emblem--featured" src="{{ asset('images/bsit-department-logo.jpg') }}" alt="Information Technology Department logo">
                </div>
                <img class="college-logo" src="{{ asset('images/mcc-college-logo.png') }}" alt="Madridejos Community College logo">
                <div class="department-logo-group department-logo-group--right">
                    <img class="department-emblem department-emblem--featured" src="{{ asset('images/bshm-department-logo.jpg') }}" alt="Hospitality Management Department logo">
                    <img class="department-emblem" src="{{ asset('images/education-department-logo.jpg') }}" alt="College of Education logo">
                </div>
            </div>
            <div class="brand-copy">
                <h1 id="landingTitle" class="brand-name" aria-label="MCC | Scheduler"><span>MCC</span><b>|</b><span>Scheduler</span></h1>
                <p class="brand-tagline">An Automated Class Scheduling and Room Allocation System</p>
            </div>
        </section>

        <section class="portal-area" aria-labelledby="portalTitle">
            <div class="portal-heading"><div><span>Secure role access</span><h2 id="portalTitle">Choose your portal</h2></div><p>Continue to the workspace assigned to your account.</p></div>
            <div class="portal-grid">
                <a class="portal-card" href="{{ route('login', ['role' => 'admin']) }}"><div class="portal-top"><span class="portal-symbol">A</span><span class="portal-arrow">→</span></div><div><h3>Administrator</h3><p>Accounts and institution analytics</p></div></a>
                <button id="openCourses" class="portal-card" type="button"><div class="portal-top"><span class="portal-symbol">D</span><span class="portal-arrow">→</span></div><div><h3>Dean</h3><p>Department schedules and resources</p></div></button>
                <a class="portal-card" href="{{ route('login', ['role' => 'instructor']) }}"><div class="portal-top"><span class="portal-symbol">I</span><span class="portal-arrow">→</span></div><div><h3>Instructor</h3><p>Workload and room monitoring</p></div></a>
                <a class="portal-card" href="{{ route('login', ['role' => 'student']) }}"><div class="portal-top"><span class="portal-symbol">S</span><span class="portal-arrow">→</span></div><div><h3>Student</h3><p>Study Load and class schedule</p></div></a>
            </div>
        </section>
    </main>

    <div id="courseModal" class="course-modal" hidden>
        <section class="course-dialog" role="dialog" aria-modal="true" aria-labelledby="courseTitle">
            <div class="dialog-head"><h2 id="courseTitle">Choose your Dean portal</h2><button id="closeCourses" class="close-modal" type="button" aria-label="Close">&times;</button></div>
            <p>Select your department to continue to its dedicated scheduling workspace.</p>
            @php
                $departments = [
                    'BSIT' => ['images/bsit-department-logo.jpg', 'Information Technology', '#9e0b1f', '#c31932', '#5a0310'],
                    'BSED' => ['images/education-department-logo.jpg', 'Secondary Education', '#0b3f73', '#175e96', '#062847'],
                    'BSBA' => ['images/bsba-department-logo.jpg', 'Business Administration', '#0b5d32', '#167a43', '#06391f'],
                    'BEED' => ['images/education-department-logo.jpg', 'Elementary Education', '#1768a2', '#3a91c8', '#0a3e69'],
                    'BSHM' => ['images/bshm-department-logo.jpg', 'Hospitality Management', '#800020', '#a83b55', '#4d0013'],
                ];
            @endphp
            <div class="course-grid">
                @foreach($departments as $course => [$logo, $departmentName, $flagColor, $flagLight, $flagDark])
                    <a class="course-link" href="{{ route('login', ['role' => 'dean', 'course' => $course]) }}" style="--flag-color: {{ $flagColor }}; --flag-light: {{ $flagLight }}; --flag-dark: {{ $flagDark }};">
                        <span class="course-flag">
                            <img class="course-logo" src="{{ asset($logo) }}" alt="{{ $course }} department logo">
                            <span class="course-name"><strong class="course-code--vertical">{{ $course }}</strong><small>{{ $departmentName }}</small></span>
                            <span class="course-arrow" aria-hidden="true">&rarr;</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    </div>

    <script>
        (() => {
            const modal = document.getElementById('courseModal');
            const openButton = document.getElementById('openCourses');
            const closeButton = document.getElementById('closeCourses');
            const openModal = () => { modal.hidden = false; document.body.classList.add('modal-open'); closeButton.focus(); };
            const closeModal = () => { modal.hidden = true; document.body.classList.remove('modal-open'); openButton.focus(); };
            openButton.addEventListener('click', openModal);
            closeButton.addEventListener('click', closeModal);
            modal.addEventListener('click', event => { if (event.target === modal) closeModal(); });
            document.addEventListener('keydown', event => { if (event.key === 'Escape' && !modal.hidden) closeModal(); });
        })();
    </script>
</body>
</html>
