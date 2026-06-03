<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirecting to Payment...</title>
    <script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
    <style>
        body { font-family: -apple-system, system-ui, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #f9fafb; }
        .loader { text-align: center; }
        .spinner { width: 40px; height: 40px; border: 4px solid #e5e7eb; border-top-color: #10b981; border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 16px; }
        @keyframes spin { to { transform: rotate(360deg); } }
        p { color: #374151; font-size: 16px; }
        .error { color: #dc2626; display: none; margin-top: 12px; font-size: 14px; }
        a { color: #2563eb; }
    </style>
</head>
<body>
    <div class="loader">
        <div class="spinner"></div>
        <p>Redirecting to secure payment...</p>
        <p class="error" id="error-msg">
            Payment gateway could not load. <a href="{{ route('checkout.index') }}">Go back to checkout</a> and try again.
        </p>
    </div>
    <script>
        (function() {
            var sessionId = @json($sessionId);
            var mode = @json($mode);

            function startCheckout() {
                if (typeof window.Cashfree !== 'function') {
                    document.querySelector('.spinner').style.display = 'none';
                    document.getElementById('error-msg').style.display = 'block';
                    return;
                }
                var cashfree = Cashfree({ mode: mode === 'sandbox' ? 'sandbox' : 'production' });
                cashfree.checkout({
                    paymentSessionId: sessionId,
                    redirectTarget: '_self',
                });
            }

            // Wait for SDK to be ready
            if (typeof window.Cashfree === 'function') {
                startCheckout();
            } else {
                var attempts = 0;
                var interval = setInterval(function() {
                    attempts++;
                    if (typeof window.Cashfree === 'function') {
                        clearInterval(interval);
                        startCheckout();
                    } else if (attempts > 20) {
                        clearInterval(interval);
                        startCheckout(); // will show error
                    }
                }, 500);
            }
        })();
    </script>
</body>
</html>
