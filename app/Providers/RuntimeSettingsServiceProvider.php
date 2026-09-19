<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\ServiceProvider;
use Throwable;

/**
 * Applies DB-stored Setting overrides on top of the `.env`-driven config
 * for `gowa.*`, `radiologi.*` and the `sik` database connection, so an admin
 * can change the WhatsApp/SIMRS connection details from the Settings page without a
 * redeploy. An empty field on the Setting record leaves the `.env`
 * value in place — only non-empty overrides are applied. Runs on every
 * request, so it relies on `Setting::current()`'s cache rather than
 * querying directly; safe to run before the first migration since a
 * missing `settings`/`cache` table just throws, which is caught below.
 */
class RuntimeSettingsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        try {
            $setting = Setting::current();
        } catch (Throwable) {
            return;
        }

        $this->overrideGowaConfig($setting);
        $this->overrideSikConnection($setting);
        $this->overrideRadiologiConfig($setting);
    }

    private function overrideRadiologiConfig(Setting $setting): void
    {
        config(array_filter([
            'radiologi.image_base_url' => $setting->radiologi_image_base_url,
        ], fn ($value) => filled($value)));
    }

    private function overrideGowaConfig(Setting $setting): void
    {
        config(array_filter([
            'gowa.base_url' => $setting->gowa_base_url,
            'gowa.username' => $setting->gowa_username,
            'gowa.password' => $setting->gowa_password,
            'gowa.timeout' => $setting->gowa_timeout,
            'gowa.default_device_id' => $setting->gowa_default_device_id,
        ], fn ($value) => filled($value)));
    }

    private function overrideSikConnection(Setting $setting): void
    {
        $overrides = array_filter([
            'host' => $setting->sik_db_host,
            'port' => $setting->sik_db_port,
            'database' => $setting->sik_db_database,
            'username' => $setting->sik_db_username,
            'password' => $setting->sik_db_password,
        ], fn ($value) => filled($value));

        if ($overrides === []) {
            return;
        }

        config(['database.connections.sik' => array_merge(
            config('database.connections.sik', []),
            $overrides,
        )]);
    }
}
