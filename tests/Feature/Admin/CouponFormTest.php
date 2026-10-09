<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Discounts\Coupons\Form;
use App\Models\Coupon;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CouponFormTest extends TestCase
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

    private function actingAsAdmin(): User
    {
        $admin = User::where('email', 'administracion@brevare.com')->firstOrFail();

        $this->actingAs($admin);

        return $admin;
    }

    public function test_crea_el_cupon_con_los_valores_por_defecto_cuando_se_dejan_vacios(): void
    {
        $this->actingAsAdmin();

        Livewire::test(Form::class)
            ->call('create')
            ->set('form.code', 'MISI-10')
            ->set('form.name', 'Descuento Michi al 10%')
            ->set('form.type', 'percentage')
            ->set('form.value', '10')
            ->set('form.min_subtotal', '')
            ->set('form.per_user_limit', '')
            ->set('form.max_discount', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('coupons', [
            'code' => 'MISI-10',
            'name' => 'Descuento Michi al 10%',
            'type' => 'percentage',
            'value' => 10,
            'is_active' => true,
        ]);

        $coupon = Coupon::where('code', 'MISI-10')->firstOrFail();

        // min_subtotal y per_user_limit son NOT NULL: vacío = sin mínimo / un uso.
        $this->assertSame(0.0, (float) $coupon->min_subtotal);
        $this->assertSame(1, (int) $coupon->per_user_limit);
        $this->assertNull($coupon->max_discount);
    }

    public function test_crea_el_cupon_con_el_subtotal_minimo_ingresado(): void
    {
        $this->actingAsAdmin();

        Livewire::test(Form::class)
            ->call('create')
            ->set('form.code', 'BIENVENIDO20')
            ->set('form.name', 'Bienvenida 20 soles off')
            ->set('form.type', 'fixed')
            ->set('form.value', '20')
            ->set('form.min_subtotal', '150.50')
            ->set('form.per_user_limit', '2')
            ->call('save')
            ->assertHasNoErrors();

        $coupon = Coupon::where('code', 'BIENVENIDO20')->firstOrFail();

        $this->assertSame(150.50, (float) $coupon->min_subtotal);
        $this->assertSame(2, (int) $coupon->per_user_limit);
    }

    public function test_actualiza_el_cupon_sin_tocar_los_campos_obligatorios(): void
    {
        $admin = $this->actingAsAdmin();

        $coupon = Coupon::create([
            'code' => 'PROMO-01',
            'name' => 'Promo original',
            'type' => 'percentage',
            'value' => 15,
            'min_subtotal' => 0,
            'per_user_limit' => 1,
            'applies_to' => 'all',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        Livewire::test(Form::class)
            ->call('edit', $coupon->id)
            ->set('form.name', 'Promo renamed')
            ->set('form.min_subtotal', '')
            ->set('form.per_user_limit', '')
            ->call('save')
            ->assertHasNoErrors();

        $coupon->refresh();

        $this->assertSame('Promo renamed', $coupon->name);
        $this->assertSame(0.0, (float) $coupon->min_subtotal);
        $this->assertSame(1, (int) $coupon->per_user_limit);
    }
}
