<?php

namespace Tests\Feature\Order;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Order $order;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->user = User::factory()->create(['role' => 'customer']);

        $category = Category::create([
            'name' => 'Gadgets',
            'slug' => 'gadgets',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'name' => 'USB Cable',
            'slug' => 'usb-cable',
            'sku' => 'USB-001',
            'price' => 299,
            'mrp' => 499,
            'cost_price' => 80,
            'stock_quantity' => 50,
            'category_id' => $category->id,
            'status' => 'approved',
            'is_active' => true,
        ]);

        $address = UserAddress::create([
            'user_id' => $this->user->id,
            'label' => 'Home',
            'first_name' => 'Test',
            'last_name' => 'User',
            'phone' => '9876543210',
            'address_line_1' => '123 Test Street',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'postal_code' => '110001',
            'country' => 'IN',
            'is_default' => true,
        ]);

        $this->order = Order::create([
            'user_id' => $this->user->id,
            'shipping_address_id' => $address->id,
            'billing_address_id' => $address->id,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'subtotal' => 598,
            'discount' => 0,
            'tax' => 0,
            'shipping_cost' => 0,
            'total' => 598,
            'paid_amount' => 598,
            'source' => 'web',
        ]);

        OrderItem::create([
            'order_id' => $this->order->id,
            'product_id' => $this->product->id,
            'product_name' => 'USB Cable',
            'sku' => 'USB-001',
            'price' => 299,
            'mrp' => 499,
            'quantity' => 2,
            'tax' => 0,
            'discount' => 0,
            'total' => 598,
        ]);
    }

    // ── Auth ──

    public function test_order_listing_requires_authentication(): void
    {
        $response = $this->get('/account/orders');
        $response->assertRedirect('/login');
    }

    public function test_order_listing_shows_user_orders(): void
    {
        $response = $this->actingAs($this->user)->get('/account/orders');
        $response->assertStatus(200);
    }

    public function test_order_detail_page_loads(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/account/orders/' . $this->order->id);
        $response->assertStatus(200);
    }

    public function test_user_cannot_view_another_users_order(): void
    {
        $otherUser = User::factory()->create(['role' => 'customer']);
        $response = $this->actingAs($otherUser)
            ->get('/account/orders/' . $this->order->id);
        $response->assertStatus(403);
    }

    // ── Status ──

    public function test_order_has_correct_initial_status(): void
    {
        $this->assertEquals('confirmed', $this->order->status);
        $this->assertTrue($this->order->isConfirmed());
        $this->assertFalse($this->order->isCancelled());
        $this->assertFalse($this->order->isDelivered());
    }

    public function test_order_can_be_cancelled_before_shipping(): void
    {
        $this->assertTrue($this->order->canBeCancelled());

        $this->order->updateStatus('cancelled');
        $this->order->refresh();

        $this->assertEquals('cancelled', $this->order->status);
        $this->assertTrue($this->order->isCancelled());
    }

    public function test_delivered_order_cannot_be_cancelled(): void
    {
        $this->order->update(['status' => 'delivered']);
        $this->order->refresh();

        $this->assertFalse($this->order->canBeCancelled());
    }

    // ── Tracking ──

    public function test_order_tracking_steps(): void
    {
        $steps = $this->order->getTrackingSteps();

        $this->assertIsArray($steps);
        $this->assertNotEmpty($steps);
        // First step (confirmed) should be completed
        $this->assertTrue($steps[0]['completed'] ?? false);
    }

    // ── Order Number ──

    public function test_order_number_auto_generated(): void
    {
        $this->assertNotNull($this->order->order_number);
        $this->assertStringStartsWith('ORD-', $this->order->order_number);
    }

    // ── Guest Tracking ──

    public function test_track_order_page_loads(): void
    {
        $response = $this->get('/track-order');
        $response->assertStatus(200);
    }
}
