<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Page Not Found - <?php echo e(config('app.name')); ?></title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700&display=swap" rel="stylesheet" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #fafafa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .header {
            background: #fff;
            border-bottom: 1px solid #f0f0f0;
            padding: 1rem 1.5rem;
            text-align: center;
        }
        .header img { height: 2.5rem; object-fit: contain; }
        .content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.5rem;
        }
        .card {
            text-align: center;
            max-width: 28rem;
        }
        .icon-wrap {
            width: 6rem;
            height: 6rem;
            margin: 0 auto 1.5rem;
            background: #171717;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .icon-wrap svg { width: 3rem; height: 3rem; color: #fff; }
        .code {
            font-size: 3.5rem;
            font-weight: 700;
            color: #222;
            line-height: 1;
            margin-bottom: 0.5rem;
        }
        .title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #222;
            margin-bottom: 0.5rem;
        }
        .message {
            font-size: 0.875rem;
            color: #666;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        .actions { display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap; }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            border-radius: 0.75rem;
            transition: all 0.2s;
        }
        .btn-primary {
            background: #171717;
            color: #fff;
        }
        .btn-primary:hover { opacity: 0.85; transform: translateY(-1px); }
        .btn-outline {
            background: #fff;
            color: #555;
            border: 1px solid #ddd;
        }
        .btn-outline:hover { border-color: #171717; color: #171717; }
        .btn svg { width: 1rem; height: 1rem; }
    </style>
</head>
<body>
    <div class="header">
        <a href="<?php echo e(url('/')); ?>">
            <img src="<?php echo e(asset(\App\Models\Setting::get('store_logo', 'images/logo.png'))); ?>" alt="<?php echo e(config('app.name')); ?>">
        </a>
    </div>
    <div class="content">
        <div class="card">
            <div class="icon-wrap">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <div class="code">404</div>
            <h1 class="title">Page Not Found</h1>
            <p class="message">Oops! The page you're looking for doesn't exist or has been moved. Try searching or browse our products.</p>

            
            <form action="<?php echo e(url('/search')); ?>" method="GET" style="margin-bottom:1.5rem;display:flex;gap:0.5rem;max-width:24rem;margin-left:auto;margin-right:auto">
                <input type="text" name="q" placeholder="Search products..." required
                       style="flex:1;padding:0.65rem 1rem;border:1px solid #ddd;border-radius:0.75rem;font-size:0.875rem;outline:none;font-family:inherit">
                <button type="submit" class="btn btn-primary" style="padding:0.65rem 1rem">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:1.1rem;height:1.1rem"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </button>
            </form>

            <div class="actions">
                <a href="<?php echo e(url('/products')); ?>" class="btn btn-primary">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    Browse Products
                </a>
                <a href="<?php echo e(url('/')); ?>" class="btn btn-outline">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Go Home
                </a>
            </div>
        </div>
    </div>
</body>
</html>
<?php /**PATH D:\projects\grytlabs345\resources\views/errors/404.blade.php ENDPATH**/ ?>