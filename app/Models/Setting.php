<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PDO;
use PDOException;

/**
 * Singleton row of app-wide configuration, editable from the Filament
 * Settings page. Values here are applied over the `.env` defaults at
 * boot by App\Providers\RuntimeSettingsServiceProvider — an empty field
 * here means "use .env", not "use an empty value".
 */
class Setting extends Model
{
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'app_name',
        'logo_path',
        'icon_path',
        'favicon_path',
        'gowa_base_url',
        'gowa_username',
        'gowa_password',
        'gowa_timeout',
        'gowa_default_device_id',
        'sik_db_host',
        'sik_db_port',
        'sik_db_database',
        'sik_db_username',
        'sik_db_password',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gowa_password' => 'encrypted',
            'sik_db_password' => 'encrypted',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('settings.current'));
        static::deleted(fn () => Cache::forget('settings.current'));
    }

    /**
     * Cached indefinitely since this row changes only via the Settings
     * page, which invalidates the cache itself (see `booted()` above) —
     * avoids re-querying it on every request.
     */
    public static function current(): self
    {
        return Cache::rememberForever(
            'settings.current',
            fn (): self => self::query()->first() ?? self::create([]),
        );
    }

    public function getAppNameAttribute($value): ?string
    {
        return $value ?: config('app.name');
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    public function iconUrl(): ?string
    {
        return $this->icon_path ? Storage::disk('public')->url($this->icon_path) : null;
    }

    public function faviconUrl(): ?string
    {
        return $this->favicon_path ? Storage::disk('public')->url($this->favicon_path) : null;
    }

    /**
     * Whether the app is configured enough for the patient portal to work:
     * a GOWA base URL (for OTP/notifications) and a reachable Khanza `sik`
     * connection. Checks the *effective* config, which already has
     * RuntimeSettingsServiceProvider's DB overrides applied, so this is
     * true whether the values came from `.env` or from this record.
     * Result is cached briefly since the DB check opens a real connection.
     */
    public function isPortalReady(): bool
    {
        return Cache::remember('portal.setup_ready', 30, function (): bool {
            return filled(config('gowa.base_url')) && $this->canConnectToSik();
        });
    }

    private function canConnectToSik(): bool
    {
        $config = config('database.connections.sik');

        try {
            new PDO(
                "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']}",
                $config['username'],
                $config['password'],
                [PDO::ATTR_TIMEOUT => 5],
            );

            return true;
        } catch (PDOException) {
            return false;
        }
    }
}
