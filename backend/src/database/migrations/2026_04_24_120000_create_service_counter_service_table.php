<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_counter_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_counter_id')->constrained('service_counters')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['service_counter_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_counter_service');
    }
};