<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->integer('total_appointments');
            $table->decimal('average_wait_time', 8, 2);
            $table->timestamps();

            $table->unique(['date', 'institution_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics');
    }
};
