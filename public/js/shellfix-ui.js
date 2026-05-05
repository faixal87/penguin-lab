(function () {
    const body = document.body;
    const toggle = document.querySelector('[data-sidebar-toggle]');
    const closeTargets = document.querySelectorAll('[data-sidebar-close]');

    if (toggle) {
        toggle.addEventListener('click', () => {
            body.classList.toggle('sidebar-pinned');
        });
    }

    closeTargets.forEach((target) => {
        target.addEventListener('click', () => body.classList.remove('sidebar-pinned'));
    });

    const sidebarMenu = document.getElementById('shellfixSidebarMenu');

    if (sidebarMenu && window.bootstrap) {
        const submenus = Array.from(sidebarMenu.querySelectorAll('.sidebar-submenu.collapse'));
        const buttonFor = (submenu) => sidebarMenu.querySelector(`[data-bs-target="#${submenu.id}"]`);

        submenus.forEach((submenu) => {
            const button = buttonFor(submenu);
            button?.classList.toggle('is-open', submenu.classList.contains('show'));

            submenu.addEventListener('show.bs.collapse', () => {
                submenus.forEach((other) => {
                    if (other !== submenu) {
                        window.bootstrap.Collapse.getOrCreateInstance(other, { toggle: false }).hide();
                    }
                });

                button?.classList.add('is-open');
                button?.setAttribute('aria-expanded', 'true');
            });

            submenu.addEventListener('hide.bs.collapse', () => {
                button?.classList.remove('is-open');
                button?.setAttribute('aria-expanded', 'false');
            });
        });
    }

    document.querySelectorAll('.shellfix-sidebar a.nav-link').forEach((link) => {
        link.addEventListener('click', () => {
            if (window.matchMedia('(max-width: 991.98px)').matches) {
                body.classList.remove('sidebar-pinned');
            }
        });
    });

    const scenarioPanel = document.querySelector('[data-scenarios-panel]');
    if (scenarioPanel && scenarioPanel.dataset.loadUrl) {
        scenarioPanel.classList.add('is-loading');
        fetch(scenarioPanel.dataset.loadUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then((response) => response.text())
            .then((html) => {
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const updatedPanel = doc.querySelector('[data-scenarios-panel]');
                if (updatedPanel) {
                    scenarioPanel.innerHTML = updatedPanel.innerHTML;
                }
            })
            .catch(() => {})
            .finally(() => scenarioPanel.classList.remove('is-loading'));
    }

    const answerForm = document.querySelector('[data-answer-form]');
    if (answerForm) {
        answerForm.addEventListener('submit', (event) => {
            event.preventDefault();

            const submitButton = answerForm.querySelector('[type="submit"]');
            const resultTarget = document.querySelector('[data-answer-result]');
            const formData = new FormData(answerForm);

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.dataset.originalText = submitButton.textContent;
                submitButton.textContent = 'Checking...';
            }

            fetch(answerForm.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html'
                }
            })
                .then((response) => response.text())
                .then((html) => {
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const freshResult = doc.querySelector('[data-answer-result]');
                    const freshErrors = doc.querySelector('[data-answer-errors]');

                    if (freshResult && resultTarget) {
                        resultTarget.innerHTML = freshResult.innerHTML;
                        resultTarget.classList.add('answer-flash');
                        setTimeout(() => resultTarget.classList.remove('answer-flash'), 700);
                    } else if (freshErrors && resultTarget) {
                        resultTarget.innerHTML = freshErrors.innerHTML;
                    } else {
                        window.location.reload();
                    }
                })
                .catch(() => answerForm.submit())
                .finally(() => {
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.textContent = submitButton.dataset.originalText || 'Submit';
                    }
                });
        });
    }

    const realtimeConfig = window.ShellFixNotifications;
    if (realtimeConfig && window.Echo && window.Pusher && !window.ShellFixRealtimeBootstrapped) {
        const receivedIds = new Set();
        const list = document.querySelector('[data-notification-list]');
        const countBadge = document.querySelector('[data-notification-count]');
        const muteButton = document.querySelector('[data-notification-mute]');
        const sound = new Audio(realtimeConfig.soundUrl);
        let muted = localStorage.getItem('shellfix_notifications_muted') === '1';
        let lastToastAt = 0;

        const updateMuteButton = () => {
            if (muteButton) {
                muteButton.textContent = muted ? 'Sound Off' : 'Sound On';
                muteButton.classList.toggle('btn-outline-warning', muted);
            }
        };

        const incrementCount = () => {
            if (!countBadge) {
                return;
            }

            const current = Number.parseInt(countBadge.textContent || '0', 10) || 0;
            countBadge.textContent = String(current + 1);
            countBadge.classList.remove('d-none');
        };

        const csrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || realtimeConfig.csrfToken;

        const postAction = (url) => fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf(),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html',
            },
        }).catch(() => {});

        const notificationCard = (notification) => {
            const isFeedback = notification.title === 'Course Feedback is now available';
            const feedbackButton = isFeedback
                ? `<a href="${notification.feedback_url || realtimeConfig.feedbackUrl}" class="btn btn-sm btn-primary">Open Feedback</a>`
                : '';

            return `
                <div class="card border-0 shadow-sm mb-3 notification-unread" data-realtime-notification-id="${notification.id}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between gap-2">
                            <h3 class="h6 mb-1">${escapeHtml(notification.title)}</h3>
                            <span class="badge text-bg-info">Unread</span>
                        </div>
                        <p class="small text-secondary mb-3">${escapeHtml(notification.message)}</p>
                        <div class="d-flex gap-2 flex-wrap">
                            ${feedbackButton}
                            <button type="button" class="btn btn-sm btn-outline-primary" data-notification-post="${notification.read_url}">Mark Read</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-notification-dismiss="${notification.dismiss_url}">Dismiss</button>
                        </div>
                    </div>
                </div>
            `;
        };

        const showToast = (notification) => {
            const now = Date.now();
            if (now - lastToastAt < 450) {
                window.setTimeout(() => showToast(notification), 500);
                return;
            }
            lastToastAt = now;

            const existingToast = document.querySelector('.notification-float[data-realtime-toast="1"]');
            if (existingToast) {
                existingToast.remove();
            }

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
                                ${notification.title === 'Course Feedback is now available' ? `<a href="${notification.feedback_url || realtimeConfig.feedbackUrl}" class="btn btn-sm btn-primary">Open Feedback</a>` : ''}
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
        };

        const handleNotification = (notification) => {
            if (!notification || receivedIds.has(notification.id)) {
                return;
            }

            receivedIds.add(notification.id);
            incrementCount();

            if (list) {
                list.insertAdjacentHTML('afterbegin', notificationCard(notification));
            }

            showToast(notification);
        };

        const subscribe = (channel) => {
            window.Echo.private(channel).listen('.notification.created', handleNotification);
        };

        window.Pusher.logToConsole = false;
        window.Echo = new window.Echo({
            broadcaster: 'pusher',
            key: realtimeConfig.reverb.key,
            wsHost: realtimeConfig.reverb.host || window.location.hostname,
            wsPort: realtimeConfig.reverb.port || 8080,
            wssPort: realtimeConfig.reverb.port || 8080,
            forceTLS: realtimeConfig.reverb.scheme === 'https',
            enabledTransports: ['ws', 'wss'],
            authEndpoint: '/broadcasting/auth',
            auth: {
                headers: {
                    'X-CSRF-TOKEN': csrf(),
                },
            },
        });

        subscribe('all');
        subscribe(`user.${realtimeConfig.userId}`);
        subscribe(`role.${realtimeConfig.role}`);
        (realtimeConfig.classIds || []).forEach((classId) => subscribe(`class.${classId}`));

        updateMuteButton();

        if (muteButton) {
            muteButton.addEventListener('click', () => {
                muted = !muted;
                localStorage.setItem('shellfix_notifications_muted', muted ? '1' : '0');
                updateMuteButton();
            });
        }

        document.addEventListener('click', (event) => {
            const postButton = event.target.closest('[data-notification-post]');
            const dismissButton = event.target.closest('[data-notification-dismiss]');

            if (postButton) {
                postAction(postButton.dataset.notificationPost);
                const card = postButton.closest('[data-realtime-notification-id]');
                card?.querySelector('.badge')?.classList.replace('text-bg-info', 'text-bg-success');
            }

            if (dismissButton) {
                postAction(dismissButton.dataset.notificationDismiss);
                dismissButton.closest('[data-realtime-notification-id]')?.remove();
                dismissButton.closest('[data-realtime-toast]')?.remove();
            }
        });

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
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value || '';
        return div.innerHTML;
    }
})();
