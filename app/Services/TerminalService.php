<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Throwable;

class TerminalService
{
    public function generateLinuxUsername(User $user): string
    {
        if ($user->linux_username) {
            return $this->slug($user->linux_username);
        }

        $source = $user->matric_no ?: $user->registration_no ?: $user->email ?: 'student_' . $user->id;

        return $this->validLinuxUsername('sf_' . $this->slug($source));
    }

    public function generateContainerName(User $user): string
    {
        if ($user->container_name) {
            return $this->validContainerName($user->container_name);
        }

        $source = $user->matric_no ?: $user->registration_no ?: (string) $user->id;

        return $this->validContainerName(config('services.terminal.container_prefix', 'penguinlab_') . $user->id . '_' . $this->slug($source));
    }

    public function prepareContainerCommand(User $user): string
    {
        $containerName = $this->generateContainerName($user);
        $linuxUsername = $this->generateLinuxUsername($user);
        $linuxPassword = $this->generateLinuxPassword($user);
        $image = config('services.terminal.docker_image', 'ubuntu:22.04');
        $memory = config('services.terminal.memory_limit', '512m');
        $cpus = config('services.terminal.cpu_limit', '0.5');
        $runCommand = sprintf(
            'docker run -dit --name %s --hostname %s --network %s --memory %s --cpus %s --label shellfix_user_id=%d %s tail -f /dev/null',
            escapeshellarg($containerName),
            escapeshellarg($containerName),
            escapeshellarg('penguinlab-net'),
            escapeshellarg($memory),
            escapeshellarg($cpus),
            $user->id,
            escapeshellarg($image)
        );
        $ensureContainerCommand = sprintf(
            'if docker inspect %s >/dev/null 2>&1; then if [ "$(docker inspect -f %s %s)" != "true" ]; then docker start %s; fi; else %s; fi',
            escapeshellarg($containerName),
            escapeshellarg('{{.State.Running}}'),
            escapeshellarg($containerName),
            escapeshellarg($containerName),
            $runCommand
        );
        $setupScript = implode(' && ', [
            'if [ ! -f /root/.penguinlab_ready ]; then apt-get update -y && DEBIAN_FRONTEND=noninteractive apt-get install -y openssh-server sudo nano vim iproute2 net-tools curl grep findutils procps passwd; fi',
            'mkdir -p /var/run/sshd',
            'id -u ' . escapeshellarg($linuxUsername) . ' >/dev/null 2>&1 || useradd -m -s /bin/bash ' . escapeshellarg($linuxUsername),
            'echo ' . escapeshellarg($linuxUsername . ':' . $linuxPassword) . ' | chpasswd',
            "sed -i '/PasswordAuthentication/d' /etc/ssh/sshd_config",
            "sed -i '/ListenAddress/d' /etc/ssh/sshd_config",
            "sed -i '/PermitRootLogin/d' /etc/ssh/sshd_config",
            "sed -i '/UsePAM/d' /etc/ssh/sshd_config",
            "printf '\\nPasswordAuthentication yes\\nListenAddress 0.0.0.0\\nPermitRootLogin yes\\nUsePAM yes\\n' >> /etc/ssh/sshd_config",
            '/usr/sbin/sshd -t',
            '(pkill sshd || true)',
            '/usr/sbin/sshd',
            'touch /root/.penguinlab_ready',
        ]);
        $setupCommand = sprintf(
            'docker exec %s bash -lc %s',
            escapeshellarg($containerName),
            escapeshellarg($setupScript)
        );

        return sprintf(
            '%s && %s',
            $ensureContainerCommand,
            $setupCommand
        );
    }

    public function startTerminal(User $user): array
    {
        $rawCommand = $this->prepareContainerCommand($user);
        $command = $this->wrapForRemoteServer($rawCommand);

        if (! $this->automationEnabled()) {
            return [
                'command' => $this->maskSensitiveCommand($command),
                'automation_enabled' => false,
                'executed' => false,
                'success' => true,
                'message' => 'Terminal automation is currently in preview mode.',
                'output' => '',
            ];
        }

        $result = $this->executeRemoteCommand($rawCommand);

        $user->forceFill([
            'linux_username' => $this->generateLinuxUsername($user),
            'container_name' => $this->generateContainerName($user),
            'container_status' => $result['success'] ? 'running' : 'error',
            'terminal_last_started_at' => $result['success'] ? Carbon::now() : $user->terminal_last_started_at,
        ])->save();

        if ($result['success']) {
            $guacamoleResult = $this->createOrUpdateGuacamoleConnection($user->refresh());

            if (! $guacamoleResult['success']) {
                $result['output'] = trim($result['output'] . PHP_EOL . 'Guacamole sync: ' . $guacamoleResult['message']);
            }
        }

        return [
            'command' => $this->maskSensitiveCommand($command),
            'automation_enabled' => true,
            'executed' => true,
            'success' => $result['success'],
            'message' => $result['success'] ? 'Terminal container started.' : 'Terminal container failed to start.',
            'output' => $result['output'],
        ];
    }

