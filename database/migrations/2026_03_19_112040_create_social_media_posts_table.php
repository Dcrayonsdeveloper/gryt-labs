<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_media_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();

            $table->string('title');
            $table->text('caption');
            $table->string('media_type'); // image, video, carousel
            $table->json('media_urls')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->string('link')->nullable();
            $table->json('hashtags')->nullable();

            $table->json('platforms'); // ["ig_post","ig_reel","ig_story","fb_post","fb_reel","fb_story"]

            $table->string('status')->default('draft');
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('published_at')->nullable();

            $table->json('platform_results')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedTinyInteger('retry_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_media_posts');
    }
};
