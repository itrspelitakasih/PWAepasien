<?php

namespace App\Models\Khanza\Concerns;

use RuntimeException;

/**
 * Blocks writes on models backed by the read-only `sik` connection
 * (SIMRS Khanza). See config/database.php for why that link is read-only.
 */
trait ReadOnlyFromKhanza
{
    public static function bootReadOnlyFromKhanza(): void
    {
        static::saving(function (): never {
            throw new RuntimeException(static::class.' is read-only: SIMRS Khanza data must not be written from this application.');
        });

        static::deleting(function (): never {
            throw new RuntimeException(static::class.' is read-only: SIMRS Khanza data must not be written from this application.');
        });
    }
}
