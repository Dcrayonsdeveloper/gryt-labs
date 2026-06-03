<?php

/**
 * Add meta_title (varchar 255) + meta_description (text) to products.
 *
 * Background:
 *   Audit on 2026-04-21 (Prerna) found that only the `jikra` tenant DB had
 *   meta_title / meta_description on `products`. The other 3 tenants
 *   (natually, getsetnova, ayurvexa) were structurally missing these columns,
 *   making it impossible to set per-product SEO meta from the admin panel
 *   and blocking Tanvi from any SEO work.
 *
 *   Column types matched against jikra for cross-tenant consistency:
 *     meta_title         character varying(255)  NULL
 *     meta_description   text                    NULL
 *
 * Safety:
 *   Both up() and down() are guarded with hasColumn() so this migration
 *   is a no-op on jikra (which already has the columns) and idempotent
 *   on tenants where it has already run.
 *
 * Post-deploy backfill (Karan — run on production after `tenants:run migrate`
 * succeeds. Run per-tenant for natually, getsetnova, ayurvexa only —
 * NOT on jikra, which already has populated values curated by Prerna):
 *
 *   PGPASSWORD='JikraSecure2026' psql -U jikra_pg -h localhost -d natually   -f /tmp/backfill_meta.sql
 *   PGPASSWORD='JikraSecure2026' psql -U jikra_pg -h localhost -d getsetnova -f /tmp/backfill_meta.sql
 *   PGPASSWORD='JikraSecure2026' psql -U jikra_pg -h localhost -d ayurvexa   -f /tmp/backfill_meta.sql
 *
 * /tmp/backfill_meta.sql contents:
 *   -- meta_description: first 155 chars of short_description (or description fallback)
 *   UPDATE products
 *      SET meta_description = LEFT(COALESCE(NULLIF(short_description, ''), description), 155)
 *    WHERE deleted_at IS NULL
 *      AND (meta_description IS NULL OR meta_description = '')
 *      AND COALESCE(NULLIF(short_description, ''), description) IS NOT NULL;
 *
 *   -- meta_title: product name capped at 70 chars (Google SERP truncation)
 *   UPDATE products
 *      SET meta_title = LEFT(name, 70)
 *    WHERE deleted_at IS NULL
 *      AND (meta_title IS NULL OR meta_title = '')
 *      AND name IS NOT NULL;
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'meta_title')) {
                if (Schema::hasColumn('products', 'name')) {
                    $table->string('meta_title', 255)->nullable()->after('name');
                } else {
                    $table->string('meta_title', 255)->nullable();
                }
            }

            if (! Schema::hasColumn('products', 'meta_description')) {
                if (Schema::hasColumn('products', 'meta_title')) {
                    $table->text('meta_description')->nullable()->after('meta_title');
                } else {
                    $table->text('meta_description')->nullable();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'meta_description')) {
                $table->dropColumn('meta_description');
            }

            if (Schema::hasColumn('products', 'meta_title')) {
                $table->dropColumn('meta_title');
            }
        });
    }
};
