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
        Schema::create('patient_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('no_rkm_medis', 15)->nullable()->index();
            $table->string('wa_number')->nullable();
            $table->string('type');
            $table->text('message');
            $table->string('status')->default('pending');
            $table->string('gowa_message_id')->nullable();
            $table->string('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_notification_logs');
    }
};
