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
        Schema::create('lab_result_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('noorder', 15)->unique();
            $table->string('no_rawat', 17);
            $table->string('no_rkm_medis', 15)->index();
            $table->timestamp('resulted_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_result_notifications');
    }
};
