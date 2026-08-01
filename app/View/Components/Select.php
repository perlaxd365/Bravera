<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Select extends Component
{
    public function __construct(
        public string $label = ''
    ) {}

    public function render(): View|Closure|string
    {
        return view('components.select');
    }
}
