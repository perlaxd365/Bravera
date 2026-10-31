<?php

namespace App\Livewire\Admin\Settings;

use App\Services\AppSettingsService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('admin.layouts.app')]
class Integrations extends Component
{
    #[Url(as: 'group', except: 'seo')]
    public string $activeGroup = 'seo';

    public array $values = [];

    public array $secrets = [];

    public function mount(): void
    {
        $this->activeGroup = array_key_exists($this->activeGroup, AppSettingsService::GROUPS)
            ? $this->activeGroup
            : 'seo';
        $this->loadValues();
    }

    public function setGroup(string $group): void
    {
        if (! array_key_exists($group, AppSettingsService::GROUPS)) {
            return;
        }

        $this->activeGroup = $group;
        $this->resetValidation();
    }

    public function save(AppSettingsService $settings): void
    {
        $rules = [];

        foreach (AppSettingsService::FIELDS as $field => $definition) {
            if (! empty($definition['secret'])) {
                $rules['secrets.'.$field] = ['nullable', 'string', 'max:4096'];

                continue;
            }

            if (($definition['type'] ?? null) === 'boolean') {
                $rules['values.'.$field] = ['boolean'];

                continue;
            }

            $rules['values.'.$field] = match ($definition['type'] ?? 'text') {
                'url' => ['nullable', 'url:http,https', 'max:2048'],
                default => ['nullable', 'string', 'max:2048'],
            };
        }

        $this->validate($rules, [
            'values.*.url' => 'Escribe una URL completa que empiece con https://.',
            'secrets.*.max' => 'La credencial es demasiado larga.',
        ]);

        $capiEnabled = filter_var(
            $this->values['meta_capi_enabled'] ?? false,
            FILTER_VALIDATE_BOOL,
        );
        $newCapiToken = trim((string) ($this->secrets['meta_capi_access_token'] ?? ''));

        if ($capiEnabled && $newCapiToken === '' && ! $settings->isConfigured('meta_capi_access_token')) {
            $this->addError(
                'secrets.meta_capi_access_token',
                'Pega el token de Conversions API antes de activarla.',
            );

            return;
        }

        $settings->save($this->values, $this->secrets);
        $settings->applyToConfiguration();
        $this->loadValues();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'La configuración de '.AppSettingsService::GROUPS[$this->activeGroup]['label'].' se guardó correctamente.',
        ]);
    }

    public function render()
    {
        $settings = app(AppSettingsService::class);
        $fields = array_filter(
            AppSettingsService::FIELDS,
            fn (array $definition): bool => $definition['group'] === $this->activeGroup,
        );

        return view('livewire.admin.settings.integrations', [
            'groups' => AppSettingsService::GROUPS,
            'fields' => $fields,
            'configured' => collect(array_keys($fields))
                ->mapWithKeys(fn (string $field): array => [$field => $settings->isConfigured($field)])
                ->all(),
            'summary' => $settings->summary(),
            'metaAdsLinks' => $this->metaAdsLinks(),
        ]);
    }

    /**
     * @return array{app: ?string, business: ?string, ads_manager: ?string}
     */
    private function metaAdsLinks(): array
    {
        $appId = preg_replace('/\D+/', '', (string) config('meta.ads.app_id'));
        $businessId = preg_replace('/\D+/', '', (string) config('meta.ads.business_id'));
        $adAccountId = preg_replace('/\D+/', '', (string) config('meta.ads.ad_account_id'));

        return [
            'app' => $appId !== ''
                ? 'https://developers.facebook.com/apps/'.$appId.'/dashboard/'
                : null,
            'business' => $businessId !== ''
                ? 'https://business.facebook.com/settings/people?business_id='.$businessId
                : null,
            'ads_manager' => $adAccountId !== ''
                ? 'https://adsmanager.facebook.com/adsmanager/manage/campaigns?act='.$adAccountId
                : null,
        ];
    }

    private function loadValues(): void
    {
        $settings = app(AppSettingsService::class);
        $this->values = [];
        $this->secrets = [];

        foreach (AppSettingsService::FIELDS as $field => $definition) {
            if (($definition['type'] ?? null) === 'boolean') {
                $this->values[$field] = (bool) $settings->displayValue($field);

                continue;
            }

            if (! empty($definition['secret'])) {
                $this->secrets[$field] = '';

                continue;
            }

            $this->values[$field] = (string) ($settings->displayValue($field) ?? '');
        }
    }
}
