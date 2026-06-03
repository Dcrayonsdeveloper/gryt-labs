<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageSection;
use Illuminate\Http\Request;

class PageSectionController extends Controller
{
    public function index(string $page = 'home')
    {
        $sections = PageSection::where('page', $page)->orderBy('position')->get();
        $availableTypes = PageSection::availableTypes();

        return view('admin.sections.builder', compact('sections', 'availableTypes', 'page'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'page' => 'required|string',
            'type' => 'required|string',
        ]);

        $types = PageSection::availableTypes();
        $typeConfig = $types[$validated['type']] ?? null;

        if (!$typeConfig) {
            return back()->with('error', 'Invalid section type.');
        }

        $maxPosition = PageSection::where('page', $validated['page'])->max('position') ?? 0;

        $section = PageSection::create([
            'page' => $validated['page'],
            'type' => $validated['type'],
            'name' => $typeConfig['name'],
            'position' => $maxPosition + 1,
            'settings' => $typeConfig['defaults'],
            'is_active' => true,
        ]);

        return back()->with('success', "'{$typeConfig['name']}' section added.");
    }

    public function update(Request $request, PageSection $section)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'settings' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        if (isset($validated['settings'])) {
            $section->settings = array_merge($section->settings ?? [], $validated['settings']);
        }
        if (isset($validated['name'])) {
            $section->name = $validated['name'];
        }
        if (array_key_exists('is_active', $validated)) {
            $section->is_active = $validated['is_active'];
        }

        $section->save();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Section updated.');
    }

    public function destroy(PageSection $section)
    {
        $section->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Section removed.');
    }

    /**
     * Reorder sections via AJAX.
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:page_sections,id',
        ]);

        foreach ($validated['order'] as $position => $id) {
            PageSection::where('id', $id)->update(['position' => $position]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Toggle section active/inactive.
     */
    public function toggle(PageSection $section)
    {
        $section->update(['is_active' => !$section->is_active]);

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'is_active' => $section->is_active]);
        }

        return back();
    }
}
