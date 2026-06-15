<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $isPostgres = DB::connection()->getDriverName() === 'pgsql';

        Schema::table('users', function (Blueprint $table) use ($isPostgres) {
            $table->text('admin_notes')->nullable()->after('phone');

            if ($isPostgres) {
                $table->jsonb('admin_tags')->nullable()->default(DB::raw("'[]'::jsonb"))->after('admin_notes');
            } else {
                // MySQL: JSON columns cannot have a literal default; app handles the [] default.
                $table->json('admin_tags')->nullable()->after('admin_notes');
            }
        });

        // GIN index for fast JSONB tag search (PostgreSQL only; MySQL has no GIN).
        if ($isPostgres) {
            DB::statement('CREATE INDEX IF NOT EXISTS users_admin_tags_gin_idx ON users USING GIN (admin_tags)');
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS users_admin_tags_gin_idx');
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'admin_notes',
                'admin_tags',
            ]);
        });
    }
};
