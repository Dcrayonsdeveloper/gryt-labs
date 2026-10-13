{{--
    Compact pagination info bar — "Showing 1–20 of 57" plus Previous/Next links.

    Referenced as ->links('vendor.pagination.info-bar') by the admin index pages
    (brands, attributes, notifications, reports, sellers, settings, …). The view
    was never created, so every one of those pages threw a 500.

    Callers render this inside their own padded wrapper (e.g. px-5 py-3 on a card
    header), so it deliberately adds no padding, border, or background of its own.
    Markup and classes mirror the inline pagination in admin/customers/index.blade.php
    so the bar looks identical across admin and needs no new Tailwind classes.
--}}
@if ($paginator->total() > 0)
    <div class="flex items-center justify-between">
        <p class="text-sm text-gray-500">
            Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}
        </p>

        @if ($paginator->hasPages())
            <div class="flex items-center gap-1">
                @if ($paginator->onFirstPage())
                    <span class="px-3 py-1.5 text-sm text-gray-300 border border-gray-200 rounded-lg">&lsaquo; Previous</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">&lsaquo; Previous</a>
                @endif

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">Next &rsaquo;</a>
                @else
                    <span class="px-3 py-1.5 text-sm text-gray-300 border border-gray-200 rounded-lg">Next &rsaquo;</span>
                @endif
            </div>
        @endif
    </div>
@endif
