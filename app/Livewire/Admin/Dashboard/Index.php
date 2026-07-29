<?php


namespace App\Livewire\Admin\Dashboard;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('admin.layouts.app')]
class Index extends Component
{
    public function render()
    {
        return view('livewire.admin.dashboard.index');
    }
}
