<x-layouts.admin>
    <x-slot name="title">Site Settings</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-neutral-900">Site Settings</h1>
                <p class="text-sm text-neutral-600 mt-1">Manage logo, brand identity, and social links</p>
            </div>
            <a href="{{ route('admin.homepage.index') }}" class="btn btn-secondary">Back to Homepage</a>
        </div>
    </x-slot>

    <form action="{{ route('admin.homepage.site-settings.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Brand Identity -->
            <div class="card p-6">
                <h2 class="text-lg font-semibold text-neutral-900 mb-4">Brand Identity</h2>

                <div class="space-y-4">
                    <div>
                        <label class="form-label">Site Logo</label>
                        @php
                            $__logoPath = $settings['site_logo'] ?: \App\Models\Setting::get('store_logo');
                            $currentLogo = $__logoPath ? asset($__logoPath) : null;
                        @endphp
                        @if($currentLogo)
                            <div class="mb-2 p-3 bg-neutral-50 rounded-lg border border-neutral-200 inline-block">
                                <img src="{{ $currentLogo }}" alt="Current Logo" class="h-16 object-contain">
                            </div>
                        @else
                            <div class="mb-2 p-4 bg-neutral-50 rounded-lg border border-dashed border-neutral-300 text-sm text-neutral-400">
                                No logo uploaded yet
                            </div>
                        @endif
                        <input type="file" name="site_logo" accept="image/*" class="form-input">
                        <p class="form-help">Recommended: PNG with transparent background, 200x60px</p>
                    </div>

                    <div>
                        <label class="form-label">Site Name</label>
                        <input type="text" name="site_name" value="{{ $settings['site_name'] }}" class="form-input">
                    </div>

                    <div>
                        <label class="form-label">Tagline</label>
                        <input type="text" name="site_tagline" value="{{ $settings['site_tagline'] }}" class="form-input">
                    </div>

                    <div>
                        <label class="form-label">Site Description</label>
                        <textarea name="site_description" rows="3" class="form-input">{{ $settings['site_description'] }}</textarea>
                    </div>

                    <div>
                        <label class="form-label">Announcement Bar Text</label>
                        <input type="text" name="announcement_text" value="{{ $settings['announcement_text'] }}" class="form-input" placeholder="e.g. Free Shipping on Orders Over ₹500!">
                        <p class="form-help">Displayed in the teal bar at the top of every page. Leave empty to hide.</p>
                    </div>

                    <div>
                        <label class="form-label">Marquee Ticker Text</label>
                        <input type="text" name="marquee_text" value="{{ $settings['marquee_text'] ?? '' }}" class="form-input" placeholder="e.g. FAST DISPATCH IN 24-48 HOURS|EXPRESS DELIVERY AVAILABLE|FREE SHIPPING">
                        <p class="form-help">Scrolling ticker below header. Separate items with <code>|</code> (pipe). Leave empty to hide.</p>
                    </div>

                    <div>
                        <label class="form-label">Product Page Text Slider</label>
                        <input type="text" name="pdp_text_slider" value="{{ $settings['pdp_text_slider'] ?? '' }}" class="form-input" placeholder="e.g. Free Shipping Above ₹499|100% Authentic|Easy Returns|COD Available">
                        <p class="form-help">Horizontal sliding strip on product detail page (above price). Separate items with <code>|</code> (pipe). Leave empty to hide.</p>
                    </div>
                </div>
            </div>

            <!-- Social Media -->
            <div class="card p-6">
                <h2 class="text-lg font-semibold text-neutral-900 mb-4">Social Media Links</h2>

                <div class="space-y-4">
                    <div>
                        <label class="form-label">Facebook</label>
                        <input type="url" name="social_facebook" value="{{ $settings['social_facebook'] }}" class="form-input" placeholder="https://facebook.com/...">
                    </div>
                    <div>
                        <label class="form-label">Instagram</label>
                        <input type="url" name="social_instagram" value="{{ $settings['social_instagram'] }}" class="form-input" placeholder="https://instagram.com/...">
                    </div>
                    <div>
                        <label class="form-label">Twitter / X</label>
                        <input type="url" name="social_twitter" value="{{ $settings['social_twitter'] }}" class="form-input" placeholder="https://x.com/...">
                    </div>
                    <div>
                        <label class="form-label">YouTube</label>
                        <input type="url" name="social_youtube" value="{{ $settings['social_youtube'] }}" class="form-input" placeholder="https://youtube.com/...">
                    </div>
                    <div>
                        <label class="form-label">TikTok</label>
                        <input type="url" name="social_tiktok" value="{{ $settings['social_tiktok'] }}" class="form-input" placeholder="https://tiktok.com/...">
                    </div>
                    <div>
                        <label class="form-label">Pinterest</label>
                        <input type="url" name="social_pinterest" value="{{ $settings['social_pinterest'] }}" class="form-input" placeholder="https://pinterest.com/...">
                    </div>
                </div>
            </div>

            <!-- Contact Info -->
            <div class="card p-6">
                <h2 class="text-lg font-semibold text-neutral-900 mb-4">Contact Information</h2>

                <div class="space-y-4">
                    <div>
                        <label class="form-label">Email</label>
                        <input type="email" name="contact_email" value="{{ $settings['contact_email'] }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Phone</label>
                        <input type="text" name="contact_phone" value="{{ $settings['contact_phone'] }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Address</label>
                        <textarea name="contact_address" rows="3" class="form-input">{{ $settings['contact_address'] }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="card p-6">
                <h2 class="text-lg font-semibold text-neutral-900 mb-4">Footer Content</h2>

                <div class="space-y-4">
                    <div>
                        <label class="form-label">About Text</label>
                        <textarea name="footer_about" rows="4" class="form-input">{{ $settings['footer_about'] }}</textarea>
                    </div>
                    <div>
                        <label class="form-label">Copyright Text</label>
                        <input type="text" name="footer_copyright" value="{{ $settings['footer_copyright'] }}" class="form-input">
                    </div>
                </div>
            </div>
        </div>

        <!-- About Us Page -->
        <div class="card p-6 lg:col-span-2 mt-6">
            <h2 class="text-lg font-semibold text-neutral-900 mb-1">About Us Page</h2>
            <p class="text-sm text-neutral-500 mb-4">Content shown on the <a href="{{ url('/pages/about-us') }}" target="_blank" class="text-blue-600 hover:underline">About Us</a> page. The hero headline uses the <strong>Tagline</strong> above.</p>

            <div class="space-y-4">
                <div>
                    <label class="form-label">Intro / Story (hero summary)</label>
                    <textarea name="about_story" rows="3" class="form-input" placeholder="Short summary shown in the page hero (first ~250 characters).">{{ $settings['about_story'] }}</textarea>
                    <p class="text-xs text-neutral-400 mt-1">Plain text. Shown at the top of the About page.</p>
                </div>
                <div>
                    <label class="form-label">Full Content (HTML)</label>
                    <textarea name="about_html_content" rows="20" class="form-input font-mono text-xs leading-relaxed">{{ $settings['about_html_content'] }}</textarea>
                    <p class="text-xs text-neutral-400 mt-1">Supports HTML — use &lt;h2&gt; for section titles, &lt;h3&gt; for sub-titles, &lt;p&gt; for paragraphs, and &lt;ul&gt;&lt;li&gt; for bullet lists. Leave blank to show the default values grid.</p>
                </div>
            </div>
        </div>

        <!-- Homepage Appearance -->
        <div class="card p-6 lg:col-span-2 mt-6">
            <h2 class="text-lg font-semibold text-neutral-900 mb-1">Homepage Appearance</h2>
            <p class="text-sm text-neutral-500 mb-4">Colors, newsletter text, flash sale labels, and other homepage customizations</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="form-label">Alternate Section Background Color</label>
                    <div class="flex items-center gap-2">
                        <input type="color" name="homepage_alt_bg_color" value="{{ $settings['homepage_alt_bg_color'] }}" class="w-10 h-10 rounded border border-neutral-200 cursor-pointer">
                        <input type="text" value="{{ $settings['homepage_alt_bg_color'] }}" class="form-input flex-1" readonly>
                    </div>
                    <p class="form-help">Background for bestsellers, deals, testimonials sections</p>
                </div>
                <div>
                    <label class="form-label">Newsletter Heading</label>
                    <input type="text" name="newsletter_heading" value="{{ $settings['newsletter_heading'] }}" class="form-input" placeholder="Get 20% Off Your First Order!">
                </div>
                <div>
                    <label class="form-label">Newsletter Subtitle</label>
                    <input type="text" name="newsletter_subtitle" value="{{ $settings['newsletter_subtitle'] }}" class="form-input" placeholder="Sign up for exclusive deals...">
                </div>
                <div>
                    <label class="form-label">Newsletter Badge Text</label>
                    <input type="text" name="newsletter_badge_text" value="{{ $settings['newsletter_badge_text'] }}" class="form-input" placeholder="Exclusive Offer">
                </div>
                <div>
                    <label class="form-label">Flash Sale Label</label>
                    <input type="text" name="flash_sale_label" value="{{ $settings['flash_sale_label'] }}" class="form-input" placeholder="Limited Time Offer">
                </div>
                <div>
                    <label class="form-label">Flash Sale Button Text</label>
                    <input type="text" name="flash_sale_button_text" value="{{ $settings['flash_sale_button_text'] }}" class="form-input" placeholder="Shop the Sale Now">
                </div>
            </div>
        </div>

        <!-- Consultation Settings -->
        <div class="card p-6 lg:col-span-2 mt-6"
             x-data="{
                types: @js(json_decode($settings['consultation_types'] ?: '[]', true) ?: []),
                slots: @js(json_decode($settings['consultation_time_slots'] ?: '[]', true) ?: []),
                newType: '', newSlot: ''
             }">
            <h2 class="text-lg font-semibold text-neutral-900 mb-1">Consultation Settings</h2>
            <p class="text-sm text-neutral-500 mb-4">Configure the consultation booking form (requires consultation_enabled = 1)</p>

            <input type="hidden" name="consultation_types" :value="JSON.stringify(types)">
            <input type="hidden" name="consultation_time_slots" :value="JSON.stringify(slots)">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label class="form-label mb-2">Consultation Types</label>
                    <div class="space-y-2 mb-3">
                        <template x-for="(t, i) in types" :key="i">
                            <div class="flex items-center gap-2">
                                <input type="text" x-model="types[i]" class="form-input flex-1" placeholder="e.g. Energy & Stamina">
                                <button type="button" @click="types.splice(i, 1)" class="p-1.5 text-neutral-400 hover:text-red-500 transition-colors" title="Remove">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </template>
                    </div>
                    <div class="flex gap-2">
                        <input type="text" x-model="newType" @keydown.enter.prevent="if(newType.trim()){types.push(newType.trim());newType=''}" class="form-input flex-1 text-sm" placeholder="Add new type...">
                        <button type="button" @click="if(newType.trim()){types.push(newType.trim());newType=''}" class="btn btn-secondary text-xs px-3">Add</button>
                    </div>
                </div>
                <div>
                    <label class="form-label mb-2">Time Slots</label>
                    <div class="space-y-2 mb-3">
                        <template x-for="(s, i) in slots" :key="i">
                            <div class="flex items-center gap-2">
                                <input type="text" x-model="slots[i]" class="form-input flex-1" placeholder="e.g. 10:00 AM - 11:00 AM">
                                <button type="button" @click="slots.splice(i, 1)" class="p-1.5 text-neutral-400 hover:text-red-500 transition-colors" title="Remove">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </template>
                    </div>
                    <div class="flex gap-2">
                        <input type="text" x-model="newSlot" @keydown.enter.prevent="if(newSlot.trim()){slots.push(newSlot.trim());newSlot=''}" class="form-input flex-1 text-sm" placeholder="Add new time slot...">
                        <button type="button" @click="if(newSlot.trim()){slots.push(newSlot.trim());newSlot=''}" class="btn btn-secondary text-xs px-3">Add</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Testimonial Videos -->
        <div class="card p-6 lg:col-span-2 mt-6"
             x-data="{
                videos: @js(json_decode($settings['testimonial_videos'] ?: '[]', true) ?: []),
                uploading: false,
                uploadProgress: 0
             }">
            <h2 class="text-lg font-semibold text-neutral-900 mb-1">Testimonial Videos</h2>
            <p class="text-sm text-neutral-500 mb-4">Upload customer testimonial videos or paste a URL.</p>

            <input type="hidden" name="testimonial_videos" :value="JSON.stringify(videos)">

            <div class="space-y-3 mb-4">
                <template x-for="(vid, i) in videos" :key="i">
                    <div class="flex items-center gap-3 p-3 bg-neutral-50 rounded-lg border border-neutral-200">
                        <div class="w-10 h-10 bg-neutral-900 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-neutral-700 truncate" x-text="videos[i]"></p>
                        </div>
                        <span class="text-xs text-neutral-400 shrink-0" x-text="'#' + (i + 1)"></span>
                        <button type="button" @click="videos.splice(i, 1)" class="p-1.5 text-neutral-400 hover:text-red-500 transition-colors shrink-0" title="Remove video">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </template>

                <template x-if="videos.length === 0">
                    <div class="text-center py-8 text-neutral-400 text-sm border border-dashed border-neutral-300 rounded-lg">
                        <svg class="w-10 h-10 mx-auto mb-2 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        No videos added yet
                    </div>
                </template>
            </div>

            {{-- Upload & Add buttons --}}
            <div class="flex flex-col sm:flex-row gap-2">
                <label class="btn btn-primary text-sm px-4 cursor-pointer inline-flex items-center gap-1.5" :class="uploading ? 'opacity-50 pointer-events-none' : ''">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    <span x-text="uploading ? 'Uploading... ' + uploadProgress + '%' : 'Upload Video'"></span>
                    <input type="file" accept="video/*" class="hidden" @change="
                        if (!$el.files[0]) return;
                        var file = $el.files[0];
                        if (file.size > 50 * 1024 * 1024) { toastr.error('Video must be under 50MB'); $el.value=''; return; }
                        uploading = true; uploadProgress = 0;
                        var fd = new FormData();
                        fd.append('video', file);
                        fd.append('_token', '{{ csrf_token() }}');
                        var xhr = new XMLHttpRequest();
                        xhr.upload.onprogress = function(e) { if(e.lengthComputable) uploadProgress = Math.round(e.loaded/e.total*100); };
                        xhr.onload = function() {
                            uploading = false; $el.value='';
                            if (xhr.status === 200) {
                                var data = JSON.parse(xhr.responseText);
                                videos.push(data.path);
                                toastr.success('Video uploaded');
                            } else { toastr.error('Upload failed'); }
                        };
                        xhr.onerror = function() { uploading = false; $el.value=''; toastr.error('Upload failed'); };
                        xhr.open('POST', '{{ route('admin.homepage.upload-video') }}');
                        xhr.send(fd);
                    ">
                </label>
                <span class="text-neutral-400 text-sm self-center hidden sm:inline">or</span>
                <div class="flex gap-2 flex-1" x-data="{ url: '' }">
                    <input type="text" x-model="url" @keydown.enter.prevent="if(url.trim()){videos.push(url.trim());url=''}" class="form-input flex-1 text-sm" placeholder="Paste video URL...">
                    <button type="button" @click="if(url.trim()){videos.push(url.trim());url=''}" class="btn btn-secondary text-sm px-3">Add</button>
                </div>
            </div>
            <p class="text-xs text-neutral-400 mt-2">Supported: MP4, MOV, WebM. Max 50MB per video.</p>
        </div>

        <!-- Amazon Store -->
        <div class="card p-6 lg:col-span-2 mt-6">
            <h2 class="text-lg font-semibold text-neutral-900 mb-1">Amazon Store Banner</h2>
            <p class="text-sm text-neutral-500 mb-4">Shows a banner linking to your Amazon store. Leave URL empty to hide.</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="form-label">Amazon Store URL</label>
                    <input type="url" name="amazon_store_url" value="{{ $settings['amazon_store_url'] }}" class="form-input" placeholder="https://amazon.in/stores/...">
                </div>
                <div>
                    <label class="form-label">Banner Title</label>
                    <input type="text" name="amazon_banner_title" value="{{ $settings['amazon_banner_title'] }}" class="form-input" placeholder="Also Available on Amazon">
                </div>
                <div>
                    <label class="form-label">Banner Subtitle</label>
                    <input type="text" name="amazon_banner_subtitle" value="{{ $settings['amazon_banner_subtitle'] }}" class="form-input" placeholder="Shop with Prime delivery">
                </div>
                <div>
                    <label class="form-label">Button Text</label>
                    <input type="text" name="amazon_banner_button_text" value="{{ $settings['amazon_banner_button_text'] }}" class="form-input" placeholder="Shop on Amazon">
                </div>
            </div>
        </div>

        <!-- Store Localization -->
        <div class="card p-6 lg:col-span-2 mt-6">
            <h2 class="text-lg font-semibold text-neutral-900 mb-1">Store Localization</h2>
            <p class="text-sm text-neutral-500 mb-4">Used in SEO structured data (JSON-LD)</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="form-label">Country Name</label>
                    <input type="text" name="store_country" value="{{ $settings['store_country'] }}" class="form-input" placeholder="India">
                </div>
                <div>
                    <label class="form-label">Country Code</label>
                    <input type="text" name="store_country_code" value="{{ $settings['store_country_code'] }}" class="form-input" placeholder="IN" maxlength="2">
                </div>
                <div>
                    <label class="form-label">Locale</label>
                    <input type="text" name="store_locale" value="{{ $settings['store_locale'] }}" class="form-input" placeholder="en-IN">
                </div>
                <div>
                    <label class="form-label">Languages</label>
                    <input type="text" name="store_languages" value="{{ $settings['store_languages'] }}" class="form-input" placeholder="English,Hindi">
                    <p class="form-help">Comma-separated</p>
                </div>
            </div>
        </div>

        <!-- Homepage Display Settings -->
        <div class="card p-6 lg:col-span-2 mt-6">
            <h2 class="text-lg font-semibold text-neutral-900 mb-1">Homepage Display Settings</h2>
            <p class="text-sm text-neutral-500 mb-4">Control how many items appear in each homepage section</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="form-label">Featured Products Count</label>
                    <input type="number" name="homepage_featured_count" value="{{ $settings['homepage_featured_count'] }}" class="form-input" min="1" max="50">
                    <p class="form-help">Number of featured products to display</p>
                </div>
                <div>
                    <label class="form-label">New Arrivals Count</label>
                    <input type="number" name="homepage_new_arrivals_count" value="{{ $settings['homepage_new_arrivals_count'] }}" class="form-input" min="1" max="50">
                    <p class="form-help">Number of new arrivals to display</p>
                </div>
                <div>
                    <label class="form-label">New Arrivals Days</label>
                    <input type="number" name="homepage_new_arrivals_days" value="{{ $settings['homepage_new_arrivals_days'] }}" class="form-input" min="1" max="365">
                    <p class="form-help">Products added within this many days are considered new</p>
                </div>
                <div>
                    <label class="form-label">Bestsellers Count</label>
                    <input type="number" name="homepage_bestsellers_count" value="{{ $settings['homepage_bestsellers_count'] }}" class="form-input" min="1" max="50">
                    <p class="form-help">Number of bestselling products to display</p>
                </div>
                <div>
                    <label class="form-label">Deals Count</label>
                    <input type="number" name="homepage_deals_count" value="{{ $settings['homepage_deals_count'] }}" class="form-input" min="1" max="50">
                    <p class="form-help">Number of deal products to display</p>
                </div>
                <div>
                    <label class="form-label">Testimonials Count</label>
                    <input type="number" name="homepage_testimonials_count" value="{{ $settings['homepage_testimonials_count'] }}" class="form-input" min="1" max="50">
                    <p class="form-help">Number of testimonials to display</p>
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-end">
            <button type="submit" class="btn btn-primary btn-lg">Save Settings</button>
        </div>
    </form>
</x-layouts.admin>
