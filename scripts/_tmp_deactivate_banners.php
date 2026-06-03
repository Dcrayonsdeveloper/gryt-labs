<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

tenancy()->initialize(App\Models\Tenant::find('urbanindia'));

// Deactivate banners 6 and 7, keep only banner 5
App\Models\Banner::whereIn('id', [6, 7])->update(['is_active' => false]);

echo "Banners after update:\n";
App\Models\Banner::all()->each(function($b) {
    echo "  ID:{$b->id} active=" . ($b->is_active ? 'YES' : 'NO') . " title='{$b->title}'\n";
});
