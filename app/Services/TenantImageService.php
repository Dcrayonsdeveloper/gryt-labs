<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Tenant-scoped image storage — Shopify-like architecture.
 *
 * Storage structure:
 *   public/storage/tenants/{tenant_id}/products/  — product images
 *   public/storage/tenants/{tenant_id}/branding/  — logo, favicon
 *   public/storage/tenants/{tenant_id}/banners/   — hero banners
 *   public/storage/tenants/{tenant_id}/content/   — rich text editor uploads
 *
 * Each image is stored in multiple sizes:
 *   original.webp   — full size (max 2000px)
 *   medium.webp     — 800px wide
 *   thumb.webp      — 400px wide
 */
class TenantImageService
{
    /**
     * Get the current tenant ID for storage scoping.
     */
    public static function tenantId(): string
    {
        if (function_exists('tenant') && tenant()) {
            return tenant()->id;
        }
        return 'default';
    }

    /**
     * Get the tenant-scoped storage path prefix.
     */
    public static function basePath(string $type = 'products'): string
    {
        return 'tenants/' . static::tenantId() . '/' . $type;
    }

    /**
     * Upload and process a product image.
     * Returns the relative storage path (e.g., tenants/jikra/products/slug-abc123.webp)
     */
    public static function uploadProductImage(UploadedFile $file, string $slug = ''): string
    {
        return static::upload($file, 'products', $slug);
    }

    /**
     * Upload a banner image.
     */
    public static function uploadBanner(UploadedFile $file, string $name = ''): string
    {
        return static::upload($file, 'banners', $name);
    }

    /**
     * Upload a content/editor image (for rich text).
     */
    public static function uploadContent(UploadedFile $file): string
    {
        return static::upload($file, 'content', '');
    }

    /**
     * Upload a branding asset (logo, favicon).
     */
    public static function uploadBranding(UploadedFile $file, string $type = 'logo'): string
    {
        $path = static::basePath('branding');
        $filename = $type . '-' . time() . '.' . $file->getClientOriginalExtension();
        $file->storeAs($path, $filename, 'public');
        return $path . '/' . $filename;
    }

    /**
     * Core upload method — saves image with optional resizing.
     */
    public static function upload(UploadedFile $file, string $type, string $prefix = ''): string
    {
        $path = static::basePath($type);

        // Generate unique filename
        $slug = $prefix ? Str::slug($prefix) . '-' : '';
        $hash = substr(md5(uniqid(rand(), true)), 0, 8);
        $ext = $file->getClientOriginalExtension() ?: 'jpg';
        $filename = $slug . $hash . '.' . $ext;

        // Store original
        $file->storeAs($path, $filename, 'public');
        $storedPath = $path . '/' . $filename;

        // Generate resized versions if GD/Imagick available
        static::generateResized($storedPath, $filename, $path);

        return $storedPath;
    }

    /**
     * Generate thumbnail and medium versions.
     */
    private static function generateResized(string $originalPath, string $filename, string $dir): void
    {
        if (!extension_loaded('gd')) {
            return;
        }

        $fullPath = Storage::disk('public')->path($originalPath);
        if (!file_exists($fullPath)) {
            return;
        }

        $info = @getimagesize($fullPath);
        if (!$info) {
            return;
        }

        [$width, $height, $imgType] = $info;

        // Skip if already small
        if ($width <= 400) {
            return;
        }

        $source = match ($imgType) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($fullPath),
            IMAGETYPE_PNG => @imagecreatefrompng($fullPath),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($fullPath) : null,
            IMAGETYPE_GIF => @imagecreatefromgif($fullPath),
            default => null,
        };

        if (!$source) {
            return;
        }

        $nameBase = pathinfo($filename, PATHINFO_FILENAME);
        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        // Generate medium (800px max width)
        if ($width > 800) {
            $mediumWidth = 800;
            $mediumHeight = (int) ($height * ($mediumWidth / $width));
            $medium = imagecreatetruecolor($mediumWidth, $mediumHeight);
            static::preserveTransparency($medium, $imgType);
            imagecopyresampled($medium, $source, 0, 0, 0, 0, $mediumWidth, $mediumHeight, $width, $height);
            $mediumPath = Storage::disk('public')->path($dir . '/' . $nameBase . '-medium.' . $ext);
            static::saveImage($medium, $mediumPath, $imgType);
            imagedestroy($medium);
        }

        // Generate thumbnail (400px max width)
        $thumbWidth = min(400, $width);
        $thumbHeight = (int) ($height * ($thumbWidth / $width));
        $thumb = imagecreatetruecolor($thumbWidth, $thumbHeight);
        static::preserveTransparency($thumb, $imgType);
        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);
        $thumbPath = Storage::disk('public')->path($dir . '/' . $nameBase . '-thumb.' . $ext);
        static::saveImage($thumb, $thumbPath, $imgType);
        imagedestroy($thumb);

        imagedestroy($source);
    }

    private static function preserveTransparency($image, int $type): void
    {
        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP) {
            imagealphablending($image, false);
            imagesavealpha($image, true);
            $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
            imagefill($image, 0, 0, $transparent);
        }
    }

    private static function saveImage($image, string $path, int $type): void
    {
        match ($type) {
            IMAGETYPE_JPEG => imagejpeg($image, $path, 85),
            IMAGETYPE_PNG => imagepng($image, $path, 8),
            IMAGETYPE_WEBP => function_exists('imagewebp') ? imagewebp($image, $path, 85) : imagejpeg($image, $path, 85),
            IMAGETYPE_GIF => imagegif($image, $path),
            default => imagejpeg($image, $path, 85),
        };
    }

    /**
     * Delete an image and its resized variants.
     */
    public static function delete(string $path): void
    {
        if (!$path || !str_starts_with($path, 'tenants/')) {
            return;
        }

        Storage::disk('public')->delete($path);

        // Delete resized variants
        $nameBase = pathinfo($path, PATHINFO_FILENAME);
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $dir = dirname($path);

        Storage::disk('public')->delete($dir . '/' . $nameBase . '-medium.' . $ext);
        Storage::disk('public')->delete($dir . '/' . $nameBase . '-thumb.' . $ext);
    }

    /**
     * Get URL for an image (with optional size variant).
     */
    public static function url(string $path, string $size = 'original'): string
    {
        if (str_starts_with($path, 'http')) {
            return $path;
        }

        if ($size !== 'original') {
            $nameBase = pathinfo($path, PATHINFO_FILENAME);
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            $dir = dirname($path);
            $variantPath = $dir . '/' . $nameBase . '-' . $size . '.' . $ext;

            if (Storage::disk('public')->exists($variantPath)) {
                return asset('storage/' . $variantPath);
            }
        }

        return asset('storage/' . $path);
    }

    /**
     * Validate an uploaded image file.
     */
    public static function validateRules(string $type = 'product'): array
    {
        return match ($type) {
            'product' => ['image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'], // 5MB
            'banner' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],     // 10MB
            'branding' => ['image', 'mimes:jpg,jpeg,png,svg,webp', 'max:2048'], // 2MB
            'content' => ['image', 'mimes:jpg,jpeg,png,webp,gif', 'max:3072'],  // 3MB
            default => ['image', 'max:5120'],
        };
    }
}
