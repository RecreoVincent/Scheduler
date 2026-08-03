@php
    $notificationUser = auth()->user();
    $notificationRoutePrefix = $notificationUser->role === 'student' ? 'student' : 'instructor';
    $scheduleNotifications = $notificationUser->notifications()->latest()->limit(8)->get();
    $unreadScheduleNotificationCount = $notificationUser->unreadNotifications()->count();
@endphp

<div
    class="schedule-notification-menu"
    data-schedule-notifications
    data-feed-url="{{ route($notificationRoutePrefix.'.notifications.index') }}"
    data-read-all-url="{{ route($notificationRoutePrefix.'.notifications.read-all') }}"
>
    <button
        class="schedule-notification-trigger"
        type="button"
        aria-label="Open schedule notifications"
        aria-haspopup="true"
        aria-expanded="false"
        aria-controls="scheduleNotificationPanel"
        data-notification-trigger
    >
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/>
            <path d="M10 21h4"/>
        </svg>
        <span
            class="schedule-notification-count"
            data-notification-count
            @if($unreadScheduleNotificationCount === 0) hidden @endif
        >{{ $unreadScheduleNotificationCount > 99 ? '99+' : $unreadScheduleNotificationCount }}</span>
    </button>

    <section
        id="scheduleNotificationPanel"
        class="schedule-notification-panel"
        aria-label="Schedule notifications"
        data-notification-panel
        hidden
    >
        <header class="schedule-notification-header">
            <h2>Schedule notifications</h2>
            <button class="schedule-notification-mark-all" type="button" data-notification-read-all>
                Mark all as read
            </button>
        </header>
        <div class="schedule-notification-list" data-notification-list>
            @forelse($scheduleNotifications as $scheduleNotification)
                <button
                    class="schedule-notification-item {{ $scheduleNotification->read_at === null ? 'is-unread' : '' }}"
                    type="button"
                    data-read-url="{{ route($notificationRoutePrefix.'.notifications.read', $scheduleNotification->id) }}"
                >
                    <strong>{{ $scheduleNotification->data['title'] ?? 'Schedule notification' }}</strong>
                    <span>{{ $scheduleNotification->data['message'] ?? 'Your schedule has changed.' }}</span>
                    <time datetime="{{ $scheduleNotification->created_at?->toIso8601String() }}">
                        {{ $scheduleNotification->created_at?->diffForHumans() ?? 'Just now' }}
                    </time>
                </button>
            @empty
                <p class="schedule-notification-empty" data-notification-empty>
                    No schedule notifications yet.
                </p>
            @endforelse
        </div>
    </section>
</div>

@push('scripts')
<script>
(() => {
    const menu = document.querySelector('[data-schedule-notifications]');
    if (!menu) return;

    const trigger = menu.querySelector('[data-notification-trigger]');
    const panel = menu.querySelector('[data-notification-panel]');
    const count = menu.querySelector('[data-notification-count]');
    const list = menu.querySelector('[data-notification-list]');
    const readAll = menu.querySelector('[data-notification-read-all]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    const setCount = value => {
        const unread = Number(value) || 0;
        count.textContent = unread > 99 ? '99+' : String(unread);
        count.hidden = unread === 0;
    };

    const closePanel = () => {
        panel.hidden = true;
        trigger.setAttribute('aria-expanded', 'false');
    };

    const renderNotifications = notifications => {
        list.replaceChildren();

        if (!notifications.length) {
            const empty = document.createElement('p');
            empty.className = 'schedule-notification-empty';
            empty.textContent = 'No schedule notifications yet.';
            list.append(empty);
            return;
        }

        notifications.forEach(notification => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = `schedule-notification-item${notification.unread ? ' is-unread' : ''}`;
            item.dataset.readUrl = notification.read_url;

            const title = document.createElement('strong');
            title.textContent = notification.title;
            const message = document.createElement('span');
            message.textContent = notification.message;
            const time = document.createElement('time');
            time.textContent = notification.created_at;

            item.append(title, message, time);
            list.append(item);
        });
    };

    const post = url => fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
        },
    }).then(response => {
        if (!response.ok) throw new Error('Unable to update the notification.');
        return response.json();
    });

    const refreshNotifications = () => fetch(menu.dataset.feedUrl, {
        credentials: 'same-origin',
        headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
    })
        .then(response => response.ok ? response.json() : Promise.reject())
        .then(data => {
            setCount(data.unread_count);
            renderNotifications(data.notifications);
        })
        .catch(() => {});

    trigger.addEventListener('click', () => {
        const willOpen = panel.hidden;
        panel.hidden = !willOpen;
        trigger.setAttribute('aria-expanded', String(willOpen));
        if (willOpen) refreshNotifications();
    });

    list.addEventListener('click', event => {
        const item = event.target.closest('[data-read-url]');
        if (!item) return;

        item.disabled = true;
        post(item.dataset.readUrl)
            .then(data => {
                item.classList.remove('is-unread');
                setCount(data.unread_count);
                window.setTimeout(() => window.location.assign(data.redirect_url), 120);
            })
            .catch(() => {
                item.disabled = false;
            });
    });

    readAll.addEventListener('click', () => {
        post(menu.dataset.readAllUrl)
            .then(() => {
                setCount(0);
                list.querySelectorAll('.is-unread').forEach(item => item.classList.remove('is-unread'));
            })
            .catch(() => {});
    });

    document.addEventListener('click', event => {
        if (!menu.contains(event.target)) closePanel();
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') closePanel();
    });
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) refreshNotifications();
    });

    window.setInterval(refreshNotifications, 15000);
})();
</script>
@endpush
