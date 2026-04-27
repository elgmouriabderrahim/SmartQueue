<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institution_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique('user_id');
            $table->unique(['institution_id', 'user_id']);
        });

        $rows = DB::table('users')
            ->select('institution_id', 'id')
            ->whereNotNull('institution_id')
            ->get();

        foreach ($rows as $row) {
            DB::table('institution_user')->updateOrInsert(
                ['user_id' => $row->id],
                [
                    'institution_id' => $row->institution_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_user');
    }
};
