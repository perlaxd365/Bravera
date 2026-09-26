@props([
    'name' => null,
    'show' => null,
    'title' => null,
    'subtitle' => null,
    'size' => 'md',
    'focusable' => false,
])

@php
    $wireModel = $attributes->wire('model');
    $model = $wireModel ? $wireModel->value() : null;

    $entangleExpr = $model ? "$wire.entangle('" . str_replace("'", "\\'", $model) . "')" : 'false';

    $widths = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
        '3xl' => 'max-w-3xl',
        '4xl' => 'max-w-4xl',
        'modal-lg' => 'max-w-lg',
        'modal-xl' => 'max-w-2xl',
        'modal-sm' => 'max-w-sm',
    ];
    $width = $widths[$size] ?? $widths['md'];
@endphp

<div
    {{ $attributes->wire('model') ? '' : $attributes->except(['wire:model', 'name', 'show', 'title', 'subtitle', 'size', 'focusable']) }}
    x-data="{
        open: @js((bool) $show) || {{ $entangleExpr }},
        close() {
            @if ($model)
                $wire.set('{{ str_replace("'", "\\'", $model) }}', false);
            @else
                this.open = false;
            @endif
        }
    }"
    x-on:open-modal.window="if ($event.detail === '{{ $name }}') open = true"
    x-on:close-modal.window="if ($event.detail === '{{ $name }}') open = false"
    x-on:close.window="if (open) close()"
    x-on:keydown.escape.window="if (open) close()"
    x-cloak
>
    <div x-show="open" x-transition.opacity.duration.200ms class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" aria-label="{{ $title ?? 'Diálogo' }}">
        <div class="flex min-h-full items-center justify-center p-4" @click="if ($event.target === $el) close()">
            <div class="fixed inset-0 bg-gray-950/40 backdrop-blur-sm" aria-hidden="true"></div>

            <div
                x-show="open"
                x-transition.scale.origin.center.duration.200ms
                class="{{ $width }} relative w-full rounded-2xl border border-gray-200 bg-white shadow-2xl shadow-gray-950/10"
            >
                @if ($title)
                    <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-6 py-4">
                        <div>
                            <h2 class="text-base font-semibold tracking-tight text-gray-900">{{ $title }}</h2>
                            @if ($subtitle)
                                <p class="mt-1 text-sm text-gray-500">{{ $subtitle }}</p>
                            @endif
                        </div>
                        <button type="button" @click="close()" class="rounded-lg p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600" aria-label="Cerrar">
                            <flux:icon name="x-mark" variant="mini" class="size-5" />
                        </button>
                    </div>
                @endif

                <div class="px-6 py-5">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</div>