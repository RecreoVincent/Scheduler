<style>
    @media screen {
        html { min-height:100%; background:#bfa3d6; }

        body.print-preview {
            min-height:100vh;
            margin:0;
            padding:0 0 40px !important;
            color:#34283d;
            background-image:
                linear-gradient(rgba(0,0,0,.20),rgba(0,0,0,.20)),
                url("{{ asset('images/landing-background.png') }}");
            background-position:center;
            background-repeat:no-repeat;
            background-size:cover;
            background-attachment:fixed;
            font-family:"Segoe UI",Inter,Arial,Helvetica,sans-serif;
        }

        .print-preview .print-toolbar,
        .print-preview .toolbar {
            position:relative;
            z-index:20;
            top:auto;
            left:auto;
            width:100%;
            min-height:76px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:20px;
            margin:0 0 24px;
            padding:12px clamp(20px,3vw,48px);
            color:#fff;
            background:linear-gradient(145deg,rgba(255,255,255,.22),rgba(255,255,255,.12));
            border:0;
            border-bottom:1px solid rgba(255,255,255,.3);
            border-radius:0;
            box-shadow:0 18px 45px rgba(30,4,50,.19);
            backdrop-filter:blur(16px) saturate(125%);
            -webkit-backdrop-filter:blur(16px) saturate(125%);
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

        .print-preview .reports { padding:0 24px 30px; }

        .print-preview .schedule-sheet,
        .print-preview .faculty-sheet,
        .print-preview .teaching-load-sheet,
        .print-preview .print-sheet,
        .print-preview .empty-report {
            border:1px solid rgba(255,255,255,.72);
            border-radius:18px;
            box-shadow:0 24px 65px rgba(31,5,54,.18);
            overflow:hidden;
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
        .print-preview .print-sheet { width:100%; margin:0; padding:0; background:#fff; border:0; border-radius:0; box-shadow:none; overflow:visible; }
    }
</style>
