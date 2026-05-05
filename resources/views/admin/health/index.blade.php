@extends('layouts.app')

@section('title', 'System Health Dashboard | ShellFix')
@section('page-title', 'System Health Dashboard')
@section('page-description', 'Monitor ShellFix services, lab infrastructure, and integration health.')

@section('content')
    <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-4">
        <div>
            <span class="badge {{ $snapshot['overall_status'] === 'ONLINE' ? 'text-bg-success' : ($snapshot['overall_status'] === 'WARNING' ? 'text-bg-warning' : 'text-bg-danger') }}" data-health-overall>
                {{ $snapshot['overall_status'] }}
            </span>
            <span class="text-secondary small ms-2">Last checked: <span data-health-checked>{{ $snapshot['checked_at'] }}</span></span>
        </div>
        <button type="button" class="btn btn-outline-primary" data-health-refresh>Refresh Now</button>
    </div>

    <div class="alert alert-danger border-0 shadow-sm {{ count($snapshot['alerts']) ? '' : 'd-none' }}" data-health-alerts>
        @foreach($snapshot['alerts'] as $alert)
            <div>{{ $alert }}</div>
        @endforeach
    </div>

    <div class="row g-4" data-health-grid data-health-url="{{ route('admin.health.json') }}">
        @foreach($snapshot['services'] as $key => $service)
            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm health-card health-{{ strtolower($service['status']) }}" data-health-card="{{ $key }}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div>
                                <div class="health-icon mb-3" data-health-icon>{{ $service['icon'] }}</div>
                                <h2 class="h5 mb-1" data-health-name>{{ $service['name'] }}</h2>
                            </div>
                            <span class="badge {{ $service['status'] === 'ONLINE' ? 'text-bg-success' : ($service['status'] === 'WARNING' ? 'text-bg-warning' : 'text-bg-danger') }}" data-health-status>{{ $service['status'] }}</span>
                        </div>
                        <p class="text-secondary small mb-3" data-health-message>{{ $service['message'] }}</p>
                        @if(isset($service['count']))
                            <div class="display-6 fw-semibold mb-2" data-health-count>{{ $service['count'] }}</div>
                        @else
                            <div class="display-6 fw-semibold mb-2 d-none" data-health-count></div>
                        @endif
                        <div class="text-secondary small">Last checked: <span data-health-last>{{ $service['last_checked'] }}</span></div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const grid = document.querySelector('[data-health-grid]');
            const refreshButton = document.querySelector('[data-health-refresh]');
            const overall = document.querySelector('[data-health-overall]');
            const checked = document.querySelector('[data-health-checked]');
            const alerts = document.querySelector('[data-health-alerts]');

            if (!grid) {
                return;
            }

            function statusClass(status) {
                if (status === 'ONLINE') return 'text-bg-success';
                if (status === 'WARNING') return 'text-bg-warning';
                return 'text-bg-danger';
            }

            function cardClass(status) {
                return 'health-' + String(status || 'warning').toLowerCase();
            }

            function updateBadge(element, status) {
                element.classList.remove('text-bg-success', 'text-bg-warning', 'text-bg-danger');
                element.classList.add(statusClass(status));
                element.textContent = status;
            }

            function render(payload) {
                if (!payload || !payload.services) {
                    return;
                }

                if (overall) updateBadge(overall, payload.overall_status);
                if (checked) checked.textContent = payload.checked_at;

                if (alerts) {
                    alerts.innerHTML = (payload.alerts || []).map((item) => `<div>${escapeHtml(item)}</div>`).join('');
                    alerts.classList.toggle('d-none', !(payload.alerts || []).length);
                }

                Object.entries(payload.services).forEach(([key, service]) => {
                    const card = grid.querySelector(`[data-health-card="${key}"]`);
                    if (!card) return;

                    card.classList.remove('health-online', 'health-warning', 'health-offline');
                    card.classList.add(cardClass(service.status));
                    card.querySelector('[data-health-icon]').textContent = service.icon;
                    card.querySelector('[data-health-name]').textContent = service.name;
                    card.querySelector('[data-health-message]').textContent = service.message;
                    card.querySelector('[data-health-last]').textContent = service.last_checked;
                    updateBadge(card.querySelector('[data-health-status]'), service.status);

                    const count = card.querySelector('[data-health-count]');
                    if (Object.prototype.hasOwnProperty.call(service, 'count')) {
                        count.textContent = service.count;
                        count.classList.remove('d-none');
                    } else {
                        count.classList.add('d-none');
                    }
                });
            }

            function refresh() {
                fetch(grid.dataset.healthUrl, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                })
                    .then((response) => response.ok ? response.json() : null)
                    .then(render)
                    .catch(() => {});
            }

            function escapeHtml(value) {
                const div = document.createElement('div');
                div.textContent = value || '';
                return div.innerHTML;
            }

            refreshButton?.addEventListener('click', refresh);
            window.setInterval(refresh, 10000);
        });
    </script>
@endpush
