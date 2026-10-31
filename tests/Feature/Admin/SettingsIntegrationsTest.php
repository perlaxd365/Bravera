<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Settings\Integrations;
use App\Models\AppSetting;
use App\Models\User;
use App\Services\AppSettingsService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsIntegrationsTest extends TestCase
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

    public function test_admin_can_open_settings_sections(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Configuración de Brevare');

        $this->get(route('admin.settings.integrations'))
            ->assertOk()
            ->assertSee('Integraciones y credenciales');

        $this->get(route('admin.settings.homepage'))
            ->assertOk()
            ->assertSee('Contenido de la portada');
    }

    public function test_meta_ads_settings_include_required_identifiers_and_direct_links(): void
    {
        config([
            'meta.ads.app_id' => '1639712417849450',
            'meta.ads.business_id' => '894366043768471',
            'meta.ads.ad_account_id' => 'act_828589206521308',
        ]);

        $this->actingAs($this->admin());

        Livewire::test(Integrations::class)
            ->set('activeGroup', 'meta_ads')
            ->assertSee('ID de aplicación de Meta')
            ->assertSee('ID de empresa de Meta')
            ->assertSee('ID de cuenta publicitaria')
            ->assertSee('ID de página')
            ->assertSee('Token de Marketing API')
            ->assertSee('Abrir aplicación')
            ->assertSee('Abrir empresa')
            ->assertSee('Autenticar en Ads Manager');
    }

    public function test_optional_meta_ads_fields_do_not_block_connection_readiness(): void
    {
        config([
            'meta.ads.enabled' => true,
            'meta.ads.app_id' => '1639712417849450',
            'meta.ads.business_id' => '894366043768471',
            'meta.ads.ad_account_id' => 'act_828589206521308',
            'meta.ads.page_id' => '1444215782099345',
            'meta.ads.access_token' => 'private-token',
            'meta.ads.instagram_actor_id' => null,
            'meta.ads.app_secret' => null,
        ]);

        $summary = app(AppSettingsService::class)->summary()['meta_ads'];

        $this->assertSame(6, $summary['total']);
        $this->assertSame(6, $summary['configured']);
        $this->assertTrue($summary['ready']);
    }

    public function test_admin_can_update_public_urls_and_encrypted_credentials(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(Integrations::class)
            ->set('values.seo_organization_image', 'https://cdn.example.com/brevare-logo.png')
            ->set('values.cloudinary_cloud_name', 'brevare-media')
            ->set('secrets.cloudinary_api_secret', 'super-secret-value')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDontSee('super-secret-value');

        $record = AppSetting::query()
            ->where('key', 'cloudinary_api_secret')
            ->firstOrFail();

        $this->assertTrue($record->is_encrypted);
        $this->assertNotSame('super-secret-value', $record->value);
        $this->assertSame('super-secret-value', Crypt::decryptString($record->value));
        $this->assertSame('brevare-media', config('services.cloudinary.cloud_name'));
        $this->assertSame(
            'https://cdn.example.com/brevare-logo.png',
            config('seo.organization_image'),
        );
    }

    public function test_customer_cannot_open_settings(): void
    {
        $customer = User::create([
            'name' => 'Cliente Demo',
            'email' => 'cliente-settings@test.com',
            'password' => bcrypt('12345678'),
            'email_verified_at' => now(),
        ]);
        $customer->assignRole('Cliente');

        $this->actingAs($customer)
            ->get(route('admin.settings.integrations'))
            ->assertForbidden();
    }

    public function test_capi_cannot_be_enabled_without_its_token(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(Integrations::class)
            ->set('activeGroup', 'meta')
            ->set('values.meta_capi_enabled', true)
            ->call('save')
            ->assertHasErrors('secrets.meta_capi_access_token');

        $this->assertDatabaseMissing('app_settings', [
            'key' => 'meta_capi_enabled',
            'value' => '1',
        ]);
    }

    public function test_capi_toggle_and_token_are_persisted_atomically(): void
    {
        $this->actingAs($this->admin());

        $component = Livewire::test(Integrations::class)
            ->set('activeGroup', 'meta')
            ->set('values.meta_capi_enabled', true)
            ->set('secrets.meta_capi_access_token', 'capi-private-token')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('values.meta_capi_enabled', true)
            ->assertSet('secrets.meta_capi_access_token', '');

        $component->assertSee('Configurado');

        $this->assertDatabaseHas('app_settings', [
            'key' => 'meta_capi_enabled',
            'value' => '1',
        ]);
        $this->assertSame(
            'capi-private-token',
            app(AppSettingsService::class)
                ->displayValue('meta_capi_access_token'),
        );
    }

    private function admin(): User
    {
        return User::where('email', 'administracion@brevare.com')->firstOrFail();
    }
}
