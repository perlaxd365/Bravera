<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Catalog\Suppliers\Form;
use App\Models\Location;
use App\Models\Supplier;
use App\Modules\Location\Enums\LocationLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SupplierEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_can_be_edited_and_saved(): void
    {
        $department = Location::create([
            'ubigeo' => '900000',
            'name' => 'Lima',
            'level' => LocationLevel::DEPARTMENT,
        ]);
        $province = Location::create([
            'ubigeo' => '900001',
            'name' => 'Lima',
            'level' => LocationLevel::PROVINCE,
            'parent_id' => $department->id,
        ]);
        $district = Location::create([
            'ubigeo' => '900002',
            'name' => 'Miraflores',
            'level' => LocationLevel::DISTRICT,
            'parent_id' => $province->id,
        ]);
        $supplier = Supplier::create([
            'code' => 'SUP000101',
            'business_name' => 'Proveedor Original S.A.C.',
            'location_id' => $district->id,
            'estimated_dispatch_days' => 3,
            'status' => 'active',
        ]);

        Livewire::test(Form::class)
            ->dispatch('supplier-edit', id: $supplier->id)
            ->assertSet('show', true)
            ->assertSet('form.code', 'SUP000101')
            ->set('form.business_name', 'Proveedor Actualizado S.A.C.')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('show', false);

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'code' => 'SUP000101',
            'business_name' => 'Proveedor Actualizado S.A.C.',
        ]);
    }

    public function test_legacy_supplier_without_location_and_zero_dispatch_days_can_be_edited(): void
    {
        $supplier = Supplier::create([
            'code' => 'SUP000102',
            'business_name' => 'Proveedor antiguo S.A.C.',
            'location_id' => null,
            'estimated_dispatch_days' => 0,
            'status' => 'active',
        ]);

        Livewire::test(Form::class)
            ->dispatch('supplier-edit', id: $supplier->id)
            ->set('form.business_name', 'Proveedor antiguo actualizado S.A.C.')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('show', false);

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'business_name' => 'Proveedor antiguo actualizado S.A.C.',
            'location_id' => null,
            'estimated_dispatch_days' => 0,
        ]);
    }
}
