<?php

namespace App\Http\Controllers;

use App\Services\InstagramReelsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReelController extends Controller
{
    public function apiLatest(InstagramReelsService $service): JsonResponse
    {
        $reels = $service->getLatestReels(20);
        $handle = config('services.instagram.handle');

        return response()->json([
            'reels' => $reels,
            'handle' => $handle,
            'reels_url' => route('reels.index'),
        ]);
    }

    public function index(InstagramReelsService $service): View
    {
        $reels = $service->getLatestReels(20);

        return view('reels.index', compact('reels'));
    }

    public function show(string $shortcode, InstagramReelsService $service): View
    {
        $reels = $service->getLatestReels(20);
        $reel = collect($reels)->firstWhere('shortcode', $shortcode);

        if (!$reel) {
            abort(404);
        }

        // Get other reels for "More Videos" section
        $moreReels = collect($reels)->where('shortcode', '!=', $shortcode)->take(8)->values()->all();

        return view('reels.show', compact('reel', 'moreReels'));
    }
}