    public function stopTerminal(User $user): array
    {
        $dockerCommand = sprintf('docker stop %s', escapeshellarg($this->generateContainerName($user)));
        $command = $this->wrapForRemoteServer($dockerCommand);

        if (! $this->automationEnabled()) {
            return [
                'command' => $command,
                'automation_enabled' => false,
                'executed' => false,
                'success' => true,
                'message' => 'Terminal automation is currently in preview mode.',
                'output' => '',
            ];
        }

        $result = $this->executeRemoteCommand($dockerCommand);

        $user->forceFill([
            'container_status' => $result['success'] ? 'stopped' : 'error',
            'guacamole_connection_status' => $result['success'] ? 'Not synced' : $user->guacamole_connection_status,
            'terminal_last_stopped_at' => $result['success'] ? Carbon::now() : $user->terminal_last_stopped_at,
        ])->save();

        return [
            'command' => $command,
            'automation_enabled' => true,
            'executed' => true,
            'success' => $result['success'],
            'message' => $result['success'] ? 'Terminal container stopped.' : 'Terminal container failed to stop.',
            'output' => $result['output'],
        ];
    }

    public function previewStartTerminal(User $user): array
    {
        return [
            'command' => $this->maskSensitiveCommand($this->wrapForRemoteServer($this->prepareContainerCommand($user))),
            'automation_enabled' => $this->automationEnabled(),
            'executed' => false,
            'success' => true,
            'message' => 'Command preview only. Nothing was executed.',
            'output' => '',
        ];
    }

    public function previewStopTerminal(User $user): array
    {
        $dockerCommand = sprintf('docker stop %s', escapeshellarg($this->generateContainerName($user)));

        return [
            'command' => $this->wrapForRemoteServer($dockerCommand),
            'automation_enabled' => $this->automationEnabled(),
            'executed' => false,
            'success' => true,
            'message' => 'Command preview only. Nothing was executed.',
            'output' => '',
        ];
    }

    public function automationEnabled(): bool
    {
        return filter_var(config('services.terminal.automation_enabled', false), FILTER_VALIDATE_BOOLEAN);
    }

    public function getContainerIp(User $user): ?string
    {
        if (! $this->automationEnabled()) {
            return null;
        }

        $command = sprintf(
            'docker inspect -f %s %s',
            escapeshellarg('{{range.NetworkSettings.Networks}}{{.IPAddress}}{{end}}'),
            escapeshellarg($this->generateContainerName($user))
        );
        $result = $this->executeRemoteCommand($command);

        if (! $result['success']) {
            return null;
        }

        $ipAddress = trim($result['output']);

        return filter_var($ipAddress, FILTER_VALIDATE_IP) ? $ipAddress : null;
    }

    public function createOrUpdateGuacamoleConnection(User $user): array
    {
        $user->forceFill([
            'linux_username' => $this->generateLinuxUsername($user),
            'container_name' => $this->generateContainerName($user),
        ])->save();

        $connectionName = $this->guacamoleConnectionName($user);
        $parameters = [
            'hostname' => $this->generateContainerName($user),
            'port' => '22',
            'username' => $this->generateLinuxUsername($user),
            'password' => (string) config('services.guacamole.default_password', '123456'),
        ];

        try {
            $connection = $this->guacamoleDb();
            $connectionId = $connection->table('guacamole_connection')
                ->where('connection_name', $connectionName)
                ->value('connection_id');

            if ($connectionId) {
                $connection->table('guacamole_connection')
                    ->where('connection_id', $connectionId)
                    ->update([
                        'protocol' => 'ssh',
                    ]);
            } else {
                $connectionId = $connection->table('guacamole_connection')->insertGetId([
                    'connection_name' => $connectionName,
                    'parent_id' => null,
                    'protocol' => 'ssh',
                    'max_connections' => null,
                    'max_connections_per_user' => null,
                    'connection_weight' => null,
                    'failover_only' => 0,
                ]);
            }

            foreach ($parameters as $name => $value) {
                $connection->table('guacamole_connection_parameter')->updateOrInsert(
                    [
                        'connection_id' => $connectionId,
                        'parameter_name' => $name,
                    ],
                    [
                        'parameter_value' => $value,
                    ]
                );
            }
            $connection->table('guacamole_connection_parameter')
                ->where('connection_id', $connectionId)
                ->whereIn('parameter_name', ['public-host-key', 'private-key', 'passphrase'])
                ->delete();

            $entityId = $this->createOrUpdateGuacamoleUser($connection, $user);
            $connection->table('guacamole_connection_permission')
                ->where('entity_id', $entityId)
                ->where('permission', 'READ')
                ->where('connection_id', '<>', $connectionId)
                ->delete();
            $connection->table('guacamole_connection_permission')->updateOrInsert(
                [
                    'entity_id' => $entityId,
                    'connection_id' => $connectionId,
                    'permission' => 'READ',
                ],
                [
                    'permission' => 'READ',
                ]
            );

            $user->forceFill(['guacamole_connection_status' => 'Synced'])->save();

            return [
                'success' => true,
                'message' => 'Guacamole connection, user, and permission synced.',
                'connection_id' => $connectionId,
            ];
        } catch (Throwable $exception) {
            $user->forceFill(['guacamole_connection_status' => 'Failed'])->save();

            return [
                'success' => false,
                'message' => 'Unable to sync Guacamole connection. Please check Guacamole database settings and try again.',
                'connection_id' => null,
            ];
        }
    }

