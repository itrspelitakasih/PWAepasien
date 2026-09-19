<?php

namespace App\Support;

/**
 * Builds public URLs for radiology images stored by SIMRS Khanza. Khanza's
 * `gambar_radiologi.lokasi_gambar` holds only a path relative to its webapps
 * radiology folder; the folder's URL comes from the Settings page.
 */
class RadiologiImage
{
    public static function isConfigured(): bool
    {
        return filled(config('radiologi.image_base_url'));
    }

    /**
     * Full URL for a `lokasi_gambar` value, or null when no base URL is
     * configured or the path is empty.
     */
    public static function url(?string $lokasiGambar): ?string
    {
        $path = ltrim(str_replace('\\', '/', trim((string) $lokasiGambar)), '/');

        if ($path === '' || ! self::isConfigured()) {
            return null;
        }

        return rtrim((string) config('radiologi.image_base_url'), '/').'/'.implode('/', array_map('rawurlencode', explode('/', $path)));
    }
}
