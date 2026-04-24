<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('queue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->unique()->constrained()->cascadeOnDelete();
            $table->integer('position');
            $table->unsignedInteger('estimated_wait_time')->default(0);
            $table->enum('status', ['waiting', 'called', 'serving', 'served', 'skipped', 'transferred'])->default('waiting');
            $table->timestamps();

            $table->unique(['queue_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_entries');
    }
};