    public function resetGuacamolePassword(User $user): array
    {
        try {
            $connection = $this->guacamoleDb();
            $entityId = $this->createOrUpdateGuacamoleUser($connection, $user);

            $this->updateGuacamoleUserPassword($connection, $entityId, $this->guacamoleUserDefaultPassword());

            return [
                'success' => true,
                'message' => 'Guacamole password reset to the default password.',
            ];
        } catch (Throwable) {
            return [
                'success' => false,
                'message' => 'Unable to reset Guacamole password. Please check Guacamole database settings and try again.',
            ];
        }
    }

    public function getGuacamoleConnectionUrl(User $user): ?string
    {
        $baseUrl = rtrim((string) config('services.guacamole.base_url', '#'), '/');

        if ($baseUrl === '' || $baseUrl === '#') {
            return null;
        }

        if (($user->guacamole_connection_status ?? 'Not synced') !== 'Synced') {
            return null;
        }

        try {
            $connectionId = $this->guacamoleDb()
                ->table('guacamole_connection')
                ->where('connection_name', $this->guacamoleConnectionName($user))
                ->value('connection_id');

            if (! $connectionId) {
                return null;
            }

            $clientIdentifier = rtrim(strtr(base64_encode($connectionId . "\0c\0mysql"), '+/', '-_'), '=');

            return $baseUrl . '/#/client/' . $clientIdentifier;
        } catch (Throwable) {
            return null;
        }
    }

    private function wrapForRemoteServer(string $dockerCommand): string
    {
        $host = config('services.terminal.server_host');
        $user = config('services.terminal.server_user');
        $keyPath = config('services.terminal.server_ssh_key_path');

        if (! $host || ! $user) {
            return $dockerCommand;
        }

        $sshParts = ['ssh'];

        if ($keyPath) {
            $sshParts[] = '-i ' . escapeshellarg($keyPath);
        }

        $sshParts[] = escapeshellarg($user . '@' . $host);
        $sshParts[] = escapeshellarg($dockerCommand);

        return implode(' ', $sshParts);
    }

    private function executeRemoteCommand(string $dockerCommand): array
    {
        $host = config('services.terminal.server_host');
        $user = config('services.terminal.server_user');
        $keyPath = config('services.terminal.server_ssh_key_path');

        if (! $host || ! $user) {
            return [
                'success' => false,
                'output' => 'Terminal server host or user is not configured.',
            ];
        }

        $command = ['ssh', '-o', 'BatchMode=yes', '-o', 'StrictHostKeyChecking=accept-new'];

        if ($keyPath) {
            $command[] = '-i';
            $command[] = $keyPath;
        }

        $command[] = $user . '@' . $host;
        $command[] = $dockerCommand;

        $descriptorSpec = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptorSpec, $pipes);

        if (! is_resource($process)) {
            return [
                'success' => false,
                'output' => 'Unable to start SSH process.',
            ];
        }

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $output = '';
        $startedAt = time();
        $timedOut = false;
        $exitCode = null;

        do {
            $output .= stream_get_contents($pipes[1]) ?: '';
            $output .= stream_get_contents($pipes[2]) ?: '';
            $status = proc_get_status($process);

            if ((time() - $startedAt) > 5) {
                $timedOut = true;
                proc_terminate($process);
                break;
            }

            usleep(100000);
        } while ($status['running']);

