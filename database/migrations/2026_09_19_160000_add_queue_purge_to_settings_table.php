<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('queue_purge_enabled')->default(false)->after('radiologi_image_base_url');
            $table->unsignedSmallInteger('queue_purge_keep_days')->default(0)->after('queue_purge_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['queue_purge_enabled', 'queue_purge_keep_days']);
        });
    }
};
