@php
    $printDepartmentLogo = match(strtoupper((string) ($course ?? 'BSIT'))) {
        'BSBA' => 'images/bsba-department-logo.jpg',
        'BSHM' => 'images/bshm-department-logo.jpg',
        'BSED', 'BEED' => 'images/education-department-logo.jpg',
        default => 'images/bsit-department-logo.jpg',
    };
@endphp
<style>
    @media screen {
        html { min-height:100%; background:#fff; }

        body.print-preview {
            position:relative;
            min-height:100vh;
            margin:0;
            padding:0 0 40px !important;
            color:#34283d;
            background:#fff;
            font-family:"Segoe UI",Inter,Arial,Helvetica,sans-serif;
        }

        body.print-preview::before {
            position:fixed;
            z-index:0;
            inset:76px 0 0;
            content:'';
            pointer-events:none;
            background-image:url("{{ asset($printDepartmentLogo) }}");
            background-position:center;
            background-repeat:no-repeat;
            background-size:min(54vmin,650px) min(54vmin,650px);
            opacity:.12;
        }

        .print-preview .print-toolbar,
        .print-preview .toolbar {
            position:sticky;
            z-index:20;
            top:0;
            left:auto;
            width:100%;
            min-height:64px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:20px;
            margin:0 0 28px;
            padding:8px clamp(20px,3vw,48px);
            color:#fff;
            background-image:
                linear-gradient(100deg,rgba(45,4,95,.98),rgba(69,6,147,.97) 58%,rgba(101,20,171,.96)),
                url("{{ asset('images/landing-background.png') }}");
            background-position:center;
            background-size:cover;
            border:0;
            border-bottom:1px solid rgba(255,255,255,.18);
            border-radius:0;
            box-shadow:0 12px 35px rgba(45,4,95,.22);
            transform:none;
        }

        .print-preview .print-toolbar p,
        .print-preview .toolbar p {
            margin:0;
            color:#fff;
            font-size:13px;
            font-weight:750;
            letter-spacing:.1px;
            text-shadow:0 2px 10px rgba(28,3,47,.25);
        }

        .print-preview .toolbar-actions { display:flex; align-items:center; gap:9px; }
        .print-preview .toolbar-actions form { margin:0; }

        .print-preview .print-button,
        .print-preview .toolbar .button {
            min-height:41px;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            padding:9px 19px;
            color:#fff;
            background:#450693;
            border:1px solid rgba(255,255,255,.18);
            border-radius:10px;
            box-shadow:0 9px 24px rgba(38,3,70,.22);
            font-size:12px;
            font-weight:800;
            text-decoration:none;
            cursor:pointer;
        }

        .print-preview .exit-button {
            min-height:41px;
            padding:9px 17px;
            color:#fff;
            background:rgba(255,255,255,.10);
            border:1px solid rgba(255,255,255,.52);
            border-radius:10px;
            font-size:12px;
            font-weight:800;
            cursor:pointer;
        }

        .print-preview .print-button:hover,
        .print-preview .toolbar .button:hover { background:#5d0bad; }
        .print-preview .exit-button:hover { background:rgba(255,255,255,.20); }

        .print-preview .reports { position:relative;z-index:1;padding:0 24px 30px; }

        .print-preview .schedule-sheet,
        .print-preview .faculty-sheet,
        .print-preview .teaching-load-sheet,
        .print-preview .print-sheet,
        .print-preview .empty-report {
            background-color:rgba(255,255,255,.94);
            border:1px solid rgba(69,6,147,.18);
            border-radius:18px;
            box-shadow:0 24px 65px rgba(45,4,95,.14);
            overflow:hidden;
            backdrop-filter:blur(10px);
            -webkit-backdrop-filter:blur(10px);
        }

        .print-preview .print-sheet {
            width:min(1180px,calc(100% - 32px));
            margin:0 auto;
            padding:34px;
            background:rgba(255,255,255,.95);
            backdrop-filter:blur(14px);
            -webkit-backdrop-filter:blur(14px);
        }
    }

    @media screen and (max-width:700px) {
        body.print-preview { padding-top:0 !important; }
        .print-preview .print-toolbar,
        .print-preview .toolbar { top:auto; align-items:flex-start; flex-direction:column; padding:14px; }
        .print-preview .toolbar-actions { width:100%; }
        .print-preview .toolbar-actions > * { flex:1; }
        .print-preview .toolbar-actions form .print-button { width:100%; }
        .print-preview .reports { padding:0 10px 20px; }
        .print-preview .print-sheet { width:calc(100% - 20px); padding:20px 14px; overflow-x:auto; }
    }

    @media print {
        body.print-preview { padding:0 !important; background:#fff !important; }
        body.print-preview::before { display:none !important; }
        .print-preview .print-sheet { width:100%; margin:0; padding:0; background:#fff; border:0; border-radius:0; box-shadow:none; overflow:visible; }
    }
</style>
