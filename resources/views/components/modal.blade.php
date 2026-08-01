@php
    $wireModel = $attributes->wire('model');
    $model = $wireModel ? $wireModel->value() : null;
@endphp

<div class="modal fade " data-bs-backdrop="static" data-bs-keyboard="false" wire:ignore.self tabindex="-1"
    aria-hidden="true" x-data="{
        open: @entangle($model)
    }"
    x-effect="
        const modal = bootstrap.Modal.getOrCreateInstance($el);

        if (open) {
            modal.show();
        } else {
            modal.hide();
        }
    "
    x-on:hidden.bs.modal="
    open = false;
    $wire.set('{{ $model }}', false);
">

    <div class="modal-dialog {{ $size }} modal-dialog-scrollable">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">
                    {{ $title }}
                </h5>

                <button type="button" class="btn-close"
                    @click="
        open = false;
        $wire.set('{{ $model }}', false);
    ">
                </button>

            </div>

            <div class="modal-body">

                {{ $slot }}

            </div>

        </div>

    </div>

</div>
