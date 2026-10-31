<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Catalog\Products\Form;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CourseLocalizationAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RolesAndPermissionsSeeder::class,
            UserSeeder::class,
        ]);
    }

    public function test_admin_can_add_english_course_content_and_material(): void
    {
        $admin = User::where('email', 'administracion@brevare.com')->firstOrFail();
        $this->actingAs($admin);
        $category = Category::create([
            'name' => 'Cursos bilingües',
            'slug' => 'cursos-bilingues-admin',
            'path' => 'cursos-bilingues-admin',
            'is_visible' => true,
        ]);

        $component = Livewire::test(Form::class)
            ->call('create')
            ->set('form.category_id', $category->id)
            ->set('form.product_type', 'course')
            ->set('form.course_price', '99')
            ->set('form.name', 'Curso en español')
            ->set('form.slug', 'curso-bilingue-admin')
            ->set('form.translations.en.name', 'English course')
            ->set('form.translations.en.short_description', 'Learn from anywhere.')
            ->set('form.translations.en.description', 'Complete English course content.')
            ->set('form.status', true)
            ->set('form.is_visible', false)
            ->call('save')
            ->assertHasNoErrors();

        $product = Product::where('slug', 'curso-bilingue-admin')->firstOrFail();

        $this->assertDatabaseHas('product_translations', [
            'product_id' => $product->id,
            'locale' => 'en',
            'name' => 'English course',
            'short_description' => 'Learn from anywhere.',
        ]);

        $component
            ->set('courseAssetLocale', 'en')
            ->set('courseAssetTitle', 'English guide')
            ->set('courseAssetUrl', 'https://example.com/english-guide')
            ->call('saveCourseLink')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('course_assets', [
            'product_id' => $product->id,
            'locale' => 'en',
            'title' => 'English guide',
        ]);
    }
}
