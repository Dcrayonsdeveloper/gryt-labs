<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Status column is text/varchar in PostgreSQL — no ENUM modification needed.
        // Values (packed, out_for_delivery) are enforced at application level.
    }

    public function down(): void
    {
        // No-op
    }
};
