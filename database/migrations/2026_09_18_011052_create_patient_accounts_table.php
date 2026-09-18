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
        Schema::create('patient_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('no_rkm_medis', 15)->unique();
            $table->string('name')->nullable();
            $table->string('wa_number')->nullable();
            $table->timestamp('wa_verified_at')->nullable();
            $table->boolean('notify_appointment')->default(true);
            $table->boolean('notify_queue')->default(true);
            $table->boolean('notify_lab_result')->default(false);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_accounts');
    }
};
