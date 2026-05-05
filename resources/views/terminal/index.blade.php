@extends('layouts.app')

@section('title', 'Terminal | ShellFix')
@section('page-title', 'Terminal')
@section('page-description', 'Open your assigned Apache Guacamole terminal.')

@section('content')
    @if (session('status'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('status') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <h2 class="h5">Terminal Status</h2>
                    <p class="mb-2">
                        Access:
                        <span class="badge {{ $terminalEnabled ? 'text-bg-success' : 'text-bg-secondary' }}">
                            {{ $terminalEnabled ? 'Enabled' : 'Disabled' }}
                        </span>
                    </p>
                    <div class="text-secondary small">
                        Enabled by user: {{ $enabledByUser ? 'Yes' : 'No' }}<br>
                        Enabled by class: {{ $enabledByClass ? 'Yes' : 'No' }}<br>
                        Linux username: {{ $linuxUsername }}<br>
                        Guacamole username: {{ $guacamoleUsername }}<br>
                        Container status:
                        <span class="badge {{ $containerStatus === 'initializing' ? 'text-bg-warning' : ($containerStatus === 'running' ? 'text-bg-success' : ($containerStatus === 'error' ? 'text-bg-danger' : 'text-bg-secondary')) }}">
                            {{ str_replace('_', ' ', $containerStatus) }}
                        </span>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        @if (! $terminalEnabled)
                            <span class="btn btn-outline-secondary disabled">Terminal Disabled</span>
                        @elseif ($containerStatus === 'initializing')
                            <span class="btn btn-outline-warning disabled">Terminal Initializing</span>
                        @elseif ($containerStatus !== 'running')
                            <span class="btn btn-outline-secondary disabled">Waiting for Container</span>
                        @else
                            <a href="{{ $guacamoleBaseUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary">Launch Terminal</a>
                        @endif
                    </div>

                    @if ($terminalEnabled)
                        <div class="alert alert-warning mt-4 mb-0">
                            Use your Guacamole username and default password to login.
                        </div>
                    @endif

                    @if (! $automationEnabled)
                        <div class="alert alert-info mt-3 mb-0">
                            Terminal automation is currently in preview mode.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5">Guacamole Link Preview</h2>
                    <div class="terminal-placeholder mt-3">
                        <div class="terminal-bar">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                        <div class="terminal-screen">
                            <div>$ shellfix-terminal --phase T5</div>
                            <div>Guacamole URL: {{ $guacamoleBaseUrl }}</div>
                            <div>Guacamole username: {{ $guacamoleUsername }}</div>
                            <div>Mode: {{ $guacamoleMode }}</div>
                            <div>Container status: {{ $containerStatus }}</div>
                            <div>Docker commands: {{ $automationEnabled ? 'preview only' : 'disabled' }}</div>
                            <div>Auto-login: disabled</div>
                            <div class="text-secondary mt-3">The Launch Terminal button opens Apache Guacamole in a new browser tab.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
