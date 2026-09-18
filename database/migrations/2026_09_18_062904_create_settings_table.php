<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();

            // Identitas aplikasi
            $table->string('app_name')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('icon_path')->nullable();
            $table->string('favicon_path')->nullable();

            // Koneksi WhatsApp (GOWA)
            $table->string('gowa_base_url')->nullable();
            $table->string('gowa_username')->nullable();
            $table->text('gowa_password')->nullable();
            $table->unsignedSmallInteger('gowa_timeout')->nullable();
            $table->string('gowa_default_device_id')->nullable();

            // Koneksi database SIMRS Khanza
            $table->string('sik_db_host')->nullable();
            $table->unsignedInteger('sik_db_port')->nullable();
            $table->string('sik_db_database')->nullable();
            $table->string('sik_db_username')->nullable();
            $table->text('sik_db_password')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
