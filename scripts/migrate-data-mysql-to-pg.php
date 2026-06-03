<?php
/**
 * MySQL → PostgreSQL Data Migration Script
 * Reads every row from MySQL and inserts into PostgreSQL.
 *
 * Usage: php scripts/migrate-data-mysql-to-pg.php [database] [--dry-run]
 * Example: php scripts/migrate-data-mysql-to-pg.php jikra
 *          php scripts/migrate-data-mysql-to-pg.php jikra_central
 *          php scripts/migrate-data-mysql-to-pg.php natually
 */

$database = $argv[1] ?? 'jikra';
$dryRun = in_array('--dry-run', $argv);

// MySQL connection
$mysql = new PDO("mysql:host=127.0.0.1;dbname={$database};charset=utf8mb4", 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

// PostgreSQL connection
$pg = new PDO("pgsql:host=127.0.0.1;dbname={$database}", 'postgres', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

echo "=== Migrating: {$database} ===\n";
echo $dryRun ? "[DRY RUN]\n" : "";
echo "\n";

// Get all MySQL tables
$tables = $mysql->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "Found " . count($tables) . " tables\n\n";

// Skip migrations table (Laravel manages this)
$skipTables = ['migrations'];

// Table creation order matters for foreign keys — get MySQL schema
$totalRows = 0;
$totalTables = 0;
$errors = [];

foreach ($tables as $table) {
    if (in_array($table, $skipTables)) {
        echo "SKIP: {$table} (system table)\n";
        continue;
    }

    // Count rows
    $count = (int) $mysql->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();

    echo "TABLE: {$table} ({$count} rows) ... ";

    // Get MySQL columns
    $columns = $mysql->query("DESCRIBE `{$table}`")->fetchAll();

    // Create PostgreSQL table if not exists
    $pgCols = [];
    $colNames = [];
    foreach ($columns as $col) {
        $name = $col['Field'];
        $colNames[] = $name;
        $type = strtolower($col['Type']);
        $nullable = $col['Null'] === 'YES';
        $default = $col['Default'];
        $extra = $col['Extra'];
        $key = $col['Key'];

        // MySQL → PostgreSQL type mapping
        $pgType = match (true) {
            str_contains($type, 'tinyint(1)') => 'BOOLEAN',
            str_contains($type, 'tinyint') => 'SMALLINT',
            str_contains($type, 'bigint') => 'BIGINT',
            str_contains($type, 'smallint') => 'SMALLINT',
            str_contains($type, 'mediumint') => 'INTEGER',
            str_contains($type, 'int') => 'INTEGER',
            str_contains($type, 'mediumint') => 'INTEGER',
            str_contains($type, 'decimal') || str_contains($type, 'numeric') => preg_replace('/decimal|numeric/', 'NUMERIC', $type),
            str_contains($type, 'float') => 'REAL',
            str_contains($type, 'double') => 'DOUBLE PRECISION',
            str_contains($type, 'varchar') => str_replace('varchar', 'VARCHAR', $type),
            str_contains($type, 'char') => str_replace('char', 'CHAR', $type),
            str_contains($type, 'longtext') || str_contains($type, 'mediumtext') => 'TEXT',
            str_contains($type, 'text') => 'TEXT',
            str_contains($type, 'blob') || str_contains($type, 'longblob') => 'BYTEA',
            str_contains($type, 'json') => 'JSONB',
            str_contains($type, 'enum') => 'TEXT', // ENUMs become TEXT in PG
            str_contains($type, 'set') => 'TEXT',
            str_contains($type, 'datetime') || str_contains($type, 'timestamp') => 'TIMESTAMP',
            str_contains($type, 'date') => 'DATE',
            str_contains($type, 'time') => 'TIME',
            str_contains($type, 'year') => 'INTEGER',
            default => 'TEXT',
        };

        // Handle auto-increment
        if (str_contains($extra, 'auto_increment')) {
            $pgType = str_contains($type, 'bigint') ? 'BIGSERIAL' : 'SERIAL';
        }

        $colDef = "\"{$name}\" {$pgType}";
        if ($key === 'PRI' && !str_contains($extra, 'auto_increment')) {
            $colDef .= ' PRIMARY KEY';
        }
        if (!$nullable && !str_contains($extra, 'auto_increment')) {
            // Skip NOT NULL for columns with defaults to avoid insert issues
        }

        $pgCols[] = $colDef;
    }

    // Find primary key
    $pkCol = null;
    foreach ($columns as $col) {
        if ($col['Key'] === 'PRI' && str_contains($col['Extra'], 'auto_increment')) {
            $pkCol = $col['Field'];
            break;
        }
    }

    if (!$dryRun) {
        // Drop and recreate table in PostgreSQL
        $pg->exec("DROP TABLE IF EXISTS \"{$table}\" CASCADE");

        $createSql = "CREATE TABLE \"{$table}\" (\n  " . implode(",\n  ", $pgCols);
        if ($pkCol) {
            $createSql .= ",\n  PRIMARY KEY (\"{$pkCol}\")";
        }
        $createSql .= "\n)";

        try {
            $pg->exec($createSql);
        } catch (PDOException $e) {
            $errors[] = "CREATE {$table}: " . $e->getMessage();
            echo "FAILED (create)\n";
            continue;
        }
    }

    // Migrate rows in batches
    $batchSize = 500;
    $offset = 0;
    $inserted = 0;

    while (true) {
        $rows = $mysql->query("SELECT * FROM `{$table}` LIMIT {$batchSize} OFFSET {$offset}")->fetchAll();
        if (empty($rows)) break;

        if (!$dryRun) {
            foreach ($rows as $row) {
                $cols = array_keys($row);
                $placeholders = array_map(fn($i) => ':p' . $i, range(0, count($cols) - 1));
                $quotedCols = array_map(fn($c) => "\"{$c}\"", $cols);

                $sql = "INSERT INTO \"{$table}\" (" . implode(', ', $quotedCols) . ") VALUES (" . implode(', ', $placeholders) . ")";

                try {
                    $stmt = $pg->prepare($sql);
                    foreach (array_values($row) as $i => $val) {
                        // Handle boolean conversion (MySQL tinyint(1) → PG boolean)
                        // Detect boolean columns by checking the MySQL schema
                        $colType = '';
                        foreach ($columns as $colDef) {
                            if ($colDef['Field'] === $cols[$i]) {
                                $colType = strtolower($colDef['Type']);
                                break;
                            }
                        }
                        if (str_contains($colType, 'tinyint(1)') && $val !== null) {
                            $stmt->bindValue(':p' . $i, (bool) $val, PDO::PARAM_BOOL);
                            continue;
                        }
                        $stmt->bindValue(':p' . $i, $val);
                    }
                    $stmt->execute();
                    $inserted++;
                } catch (PDOException $e) {
                    // Skip duplicate key errors on retry
                    if (!str_contains($e->getMessage(), 'duplicate key')) {
                        $errors[] = "{$table} row: " . substr($e->getMessage(), 0, 100);
                    }
                }
            }
        } else {
            $inserted += count($rows);
        }

        $offset += $batchSize;
    }

    // Fix sequence if auto-increment
    if ($pkCol && !$dryRun) {
        try {
            $maxId = $pg->query("SELECT COALESCE(MAX(\"{$pkCol}\"), 0) + 1 FROM \"{$table}\"")->fetchColumn();
            $seqName = "{$table}_{$pkCol}_seq";
            $pg->exec("SELECT setval('{$seqName}', {$maxId}, false)");
        } catch (PDOException $e) {
            // Sequence might not exist for non-serial PKs
        }
    }

    echo "{$inserted} rows " . ($dryRun ? '(dry run)' : 'migrated') . "\n";
    $totalRows += $inserted;
    $totalTables++;
}

echo "\n=== COMPLETE ===\n";
echo "Tables: {$totalTables}\n";
echo "Rows: {$totalRows}\n";
echo "Errors: " . count($errors) . "\n";

if (count($errors) > 0) {
    echo "\nError details:\n";
    foreach (array_slice($errors, 0, 20) as $err) {
        echo "  - {$err}\n";
    }
    if (count($errors) > 20) {
        echo "  ... and " . (count($errors) - 20) . " more\n";
    }
}
