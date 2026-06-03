<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['faqs' => null, 'product' => null]));

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

foreach (array_filter((['faqs' => null, 'product' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $cs = currency_symbol();
    $storeName = $theme->get('store_name', config('app.name'));
    $dispatchTime = $theme->get('dispatch_time', '24 hours');
    $deliveryDays = $theme->get('standard_delivery_days', '3-7');
    $returnDays = $theme->get('return_policy_days', 7);
    $codAdvance = $theme->get('cod_advance_amount', 100);

    // Product-specific context for more relevant answers
    $productName = $product->name ?? 'this product';
    $categoryName = $product?->category?->name ?? '';
    $brandName = $product?->brand?->name ?? $storeName;
    $price = $product?->price ? ($cs . number_format($product->price, 0)) : '';

    // Pool of question templates — a per-product deterministic subset is shown
    $faqPool = [
        ['q' => "How long does delivery take for {$productName}?", 'a' => "We dispatch all orders within {$dispatchTime}. Standard delivery takes {$deliveryDays} business days depending on your location. Metro cities typically receive {$productName} in 3-4 days."],
        ['q' => "What is the return policy for {$productName}?", 'a' => "We offer a {$returnDays}-day hassle-free return policy on {$productName}. If you receive a damaged or wrong product, contact us within {$returnDays} days and we will arrange a pickup and full refund."],
        ['q' => "Is Cash on Delivery available for {$productName}?", 'a' => "Yes! We offer Partial Pay (COD) on {$productName} where you pay a small advance of {$cs}{$codAdvance} online and the rest on delivery."],
        ['q' => "Is {$productName} genuine and authentic?", 'a' => "Absolutely. {$productName} is sourced directly from " . ($brandName ?: 'authorized distributors') . " and comes with a quality guarantee."],
        ['q' => "How do I track my {$productName} order?", 'a' => "Once {$productName} is shipped, you will receive a tracking link via SMS and email. You can also track anytime on our Track Order page using your order number."],
        ['q' => "What payment methods can I use to buy {$productName}?", 'a' => "You can buy {$productName} using all major payment methods via Razorpay — Credit/Debit Cards, UPI (GPay, PhonePe, Paytm), Net Banking, Wallets, and Partial Pay (COD)."],
        ['q' => "Can I cancel my {$productName} order after placing it?", 'a' => "Yes, you can cancel your {$productName} order anytime before it is shipped from our warehouse. Cancellations after dispatch are handled as a return."],
        ['q' => "Does {$productName} come with a warranty?", 'a' => "Where applicable, {$productName} comes with the manufacturer's standard warranty. Warranty details are listed in the product information section above."],
        ['q' => "How should I store or use {$productName}?", 'a' => "Please refer to the Product Description and Information tabs above for detailed usage and storage instructions specific to {$productName}."],
        ['q' => "Is {$productName} safe to use?", 'a' => "Yes. {$productName} meets all relevant quality and safety standards. Please follow the usage instructions provided on the packaging and in the product description."],
        ['q' => "Will I get an invoice for {$productName}?", 'a' => "Yes, a GST invoice for {$productName} is automatically emailed to you after your order is confirmed and is also available in your account's order history."],
        ['q' => "Can I gift {$productName} to someone?", 'a' => "Absolutely. You can have {$productName} shipped directly to any address in India by entering the recipient's details at checkout."],
        ['q' => "What if I receive a damaged {$productName}?", 'a' => "If your {$productName} arrives damaged, contact us within 48 hours of delivery with photos and we'll arrange a free replacement or refund immediately."],
        ['q' => "Do you offer bulk discounts on {$productName}?", 'a' => "Yes, we offer special pricing on bulk orders of {$productName}. Please contact our support team for a custom quote."],
    ];

    // Priority: 1) explicit $faqs prop, 2) product-specific FAQs from DB, 3) auto-generated pool
    $productFaqs = is_array($product?->faqs) ? $product->faqs : [];

    if ($faqs) {
        $items = $faqs;
    } elseif (!empty($productFaqs)) {
        // Use product-specific FAQs from DB, then pad with generic ones
        $items = $productFaqs;
        $remaining = 6 - count($items);
        if ($remaining > 0) {
            $seed = (int) ($product->id ?? crc32($productName));
            mt_srand($seed);
            $keys = array_keys($faqPool);
            shuffle($keys);
            mt_srand();
            $items = array_merge($items, array_map(fn($k) => $faqPool[$k], array_slice($keys, 0, $remaining)));
        }
    } else {
        // Deterministic per-product selection so each product shows a different mix
        $seed = $product?->id ? (int) $product->id : crc32($productName);
        mt_srand($seed);
        $keys = array_keys($faqPool);
        shuffle($keys);
        mt_srand();
        $picked = array_slice($keys, 0, 6);
        sort($picked);
        $items = array_map(fn($k) => $faqPool[$k], $picked);
    }
?>

<section <?php echo e($attributes->merge(['class' => 'py-10 bg-[#F7F8FA]'])); ?>>
    <div class="container mx-auto px-4">
        <h2 class="text-lg font-bold text-[#0F1111] mb-5 text-center">Frequently Asked Questions</h2>
        <div class="max-w-3xl mx-auto space-y-2" x-data="{ open: null }">
            <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $faq): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="bg-white border border-[#E3E6E6] rounded overflow-hidden">
                    <button type="button"
                            @click="open === <?php echo e($i); ?> ? open = null : open = <?php echo e($i); ?>"
                            class="w-full flex items-center justify-between px-4 py-3 text-left hover:bg-[#F7F8FA] transition-colors">
                        <span class="text-sm font-medium text-[#0F1111] pr-4"><?php echo e($faq['q']); ?></span>
                        <svg class="w-4 h-4 text-[#3a3a3a] shrink-0 transition-transform duration-200"
                             :class="open === <?php echo e($i); ?> && 'rotate-180'"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open === <?php echo e($i); ?>" x-collapse x-cloak>
                        <div class="px-4 pb-3 text-sm text-[#3a3a3a] leading-relaxed border-t border-[#E3E6E6]">
                            <p class="pt-3"><?php echo e($faq['a']); ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</section>


<?php
$faqSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => collect($items)->map(fn($faq) => [
        '@type' => 'Question',
        'name' => $faq['q'],
        'acceptedAnswer' => [
            '@type' => 'Answer',
            'text' => $faq['a'],
        ],
    ])->toArray(),
];
?>
<script type="application/ld+json">
<?php echo json_encode($faqSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>

</script>
<?php /**PATH D:\projects\grytlabs345\resources\views/components/faq-section.blade.php ENDPATH**/ ?>