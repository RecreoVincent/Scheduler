<style>
    .topbar {
        position:relative;
        z-index:900;
        overflow:visible;
    }
    .content {
        position:relative;
        z-index:1;
    }
    .topbar-actions {
        display:flex;
        align-items:center;
        justify-content:flex-end;
        gap:11px;
        min-width:0;
    }
    .schedule-notification-menu {
        position:relative;
        flex:0 0 auto;
    }
    .schedule-notification-trigger {
        position:relative;
        width:48px;
        height:48px;
        display:grid;
        place-items:center;
        overflow:visible;
        color:#fff;
        background:linear-gradient(145deg,rgba(255,255,255,.22),rgba(255,255,255,.09));
        border:1px solid rgba(255,255,255,.30);
        border-radius:13px;
        box-shadow:0 10px 28px rgba(24,3,48,.20),inset 0 1px 0 rgba(255,255,255,.16);
        backdrop-filter:blur(12px);
        -webkit-backdrop-filter:blur(12px);
        cursor:pointer;
        transition:transform .18s ease,background .18s ease,border-color .18s ease,box-shadow .18s ease;
    }
    .schedule-notification-trigger:hover,
    .schedule-notification-trigger[aria-expanded="true"] {
        background:linear-gradient(145deg,rgba(255,255,255,.30),rgba(255,255,255,.14));
        border-color:rgba(255,255,255,.52);
        box-shadow:0 13px 32px rgba(24,3,48,.26),inset 0 1px 0 rgba(255,255,255,.20);
        transform:translateY(-1px);
    }
    .schedule-notification-trigger svg {
        width:22px;
        height:22px;
        fill:none;
        stroke:currentColor;
        stroke-linecap:round;
        stroke-linejoin:round;
        stroke-width:2;
    }
    .schedule-notification-count {
        position:absolute;
        top:-6px;
        right:-6px;
        min-width:21px;
        height:21px;
        display:grid;
        place-items:center;
        padding:0 5px;
        color:#fff;
        background:linear-gradient(145deg,#ff5c72,#c91f3d);
        border:2px solid #f3eafb;
        border-radius:999px;
        box-shadow:0 5px 13px rgba(130,13,44,.28);
        font-size:9px;
        font-weight:850;
        line-height:1;
    }
    .schedule-notification-count[hidden] {
        display:none;
    }
    .schedule-notification-panel {
        position:absolute;
        z-index:950;
        top:calc(100% + 14px);
        right:0;
        width:min(400px,calc(100vw - 30px));
        overflow:hidden;
        background:rgba(248,244,251,.96);
        border:1px solid rgba(255,255,255,.42);
        border-radius:18px;
        box-shadow:0 26px 75px rgba(28,6,52,.30),0 0 0 1px rgba(69,6,147,.12);
        backdrop-filter:blur(20px);
        -webkit-backdrop-filter:blur(20px);
    }
    .schedule-notification-panel[hidden] {
        display:none;
    }
    .schedule-notification-header {
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
        padding:17px 18px;
        color:#fff;
        background-image:linear-gradient(120deg,rgba(45,4,95,.99),rgba(69,6,147,.97) 60%,rgba(104,24,171,.95));
        border-bottom:1px solid rgba(255,255,255,.15);
    }
    .schedule-notification-header h2 {
        margin:0;
        color:#fff;
        font-size:16px;
        font-weight:850;
        line-height:1.2;
    }
    .schedule-notification-mark-all {
        padding:0;
        min-height:30px;
        padding:6px 10px;
        color:rgba(255,255,255,.92);
        background:rgba(255,255,255,.10);
        border:1px solid rgba(255,255,255,.18);
        border-radius:8px;
        font-size:11px;
        font-weight:800;
        cursor:pointer;
    }
    .schedule-notification-mark-all:hover {
        color:#fff;
        background:rgba(255,255,255,.18);
    }
    .schedule-notification-list {
        position:relative;
        z-index:1;
        max-height:370px;
        overflow-y:auto;
        padding:8px;
        background:transparent;
        overscroll-behavior:contain;
    }
    .schedule-notification-item {
        position:relative;
        width:100%;
        display:block;
        margin:0 0 6px;
        padding:14px 15px 14px 29px;
        color:var(--text);
        text-align:left;
        background:rgba(255,255,255,.72);
        border:1px solid rgba(69,6,147,.08);
        border-radius:11px;
        cursor:pointer;
    }
    .schedule-notification-item:hover {
        background:#fff;
        border-color:rgba(69,6,147,.20);
        box-shadow:0 8px 20px rgba(45,4,95,.08);
        transform:translateY(-1px);
    }
    .schedule-notification-item.is-unread {
        background:linear-gradient(105deg,rgba(238,224,249,.94),rgba(255,255,255,.88));
        border-color:rgba(69,6,147,.16);
    }
    .schedule-notification-item.is-unread::before {
        content:"";
        position:absolute;
        top:19px;
        left:12px;
        width:8px;
        height:8px;
        background:linear-gradient(145deg,#8b37dc,#450693);
        border-radius:50%;
        box-shadow:0 0 0 4px rgba(69,6,147,.09);
    }
    .schedule-notification-item strong {
        display:block;
        margin-bottom:5px;
        color:#2d045f;
        font-size:12.5px;
        line-height:1.3;
    }
    .schedule-notification-item span {
        display:block;
        color:#55465f;
        font-size:11px;
        line-height:1.45;
    }
    .schedule-notification-item time {
        display:block;
        margin-top:7px;
        color:#85778f;
        font-size:9px;
        font-weight:700;
    }
    .schedule-notification-empty {
        margin:8px;
        padding:34px 22px;
        color:#675b70;
        background:rgba(255,255,255,.68);
        border:1px dashed rgba(69,6,147,.18);
        border-radius:12px;
        font-size:12px;
        line-height:1.5;
        text-align:center;
    }
    @media(max-width:640px) {
        .topbar-actions {
            align-self:stretch;
        }
        .schedule-notification-panel {
            position:fixed;
            top:76px;
            right:15px;
            left:15px;
            width:auto;
        }
        .schedule-notification-trigger {
            width:44px;
            height:44px;
        }
    }
</style>
