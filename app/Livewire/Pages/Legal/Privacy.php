<?php

namespace App\Livewire\Pages\Legal;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('store.layouts.app')]
class Privacy extends Component
{
    public function render()
    {
        return view('livewire.pages.legal.privacy');
    }
}
