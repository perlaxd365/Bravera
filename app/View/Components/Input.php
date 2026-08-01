<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Input extends Component
{
    public function __construct(
        public string $label = '',
        public string $type = 'text',
        public ?string $placeholder = null,
    ) {}

    public function render(): View|Closure|string
    {
        return view('components.input');
    }
}
