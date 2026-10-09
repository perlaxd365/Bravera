<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Catalog\Reviews\Index as ReviewsIndex;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductReviewModerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RolesAndPermissionsSeeder::class, UserSeeder::class]);
    }

    private function makeProduct(): Product
    {
        $category = Category::create([
            'name' => 'Pruebas',
            'slug' => 'pruebas',
            'path' => 'pruebas',
            'is_visible' => true,
        ]);

        return Product::create([
            'category_id' => $category->id,
            'name' => 'Producto reseñado',
            'slug' => 'producto-resenado',
            'status' => true,
            'is_visible' => true,
        ]);
    }

    public function test_new_reviews_start_pending_and_are_not_public(): void
    {
        $product = $this->makeProduct();
        $customer = User::create([
            'name' => 'Cliente Reseña',
            'email' => 'cliente@example.test',
            'password' => bcrypt('password'),
        ]);
        $customer->assignRole('Cliente');

        $review = ProductReview::create([
            'product_id' => $product->id,
            'user_id' => $customer->id,
            'rating' => 5,
            'comment' => 'Excelente producto, muy recomendado.',
            'status' => ProductReview::STATUS_PENDING,
        ]);

        $this->assertSame(ProductReview::STATUS_PENDING, $review->status);
        $this->assertSame(0, $product->reviews()->approved()->count());
    }

    public function test_admin_can_approve_a_review(): void
    {
        $product = $this->makeProduct();
        $customer = User::create([
            'name' => 'Cliente Reseña',
            'email' => 'cliente@example.test',
            'password' => bcrypt('password'),
        ]);
        $customer->assignRole('Cliente');

        $review = ProductReview::create([
            'product_id' => $product->id,
            'user_id' => $customer->id,
            'rating' => 4,
            'comment' => 'Muy buena atención.',
            'status' => ProductReview::STATUS_PENDING,
        ]);

        $admin = User::where('email', 'administracion@brevare.com')->firstOrFail();

        Livewire::actingAs($admin)
            ->test(ReviewsIndex::class)
            ->call('approve', $review->id)
            ->assertHasNoErrors();

        $this->assertSame(ProductReview::STATUS_APPROVED, $review->fresh()->status);
        $this->assertSame(1, $product->reviews()->approved()->count());
    }
}
