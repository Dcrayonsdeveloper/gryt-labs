<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Affiliate Program - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:300,400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .form-input { display: block; width: 100%; padding: 10px 14px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 10px; outline: none; transition: border-color 0.2s; background: #fff; }
        .form-input:focus { border-color: var(--color-primary-600); box-shadow: 0 0 0 3px rgba(32,82,88,0.08); }
        .form-input.error { border-color: #ef4444; }
    </style>
</head>
<body style="background:#f8f6f3; margin:0;">

    @include('partials.header')

    <div style="display:none;">
        </div>
    </header>

    {{-- Hero --}}
    <section style="background: linear-gradient(135deg, var(--color-primary-600) 0%, #1a3f44 100%); padding:48px 20px; text-align:center; position:relative; overflow:hidden;">
        <div style="position:absolute; top:-40px; right:-40px; width:200px; height:200px; background:rgba(255,255,255,0.03); border-radius:50%;"></div>
        <div style="position:absolute; bottom:-60px; left:-30px; width:160px; height:160px; background:rgba(255,255,255,0.03); border-radius:50%;"></div>
        <div style="max-width:600px; margin:0 auto; position:relative; z-index:1;">
            <p style="color:var(--color-accent-500); font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:2px; margin:0 0 8px;">Partner with us</p>
            <h1 style="color:#fff; font-size:32px; font-weight:700; margin:0 0 12px; line-height:1.2;">Earn Money with {{ \App\Models\Setting::get('store_name', config('app.name')) }}</h1>
            <p style="color:rgba(255,255,255,0.7); font-size:15px; margin:0;">Get <span style="color:var(--color-accent-500); font-weight:700;">{{ config('affiliate.default_commission_rate', 5) }}% commission</span> on every sale you refer. No limits.</p>
        </div>
    </section>

    {{-- How it Works --}}
    <section style="max-width:900px; margin:-30px auto 0; padding:0 20px; position:relative; z-index:2;">
        <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:16px;">
            @foreach([
                ['icon' => '📝', 'title' => 'Sign Up Free', 'desc' => 'Fill the form below. Quick approval.'],
                ['icon' => '🔗', 'title' => 'Share Your Link', 'desc' => 'Post your unique referral link on social media.'],
                ['icon' => '💰', 'title' => 'Earn & Withdraw', 'desc' => 'Get ' . config('affiliate.default_commission_rate', 5) . '% per sale. Withdraw to bank or UPI anytime.'],
            ] as $step)
                <div style="background:#fff; border-radius:16px; padding:24px 20px; text-align:center; box-shadow:0 2px 12px rgba(0,0,0,0.06);">
                    <div style="font-size:28px; margin-bottom:10px;">{{ $step['icon'] }}</div>
                    <h3 style="font-size:14px; font-weight:600; color:#0F1111; margin:0 0 6px;">{{ $step['title'] }}</h3>
                    <p style="font-size:12px; color:#565959; margin:0; line-height:1.5;">{{ $step['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Sign Up Form --}}
    <section style="max-width:520px; margin:40px auto; padding:0 20px;">
        <div style="background:#fff; border-radius:20px; padding:32px 28px; box-shadow:0 4px 20px rgba(0,0,0,0.06);">
            <h2 style="font-size:20px; font-weight:700; color:#0F1111; margin:0 0 4px;">Create your account</h2>
            <p style="font-size:13px; color:#565959; margin:0 0 24px;">Free to join. Start earning today.</p>

            @if(session('success'))
                <div style="margin-bottom:20px; padding:12px 16px; background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; border-radius:10px; font-size:13px;">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('affiliate.register') }}">
                @csrf

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px;">
                    <div>
                        <label style="display:block; font-size:12px; font-weight:500; color:#374151; margin-bottom:4px;">First name</label>
                        <input type="text" name="first_name" value="{{ old('first_name') }}" required class="form-input @error('first_name') error @enderror">
                        @error('first_name')<p style="font-size:11px; color:#ef4444; margin:4px 0 0;">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label style="display:block; font-size:12px; font-weight:500; color:#374151; margin-bottom:4px;">Last name</label>
                        <input type="text" name="last_name" value="{{ old('last_name') }}" required class="form-input @error('last_name') error @enderror">
                        @error('last_name')<p style="font-size:11px; color:#ef4444; margin:4px 0 0;">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div style="margin-bottom:14px;">
                    <label style="display:block; font-size:12px; font-weight:500; color:#374151; margin-bottom:4px;">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required class="form-input @error('email') error @enderror">
                    @error('email')<p style="font-size:11px; color:#ef4444; margin:4px 0 0;">{{ $message }}</p>@enderror
                </div>

                <div style="margin-bottom:14px;">
                    <label style="display:block; font-size:12px; font-weight:500; color:#374151; margin-bottom:4px;">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" class="form-input @error('phone') error @enderror" placeholder="+91 XXXXX XXXXX">
                    @error('phone')<p style="font-size:11px; color:#ef4444; margin:4px 0 0;">{{ $message }}</p>@enderror
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px;">
                    <div>
                        <label style="display:block; font-size:12px; font-weight:500; color:#374151; margin-bottom:4px;">Password</label>
                        <input type="password" name="password" required class="form-input @error('password') error @enderror" placeholder="Min 8 chars">
                        @error('password')<p style="font-size:11px; color:#ef4444; margin:4px 0 0;">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label style="display:block; font-size:12px; font-weight:500; color:#374151; margin-bottom:4px;">Confirm password</label>
                        <input type="password" name="password_confirmation" required class="form-input">
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px;">
                    <div>
                        <label style="display:block; font-size:12px; font-weight:500; color:#374151; margin-bottom:4px;">Platform</label>
                        <select name="channel" required class="form-input @error('channel') error @enderror">
                            <option value="">Select</option>
                            <option value="youtube" {{ old('channel') === 'youtube' ? 'selected' : '' }}>YouTube</option>
                            <option value="instagram" {{ old('channel') === 'instagram' ? 'selected' : '' }}>Instagram</option>
                            <option value="tiktok" {{ old('channel') === 'tiktok' ? 'selected' : '' }}>TikTok</option>
                            <option value="blog" {{ old('channel') === 'blog' ? 'selected' : '' }}>Blog / Website</option>
                            <option value="other" {{ old('channel') === 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('channel')<p style="font-size:11px; color:#ef4444; margin:4px 0 0;">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label style="display:block; font-size:12px; font-weight:500; color:#374151; margin-bottom:4px;">Profile URL <span style="color:#9ca3af;">(optional)</span></label>
                        <input type="url" name="channel_url" value="{{ old('channel_url') }}" class="form-input" placeholder="https://...">
                    </div>
                </div>

                <div style="margin-bottom:18px;">
                    <label style="display:block; font-size:12px; font-weight:500; color:#374151; margin-bottom:4px;">About you <span style="color:#9ca3af;">(optional)</span></label>
                    <textarea name="bio" rows="2" class="form-input" placeholder="Brief about your content & audience...">{{ old('bio') }}</textarea>
                </div>

                <label style="display:flex; align-items:flex-start; gap:8px; margin-bottom:20px; cursor:pointer;">
                    <input type="checkbox" name="terms" required style="margin-top:2px; accent-color:var(--color-primary-600);">
                    <span style="font-size:11px; color:#565959; line-height:1.5;">
                        By signing up, I agree to the <a href="{{ url('/terms') }}" target="_blank" style="color:var(--color-primary-600); text-decoration:underline;">Terms</a>
                        and <a href="{{ url('/privacy') }}" target="_blank" style="color:var(--color-primary-600); text-decoration:underline;">Privacy Policy</a>.
                    </span>
                </label>
                @error('terms')<p style="font-size:11px; color:#ef4444; margin:-14px 0 14px;">{{ $message }}</p>@enderror

                <button type="submit" style="width:100%; padding:12px; background:var(--color-accent-500); color:#fff; font-size:14px; font-weight:600; border:none; border-radius:50px; cursor:pointer; transition:all 0.2s;">
                    Create Affiliate Account
                </button>
            </form>

            <p style="text-align:center; font-size:12px; color:#565959; margin:16px 0 0;">
                Already have an account? <a href="{{ route('affiliate.login') }}" style="color:var(--color-primary-600); font-weight:600; text-decoration:none;">Sign in</a>
            </p>
        </div>
    </section>

    @include('partials.footer')
</body>
</html>
