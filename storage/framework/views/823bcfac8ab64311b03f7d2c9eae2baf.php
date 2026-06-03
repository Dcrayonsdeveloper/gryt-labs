<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['items' => []]));

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

foreach (array_filter((['items' => []]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $breadcrumbBg = isset($theme) ? $theme->get('breadcrumb_bg_image') : '';
    $hideBreadcrumb = isset($theme) && $theme->get('hide_breadcrumb');
?>

<?php if($hideBreadcrumb): ?>

<?php elseif($breadcrumbBg): ?>
<div class="relative w-screen left-1/2 -ml-[50vw] bg-cover bg-center" style="background-image: url('<?php echo e(asset($breadcrumbBg)); ?>');">
    <div class="absolute inset-0 bg-black/40"></div>
    <div class="relative container mx-auto px-4 py-5">
        <nav class="text-[13px]" aria-label="Breadcrumb">
            <ol class="flex items-center flex-wrap gap-1.5">
                <li>
                    <a href="<?php echo e(url('/')); ?>" class="inline-flex items-center gap-1 text-white/80 hover:text-white transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        <span>Home</span>
                    </a>
                </li>
                <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li class="flex items-center gap-1.5">
                        <svg class="w-3 h-3 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                        </svg>
                        <?php if($loop->last): ?>
                            <span class="text-white font-medium"><?php echo e($item['label']); ?></span>
                        <?php else: ?>
                            <a href="<?php echo e($item['url']); ?>" class="text-white/80 hover:text-white transition-colors"><?php echo e($item['label']); ?></a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ol>
        </nav>
    </div>
</div>
<?php else: ?>
<nav <?php echo e($attributes->merge(['class' => 'text-[13px]'])); ?> aria-label="Breadcrumb">
    <ol class="flex items-center flex-wrap gap-1.5">
        <li>
            <a href="<?php echo e(url('/')); ?>" class="inline-flex items-center gap-1 text-neutral-600 hover:text-primary-600 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span>Home</span>
            </a>
        </li>

        <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li class="flex items-center gap-1.5">
                <svg class="w-3 h-3 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>
                <?php if($loop->last): ?>
                    <span class="text-neutral-800 font-medium"><?php echo e($item['label']); ?></span>
                <?php else: ?>
                    <a href="<?php echo e($item['url']); ?>" class="text-neutral-600 hover:text-primary-600 transition-colors"><?php echo e($item['label']); ?></a>
                <?php endif; ?>
            </li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ol>
</nav>
<?php endif; ?>


<?php
$breadcrumbSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
    ],
];
foreach ($items as $i => $item) {
    $entry = ['@type' => 'ListItem', 'position' => $i + 2, 'name' => $item['label']];
    if (!empty($item['url'])) {
        $entry['item'] = $item['url'];
    }
    $breadcrumbSchema['itemListElement'][] = $entry;
}
?>
<script type="application/ld+json"><?php echo json_encode($breadcrumbSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
<?php /**PATH D:\projects\grytlabs345\resources\views/components/breadcrumb.blade.php ENDPATH**/ ?>