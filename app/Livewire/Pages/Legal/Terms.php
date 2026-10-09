<?php

namespace App\Livewire\Pages\Legal;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('store.layouts.app')]
class Terms extends Component
{
    public function render()
    {
        return view('livewire.pages.legal.terms');
    }
}
