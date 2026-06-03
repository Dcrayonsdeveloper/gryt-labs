@component('mail::message')
# Your Seller Account Has Been Suspended

Hi {{ $seller->user?->first_name ?? $seller->store_name }},

We're writing to let you know that your seller account **{{ $seller->store_name }}** on **{{ \App\Models\Setting::get('store_name', config('app.name')) }}** has been suspended.

## Reason

{{ $reason }}

## What this means

- Your product listings are no longer visible to customers
- You cannot accept new orders while suspended
- Any pending payouts will be held until the suspension is reviewed

## Next steps

If you'd like to discuss this decision or resolve the issue, please contact our seller support team. We'll review your case and respond as soon as possible.

@component('mail::button', ['url' => url('/contact')])
Contact Support
@endcomponent

Warm regards,
**{{ \App\Models\Setting::get('store_name', config('app.name')) }}**
@endcomponent
