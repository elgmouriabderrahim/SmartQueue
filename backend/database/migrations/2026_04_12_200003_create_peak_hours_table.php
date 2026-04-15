<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peak_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->unsignedTinyInteger('hour');
            $table->unsignedInteger('appointments_count')->default(0);
            $table->timestamps();

            $table->unique(['service_id', 'date', 'hour']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peak_hours');
    }
};
