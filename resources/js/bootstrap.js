import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

document.addEventListener('DOMContentLoaded', () => {
    const config = window.ShellFixNotifications;

    if (!config) {
        return;
    }

    window.Pusher = Pusher;

    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: import.meta.env.VITE_REVERB_APP_KEY || config.reverb?.key,
        wsHost: import.meta.env.VITE_REVERB_HOST || config.reverb?.host || window.location.hostname,
        wsPort: Number(import.meta.env.VITE_REVERB_PORT || config.reverb?.port || 8080),
        wssPort: Number(import.meta.env.VITE_REVERB_PORT || config.reverb?.port || 8080),
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME || config.reverb?.scheme) === 'https',
        enabledTransports: ['ws', 'wss'],
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': csrfToken(),
            },
        },
    });

    window.ShellFixRealtimeBootstrapped = true;

    bootRealtimeNotifications(config);
});

function bootRealtimeNotifications(config) {
    const receivedIds = new Set();
    const list = document.querySelector('[data-notification-list]');
    const countBadge = document.querySelector('[data-notification-count]');
    const muteButton = document.querySelector('[data-notification-mute]');
    const sound = new Audio(config.soundUrl);
    let muted = localStorage.getItem('shellfix_notifications_muted') === '1';
    let lastToastAt = 0;

    const subscribe = (channel) => {
        window.Echo.private(channel).listen('.notification.created', handleNotification);
    };

    subscribe('all');
    subscribe(`user.${config.userId}`);
    subscribe(`role.${config.role}`);
    (config.classIds || []).forEach((classId) => subscribe(`class.${classId}`));

    updateMuteButton();

    muteButton?.addEventListener('click', () => {
        muted = !muted;
        localStorage.setItem('shellfix_notifications_muted', muted ? '1' : '0');
        updateMuteButton();
    });

    document.addEventListener('click', (event) => {
        const postButton = event.target.closest('[data-notification-post]');
        const dismissButton = event.target.closest('[data-notification-dismiss]');

        if (postButton) {
            postAction(postButton.dataset.notificationPost);
            postButton.closest('[data-realtime-notification-id]')?.querySelector('.badge')?.classList.replace('text-bg-info', 'text-bg-success');
        }

        if (dismissButton) {
            postAction(dismissButton.dataset.notificationDismiss);
            dismissButton.closest('[data-realtime-notification-id]')?.remove();
            dismissButton.closest('[data-realtime-toast]')?.remove();
        }
    });

    function handleNotification(notification) {
        if (!notification || receivedIds.has(notification.id)) {
            return;
        }

        receivedIds.add(notification.id);
        incrementCount();

        if (list) {
            list.insertAdjacentHTML('afterbegin', notificationCard(notification));
        }

        showToast(notification);
    }

    function updateMuteButton() {
        if (!muteButton) {
            return;
        }

        muteButton.textContent = muted ? 'Sound Off' : 'Sound On';
        muteButton.classList.toggle('btn-outline-warning', muted);
    }

    function incrementCount() {
        if (!countBadge) {
            return;
        }

        const current = Number.parseInt(countBadge.textContent || '0', 10) || 0;
        countBadge.textContent = String(current + 1);
        countBadge.classList.remove('d-none');
    }

    function showToast(notification) {
        const now = Date.now();

        if (now - lastToastAt < 450) {
            window.setTimeout(() => showToast(notification), 500);
            return;
        }

        lastToastAt = now;
        document.querySelector('.notification-float[data-realtime-toast="1"]')?.remove();

        const toast = document.createElement('div');
        toast.className = 'notification-float notification-slide-in card border-0 shadow-lg';
        toast.dataset.realtimeToast = '1';
        toast.dataset.realtimeNotificationId = notification.id;
        toast.innerHTML = `
            <div class="card-body">
                <div class="d-flex justify-content-between gap-3">
                    <div>
                        <h2 class="h6 mb-1">${escapeHtml(notification.title)}</h2>
                        <p class="mb-2 text-secondary small">${escapeHtml(notification.message)}</p>
                        <div class="d-flex gap-2 flex-wrap">
                            ${feedbackButton(notification)}
                            <button type="button" class="btn btn-sm btn-outline-primary" data-notification-post="${notification.read_url}">Mark Read</button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-notification-dismiss="${notification.dismiss_url}" aria-label="Dismiss notification">&times;</button>
                </div>
            </div>
        `;

        document.body.appendChild(toast);
        window.setTimeout(() => toast.classList.add('is-visible'), 20);

        if (!muted) {
            sound.currentTime = 0;
            sound.play().catch(playFallbackBeep);
        }
    }
}

function notificationCard(notification) {
    return `
        <div class="card border-0 shadow-sm mb-3 notification-unread" data-realtime-notification-id="${notification.id}">
            <div class="card-body">
                <div class="d-flex justify-content-between gap-2">
                    <h3 class="h6 mb-1">${escapeHtml(notification.title)}</h3>
                    <span class="badge text-bg-info">Unread</span>
                </div>
                <p class="small text-secondary mb-3">${escapeHtml(notification.message)}</p>
                <div class="d-flex gap-2 flex-wrap">
                    ${feedbackButton(notification)}
                    <button type="button" class="btn btn-sm btn-outline-primary" data-notification-post="${notification.read_url}">Mark Read</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-notification-dismiss="${notification.dismiss_url}">Dismiss</button>
                </div>
            </div>
        </div>
    `;
}

function feedbackButton(notification) {
    if (notification.title !== 'Course Feedback is now available') {
        return '';
    }

    return `<a href="${notification.feedback_url || window.ShellFixNotifications?.feedbackUrl}" class="btn btn-sm btn-primary">Open Feedback</a>`;
}

function postAction(url) {
    return fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'text/html',
        },
    }).catch(() => {});
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || window.ShellFixNotifications?.csrfToken || '';
}

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value || '';
    return div.innerHTML;
}

function playFallbackBeep() {
    try {
        const AudioContext = window.AudioContext || window.webkitAudioContext;

        if (!AudioContext) {
            return;
        }

        const context = new AudioContext();
        const oscillator = context.createOscillator();
        const gain = context.createGain();
        oscillator.type = 'sine';
        oscillator.frequency.value = 880;
        gain.gain.value = 0.04;
        oscillator.connect(gain);
        gain.connect(context.destination);
        oscillator.start();
        oscillator.stop(context.currentTime + 0.12);
    } catch (error) {
    }
}
