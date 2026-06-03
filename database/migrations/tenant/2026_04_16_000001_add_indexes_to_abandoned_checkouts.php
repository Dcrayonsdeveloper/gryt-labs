<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abandoned_checkouts', function (Blueprint $table) {
            if (!Schema::hasColumn('abandoned_checkouts', 'reminder_count')) {
                $table->unsignedTinyInteger('reminder_count')->default(0)->after('recovered');
            }
        });

        try {
            Schema::table('abandoned_checkouts', function (Blueprint $table) {
                $table->index('created_at');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('abandoned_checkouts', function (Blueprint $table) {
                $table->index(['recovered', 'reminder_count', 'created_at']);
            });
        } catch (\Exception $e) {}
    }

    public function down(): void
    {
        Schema::table('abandoned_checkouts', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['recovered', 'reminder_count', 'created_at']);
        });
    }
};
