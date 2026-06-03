@component('mail::message')
# Update on Your Seller Application

Hi {{ $seller->user?->first_name ?? $seller->store_name }},

Thank you for applying to sell on **{{ \App\Models\Setting::get('store_name', config('app.name')) }}**.

After reviewing your application for **{{ $seller->store_name }}**, we're unable to approve it at this time.

## Reason

{{ $reason }}

## What you can do next

If you believe this was a mistake or you'd like to reapply with updated information, please reach out to our support team. We're happy to walk you through the requirements.

@component('mail::button', ['url' => url('/contact')])
Contact Support
@endcomponent

We appreciate your interest in selling with us.

Warm regards,
**{{ \App\Models\Setting::get('store_name', config('app.name')) }}**
@endcomponent
