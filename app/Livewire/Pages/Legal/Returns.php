<?php

namespace App\Livewire\Pages\Legal;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('store.layouts.app')]
class Returns extends Component
{
    public function render()
    {
        return view('livewire.pages.legal.returns');
    }
}
