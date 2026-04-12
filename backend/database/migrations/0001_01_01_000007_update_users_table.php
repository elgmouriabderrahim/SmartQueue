<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('identity_number')->nullable()->unique()->after('phone');
            $table->enum('role', ['citizen', 'employee', 'manager', 'admin'])->default('citizen')->after('identity_number');
            $table->foreignId('institution_id')->nullable()->after('role')->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->after('institution_id')->constrained()->nullOnDelete();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->after('department_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['identity_number']);
            $table->dropForeign(['institution_id']);
            $table->dropForeign(['department_id']);
            $table->dropColumn([
                'phone',
                'identity_number',
                'role',
                'institution_id',
                'department_id',
                'status',
            ]);
        });
    }
};
