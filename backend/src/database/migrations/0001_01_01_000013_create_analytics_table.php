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
            $table->unsignedInteger('total_appointments')->default(0);
            $table->unsignedInteger('completed_appointments')->default(0);
            $table->unsignedInteger('cancelled_appointments')->default(0);
            $table->unsignedInteger('total_visitors')->default(0);
            $table->decimal('average_rating', 8, 2)->default(0);
            $table->decimal('average_wait_time', 8, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics');
    }
};