        if (isset($status['exitcode']) && $status['exitcode'] >= 0) {
            $exitCode = $status['exitcode'];
        }

        $output .= stream_get_contents($pipes[1]) ?: '';
        $output .= stream_get_contents($pipes[2]) ?: '';

        fclose($pipes[1]);
        fclose($pipes[2]);

        $closedExitCode = proc_close($process);
        $exitCode ??= $closedExitCode;

        if ($timedOut) {
            return [
                'success' => false,
                'output' => 'SSH command timed out after 5 seconds.',
            ];
        }

        return [
            'success' => $exitCode === 0,
            'output' => trim($output),
        ];
    }

    private function generateLinuxPassword(User $user): string
    {
        return (string) config('services.guacamole.default_password', '123456');
    }

    public function guacamoleUsername(User $user): string
    {
        return $this->generateLinuxUsername($user);
    }

    private function guacamoleDb()
    {
        config([
            'database.connections.guacamole' => [
                'driver' => 'mysql',
                'host' => config('services.guacamole.db_host'),
                'port' => config('services.guacamole.db_port', 3306),
                'database' => config('services.guacamole.db_database'),
                'username' => config('services.guacamole.db_username'),
                'password' => config('services.guacamole.db_password'),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => false,
                'engine' => null,
                'options' => extension_loaded('pdo_mysql') ? [
                    \PDO::ATTR_TIMEOUT => 3,
                ] : [],
            ],
        ]);

        DB::purge('guacamole');

        return DB::connection('guacamole');
    }

    private function guacamoleConnectionName(User $user): string
    {
        return $user->name . ' - Linux Terminal';
    }

    private function createOrUpdateGuacamoleUser($connection, User $user): int
    {
        $username = $this->guacamoleUsername($user);
        $entityId = $connection->table('guacamole_entity')
            ->where('name', $username)
            ->where('type', 'USER')
            ->value('entity_id');

        if (! $entityId) {
            $entityId = $connection->table('guacamole_entity')->insertGetId([
                'name' => $username,
                'type' => 'USER',
            ]);

            $this->updateGuacamoleUserPassword($connection, $entityId, $this->guacamoleUserDefaultPassword());
        } else {
            $guacamoleUserExists = $connection->table('guacamole_user')
                ->where('entity_id', $entityId)
                ->exists();

            if (! $guacamoleUserExists) {
                $this->updateGuacamoleUserPassword($connection, $entityId, $this->guacamoleUserDefaultPassword());
            }

            $connection->table('guacamole_user')->updateOrInsert(
                ['entity_id' => $entityId],
                [
                    'disabled' => 0,
                    'expired' => 0,
                    'full_name' => $user->name,
                    'email_address' => $user->email,
                ]
            );
        }

        $connection->table('guacamole_user')->where('entity_id', $entityId)->update([
            'disabled' => 0,
            'expired' => 0,
            'full_name' => $user->name,
            'email_address' => $user->email,
        ]);

        return (int) $entityId;
    }

    private function updateGuacamoleUserPassword($connection, int $entityId, string $password): void
    {
        $salt = random_bytes(32);

        $connection->table('guacamole_user')->updateOrInsert(
            ['entity_id' => $entityId],
            [
                'password_hash' => hash('sha256', $password . strtoupper(bin2hex($salt)), true),
                'password_salt' => $salt,
                'password_date' => now(),
                'disabled' => 0,
                'expired' => 0,
            ]
        );
    }

    private function guacamoleUserDefaultPassword(): string
    {
        return (string) config('services.guacamole.user_default_password', '123456');
    }

    private function maskSensitiveCommand(string $command): string
    {
        $command = preg_replace('/printf %s ([^ ]+) \| chpasswd/', 'printf %s [masked-linux-password] | chpasswd', $command) ?: $command;

        return preg_replace('/echo ([^ ]+) \| chpasswd/', 'echo [masked-linux-password] | chpasswd', $command) ?: $command;
    }

    private function validLinuxUsername(string $value): string
    {
        $value = substr($this->slug($value), 0, 31);

        if (! preg_match('/^[a-z_]/', $value)) {
            $value = 'sf_' . $value;
        }

        return substr($value, 0, 31);
    }

    private function validContainerName(string $value): string
    {
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9_.-]+/', '_', $value) ?: 'penguinlab';
        $value = trim($value, '_.-');

        if (! preg_match('/^[a-z0-9]/', $value)) {
            $value = 'penguinlab_' . $value;
        }

        return substr($value, 0, 63);
    }

    private function slug(string $value): string
    {
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9_]+/', '_', $value) ?: 'student';
        $value = trim($value, '_');

        return $value !== '' ? $value : 'student';
    }
}
