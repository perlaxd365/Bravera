<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Textarea extends Component
{
    public function __construct(
        public string $label = '',
        public int $rows = 4,
        public ?string $placeholder = null,
    ) {}

    public function render(): View|Closure|string
    {
        return view('components.textarea');
    }
}
