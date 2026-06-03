<?php
/**
 * Fix settings table in PostgreSQL — reimport from MySQL.
 * Run: php scripts/fix-pg-settings.php jikra
 *      php scripts/fix-pg-settings.php natually
 */

$db = $argv[1] ?? 'jikra';

$mysql = new PDO("mysql:host=127.0.0.1;dbname={$db};charset=utf8mb4", 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
$pg = new PDO("pgsql:host=127.0.0.1;dbname={$db}", 'postgres', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

// Recreate settings table in PostgreSQL
$pg->exec('DROP TABLE IF EXISTS "settings" CASCADE');
$pg->exec('
    CREATE TABLE "settings" (
        "id" BIGSERIAL PRIMARY KEY,
        "group" VARCHAR(50) NOT NULL DEFAULT \'general\',
        "key" VARCHAR(255) NOT NULL,
        "value" TEXT,
        "type" TEXT NOT NULL DEFAULT \'string\',
        "is_public" BOOLEAN NOT NULL DEFAULT false,
        "created_at" TIMESTAMP NULL,
        "updated_at" TIMESTAMP NULL
    )
');
$pg->exec('CREATE UNIQUE INDEX "settings_key_unique" ON "settings" ("key")');

// Migrate rows
$rows = $mysql->query("SELECT * FROM `settings`")->fetchAll(PDO::FETCH_ASSOC);
$count = 0;

$stmt = $pg->prepare('INSERT INTO "settings" ("id", "group", "key", "value", "type", "is_public", "created_at", "updated_at") VALUES (?, ?, ?, ?, ?, ?, ?, ?)');

foreach ($rows as $row) {
    try {
        $stmt->execute([
            $row['id'],
            $row['group'],
            $row['key'],
            $row['value'],
            $row['type'],
            $row['is_public'] ? 'true' : 'false',
            $row['created_at'],
            $row['updated_at'],
        ]);
        $count++;
    } catch (PDOException $e) {
        echo "Error: {$row['key']} — " . $e->getMessage() . "\n";
    }
}

// Fix sequence
$pg->exec("SELECT setval('settings_id_seq', COALESCE(MAX(id), 0) + 1, false) FROM settings");

echo "Migrated {$count}/" . count($rows) . " settings for {$db}\n";
