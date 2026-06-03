<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['limit' => 10]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['limit' => 10]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div x-data="recentlyViewed()" x-init="load()" x-show="products.length > 0" x-cloak class="mt-8">
    <h2 class="text-xl font-bold text-gray-900 mb-4">Recently Viewed</h2>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
        <template x-for="product in products" :key="product.id">
            <a :href="'/products/' + product.slug" class="group block bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow p-3">
                <div class="aspect-square overflow-hidden rounded-md bg-gray-100 mb-2">
                    <img :src="product.image || '/images/placeholder.jpg'" :alt="product.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform" loading="lazy">
                </div>
                <h3 x-text="product.name" class="text-sm font-medium text-gray-900 truncate"></h3>
                <div class="flex items-center gap-2 mt-1">
                    <span x-text="'₹' + product.price" class="text-sm font-bold text-primary-600"></span>
                    <span x-show="product.mrp > product.price" x-text="'₹' + product.mrp" class="text-xs text-gray-500 line-through"></span>
                </div>
            </a>
        </template>
    </div>
</div>

<script>
function recentlyViewed() {
    return {
        products: [],
        async load() {
            try {
                const res = await fetch('/recommendations/recently-viewed?limit=<?php echo e($limit); ?>');
                const data = await res.json();
                this.products = data.data || [];
            } catch (e) {}
        }
    }
}
</script>
<?php /**PATH D:\projects\grytlabs345\resources\views/components/recently-viewed.blade.php ENDPATH**/ ?>