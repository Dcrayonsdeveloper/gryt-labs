<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ThemeManager;
use Illuminate\Http\Request;

class ThemeGalleryController extends Controller
{
    public function __construct(private ThemeManager $themeManager) {}

    /**
     * Show available themes.
     */
    public function index()
    {
        $themes = $this->themeManager->available();
        $activeTheme = $this->themeManager->active();

        return view('admin.theme.gallery', compact('themes', 'activeTheme'));
    }

    /**
     * Activate a theme.
     */
    public function activate(Request $request)
    {
        $request->validate(['theme' => 'required|string']);

        $theme = $request->input('theme');
        $available = $this->themeManager->available();

        if (!isset($available[$theme])) {
            return back()->with('error', 'Theme not found.');
        }

        $this->themeManager->setActive($theme);

        return back()->with('success', "Theme '{$available[$theme]['name']}' activated.");
    }

    /**
     * Preview a theme (returns theme meta + settings).
     */
    public function preview(string $theme)
    {
        $meta = $this->themeManager->getThemeMeta($theme);
        $screenshot = $this->themeManager->getScreenshot($theme);

        return response()->json([
            'meta' => $meta,
            'screenshot' => $screenshot,
        ]);
    }
}
