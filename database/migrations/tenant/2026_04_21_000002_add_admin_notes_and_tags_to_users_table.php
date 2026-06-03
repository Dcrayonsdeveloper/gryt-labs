<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('admin_notes')->nullable()->after('phone');
            $table->jsonb('admin_tags')->nullable()->default(DB::raw("'[]'::jsonb"))->after('admin_notes');
        });

        // GIN index for fast JSONB tag search (e.g. WHERE admin_tags @> '["vip"]')
        DB::statement('CREATE INDEX IF NOT EXISTS users_admin_tags_gin_idx ON users USING GIN (admin_tags)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS users_admin_tags_gin_idx');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'admin_notes',
                'admin_tags',
            ]);
        });
    }
};
