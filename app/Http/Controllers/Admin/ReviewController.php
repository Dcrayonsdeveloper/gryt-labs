<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $query = Review::with(['product:id,name,slug', 'user:id,first_name,last_name']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('rating')) {
            $rating = (int) $request->rating;
            if ($rating >= 1 && $rating <= 5) {
                $query->where('rating', $rating);
            }
        }

        $perPage = $request->input('per_page', 10);
        $reviews = $query->latest()->paginate($perPage)->withQueryString();

        return view('admin.reviews.index', compact('reviews'));
    }

    public function pending(): View
    {
        $perPage = request()->input('per_page', 10);
        $reviews = Review::where('is_approved', false)
            ->with(['product:id,name,slug', 'user:id,first_name,last_name'])
            ->latest()
            ->paginate($perPage)->withQueryString();

        return view('admin.reviews.pending', compact('reviews'));
    }

    public function show(Review $review): View
    {
        $review->load(['product', 'user']);

        return view('admin.reviews.show', compact('review'));
    }

    public function approve(Request $request, Review $review): RedirectResponse|JsonResponse
    {
        $review->approve();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'status' => 'approved',
                'message' => 'Review approved',
            ]);
        }

        return back()->with('success', 'Review approved');
    }

    public function reject(Request $request, Review $review): RedirectResponse|JsonResponse
    {
        $review->reject();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'status' => 'rejected',
                'message' => 'Review rejected',
            ]);
        }

        return back()->with('success', 'Review rejected');
    }

    public function edit(Review $review): View
    {
        $review->load(['product', 'user']);

        return view('admin.reviews.edit', compact('review'));
    }

    public function update(Request $request, Review $review): RedirectResponse
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string|max:5000',
            'reviewer_name' => 'nullable|string|max:255',
            'is_approved' => 'boolean',
            'is_featured' => 'boolean',
            'is_verified_purchase' => 'boolean',
        ]);

        $validated['is_approved'] = $request->boolean('is_approved');
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_verified_purchase'] = $request->boolean('is_verified_purchase');
        $validated['status'] = $validated['is_approved'] ? 'approved' : 'pending';
        $validated['moderated_at'] = now();

        $review->update($validated);

        return redirect()->route('admin.reviews.show', $review)->with('success', 'Review updated');
    }

    public function reply(Request $request, Review $review): RedirectResponse
    {
        $request->validate([
            'admin_reply' => 'required|string|max:2000',
        ]);

        $review->update([
            'admin_reply' => $request->admin_reply,
            'admin_replied_at' => now(),
        ]);

        return back()->with('success', 'Reply saved');
    }

    public function destroy(Review $review): RedirectResponse
    {
        $review->delete();

        return redirect()->route('admin.reviews.index')->with('success', 'Review deleted');
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $ids = json_decode($request->input('ids', '[]'), true);
        if (empty($ids)) {
            return back()->with('error', 'No items selected.');
        }

        $action = $request->input('action');
        $query = Review::whereIn('id', $ids);

        match ($action) {
            'delete'  => $query->delete(),
            'approve' => $query->update(['is_approved' => true]),
            'reject'  => $query->update(['is_approved' => false]),
            default   => null,
        };

        return back()->with('success', count($ids) . ' review(s) updated.');
    }
}
