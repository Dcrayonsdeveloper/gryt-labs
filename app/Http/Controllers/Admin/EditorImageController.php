<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TenantImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CKEditor 5 SimpleUploadAdapter endpoint.
 * Handles image uploads from the rich text editor.
 */
class EditorImageController extends Controller
{
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'upload' => array_merge(['required'], TenantImageService::validateRules('content')),
        ]);

        $path = TenantImageService::uploadContent($request->file('upload'));
        $url = asset('storage/' . $path);

        // CKEditor 5 SimpleUploadAdapter expects this format
        return response()->json([
            'url' => $url,
        ]);
    }
}
