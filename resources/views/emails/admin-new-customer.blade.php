@component('mail::message')
# New Customer Registered

A new customer has created an account on {{ \App\Models\Setting::get('store_name', config('app.name')) }}.

**Name:** {{ $user->full_name }}
**Email:** {{ $user->email }}
**Phone:** {{ $user->phone ?? 'Not provided' }}
**Registered:** {{ $user->created_at->format('d M Y, h:i A') }}

@component('mail::button', ['url' => url('/admin/customers/' . $user->id)])
View Customer
@endcomponent

Thanks,<br>
{{ \App\Models\Setting::get('store_name', config('app.name')) }}
@endcomponent
