<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->integer('current_position')->default(0);
            $table->enum('status', ['active', 'paused', 'closed'])->default('active');
            $table->timestamps();

            $table->unique(['service_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queues');
    }
};
