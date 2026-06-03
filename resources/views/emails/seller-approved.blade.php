@component('mail::message')
# Welcome aboard!

Hi {{ $seller->user?->first_name ?? $seller->store_name }},

Congratulations — your seller account **{{ $seller->store_name }}** has been approved on **{{ \App\Models\Setting::get('store_name', config('app.name')) }}**.

You can now start listing products, managing orders, and accepting payments.

@component('mail::button', ['url' => url('/seller/dashboard')])
Go to Seller Dashboard
@endcomponent

## What's next?

- Add your first product from the seller dashboard
- Set up your payout method under Settings → Payouts
- Upload your store logo and banner to make your storefront shine

If you need help getting started, our support team is here for you.

Happy selling!

Warm regards,
**{{ \App\Models\Setting::get('store_name', config('app.name')) }}**
@endcomponent
