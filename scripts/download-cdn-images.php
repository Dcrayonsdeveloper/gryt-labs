<?php
/**
 * Download all CDN product images to local tenant-scoped storage.
 * Updates DB URLs from CDN → local paths.
 *
 * Usage: php scripts/download-cdn-images.php [tenant_db] [--dry-run]
 * Example: php scripts/download-cdn-images.php jikra
 */

$tenantDb = $argv[1] ?? 'jikra';
$dryRun = in_array('--dry-run', $argv);

$storageBase = __DIR__ . '/../public/storage/tenants/' . $tenantDb . '/products';
if (!is_dir($storageBase)) {
    mkdir($storageBase, 0755, true);
}

// Connect to MySQL
$pdo = new PDO('mysql:host=127.0.0.1;dbname=' . $tenantDb, 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

// Get all CDN images
$stmt = $pdo->query("SELECT id, product_id, url FROM product_images WHERE url LIKE 'https://%' ORDER BY id");
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($images) . " CDN images in '{$tenantDb}' database.\n";
if ($dryRun) {
    echo "[DRY RUN] No files will be downloaded.\n";
}

$downloaded = 0;
$failed = 0;
$skipped = 0;

foreach ($images as $img) {
    $url = $img['url'];
    $productId = $img['product_id'];

    // Generate local filename: product_id-hash.ext
    $ext = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
    // Clean extension (remove query params)
    $ext = preg_replace('/[^a-zA-Z]/', '', $ext);
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'])) {
        $ext = 'jpg';
    }
    $hash = substr(md5($url), 0, 8);
    $filename = "p{$productId}-{$hash}.{$ext}";
    $localPath = $storageBase . '/' . $filename;
    $dbPath = "tenants/{$tenantDb}/products/{$filename}";

    // Skip if already downloaded
    if (file_exists($localPath) && filesize($localPath) > 1000) {
        $skipped++;
        // Still update DB path
        if (!$dryRun) {
            $pdo->prepare("UPDATE product_images SET url = ? WHERE id = ?")->execute([$dbPath, $img['id']]);
        }
        continue;
    }

    if ($dryRun) {
        echo "  Would download: {$url} → {$filename}\n";
        $downloaded++;
        continue;
    }

    // Download with timeout
    $ctx = stream_context_create([
        'http' => ['timeout' => 15, 'user_agent' => 'Mozilla/5.0'],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
    ]);

    $data = @file_get_contents($url, false, $ctx);
    if ($data && strlen($data) > 500) {
        file_put_contents($localPath, $data);
        // Update DB URL to local path
        $pdo->prepare("UPDATE product_images SET url = ? WHERE id = ?")->execute([$dbPath, $img['id']]);
        $downloaded++;
        if ($downloaded % 50 === 0) {
            echo "  Downloaded {$downloaded}/" . count($images) . "...\n";
        }
    } else {
        $failed++;
        echo "  FAILED: {$url}\n";
    }

    // Be nice to the CDN
    usleep(100000); // 100ms delay
}

echo "\nDone! Downloaded: {$downloaded}, Skipped: {$skipped}, Failed: {$failed}\n";
echo "Images saved to: {$storageBase}/\n";
echo "DB URLs updated to: tenants/{$tenantDb}/products/\n";
