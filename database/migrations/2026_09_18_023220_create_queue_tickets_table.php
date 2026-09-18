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
        Schema::create('queue_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('no_rawat', 17)->unique();
            $table->string('no_rkm_medis', 15)->index();
            $table->char('kd_poli', 5);
            $table->string('kd_dokter', 20);
            $table->date('tanggal');
            $table->unsignedInteger('queue_number');
            $table->enum('status', ['waiting', 'called', 'serving', 'done', 'skipped', 'cancelled'])->default('waiting');
            $table->timestamp('called_at')->nullable();
            $table->timestamp('done_at')->nullable();
            $table->timestamp('notified_issued_at')->nullable();
            $table->timestamp('notified_near_at')->nullable();
            $table->timestamp('notified_called_at')->nullable();
            $table->timestamps();

            $table->unique(['kd_poli', 'tanggal', 'queue_number']);
            $table->index(['kd_poli', 'tanggal', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queue_tickets');
    }
};
