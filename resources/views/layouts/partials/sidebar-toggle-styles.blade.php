<style>
    .sidebar { position:fixed !important; z-index:1100; inset:0 auto 0 0 !important; width:220px !important; height:100vh !important; transform:none; box-shadow:12px 0 35px rgba(15,23,42,.2); transition:transform .25s ease; }
    .main { width:calc(100% - 220px) !important; min-width:0; margin-left:220px !important; transition:none; }
    .topbar-start { min-width:0; display:flex; align-items:center; gap:14px; }
    .topbar-start > div { min-width:0; }
    .sidebar-toggle { position:relative; z-index:1080; width:42px; height:42px; display:none; flex:0 0 42px; flex-direction:column; align-items:center; justify-content:center; gap:5px; padding:0; color:var(--primary); background:white; border:1px solid var(--border); border-radius:10px; box-shadow:0 5px 15px rgba(15,23,42,.07); cursor:pointer; transition:opacity .18s ease,visibility .18s ease,transform .2s ease,box-shadow .2s ease; }
    .sidebar-toggle:hover { transform:translateY(-1px); box-shadow:0 8px 18px rgba(15,23,42,.11); }
    .sidebar-toggle span { width:20px; height:2px; display:block; background:currentColor; border-radius:3px; }
    .sidebar-backdrop { position:fixed; z-index:1090; inset:0; display:none; padding:0; background:rgba(15,23,42,.52); border:0; cursor:pointer; backdrop-filter:blur(2px); }
    @media(max-width:950px) {
        .app { display:flex !important; }
        .sidebar { width:min(280px,86vw) !important; transform:translateX(-105%); }
        .main { width:100% !important; margin-left:0 !important; }
        .sidebar-toggle { display:flex; }
        body.sidebar-open { overflow:hidden; }
        body.sidebar-open .sidebar { transform:translateX(0); }
        body.sidebar-open .sidebar-backdrop { display:block; }
        body.sidebar-open .sidebar-toggle { visibility:hidden; opacity:0; pointer-events:none; }
    }
</style>
