<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->handleRequest(Illuminate\Http\Request::capture());

$tenants = App\Models\Tenant::all();
foreach ($tenants as $t) {
    $t->run(function () use ($t) {
        try {
            if (!Illuminate\Support\Facades\Schema::hasColumn('products', 'amazon_url')) {
                Illuminate\Support\Facades\Schema::table('products', function ($table) {
                    $table->string('amazon_url', 500)->nullable();
                    $table->string('flipkart_url', 500)->nullable();
                });
                echo $t->id . ": columns added\n";
            } else {
                echo $t->id . ": already exists\n";
            }
        } catch (Exception $e) {
            echo $t->id . ": ERROR " . $e->getMessage() . "\n";
        }
    });
}
