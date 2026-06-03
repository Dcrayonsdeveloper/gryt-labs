{{--
    Shiprocket Checkout SDK — Drop-in component.

    Include once in your layout <body> (before </body>) when shiprocket_checkout_enabled is true.
    Handles:
      - SDK CSS (scroll-lock, overlay styles)
      - SDK JS loader (checkout-ui.shiprocket.com)
      - Scroll-position restoration on close
      - postMessage capture → sessionStorage['sr_checkout_customer']
      - window.__srBuyNow(evt, btn, productId) — single product Buy Now
      - window.__srCartCheckout(btn)          — full cart checkout

    Usage in layout:
        @if(\App\Models\Setting::get('shiprocket_checkout_enabled', false))
            <x-shiprocket-checkout-sdk />
        @endif

    The Buy Now button:
        <button @click="typeof window.__srBuyNow === 'function'
                ? __srBuyNow($event, $el, {{ $product->id }})
                : (window.location.href = '{{ route('checkout.index') }}')">
            Buy Now
        </button>

    The Cart Checkout button:
        <button onclick="window.__srCartCheckout ? __srCartCheckout(this) : window.location.href='/checkout'">
            Checkout
        </button>
--}}

{{-- ── Overlay / scroll-lock CSS ─────────────────────────────────────────── --}}
<style>
body.sr-checkout-open {
    overflow: hidden !important;
    position: fixed !important;
    width: 100% !important;
    top: 0 !important;
    left: 0 !important;
}
#fastrr-main-container,
#headless-container {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    width: 100vw; height: 100vh; height: 100dvh;
    z-index: 999999;
    overflow: hidden;
}
.headless-modal {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,.5);
    z-index: 999999;
    display: flex;
    justify-content: center;
    align-items: center;
    overflow: hidden;
}
.headless-modal-content {
    width: 420px; max-width: 100%;
    height: 90vh; max-height: 90vh;
    background: #fff;
    position: relative;
    overflow: hidden;
    animation: sr-fade-in .3s ease;
    box-shadow: 0 8px 32px rgba(0,0,0,.2);
    border-radius: 12px;
}
.headless-modal-body {
    width: calc(100% + 17px);
    height: 100%;
    overflow-y: auto;
    overflow-x: hidden;
}
#headless-iframe { width: 100%; height: 100%; border: none; display: block; }
.fastrr-pre-loader-container {
    position: fixed; top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(255,255,255,.95);
    z-index: 9999999;
    display: flex; align-items: center; justify-content: center; flex-direction: column;
}
.fastrr-pre-loader { display: flex; flex-direction: column; align-items: center; gap: 12px; }
.animation-container { position: relative; width: 60px; height: 60px; }
.animation-loader {
    width: 60px; height: 60px;
    border: 3px solid #e0e0e0;
    border-top: 3px solid #7b2ff7;
    border-radius: 50%;
    animation: sr-spin 1s linear infinite;
}
.animation-image {
    position: absolute; top: 50%; left: 50%;
    transform: translate(-50%,-50%);
    width: 30px; height: 30px; object-fit: contain;
}
.splash__text { font-size: 14px; color: #333; font-weight: 500; margin-top: 8px; }
.redirect-button-fastrr { font-size: 12px; color: #666; margin-top: 8px; cursor: pointer; text-decoration: underline; }
@keyframes sr-fade-in { from { opacity: 0; transform: scale(.95); } to { opacity: 1; transform: scale(1); } }
@keyframes sr-spin { to { transform: rotate(360deg); } }
@media (max-width: 480px) { .headless-modal-content { width: 100%; height: 100vh; max-height: 100vh; border-radius: 0; } }
</style>

{{-- ── Shiprocket SDK ──────────────────────────────────────────────────────── --}}
<script src="https://checkout-ui.shiprocket.com/assets/js/channels/custom.js"></script>

{{-- ── Scroll-position restoration + iframe payment permission patching ──── --}}
<script>
(function() {
    var _srScrollY = 0;
    new MutationObserver(function(mutations) {
        var container = document.getElementById('fastrr-main-container');
        if (container && !document.body.classList.contains('sr-checkout-open')) {
            _srScrollY = window.scrollY;
            document.body.classList.add('sr-checkout-open');
            document.body.style.top = '-' + _srScrollY + 'px';
        }
        if (!container && document.body.classList.contains('sr-checkout-open')) {
            document.body.classList.remove('sr-checkout-open');
            document.body.style.top = '';
            window.scrollTo(0, _srScrollY);
        }

        // Patch Shiprocket iframes for PhonePe/GPay UPI: allow payment + fix sandbox
        for (var i = 0; i < mutations.length; i++) {
            var added = mutations[i].addedNodes;
            for (var j = 0; j < added.length; j++) {
                if (added[j].nodeType !== 1) continue;
                var iframes = added[j].tagName === 'IFRAME' ? [added[j]] : (added[j].querySelectorAll ? added[j].querySelectorAll('iframe') : []);
                for (var k = 0; k < iframes.length; k++) {
                    var iframe = iframes[k];
                    // Ensure allow="payment" is present
                    var allow = iframe.getAttribute('allow') || '';
                    if (allow.indexOf('payment') === -1) {
                        iframe.setAttribute('allow', (allow ? allow + '; ' : '') + 'payment');
                    }
                    // If SDK added sandbox, ensure it allows popups + top navigation (UPI intents)
                    if (iframe.hasAttribute('sandbox')) {
                        var sb = iframe.getAttribute('sandbox');
                        var needs = ['allow-scripts', 'allow-same-origin', 'allow-forms',
                                     'allow-popups', 'allow-popups-to-escape-sandbox',
                                     'allow-top-navigation-by-user-activation'];
                        for (var n = 0; n < needs.length; n++) {
                            if (sb.indexOf(needs[n]) === -1) sb += ' ' + needs[n];
                        }
                        iframe.setAttribute('sandbox', sb.trim());
                    }
                }
            }
        }
    }).observe(document.body, { childList: true, subtree: true });
})();
</script>

{{-- ── Customer data capture from Shiprocket iframe (postMessage) ─────────── --}}
<script>
(function() {
    var SR_KEY = 'sr_checkout_customer';

    /**
     * Fields we capture from Shiprocket's postMessage events.
     * The SDK fires these during checkout as the customer fills in details.
     * We persist them in sessionStorage so the success page can use them
     * without another API call.
     */
    var CAPTURE_FIELDS = [
        'phone', 'email', 'customer_phone', 'customer_email', 'customer_name',
        'billing_phone', 'billing_email', 'billing_customer_name',
        'name', 'address', 'city', 'state', 'pincode',
        'billing_address', 'billing_city', 'billing_state', 'billing_pincode',
        'billing_address_2', 'billing_country'
    ];

    window.addEventListener('message', function(e) {
        try {
            var d = e.data;
            if (!d || typeof d !== 'object') return;
            var stored = JSON.parse(sessionStorage.getItem(SR_KEY) || '{}');
            var changed = false;

            // Format 1: { actions: [{ action: "ucNotifyHandler", data: { phone: "..." } }] }
            if (Array.isArray(d.actions)) {
                d.actions.forEach(function(a) {
                    if (!a.data || typeof a.data !== 'object') return;
                    CAPTURE_FIELDS.forEach(function(f) {
                        if (a.data[f] && typeof a.data[f] === 'string' && !stored[f]) {
                            stored[f] = a.data[f]; changed = true;
                        }
                    });
                });
            }

            // Format 2: flat object { phone: "...", email: "..." }
            CAPTURE_FIELDS.forEach(function(f) {
                if (d[f] && typeof d[f] === 'string' && !stored[f]) {
                    stored[f] = d[f]; changed = true;
                }
            });

            if (changed) sessionStorage.setItem(SR_KEY, JSON.stringify(stored));
        } catch(err) {}
    });
})();
</script>

{{-- ── Shareable discount link support (/discount/{code}) ──────────────────── --}}
@if(session('discount_code'))
<script>
(function() {
    var code = @json(session('discount_code'));
    localStorage.setItem('fastrrCouponCode', code);
    localStorage.setItem('fastrrCouponCodeFromLink', code);
    var toast = document.createElement('div');
    toast.innerHTML = 'Discount code <strong>' + code.toUpperCase() + '</strong> applied!';
    toast.style.cssText = 'position:fixed;top:16px;left:50%;transform:translateX(-50%);background:#059669;color:#fff;padding:12px 24px;border-radius:8px;z-index:999999;font-size:14px;box-shadow:0 4px 12px rgba(0,0,0,.15);';
    document.body.appendChild(toast);
    setTimeout(function() { toast.style.opacity = '0'; toast.style.transition = 'opacity .5s'; }, 4000);
    setTimeout(function() { toast.remove(); }, 4500);
})();
</script>
@endif

{{-- ── window.__srBuyNow — single-product Buy Now ─────────────────────────── --}}
<script>
/**
 * Open Shiprocket Checkout for a single product.
 *
 * Usage on Buy Now buttons:
 *   @click="typeof window.__srBuyNow === 'function'
 *       ? __srBuyNow($event, $el, [product.id], [quantity])
 *       : (window.location.href = '/checkout')"
 *
 * Quantity resolution order:
 *   1. explicitQty arg (preferred — pass the page's actual qty selector value)
 *   2. Alpine.store('pdpPack').currentQty (pack-mode products)
 *   3. 1 (fallback)
 */
window.__srBuyNow = function(evt, btn, productId, explicitQty) {
    if (btn.disabled) return;
    btn.disabled = true;
    var span = btn.querySelector('span');
    if (span) span.textContent = 'Please wait…';

    sessionStorage.removeItem('sr_checkout_customer');

    var qty = 1;
    var n = Number(explicitQty);
    if (Number.isFinite(n) && n > 0) {
        qty = Math.max(1, Math.floor(n));
    } else {
        try {
            if (window.Alpine && Alpine.store('pdpPack')) qty = Alpine.store('pdpPack').currentQty || 1;
        } catch(e) {}
    }

    fetch('/api/shiprocket-checkout-token', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ product_id: productId, quantity: qty })
    })
    .then(function(r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
    .then(function(data) {
        if (data.success && data.token) {
            if (typeof window.shiprocketCheckoutCustomHandler === 'function') {
                var old = document.getElementById('fastrr-main-container');
                if (old) old.remove();
                window.shiprocketCheckoutCustomHandler({
                    token: data.token,
                    fallbackUrl: '/checkout',
                    onInitiationFailure: function(err) {
                        console.error('Shiprocket checkout failed:', err);
                        window.location.href = '/checkout';
                    }
                });
            } else {
                console.error('Shiprocket SDK not loaded — redirecting to regular checkout');
                window.location.href = '/checkout';
            }
        } else {
            console.error('Token error:', data.message);
            window.location.href = '/checkout';
        }
    })
    .catch(function() { window.location.href = '/checkout'; })
    .finally(function() {
        btn.disabled = false;
        if (span) span.textContent = 'Buy Now';
    });
};
</script>

{{-- ── window.__srCartCheckout — full cart checkout ───────────────────────── --}}
<script>
/**
 * Open Shiprocket Checkout with all items currently in the cart.
 *
 * Usage on cart / drawer checkout buttons:
 *   <button onclick="window.__srCartCheckout ? __srCartCheckout(this) : window.location.href='/checkout'">
 *       Checkout
 *   </button>
 */
window.__srCartCheckout = function(btn) {
    if (btn && btn.disabled) return;
    if (btn) btn.disabled = true;

    sessionStorage.removeItem('sr_checkout_customer');

    fetch('/api/shiprocket-checkout-token', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ source: 'cart' })
    })
    .then(function(r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
    .then(function(data) {
        if (data.success && data.token) {
            if (typeof window.shiprocketCheckoutCustomHandler === 'function') {
                var old = document.getElementById('fastrr-main-container');
                if (old) old.remove();
                window.shiprocketCheckoutCustomHandler({
                    token: data.token,
                    fallbackUrl: '/checkout',
                    onInitiationFailure: function(err) {
                        console.error('Shiprocket cart checkout failed:', err);
                        window.location.href = '/checkout';
                    }
                });
            } else {
                window.location.href = '/checkout';
            }
        } else {
            window.location.href = '/checkout';
        }
    })
    .catch(function() { window.location.href = '/checkout'; })
    .finally(function() { if (btn) btn.disabled = false; });
};
</script>
