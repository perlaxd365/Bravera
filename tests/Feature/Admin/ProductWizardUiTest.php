<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Catalog\Products\Form;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Models\SupplierVariant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductWizardUiTest extends TestCase
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

    public function test_product_registration_uses_the_five_step_wizard(): void
    {
        $this->actingAs(User::where('email', 'administracion@brevare.com')->firstOrFail());

        Livewire::test(Form::class)
            ->call('create')
            ->assertSet('show', true)
            ->assertSet('currentStep', 1)
            ->assertSee('Registrar producto')
            ->assertSee('Paso 1 de 5')
            ->call('goToStep', 4)
            ->assertSet('currentStep', 4)
            ->call('goToStep', 9)
            ->assertSet('currentStep', 5)
            ->call('goToStep', -2)
            ->assertSet('currentStep', 1);
    }

    public function test_wizard_content_is_not_nested_inside_the_desktop_navigation(): void
    {
        $this->actingAs(User::where('email', 'administracion@brevare.com')->firstOrFail());

        $html = Livewire::test(Form::class)
            ->call('create')
            ->html();

        $document = new \DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML($html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($document);
        $content = $xpath->query("//*[@id='product-wizard-content']")->item(0);

        $this->assertNotNull($content);
        $this->assertSame(0, $xpath->query('ancestor::aside', $content)->length);
    }

    public function test_supplier_numeric_fields_can_be_cleared_before_entering_values(): void
    {
        $this->actingAs(User::where('email', 'administracion@brevare.com')->firstOrFail());

        $category = Category::create([
            'name' => 'Proveedores',
            'slug' => 'proveedores',
            'path' => 'proveedores',
            'is_visible' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Producto con proveedor',
            'slug' => 'producto-con-proveedor',
            'status' => true,
            'is_visible' => false,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'PROV-001',
            'sale_price' => 100,
            'cost_price' => 30,
            'is_active' => true,
            'is_default' => true,
        ]);

        $supplier = Supplier::create([
            'code' => 'ENKASA-HOGAR',
            'business_name' => 'Enkasa Hogar',
            'status' => 'active',
        ]);

        Livewire::test(Form::class)
            ->call('edit', $product->id)
            ->call('createSupplierVariant', $variant->id)
            ->assertSet('supplierVariantForm.stock', 0)
            ->set('supplierVariantForm.stock', '')
            ->assertSet('supplierVariantForm.stock', null)
            ->set('supplierVariantForm.cost_price', '')
            ->assertSet('supplierVariantForm.cost_price', null)
            ->set('supplierVariantForm.stock', '12')
            ->assertSet('supplierVariantForm.stock', 12)
            ->set('supplierVariantForm.cost_price', '25.50')
            ->assertSet('supplierVariantForm.cost_price', 25.5)
            ->set('supplierVariantForm.supplier_id', $supplier->id)
            ->set('supplierVariantForm.shipping_cost', '0')
            ->set('supplierVariantForm.supplier_sale_price', '49.90')
            ->set('supplierVariantForm.reserved_stock', '0')
            ->set('supplierVariantForm.minimum_stock', '1')
            ->set('supplierVariantForm.estimated_dispatch_days', '2')
            ->call('saveSupplierVariant')
            ->assertHasNoErrors()
            ->assertSee('Editar proveedor')
            ->assertSee('Actualizar proveedor');

        $this->assertDatabaseHas('supplier_variants', [
            'supplier_id' => $supplier->id,
            'product_variant_id' => $variant->id,
            'cost_price' => 25.50,
            'stock' => 12,
            'minimum_stock' => 1,
        ]);
    }

    public function test_full_product_registration_flow_saves_product_variant_and_supplier(): void
    {
        $this->actingAs(User::where('email', 'administracion@brevare.com')->firstOrFail());

        $category = Category::create([
            'name' => 'Hogar',
            'slug' => 'hogar',
            'path' => 'hogar',
            'is_visible' => true,
        ]);

        $supplier = Supplier::create([
            'code' => 'ENKASA-HOGAR-FULL',
            'business_name' => 'Enkasa Hogar',
            'status' => 'active',
        ]);

        $component = Livewire::test(Form::class)
            ->call('create')
            ->set('form.category_id', $category->id)
            ->set('form.name', 'Juego de copas')
            ->set('form.slug', 'juego-de-copas')
            ->set('form.status', true)
            ->set('form.is_visible', false)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('currentStep', 2);

        $product = Product::where('slug', 'juego-de-copas')->firstOrFail();

        $component
            ->call('createVariant')
            ->set('variantForm.sku', 'COPAS-001')
            ->set('variantForm.cost_price', '30')
            ->set('variantForm.sale_price', '100')
            ->set('variantForm.retail_price', '100')
            ->set('variantForm.discount_percent', '0')
            ->call('saveVariant')
            ->assertHasNoErrors();

        $variant = ProductVariant::where('sku', 'COPAS-001')->firstOrFail();

        $component
            ->call('createSupplierVariant', $variant->id)
            ->set('supplierVariantForm.supplier_id', $supplier->id)
            ->set('supplierVariantForm.cost_price', '25.50')
            ->set('supplierVariantForm.shipping_cost', '0')
            ->set('supplierVariantForm.supplier_sale_price', '49.90')
            ->set('supplierVariantForm.stock', '12')
            ->set('supplierVariantForm.reserved_stock', '0')
            ->set('supplierVariantForm.minimum_stock', '1')
            ->set('supplierVariantForm.estimated_dispatch_days', '2')
            ->call('saveSupplierVariant')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Juego de copas',
        ]);
        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'sku' => 'COPAS-001',
        ]);
        $this->assertDatabaseHas('supplier_variants', [
            'supplier_id' => $supplier->id,
            'product_variant_id' => $variant->id,
            'stock' => 12,
        ]);

        $component
            ->call('createSupplierVariant', $variant->id)
            ->set('supplierVariantForm.supplier_id', $supplier->id)
            ->set('supplierVariantForm.cost_price', '25.50')
            ->set('supplierVariantForm.shipping_cost', '0')
            ->set('supplierVariantForm.supplier_sale_price', '49.90')
            ->set('supplierVariantForm.stock', '8')
            ->set('supplierVariantForm.reserved_stock', '0')
            ->set('supplierVariantForm.minimum_stock', '1')
            ->set('supplierVariantForm.estimated_dispatch_days', '2')
            ->call('saveSupplierVariant')
            ->assertHasErrors([
                'supplierVariantForm.supplier_id' => ['unique'],
            ]);

        $this->assertSame(
            1,
            SupplierVariant::where('product_variant_id', $variant->id)
                ->where('supplier_id', $supplier->id)
                ->count(),
        );
    }
}
