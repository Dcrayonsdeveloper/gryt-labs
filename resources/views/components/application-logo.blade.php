<img src="{{ asset(\App\Models\Setting::get('store_logo', 'images/logo.png')) }}" alt="{{ config('app.name', 'Store') }}" {{ $attributes->merge(['class' => 'object-contain']) }}>
