<?php

namespace App\Livewire\Admin\Settings;

use App\Services\AppSettingsService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('admin.layouts.app')]
class Index extends Component
{
    public function render()
    {
        return view('livewire.admin.settings.index', [
            'integrationGroups' => app(AppSettingsService::class)->summary(),
        ]);
    }
}
