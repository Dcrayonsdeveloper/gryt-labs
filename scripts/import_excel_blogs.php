<?php
require "/var/www/jikra/vendor/autoload.php";
$app = require_once "/var/www/jikra/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tenant = App\Models\Tenant::where("id", "natually")->first();
if (!$tenant) { echo "Tenant not found!\n"; exit(1); }
tenancy()->initialize($tenant);

$json = file_get_contents(__DIR__ . "/natually_blogs_data.json");
$posts = json_decode($json, true);
if (!$posts) { echo "Failed to load JSON\n"; exit(1); }

echo "Loaded " . count($posts) . " posts\n\n";
$updated = 0; $inserted = 0;

foreach ($posts as $p) {
    $seoData = json_encode(["meta_title"=>$p["meta_title"],"meta_description"=>$p["meta_desc"]]);
    $tags = json_encode($p["tags"]);
    $existing = DB::table("blog_posts")->where("slug",$p["slug"])->first();
    if ($existing) {
        DB::table("blog_posts")->where("slug",$p["slug"])->update([
            "title"=>$p["title"],"content"=>$p["body"],
            "featured_image"=>$p["image"],"excerpt"=>$p["excerpt"],
            "seo_data"=>$seoData,"tags"=>$tags,"updated_at"=>now(),
        ]);
        $updated++; echo "UPDATED: {$p['slug']}\n";
    } else {
        DB::table("blog_posts")->insert([
            "title"=>$p["title"],"slug"=>$p["slug"],"content"=>$p["body"],
            "featured_image"=>$p["image"],"excerpt"=>$p["excerpt"],
            "seo_data"=>$seoData,"tags"=>$tags,"category"=>"Skin Care",
            "is_published"=>true,"published_at"=>now(),"view_count"=>$p["views"],
            "created_at"=>now(),"updated_at"=>now(),
        ]);
        $inserted++; echo "INSERTED: {$p['slug']}\n";
    }
}
echo "\nUpdated: $updated | Inserted: $inserted | Total: ".DB::table("blog_posts")->count()."\n";
