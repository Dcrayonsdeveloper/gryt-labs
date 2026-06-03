#!/bin/bash
# =================================================================
# MySQL → PostgreSQL Migration Script
# =================================================================
# Prerequisites:
#   1. PostgreSQL installed and running
#   2. pgloader installed: sudo apt-get install pgloader
#   3. MySQL databases accessible
#   4. PHP 8.3 + composer available
#
# Usage: bash scripts/migrate-mysql-to-postgresql.sh
# =================================================================

set -e

# Configuration (update these)
MYSQL_HOST="${MYSQL_HOST:-127.0.0.1}"
MYSQL_USER="${MYSQL_USER:-root}"
MYSQL_PASS="${MYSQL_PASS:-}"
PG_HOST="${PG_HOST:-127.0.0.1}"
PG_USER="${PG_USER:-postgres}"
PG_PASS="${PG_PASS:-}"
PG_PORT="${PG_PORT:-5432}"

# Databases to migrate
DATABASES=("jikra_central" "jikra" "natually")

echo "=== MySQL → PostgreSQL Migration ==="
echo ""

# Step 1: Create PostgreSQL databases
echo "Step 1: Creating PostgreSQL databases..."
for db in "${DATABASES[@]}"; do
    echo "  Creating database: $db"
    PGPASSWORD=$PG_PASS psql -h $PG_HOST -U $PG_USER -p $PG_PORT -c "CREATE DATABASE \"$db\" WITH ENCODING 'UTF8';" 2>/dev/null || echo "  (already exists)"
done
echo ""

# Step 2: Run Laravel migrations on PostgreSQL
echo "Step 2: Running Laravel migrations..."
echo "  Update .env: DB_CONNECTION=pgsql DB_PORT=5432"
echo "  Then run: php artisan migrate --path=database/migrations --force"
echo "  And for tenants: php artisan tenants:migrate --force"
echo ""

# Step 3: Migrate data using pgloader
echo "Step 3: Migrating data with pgloader..."
for db in "${DATABASES[@]}"; do
    echo "  Migrating: $db"

    MYSQL_URI="mysql://${MYSQL_USER}:${MYSQL_PASS}@${MYSQL_HOST}/${db}"
    PG_URI="postgresql://${PG_USER}:${PG_PASS}@${PG_HOST}:${PG_PORT}/${db}"

    cat > /tmp/pgloader_${db}.load << PGEOF
LOAD DATABASE
    FROM ${MYSQL_URI}
    INTO ${PG_URI}

WITH include no drop,
     create no tables,
     create no indexes,
     no foreign keys,
     data only,
     batch rows = 1000,
     prefetch rows = 1000

SET work_mem to '128MB',
    maintenance_work_mem to '512MB'

CAST type int to integer,
     type bigint to bigint,
     type tinyint to smallint,
     type mediumint to integer,
     type float to real,
     type double to double precision,
     type enum to text,
     type set to text,
     type json to jsonb,
     type longtext to text,
     type mediumtext to text

EXCLUDING TABLE NAMES MATCHING 'migrations'
;
PGEOF

    pgloader /tmp/pgloader_${db}.load 2>&1 || echo "  Warning: pgloader had issues with $db"
done
echo ""

# Step 4: Fix sequences (auto-increment equivalents)
echo "Step 4: Fixing PostgreSQL sequences..."
for db in "${DATABASES[@]}"; do
    PGPASSWORD=$PG_PASS psql -h $PG_HOST -U $PG_USER -p $PG_PORT -d "$db" << 'FIXSEQ'
DO $$
DECLARE
    r RECORD;
BEGIN
    FOR r IN (
        SELECT table_name, column_name
        FROM information_schema.columns
        WHERE column_default LIKE 'nextval%'
        AND table_schema = 'public'
    ) LOOP
        EXECUTE format('SELECT setval(pg_get_serial_sequence(%L, %L), COALESCE(MAX(%I), 0) + 1, false) FROM %I',
            r.table_name, r.column_name, r.column_name, r.table_name);
    END LOOP;
END $$;
FIXSEQ
    echo "  Fixed sequences for: $db"
done
echo ""

# Step 5: Install Redis packages
echo "Step 5: Installing Redis packages..."
cd /var/www/jikra  # or wherever the project is
composer require predis/predis --no-interaction 2>&1 || echo "  predis already installed"
echo ""

# Step 6: Update .env
echo "Step 6: Update your .env file:"
echo "  DB_CONNECTION=pgsql"
echo "  DB_PORT=5432"
echo "  CACHE_STORE=redis"
echo "  SESSION_DRIVER=redis"
echo "  QUEUE_CONNECTION=redis"
echo "  REDIS_CLIENT=predis"
echo ""

# Step 7: Clear caches
echo "Step 7: Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
echo ""

echo "=== Migration Complete ==="
echo "Test your application thoroughly!"
echo "If issues arise, switch back to MySQL by changing DB_CONNECTION=mysql in .env"
