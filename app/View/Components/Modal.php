<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Modal extends Component
{
    /**
     * Tamaño del modal.
     */
    public string $size;

    /**
     * Título del modal.
     */
    public string $title;

    public function __construct(
        string $title = '',
        string $size = 'modal-lg'
    ) {
        $this->title = $title;
        $this->size = $size;
    }

    public function render(): View|Closure|string
    {
        return view('components.modal');
    }
}
