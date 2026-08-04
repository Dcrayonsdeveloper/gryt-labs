<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->group(function () {
    // Guest routes
    Route::middleware(['guest:admin', 'throttle:10,1'])->group(function () {
        Route::get('/login', [App\Http\Controllers\Admin\Auth\LoginController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [App\Http\Controllers\Admin\Auth\LoginController::class, 'login'])->middleware('throttle.login');
    });

    // Authenticated admin routes (2FA routes — before admin.audit, inside auth:admin + admin)
    Route::middleware(['auth:admin', 'admin'])->group(function () {
        // 2FA setup & verification (exempt from admin.audit and 2fa middleware)
        Route::get('/2fa/setup', [App\Http\Controllers\Admin\TwoFactorController::class, 'setup'])->name('2fa.setup');
        Route::post('/2fa/enable', [App\Http\Controllers\Admin\TwoFactorController::class, 'enable'])->name('2fa.enable');
        Route::post('/2fa/disable', [App\Http\Controllers\Admin\TwoFactorController::class, 'disable'])->name('2fa.disable');
        Route::get('/2fa/verify', [App\Http\Controllers\Admin\TwoFactorController::class, 'verify'])->name('2fa.verify');
        Route::post('/2fa/verify', [App\Http\Controllers\Admin\TwoFactorController::class, 'verifyCode'])->name('2fa.verify.code');
        Route::post('/2fa/recovery', [App\Http\Controllers\Admin\TwoFactorController::class, 'verifyRecovery'])->name('2fa.recovery');

        // Logout must be accessible even when 2FA is pending
        Route::post('/logout', [App\Http\Controllers\Admin\Auth\LoginController::class, 'logout'])->name('logout');
    });

    // Authenticated admin routes (with 2FA enforcement + audit log)
    Route::middleware(['auth:admin', 'admin', '2fa', 'admin.audit'])->group(function () {

        // Dashboard
        Route::get('/', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

        // Activity Log
        Route::get('/audit-log', [App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('audit-log.index');

        // Error Logs
        Route::prefix('error-logs')->name('error-logs.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\ErrorLogController::class, 'index'])->name('index');
            Route::get('/{id}', [App\Http\Controllers\Admin\ErrorLogController::class, 'show'])->name('show');
            Route::post('/{id}/resolve', [App\Http\Controllers\Admin\ErrorLogController::class, 'resolve'])->name('resolve');
            Route::post('/bulk-resolve', [App\Http\Controllers\Admin\ErrorLogController::class, 'bulkResolve'])->name('bulk-resolve');
            Route::delete('/clear', [App\Http\Controllers\Admin\ErrorLogController::class, 'clear'])->name('clear');
        });

        // New order check (polling for notifications)
        Route::get('/orders/check-new', function () {
            $since = request('since', now()->subMinutes(1)->format('Y-m-d H:i:s'));
            $newOrders = App\Models\Order::where('created_at', '>', $since)
                ->latest()
                ->take(5)
                ->get(['id', 'order_number', 'total', 'created_at', 'guest_name']);
            return response()->json([
                'count' => $newOrders->count(),
                'orders' => $newOrders->map(fn($o) => [
                    'id' => $o->id,
                    'number' => $o->order_number,
                    'total' => number_format($o->total, 0),
                    'name' => $o->user?->first_name ?? $o->guest_name ?? 'Guest',
                ]),
                'checked_at' => now()->format('Y-m-d H:i:s'),
            ]);
        })->name('orders.check-new');

        // Profile
        Route::get('/profile', [App\Http\Controllers\Admin\ProfileController::class, 'edit'])->name('profile');
        Route::put('/profile', [App\Http\Controllers\Admin\ProfileController::class, 'update'])->name('profile.update');

        // Notifications
        Route::get('/notifications', [App\Http\Controllers\Admin\NotificationController::class, 'index'])->name('notifications');
        Route::get('/notifications/{notification}/read', [App\Http\Controllers\Admin\NotificationController::class, 'read'])->name('notifications.read');

        // AJAX search endpoints (used by multiple features)
        Route::get('/search/products', [App\Http\Controllers\Admin\SearchController::class, 'products'])->name('search.products');
        Route::get('/search/orders', [App\Http\Controllers\Admin\SearchController::class, 'orders'])->name('search.orders');
        Route::get('/search/customers', [App\Http\Controllers\Admin\SearchController::class, 'customers'])->name('search.customers');

        // Orders
        Route::middleware('admin.section:orders')->group(function () {
            // Abandoned Checkouts
            Route::get('/abandoned-checkouts', function (\Illuminate\Http\Request $request) {
                $tab = $request->get('tab', 'all');
                $query = \App\Models\AbandonedCheckout::latest();

                if ($tab === 'with-contact') {
                    $query->where(function ($q) { $q->whereNotNull('email')->orWhereNotNull('phone'); })->where('recovered', false);
                } elseif ($tab === 'recovered') {
                    $query->where('recovered', true);
                }

                // Date filter
                $dateFrom = $request->get('from');
                $dateTo = $request->get('to');
                if ($dateFrom) {
                    $query->whereDate('created_at', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $query->whereDate('created_at', '<=', $dateTo);
                }

                // CSV Export
                if ($request->input('export') === 'csv') {
                    $filename = 'abandoned_checkouts_' . now()->format('Y-m-d_His') . '.csv';
                    return response()->streamDownload(function () use ($query) {
                        $handle = fopen('php://output', 'w');
                        fputcsv($handle, [
                            'ID', 'Date', 'Customer Name', 'Email', 'Phone',
                            'Cart Total', 'Items Count', 'Step', 'Status',
                            'Reminder Count', 'Last Notified', 'Products',
                        ]);
                        $query->chunk(500, function ($rows) use ($handle) {
                            foreach ($rows as $ac) {
                                $products = '';
                                if ($ac->cart_snapshot) {
                                    $snapshot = is_array($ac->cart_snapshot) ? $ac->cart_snapshot : json_decode($ac->cart_snapshot, true);
                                    $products = collect($snapshot)->map(fn($i) => ($i['name'] ?? 'Product') . ' x' . ($i['quantity'] ?? 1))->implode(', ');
                                }
                                fputcsv($handle, [
                                    $ac->id,
                                    $ac->created_at->format('d M Y h:i A'),
                                    $ac->name ?? '',
                                    $ac->email ?? '',
                                    $ac->phone ?? '',
                                    number_format($ac->cart_total ?? 0, 2),
                                    $ac->items_count ?? 0,
                                    ucfirst($ac->step ?? ''),
                                    $ac->recovered ? 'Recovered' : ($ac->notified_at ? 'Reminded' : 'Not Recovered'),
                                    $ac->reminder_count ?? 0,
                                    $ac->notified_at ? $ac->notified_at->format('d M Y h:i A') : '',
                                    $products,
                                ]);
                            }
                        });
                        fclose($handle);
                    }, $filename, ['Content-Type' => 'text/csv']);
                }

                $abandoned = $query->paginate(25)->appends($request->only(['tab', 'from', 'to']));
                return view('admin.abandoned-checkouts', compact('abandoned', 'dateFrom', 'dateTo'));
            })->name('abandoned-checkouts');

            Route::post('/abandoned-checkouts/bulk', function (\Illuminate\Http\Request $request) {
                // Accept either ids[] (bulk) OR a single checkout_id (per-row action).
                $ids = $request->input('ids', []);
                if (empty($ids) && $request->filled('checkout_id')) {
                    $ids = [(int) $request->input('checkout_id')];
                }
                $action = $request->input('action');
                $isJson = $request->wantsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest';

                if (empty($ids)) {
                    return $isJson
                        ? response()->json(['ok' => false, 'message' => 'No items selected.'], 422)
                        : back()->with('success', 'No items selected.');
                }

                if ($action === 'delete') {
                    $count = \App\Models\AbandonedCheckout::whereIn('id', $ids)->delete();
                    return $isJson
                        ? response()->json(['ok' => true, 'message' => "{$count} abandoned checkouts deleted."])
                        : back()->with('success', "{$count} abandoned checkouts deleted.");
                }

                if ($action === 'remind') {
                    $sent = 0;
                    $items = \App\Models\AbandonedCheckout::whereIn('id', $ids)->where('recovered', false)->get();
                    foreach ($items as $ac) {
                        if ($ac->email) {
                            try {
                                $discountPct = \App\Models\Setting::get('abandoned_cart_discount_pct', 5);
                                $coupon = \App\Models\Coupon::create([
                                    'code' => 'CART' . strtoupper(\Illuminate\Support\Str::random(6)),
                                    'type' => 'percentage',
                                    'value' => $discountPct,
                                    'min_order_amount' => 0,
                                    'usage_limit' => 1,
                                    'times_used' => 0,
                                    'starts_at' => now(),
                                    'expires_at' => now()->addHours(24),
                                    'is_active' => true,
                                ]);
                                \Illuminate\Support\Facades\Mail::send('emails.abandoned-cart', [
                                    'name' => $ac->name ?: 'there',
                                    'discountCode' => $coupon->code,
                                    'cartUrl' => url('/cart'),
                                ], function ($m) use ($ac) {
                                    $m->to($ac->email, $ac->name)->subject('You left something behind! Complete your order');
                                });
                                $sent++;
                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error('Abandoned email failed: ' . $e->getMessage());
                            }
                        }
                        if ($ac->phone) {
                            try {
                                app(\App\Services\WhatsAppService::class)->sendAbandonedCartReminder($ac->phone, $ac->name ?: 'there', $ac->cart_total);
                                $sent++;
                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error('WhatsApp abandoned reminder failed: ' . $e->getMessage());
                            }
                        }
                        $ac->update([
                            'notified_at' => now(),
                            'reminder_count' => (int) ($ac->reminder_count ?? 0) + 1,
                        ]);
                    }

                    if ($isJson) {
                        $first = $items->first();
                        return response()->json([
                            'ok' => true,
                            'message' => "Reminders sent to {$sent} contacts.",
                            'sent' => $sent,
                            'notified_at_human' => $first?->fresh()?->notified_at?->diffForHumans(),
                            'notified_at_iso' => $first?->fresh()?->notified_at?->toIso8601String(),
                            'reminder_count' => $first?->fresh()?->reminder_count,
                        ]);
                    }

                    return back()->with('success', "Reminders sent to {$sent} contacts.");
                }

                return $isJson
                    ? response()->json(['ok' => false, 'message' => 'Unknown action.'], 422)
                    : back();
            })->name('abandoned-checkouts.bulk');

            Route::prefix('orders')->name('orders.')->group(function () {
                Route::get('/', [App\Http\Controllers\Admin\OrderController::class, 'index'])->name('index');
                Route::post('/bulk-action', [App\Http\Controllers\Admin\OrderController::class, 'bulkAction'])->name('bulk-action');
                Route::post('/carriers', [App\Http\Controllers\Admin\OrderController::class, 'storeCarrier'])->name('carriers.store');
                Route::get('/{order}', [App\Http\Controllers\Admin\OrderController::class, 'show'])->name('show');
                Route::put('/{order}/status', [App\Http\Controllers\Admin\OrderController::class, 'updateStatus'])->name('status');
                Route::post('/{order}/ship', [App\Http\Controllers\Admin\OrderController::class, 'ship'])->name('ship');
                Route::post('/{order}/revert', [App\Http\Controllers\Admin\OrderController::class, 'revertStatus'])->name('revert');
                Route::post('/{order}/uncancel', [App\Http\Controllers\Admin\OrderController::class, 'uncancel'])->name('uncancel');
                Route::post('/{order}/unfulfill', [App\Http\Controllers\Admin\OrderController::class, 'unfulfill'])->name('unfulfill');
                Route::get('/{order}/invoice', [App\Http\Controllers\Admin\OrderController::class, 'invoice'])->name('invoice');
                Route::get('/{order}/packing-slip', [App\Http\Controllers\Admin\OrderController::class, 'packingSlip'])->name('packing-slip');
                Route::post('/{order}/assign-partner', [App\Http\Controllers\Admin\OrderController::class, 'assignPartner'])->name('assign-partner');
                Route::put('/{order}/expected-delivery', [App\Http\Controllers\Admin\OrderController::class, 'setExpectedDelivery'])->name('expected-delivery');
                Route::post('/{order}/push-shiprocket', [App\Http\Controllers\Admin\OrderController::class, 'pushToShiprocket'])->name('push-shiprocket');
                Route::post('/sync-shiprocket-customers', [App\Http\Controllers\Admin\OrderController::class, 'syncShiprocketCustomers'])->name('sync-shiprocket-customers');
                Route::post('/sync-shiprocket-addresses', [App\Http\Controllers\Admin\OrderController::class, 'syncShiprocketAddresses'])->name('sync-shiprocket-addresses');
                Route::post('/sync-orders', [App\Http\Controllers\Admin\OrderController::class, 'syncOrders'])->name('sync-orders');
                Route::get('/{order}/edit', [App\Http\Controllers\Admin\OrderController::class, 'editOrder'])->name('edit');
                Route::put('/{order}/update', [App\Http\Controllers\Admin\OrderController::class, 'updateOrder'])->name('update-order');
                Route::post('/{order}/add-item', [App\Http\Controllers\Admin\OrderController::class, 'addItem'])->name('add-item');
                Route::delete('/{order}/remove-item/{item}', [App\Http\Controllers\Admin\OrderController::class, 'removeItem'])->name('remove-item');
                Route::put('/{order}/update-item/{item}', [App\Http\Controllers\Admin\OrderController::class, 'updateItemQuantity'])->name('update-item');
            });

            // Draft Orders
            Route::prefix('draft-orders')->name('draft-orders.')->group(function () {
                Route::get('/search/customers', [App\Http\Controllers\Admin\DraftOrderController::class, 'searchCustomers'])->name('search-customers');
                Route::get('/', [App\Http\Controllers\Admin\DraftOrderController::class, 'index'])->name('index');
                Route::get('/create', [App\Http\Controllers\Admin\DraftOrderController::class, 'create'])->name('create');
                Route::post('/', [App\Http\Controllers\Admin\DraftOrderController::class, 'store'])->name('store');
                Route::get('/{draftOrder}', [App\Http\Controllers\Admin\DraftOrderController::class, 'show'])->name('show');
                Route::post('/{draftOrder}/send', [App\Http\Controllers\Admin\DraftOrderController::class, 'send'])->name('send');
                Route::post('/{draftOrder}/complete', [App\Http\Controllers\Admin\DraftOrderController::class, 'complete'])->name('complete');
                Route::delete('/{draftOrder}', [App\Http\Controllers\Admin\DraftOrderController::class, 'destroy'])->name('destroy');
            });

            // Delivery (Delhivery Integration)
            Route::prefix('delivery')->name('delivery.')->group(function () {
                Route::get('/', [App\Http\Controllers\Admin\DeliveryController::class, 'index'])->name('index');
                Route::post('/orders/{order}/book', [App\Http\Controllers\Admin\DeliveryController::class, 'book'])->name('book');
                Route::post('/bulk-book', [App\Http\Controllers\Admin\DeliveryController::class, 'bulkBook'])->name('bulk-book');
                Route::get('/orders/{order}/track', [App\Http\Controllers\Admin\DeliveryController::class, 'track'])->name('track');
                Route::post('/orders/{order}/cancel', [App\Http\Controllers\Admin\DeliveryController::class, 'cancel'])->name('cancel');
                Route::get('/orders/{order}/label', [App\Http\Controllers\Admin\DeliveryController::class, 'label'])->name('label');
                Route::post('/orders/{order}/ndr', [App\Http\Controllers\Admin\DeliveryController::class, 'ndrAction'])->name('ndr');
                Route::post('/pickup', [App\Http\Controllers\Admin\DeliveryController::class, 'requestPickup'])->name('pickup');
                Route::get('/check-pincode', [App\Http\Controllers\Admin\DeliveryController::class, 'checkPincode'])->name('check-pincode');
                Route::get('/calculate-cost', [App\Http\Controllers\Admin\DeliveryController::class, 'calculateCost'])->name('calculate-cost');
            });

            // Returns
            Route::prefix('returns')->name('returns.')->group(function () {
                Route::get('/', [App\Http\Controllers\Admin\ReturnController::class, 'index'])->name('index');
                Route::get('/{return}', [App\Http\Controllers\Admin\ReturnController::class, 'show'])->name('show');
                Route::put('/{return}/status', [App\Http\Controllers\Admin\ReturnController::class, 'updateStatus'])->name('status');
                Route::post('/{return}/refund', [App\Http\Controllers\Admin\ReturnController::class, 'processRefund'])->name('refund');
                Route::post('/{return}/assign-partner', [App\Http\Controllers\Admin\ReturnController::class, 'assignPartner'])->name('assign-partner');
            });

            // Credit Notes
            Route::prefix('credit-notes')->name('credit-notes.')->group(function () {
                Route::get('/', [App\Http\Controllers\Admin\CreditNoteController::class, 'index'])->name('index');
                Route::get('/{creditNote}', [App\Http\Controllers\Admin\CreditNoteController::class, 'show'])->name('show');
            });
        });

        // Editor image upload (CKEditor 5 SimpleUploadAdapter)
        Route::post('/editor/upload-image', [App\Http\Controllers\Admin\EditorImageController::class, 'upload'])->name('editor.upload-image');

        // Video upload
        Route::post('/upload-video', [App\Http\Controllers\Admin\ProductController::class, 'uploadVideo'])->name('upload-video');

        // Catalog
        Route::middleware('admin.section:catalog')->group(function () {
            // Products (export/import before resource to avoid route conflict)
            Route::get('/products/export', [App\Http\Controllers\Admin\ProductController::class, 'export'])->name('products.export');
            Route::post('/products/import', [App\Http\Controllers\Admin\ProductController::class, 'import'])->name('products.import');
            Route::get('/products/bulk-edit', [App\Http\Controllers\Admin\ProductController::class, 'bulkEdit'])->name('products.bulk-edit');
            Route::put('/products/bulk-update', [App\Http\Controllers\Admin\ProductController::class, 'bulkUpdate'])->name('products.bulk-update');
            Route::resource('products', App\Http\Controllers\Admin\ProductController::class);
            Route::put('/products/{product}/toggle-status', [App\Http\Controllers\Admin\ProductController::class, 'toggleStatus'])->name('products.toggle-status');
            Route::put('/products/{product}/toggle-featured', [App\Http\Controllers\Admin\ProductController::class, 'toggleFeatured'])->name('products.toggle-featured');
            Route::post('/products/{product}/duplicate', [App\Http\Controllers\Admin\ProductController::class, 'duplicate'])->name('products.duplicate');
            Route::post('/products/bulk-action', [App\Http\Controllers\Admin\ProductController::class, 'bulkAction'])->name('products.bulk-action');

            // Categories
            Route::resource('categories', App\Http\Controllers\Admin\CategoryController::class);
            Route::put('/categories/{category}/toggle-status', [App\Http\Controllers\Admin\CategoryController::class, 'toggleStatus'])->name('categories.toggle-status');
            Route::post('/categories/reorder', [App\Http\Controllers\Admin\CategoryController::class, 'reorder'])->name('categories.reorder');

            // Brands
            Route::resource('brands', App\Http\Controllers\Admin\BrandController::class);

            // Attributes
            Route::resource('attributes', App\Http\Controllers\Admin\AttributeController::class);
            Route::resource('attributes.values', App\Http\Controllers\Admin\AttributeValueController::class)->shallow();

            // Inventory
            Route::prefix('inventory')->name('inventory.')->group(function () {
                Route::get('/', [App\Http\Controllers\Admin\InventoryController::class, 'index'])->name('index');
                Route::get('/low-stock', [App\Http\Controllers\Admin\InventoryController::class, 'lowStock'])->name('low-stock');
                Route::get('/out-of-stock', [App\Http\Controllers\Admin\InventoryController::class, 'outOfStock'])->name('out-of-stock');
                Route::put('/{product:id}/stock', [App\Http\Controllers\Admin\InventoryController::class, 'updateStock'])->name('update-stock');
                Route::get('/movements', [App\Http\Controllers\Admin\InventoryController::class, 'movements'])->name('movements');
                Route::resource('locations', App\Http\Controllers\Admin\InventoryLocationController::class);
            });
        });

        // Customers
        Route::middleware('admin.section:customers')->group(function () {
            Route::resource('customers', App\Http\Controllers\Admin\CustomerController::class)->except(['create', 'store', 'destroy']);
            Route::put('/customers/{customer}/toggle-status', [App\Http\Controllers\Admin\CustomerController::class, 'toggleStatus'])->name('customers.toggle-status');
            Route::get('/customers/{customer}/orders', [App\Http\Controllers\Admin\CustomerController::class, 'orders'])->name('customers.orders');
            Route::patch('/customers/{customer}/notes', [App\Http\Controllers\Admin\CustomerController::class, 'updateNotes'])->name('customers.notes.update');
            Route::patch('/customers/{customer}/tags', [App\Http\Controllers\Admin\CustomerController::class, 'updateTags'])->name('customers.tags.update');

            // Customer Segments
            Route::resource('customer-segments', App\Http\Controllers\Admin\CustomerSegmentController::class)->except(['edit', 'update', 'destroy']);
        });

        // Sellers
        Route::middleware('admin.section:sellers')->group(function () {
            Route::prefix('sellers')->name('sellers.')->group(function () {
                Route::get('/', [App\Http\Controllers\Admin\SellerController::class, 'index'])->name('index');
                Route::get('/pending', [App\Http\Controllers\Admin\SellerController::class, 'pending'])->name('pending');
                Route::get('/{seller}', [App\Http\Controllers\Admin\SellerController::class, 'show'])->name('show');
                Route::put('/{seller}', [App\Http\Controllers\Admin\SellerController::class, 'update'])->name('update');
                Route::post('/{seller}/approve', [App\Http\Controllers\Admin\SellerController::class, 'approve'])->name('approve');
                Route::post('/{seller}/reject', [App\Http\Controllers\Admin\SellerController::class, 'reject'])->name('reject');
                Route::post('/{seller}/suspend', [App\Http\Controllers\Admin\SellerController::class, 'suspend'])->name('suspend');
                Route::get('/{seller}/products', [App\Http\Controllers\Admin\SellerController::class, 'products'])->name('products');
                Route::get('/{seller}/payouts', [App\Http\Controllers\Admin\SellerController::class, 'payouts'])->name('payouts');
            });
        });

        // Staff (admin-only)
        Route::middleware('admin.section:staff')->group(function () {
            Route::resource('staff', App\Http\Controllers\Admin\StaffController::class);
        });

        // Delivery Partners
        Route::middleware('admin.section:delivery_partners')->group(function () {
            Route::resource('delivery-partners', App\Http\Controllers\Admin\DeliveryPartnerController::class);
            Route::put('/delivery-partners/{deliveryPartner}/toggle-status', [App\Http\Controllers\Admin\DeliveryPartnerController::class, 'toggleStatus'])->name('delivery-partners.toggle-status');
        });

        // Marketing
        Route::middleware('admin.section:marketing')->group(function () {
            // Marketing Hub & Meta OAuth
            Route::prefix('marketing')->name('marketing.')->group(function () {
                Route::get('/hub', [App\Http\Controllers\Admin\MetaConnectController::class, 'index'])->name('hub');
                Route::get('/meta/redirect', [App\Http\Controllers\Admin\MetaConnectController::class, 'redirect'])->name('meta.redirect');
                Route::get('/meta/callback', [App\Http\Controllers\Admin\MetaConnectController::class, 'callback'])->name('meta.callback');
                Route::delete('/meta/disconnect', [App\Http\Controllers\Admin\MetaConnectController::class, 'disconnect'])->name('meta.disconnect');
            });

            Route::resource('coupons', App\Http\Controllers\Admin\CouponController::class);
            Route::resource('gift-cards', App\Http\Controllers\Admin\GiftCardController::class)->except(['edit', 'update']);
            Route::resource('flash-sales', App\Http\Controllers\Admin\FlashSaleController::class);
            Route::resource('banners', App\Http\Controllers\Admin\BannerController::class);
            Route::post('/banners/reorder', [App\Http\Controllers\Admin\BannerController::class, 'reorder'])->name('banners.reorder');

            // Affiliates
            Route::prefix('affiliates')->name('affiliates.')->group(function () {
                Route::get('/', [App\Http\Controllers\Admin\AffiliateController::class, 'index'])->name('index');
                Route::get('/redemptions', [App\Http\Controllers\Admin\AffiliateController::class, 'redemptions'])->name('redemptions');
                Route::get('/{affiliate}', [App\Http\Controllers\Admin\AffiliateController::class, 'show'])->name('show');
                Route::post('/{affiliate}/approve', [App\Http\Controllers\Admin\AffiliateController::class, 'approve'])->name('approve');
                Route::post('/{affiliate}/reject', [App\Http\Controllers\Admin\AffiliateController::class, 'reject'])->name('reject');
                Route::post('/{affiliate}/suspend', [App\Http\Controllers\Admin\AffiliateController::class, 'suspend'])->name('suspend');
                Route::post('/redemptions/{redemption}/process', [App\Http\Controllers\Admin\AffiliateController::class, 'processRedemption'])->name('redemptions.process');
                Route::post('/redemptions/{redemption}/complete', [App\Http\Controllers\Admin\AffiliateController::class, 'completeRedemption'])->name('redemptions.complete');
                Route::post('/redemptions/{redemption}/fail', [App\Http\Controllers\Admin\AffiliateController::class, 'failRedemption'])->name('redemptions.fail');
            });

            // Influencers
            Route::prefix('influencers')->name('influencers.')->group(function () {
                Route::get('/', [App\Http\Controllers\Admin\InfluencerController::class, 'index'])->name('index');
                Route::get('/create', [App\Http\Controllers\Admin\InfluencerController::class, 'create'])->name('create');
                Route::get('/coupon-suggestions', [App\Http\Controllers\Admin\InfluencerController::class, 'couponSuggestions'])->name('coupon-suggestions');
                Route::post('/', [App\Http\Controllers\Admin\InfluencerController::class, 'store'])->name('store');
                Route::get('/{influencer}/edit', [App\Http\Controllers\Admin\InfluencerController::class, 'edit'])->name('edit');
                Route::put('/{influencer}', [App\Http\Controllers\Admin\InfluencerController::class, 'update'])->name('update');
                Route::delete('/{influencer}', [App\Http\Controllers\Admin\InfluencerController::class, 'destroy'])->name('destroy');
                Route::put('/{influencer}/toggle-status', [App\Http\Controllers\Admin\InfluencerController::class, 'toggleStatus'])->name('toggle-status');
                Route::put('/{influencer}/reset-password', [App\Http\Controllers\Admin\InfluencerController::class, 'resetPassword'])->name('reset-password');
                Route::get('/{influencer}/analytics', [App\Http\Controllers\Admin\InfluencerController::class, 'analytics'])->name('analytics');
            });

            // Social Media Content Calendar
            Route::prefix('social-calendar')->name('social-calendar.')->group(function () {
                Route::get('/', [App\Http\Controllers\Admin\SocialMediaPostController::class, 'index'])->name('index');
                Route::get('/calendar-data', [App\Http\Controllers\Admin\SocialMediaPostController::class, 'calendarData'])->name('calendar-data');
                Route::get('/create', [App\Http\Controllers\Admin\SocialMediaPostController::class, 'create'])->name('create');
                Route::post('/', [App\Http\Controllers\Admin\SocialMediaPostController::class, 'store'])->name('store');
                Route::get('/{post}/edit', [App\Http\Controllers\Admin\SocialMediaPostController::class, 'edit'])->name('edit');
                Route::put('/{post}', [App\Http\Controllers\Admin\SocialMediaPostController::class, 'update'])->name('update');
                Route::delete('/{post}', [App\Http\Controllers\Admin\SocialMediaPostController::class, 'destroy'])->name('destroy');
                Route::post('/{post}/publish-now', [App\Http\Controllers\Admin\SocialMediaPostController::class, 'publishNow'])->name('publish-now');
                Route::post('/{post}/retry', [App\Http\Controllers\Admin\SocialMediaPostController::class, 'retry'])->name('retry');
            });

            // Newsletter
            Route::prefix('newsletter')->name('newsletter.')->group(function () {
                Route::get('/', [App\Http\Controllers\Admin\NewsletterController::class, 'index'])->name('index');
                Route::delete('/{newsletter}', [App\Http\Controllers\Admin\NewsletterController::class, 'destroy'])->name('destroy');
                Route::put('/{newsletter}/toggle-status', [App\Http\Controllers\Admin\NewsletterController::class, 'toggleStatus'])->name('toggle-status');
                Route::post('/bulk-action', [App\Http\Controllers\Admin\NewsletterController::class, 'bulkAction'])->name('bulk-action');
                Route::get('/export', [App\Http\Controllers\Admin\NewsletterController::class, 'export'])->name('export');
            });

            // Abandoned-cart email templates (Reminder 1 / 2 / 3)
            Route::get('/abandoned-cart-templates', [App\Http\Controllers\Admin\AbandonedCartTemplateController::class, 'index'])->name('abandoned-cart-templates.edit');
            Route::put('/abandoned-cart-templates', [App\Http\Controllers\Admin\AbandonedCartTemplateController::class, 'update'])->name('abandoned-cart-templates.update');
        });

        // Content
        Route::middleware('admin.section:content')->group(function () {
            Route::resource('pages', App\Http\Controllers\Admin\PageController::class);
            Route::resource('blog-posts', App\Http\Controllers\Admin\BlogPostController::class);
            Route::post('/blog-posts/bulk-action', [App\Http\Controllers\Admin\BlogPostController::class, 'bulkAction'])->name('blog-posts.bulk-action');

            Route::prefix('reviews')->name('reviews.')->group(function () {
                Route::get('/', [App\Http\Controllers\Admin\ReviewController::class, 'index'])->name('index');
                Route::post('/bulk-action', [App\Http\Controllers\Admin\ReviewController::class, 'bulkAction'])->name('bulk-action');
                Route::get('/pending', [App\Http\Controllers\Admin\ReviewController::class, 'pending'])->name('pending');
                Route::get('/{review}', [App\Http\Controllers\Admin\ReviewController::class, 'show'])->name('show');
                Route::get('/{review}/edit', [App\Http\Controllers\Admin\ReviewController::class, 'edit'])->name('edit');
                Route::put('/{review}', [App\Http\Controllers\Admin\ReviewController::class, 'update'])->name('update');
                Route::post('/{review}/approve', [App\Http\Controllers\Admin\ReviewController::class, 'approve'])->name('approve');
                Route::post('/{review}/reject', [App\Http\Controllers\Admin\ReviewController::class, 'reject'])->name('reject');
                Route::post('/{review}/reply', [App\Http\Controllers\Admin\ReviewController::class, 'reply'])->name('reply');
                Route::delete('/{review}', [App\Http\Controllers\Admin\ReviewController::class, 'destroy'])->name('destroy');
            });
        });

        // Support Tickets
        Route::prefix('support-tickets')->name('support-tickets.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\SupportTicketController::class, 'index'])->name('index');
            Route::get('/{supportTicket}', [App\Http\Controllers\Admin\SupportTicketController::class, 'show'])->name('show');
            Route::post('/{supportTicket}/reply', [App\Http\Controllers\Admin\SupportTicketController::class, 'reply'])->name('reply');
            Route::put('/{supportTicket}/status', [App\Http\Controllers\Admin\SupportTicketController::class, 'updateStatus'])->name('status');
            Route::delete('/{supportTicket}', [App\Http\Controllers\Admin\SupportTicketController::class, 'destroy'])->name('destroy');
        });

        // Enquiries
        Route::prefix('enquiries')->name('enquiries.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\EnquiryController::class, 'index'])->name('index');
            Route::get('/{enquiry}', [App\Http\Controllers\Admin\EnquiryController::class, 'show'])->name('show');
            Route::put('/{enquiry}/toggle-read', [App\Http\Controllers\Admin\EnquiryController::class, 'toggleRead'])->name('toggle-read');
            Route::put('/{enquiry}/status', [App\Http\Controllers\Admin\EnquiryController::class, 'updateStatus'])->name('status');
            Route::delete('/{enquiry}', [App\Http\Controllers\Admin\EnquiryController::class, 'destroy'])->name('destroy');
            Route::post('/bulk-action', [App\Http\Controllers\Admin\EnquiryController::class, 'bulkAction'])->name('bulk-action');
        });

        // Reports
        Route::middleware('admin.section:reports')->group(function () {
            Route::prefix('reports')->name('reports.')->group(function () {
                Route::get('/sales', [App\Http\Controllers\Admin\ReportController::class, 'sales'])->name('sales');
                Route::get('/analytics', [App\Http\Controllers\Admin\ReportController::class, 'analytics'])->name('analytics');
                Route::redirect('/traffic', '/admin/reports/analytics');
                Route::get('/products', [App\Http\Controllers\Admin\ReportController::class, 'products'])->name('products');
                Route::get('/customers', [App\Http\Controllers\Admin\ReportController::class, 'customers'])->name('customers');
                Route::get('/sellers', [App\Http\Controllers\Admin\ReportController::class, 'sellers'])->name('sellers');
                Route::get('/inventory', [App\Http\Controllers\Admin\InventoryReportController::class, 'index'])->name('inventory');
                Route::get('/export/{type}', [App\Http\Controllers\Admin\ReportController::class, 'export'])->name('export');
                Route::get('/export-excel/{type}', [App\Http\Controllers\Admin\ReportController::class, 'exportExcel'])->name('export-excel');
            });
        });

        // Fraud Review
        Route::prefix('fraud')->name('fraud.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\FraudController::class, 'index'])->name('index');
            Route::get('/{fraudLog}', [App\Http\Controllers\Admin\FraudController::class, 'show'])->name('show');
            Route::put('/{fraudLog}/review', [App\Http\Controllers\Admin\FraudController::class, 'review'])->name('review');
        });

        // Page Section Builder (drag-drop homepage)
        Route::prefix('sections')->name('sections.')->group(function () {
            Route::get('/{page?}', [App\Http\Controllers\Admin\PageSectionController::class, 'index'])->name('index');
            Route::post('/', [App\Http\Controllers\Admin\PageSectionController::class, 'store'])->name('store');
            Route::put('/{section}', [App\Http\Controllers\Admin\PageSectionController::class, 'update'])->name('update');
            Route::delete('/{section}', [App\Http\Controllers\Admin\PageSectionController::class, 'destroy'])->name('destroy');
            Route::post('/reorder', [App\Http\Controllers\Admin\PageSectionController::class, 'reorder'])->name('reorder');
            Route::post('/{section}/toggle', [App\Http\Controllers\Admin\PageSectionController::class, 'toggle'])->name('toggle');
        });

        // Theme Customizer (edit active theme settings)
        Route::prefix('theme')->name('theme.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\ThemeController::class, 'index'])->name('index');
            Route::get('/editor', [App\Http\Controllers\Admin\ThemeController::class, 'editor'])->name('editor');
            Route::post('/', [App\Http\Controllers\Admin\ThemeController::class, 'update'])->name('update');
            Route::post('/preview', [App\Http\Controllers\Admin\ThemeController::class, 'preview'])->name('preview');
            Route::post('/apply-preset', [App\Http\Controllers\Admin\ThemeController::class, 'applyPreset'])->name('apply-preset');
            Route::post('/reset', [App\Http\Controllers\Admin\ThemeController::class, 'reset'])->name('reset');
            Route::get('/export', [App\Http\Controllers\Admin\ThemeController::class, 'export'])->name('export');
            Route::post('/import', [App\Http\Controllers\Admin\ThemeController::class, 'import'])->name('import');
        });

        // AI Team (read-only v1 — lives under the settings section gate)
        Route::middleware('admin.section:settings')->group(function () {
            Route::prefix('ai-team')->name('ai-team.')->group(function () {
                Route::get('/', [App\Http\Controllers\Admin\AiTeamController::class, 'index'])->name('index');
                Route::get('/knowledge', [App\Http\Controllers\Admin\AiTeamController::class, 'knowledge'])->name('knowledge');
                Route::get('/{slug}', [App\Http\Controllers\Admin\AiTeamController::class, 'show'])->name('show');
            });
        });

        // Settings (admin-only)
        Route::middleware('admin.section:settings')->group(function () {
            Route::prefix('settings')->name('settings.')->group(function () {
                Route::get('/general', [App\Http\Controllers\Admin\SettingController::class, 'general'])->name('general');
                Route::put('/general', [App\Http\Controllers\Admin\SettingController::class, 'updateGeneral'])->name('general.update');

                // Domains
                Route::get('/domains', [App\Http\Controllers\Admin\DomainController::class, 'index'])->name('domains');
                Route::post('/domains', [App\Http\Controllers\Admin\DomainController::class, 'store'])->name('domains.store');
                Route::delete('/domains', [App\Http\Controllers\Admin\DomainController::class, 'destroy'])->name('domains.destroy');
                Route::get('/domains/verify-dns', [App\Http\Controllers\Admin\DomainController::class, 'verifyDns'])->name('domains.verify-dns');

                Route::get('/payment', [App\Http\Controllers\Admin\SettingController::class, 'payment'])->name('payment');
                Route::put('/payment', [App\Http\Controllers\Admin\SettingController::class, 'updatePayment'])->name('payment.update');

                Route::get('/shipping', [App\Http\Controllers\Admin\SettingController::class, 'shipping'])->name('shipping');
                Route::put('/shipping', [App\Http\Controllers\Admin\SettingController::class, 'updateShipping'])->name('shipping.update');

                Route::get('/tax', [App\Http\Controllers\Admin\SettingController::class, 'tax'])->name('tax');
                Route::put('/tax', [App\Http\Controllers\Admin\SettingController::class, 'updateTax'])->name('tax.update');

                Route::get('/email', [App\Http\Controllers\Admin\SettingController::class, 'email'])->name('email');
                Route::put('/email', [App\Http\Controllers\Admin\SettingController::class, 'updateEmail'])->name('email.update');

                Route::get('/seo', [App\Http\Controllers\Admin\SettingController::class, 'seo'])->name('seo');
                Route::put('/seo', [App\Http\Controllers\Admin\SettingController::class, 'updateSeo'])->name('seo.update');

                Route::get('/integrations', [App\Http\Controllers\Admin\SettingController::class, 'integrations'])->name('integrations');
                Route::put('/integrations', [App\Http\Controllers\Admin\SettingController::class, 'updateIntegrations'])->name('integrations.update');

                Route::get('/product-card', [App\Http\Controllers\Admin\SettingController::class, 'productCard'])->name('product-card');
                Route::put('/product-card', [App\Http\Controllers\Admin\SettingController::class, 'updateProductCard'])->name('product-card.update');

                // Tax Rates
                Route::resource('tax-rates', App\Http\Controllers\Admin\TaxRateController::class);

                // Shipping Zones
                Route::resource('shipping-zones', App\Http\Controllers\Admin\ShippingZoneController::class);
                Route::resource('shipping-zones.rates', App\Http\Controllers\Admin\ShippingRateController::class)->shallow();

                // Currencies
                Route::resource('currencies', App\Http\Controllers\Admin\CurrencyController::class);

                // Roles & Permissions
                Route::resource('roles', App\Http\Controllers\Admin\RoleController::class);
            });

            // Stores (POS)
            Route::resource('stores', App\Http\Controllers\Admin\StoreController::class);
        });

        // Storefront / Homepage Manager
        Route::middleware('admin.section:storefront')->group(function () {
            Route::prefix('homepage')->name('homepage.')->group(function () {
                Route::get('/', [App\Http\Controllers\Admin\HomepageController::class, 'index'])->name('index');

                // Site Settings
                Route::get('/site-settings', [App\Http\Controllers\Admin\HomepageController::class, 'siteSettings'])->name('site-settings');
                Route::put('/site-settings', [App\Http\Controllers\Admin\HomepageController::class, 'updateSiteSettings'])->name('site-settings.update');
                Route::post('/upload-video', [App\Http\Controllers\Admin\HomepageController::class, 'uploadVideo'])->name('upload-video');

                // Hero Banners
                Route::get('/hero-banners', [App\Http\Controllers\Admin\HomepageController::class, 'heroBanners'])->name('hero-banners');
                Route::post('/hero-banners', [App\Http\Controllers\Admin\HomepageController::class, 'storeHeroBanner'])->name('hero-banners.store');
                Route::put('/hero-banners/{banner}', [App\Http\Controllers\Admin\HomepageController::class, 'updateHeroBanner'])->name('hero-banners.update');
                Route::put('/hero-banners/{banner}/toggle', [App\Http\Controllers\Admin\HomepageController::class, 'toggleHeroBanner'])->name('hero-banners.toggle');
                Route::delete('/hero-banners/{banner}', [App\Http\Controllers\Admin\HomepageController::class, 'deleteHeroBanner'])->name('hero-banners.destroy');
                Route::post('/hero-banners/reorder', [App\Http\Controllers\Admin\HomepageController::class, 'reorderHeroBanners'])->name('hero-banners.reorder');

                // Sections
                Route::get('/sections', [App\Http\Controllers\Admin\HomepageController::class, 'sections'])->name('sections');
                Route::get('/sections/{section}', [App\Http\Controllers\Admin\HomepageController::class, 'editSection'])->name('sections.edit');
                Route::put('/sections/{section}', [App\Http\Controllers\Admin\HomepageController::class, 'updateSection'])->name('sections.update');
                Route::put('/sections/{section}/toggle', [App\Http\Controllers\Admin\HomepageController::class, 'toggleSection'])->name('sections.toggle');
                Route::post('/sections/reorder', [App\Http\Controllers\Admin\HomepageController::class, 'reorderSections'])->name('sections.reorder');

                // Testimonials
                Route::get('/testimonials', [App\Http\Controllers\Admin\HomepageController::class, 'testimonials'])->name('testimonials');
                Route::post('/testimonials', [App\Http\Controllers\Admin\HomepageController::class, 'storeTestimonial'])->name('testimonials.store');
                Route::put('/testimonials/{testimonial}', [App\Http\Controllers\Admin\HomepageController::class, 'updateTestimonial'])->name('testimonials.update');
                Route::put('/testimonials/{testimonial}/toggle', [App\Http\Controllers\Admin\HomepageController::class, 'toggleTestimonial'])->name('testimonials.toggle');
                Route::delete('/testimonials/{testimonial}', [App\Http\Controllers\Admin\HomepageController::class, 'deleteTestimonial'])->name('testimonials.destroy');

                // Navigation
                Route::get('/navigation', [App\Http\Controllers\Admin\HomepageController::class, 'navigation'])->name('navigation');
                Route::post('/navigation', [App\Http\Controllers\Admin\HomepageController::class, 'storeNavItem'])->name('navigation.store');
                Route::put('/navigation/{menu}', [App\Http\Controllers\Admin\HomepageController::class, 'updateNavItem'])->name('navigation.update');
                Route::delete('/navigation/{menu}', [App\Http\Controllers\Admin\HomepageController::class, 'deleteNavItem'])->name('navigation.destroy');
            });
        });
    });
});
