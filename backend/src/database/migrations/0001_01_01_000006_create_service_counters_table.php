<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('counter_number');
            $table->string('name');
            $table->enum('status', ['available', 'busy', 'offline'])->default('available');
            $table->timestamps();

            $table->unique(['service_id', 'counter_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_counters');
    }
};
