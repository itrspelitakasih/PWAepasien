<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Gowa\Laravel\Enums\GowaInstanceStatus;
use Gowa\Laravel\Models\GowaInstance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class GowaImportDevices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gowa:import-devices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import WhatsApp devices already registered on the GOWA server into the local database';

    public function handle(): int
    {
        $baseUrl = rtrim((string) config('gowa.base_url'), '/');
        $username = (string) config('gowa.username');
        $password = (string) config('gowa.password');

        if ($baseUrl === '' || $username === '' || $password === '') {
            $this->error('GOWA is not configured. Set GOWA_BASE_URL, GOWA_USERNAME and GOWA_PASSWORD first.');

            return self::FAILURE;
        }

        $response = Http::withBasicAuth($username, $password)
            ->timeout((int) config('gowa.timeout', 15))
            ->acceptJson()
            ->get("{$baseUrl}/devices");

        if ($response->failed()) {
            $this->error("Failed to fetch devices from GOWA server: HTTP {$response->status()}");

            return self::FAILURE;
        }

        $devices = $response->json('results', []);

        if (empty($devices)) {
            $this->info('No devices found on the GOWA server.');

            return self::SUCCESS;
        }

        foreach ($devices as $device) {
            $deviceId = (string) ($device['id'] ?? '');

            if ($deviceId === '') {
                continue;
            }

            $status = $this->mapStatus((string) ($device['state'] ?? ''));
            $jid = (string) ($device['jid'] ?? '');
            $existing = GowaInstance::where('device_id', $deviceId)->first();

            $instance = GowaInstance::updateOrCreate(
                ['device_id' => $deviceId],
                [
                    'name' => (string) ($device['display_name'] ?? $deviceId),
                    'status' => $status,
                    'phone_number' => $jid !== '' ? explode('@', $jid)[0] : null,
                    'meta' => $jid !== '' ? ['jid' => $jid] : null,
                    'connected_at' => $status === GowaInstanceStatus::Open
                        ? ($existing?->connected_at ?? now())
                        : $existing?->connected_at,
                ],
            );

            $this->line("Imported: {$instance->name} ({$instance->device_id}) - {$status->label()}");
        }

        $this->info(sprintf('Imported %d device(s).', count($devices)));

        return self::SUCCESS;
    }

    private function mapStatus(string $state): GowaInstanceStatus
    {
        $state = strtolower($state);

        return match (true) {
            in_array($state, ['logged_in', 'open', 'connected', 'authenticated', 'paired'], true) => GowaInstanceStatus::Open,
            in_array($state, ['connecting', 'qr_pairing', 'code_pairing', 'login'], true) => GowaInstanceStatus::Connecting,
            in_array($state, ['close', 'closed', 'disconnected', 'logged_out', 'logout'], true) => GowaInstanceStatus::Close,
            default => GowaInstanceStatus::Created,
        };
    }
}
