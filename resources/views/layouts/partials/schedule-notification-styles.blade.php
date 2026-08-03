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
        width:42px;
        height:42px;
        display:grid;
        place-items:center;
        color:var(--primary);
        background:rgba(255,255,255,.82);
        border:1px solid rgba(69,6,147,.18);
        border-radius:11px;
        box-shadow:0 8px 22px rgba(45,7,86,.08);
        cursor:pointer;
        transition:transform .16s ease,background .16s ease,border-color .16s ease;
    }
    .schedule-notification-trigger:hover,
    .schedule-notification-trigger[aria-expanded="true"] {
        background:#fff;
        border-color:rgba(69,6,147,.42);
        transform:translateY(-1px);
    }
    .schedule-notification-trigger svg {
        width:21px;
        height:21px;
        fill:none;
        stroke:currentColor;
        stroke-linecap:round;
        stroke-linejoin:round;
        stroke-width:1.9;
    }
    .schedule-notification-count {
        position:absolute;
        top:-5px;
        right:-5px;
        min-width:19px;
        height:19px;
        display:grid;
        place-items:center;
        padding:0 5px;
        color:#fff;
        background:#dc2626;
        border:2px solid #fff;
        border-radius:999px;
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
        top:calc(100% + 12px);
        right:0;
        width:min(390px,calc(100vw - 30px));
        overflow:hidden;
        background:rgba(255,255,255,.97);
        border:1px solid rgba(69,6,147,.16);
        border-radius:16px;
        box-shadow:0 24px 70px rgba(28,6,52,.24);
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
        padding:17px 18px 14px;
        border-bottom:1px solid rgba(69,6,147,.1);
    }
    .schedule-notification-header h2 {
        color:var(--navy);
        font-size:15px;
        line-height:1.2;
    }
    .schedule-notification-mark-all {
        padding:0;
        color:var(--primary);
        background:transparent;
        border:0;
        font-size:11px;
        font-weight:800;
        cursor:pointer;
    }
    .schedule-notification-mark-all:hover {
        text-decoration:underline;
    }
    .schedule-notification-list {
        position:relative;
        z-index:1;
        max-height:370px;
        overflow-y:auto;
        background:#fff;
        overscroll-behavior:contain;
    }
    .schedule-notification-item {
        position:relative;
        width:100%;
        display:block;
        padding:15px 18px 15px 22px;
        color:var(--text);
        text-align:left;
        background:transparent;
        border:0;
        border-bottom:1px solid rgba(69,6,147,.08);
        cursor:pointer;
    }
    .schedule-notification-item:hover {
        background:rgba(69,6,147,.055);
    }
    .schedule-notification-item.is-unread {
        background:rgba(69,6,147,.075);
    }
    .schedule-notification-item.is-unread::before {
        content:"";
        position:absolute;
        top:20px;
        left:9px;
        width:6px;
        height:6px;
        background:var(--primary);
        border-radius:50%;
    }
    .schedule-notification-item strong {
        display:block;
        margin-bottom:5px;
        color:var(--navy);
        font-size:12px;
        line-height:1.3;
    }
    .schedule-notification-item span {
        display:block;
        color:var(--muted);
        font-size:11px;
        line-height:1.45;
    }
    .schedule-notification-item time {
        display:block;
        margin-top:7px;
        color:#8b8095;
        font-size:9px;
        font-weight:700;
    }
    .schedule-notification-empty {
        padding:30px 22px;
        color:var(--muted);
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
            width:40px;
            height:40px;
        }
    }
</style>
