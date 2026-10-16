<?php

namespace Tests\Feature\Cart;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->user = User::factory()->create(['role' => 'customer']);

        $category = Category::create([
            'name' => 'Accessories',
            'slug' => 'accessories',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'name' => 'USB Charger',
            'slug' => 'usb-charger',
            'sku' => 'UC-001',
            'price' => 399,
            'mrp' => 599,
            'cost_price' => 150,
            'stock_quantity' => 20,
            'category_id' => $category->id,
            'status' => 'approved',
            'is_active' => true,
        ]);
    }

    public function test_cart_page_loads(): void
    {
        $response = $this->get('/cart');
        $response->assertStatus(200);
    }

    public function test_add_product_to_cart(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/cart/add', [
                'product_id' => $this->product->id,
                'quantity' => 1,
            ]);

        $response->assertRedirect();

        // Verify item is in cart
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $this->product->id,
            'quantity' => 1,
        ]);
    }

    public function test_add_product_to_cart_correct_qty(): void
    {
        $this->actingAs($this->user)
            ->post('/cart/add', [
                'product_id' => $this->product->id,
                'quantity' => 3,
            ]);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $this->product->id,
            'quantity' => 3,
        ]);
    }

    public function test_add_product_to_cart_as_guest(): void
    {
        $response = $this->post('/cart/add', [
            'product_id' => $this->product->id,
            'quantity' => 1,
        ]);

        $response->assertRedirect();

        // Signing in is optional, so the guest's cart is keyed on the session.
        // Asserting the row rather than just "a redirect happened" is the point:
        // the /login bounce this replaced was also a redirect, so the weaker
        // assertion passed just as happily while add-to-cart was still gated.
        $cart = Cart::where('session_id', session()->getId())->first();
        $this->assertNotNull($cart, 'Guest add-to-cart should create a session cart');
        $this->assertNull($cart->user_id);
        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
        ]);
    }

    public function test_guest_cart_merges_into_user_cart_on_login(): void
    {
        $this->post('/cart/add', [
            'product_id' => $this->product->id,
            'quantity' => 2,
        ]);

        $this->post('/login', [
            'email' => $this->user->email,
            'password' => 'password',
        ]);

        // The reason a guest may fill a cart unauthenticated: whatever they
        // collected has to survive the sign-in they may do later at checkout.
        $userCart = Cart::where('user_id', $this->user->id)->first();
        $this->assertNotNull($userCart, 'Signing in should carry the guest cart over');
        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $userCart->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
        ]);
    }

    public function test_cannot_add_inactive_product(): void
    {
        $this->product->update(['is_active' => false]);

        $response = $this->actingAs($this->user)
            ->post('/cart/add', [
                'product_id' => $this->product->id,
                'quantity' => 1,
            ]);

        // Should fail validation or redirect with error
        $this->assertDatabaseMissing('cart_items', [
            'product_id' => $this->product->id,
        ]);
    }

    public function test_cannot_add_more_than_stock(): void
    {
        $this->product->update(['stock_quantity' => 2]);

        $response = $this->actingAs($this->user)
            ->post('/cart/add', [
                'product_id' => $this->product->id,
                'quantity' => 5,
            ]);

        // Should either reject or cap at available stock
        $response->assertRedirect();
    }

    public function test_get_cart_data(): void
    {
        $response = $this->actingAs($this->user)->get('/cart/data');
        $response->assertStatus(200);
    }

    public function test_clear_cart(): void
    {
        $this->actingAs($this->user)
            ->post('/cart/add', [
                'product_id' => $this->product->id,
                'quantity' => 1,
            ]);

        $response = $this->actingAs($this->user)->delete('/cart');
        $response->assertRedirect();
    }
}
