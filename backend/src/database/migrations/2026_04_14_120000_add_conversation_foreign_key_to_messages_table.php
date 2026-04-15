<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('messages')
            ->whereNotNull('conversation_id')
            ->whereNotIn('conversation_id', function ($query) {
                $query->select('id')->from('conversations');
            })
            ->update(['conversation_id' => null]);

        Schema::table('messages', function (Blueprint $table) {
            $table->foreign('conversation_id')
                ->references('id')
                ->on('conversations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['conversation_id']);
        });
    }
};
