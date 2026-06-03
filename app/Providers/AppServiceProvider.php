<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Setting;
use App\Models\UserAddress;
use App\View\ThemeDataProvider;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Apply timezone from settings
        try {
            $timezone = Setting::get('timezone', config('app.timezone'));
            if ($timezone && in_array($timezone, timezone_identifiers_list())) {
                config(['app.timezone' => $timezone]);
                date_default_timezone_set($timezone);
            }
        } catch (\Exception $e) {
            // Settings table may not exist during migrations
        }

        // Auto-apply reply-to header for tenants using shared SMTP.
        // ConfigBootstrapper sets mail.reply_to.address when tenant doesn't have own SMTP.
        Event::listen(MessageSending::class, function (MessageSending $event) {
            $replyTo = config('mail.reply_to.address');
            if ($replyTo && empty($event->message->getReplyTo())) {
                $event->message->replyTo($replyTo, config('mail.reply_to.name', ''));
            }
        });

        Route::model('address', UserAddress::class);

        // Keep Shiprocket Checkout's cached catalog in sync — every product /
        // category change is pushed to Shiprocket (wh/v1/custom/*).
        \App\Models\Product::observe(\App\Observers\ProductObserver::class);
        Category::observe(\App\Observers\CategoryObserver::class);

        Blade::directive('price', function (string $expression) {
            return "<?php echo format_price({$expression}); ?>";
        });

        // @setting('key', 'default') — pull value from $theme provider
        Blade::directive('setting', function (string $expression) {
            return "<?php echo \$theme->get({$expression}); ?>";
        });

        // @currency — output currency symbol (e.g. ₹)
        Blade::directive('currency', function () {
            return "<?php echo currency_symbol(); ?>";
        });

        // Share $theme (pre-fetched settings + nav) with ALL storefront views
        View::composer('*', function ($view) {
            if (!isset($view->getData()['theme'])) {
                try {
                    $view->with('theme', ThemeDataProvider::instance());
                } catch (\Throwable $e) {
                    // Settings table may not exist during migrations
                }
            }
        });

        View::composer('partials.mobile-nav', function ($view) {
            $view->with('navCategories', Category::whereNull('parent_id')
                ->where('is_active', true)
                ->with(['children' => fn ($q) => $q->where('is_active', true)->orderBy('position')])
                ->orderBy('position')
                ->get());
        });
    }
}
