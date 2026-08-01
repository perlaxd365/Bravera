@php
    $wireModel = $attributes->wire('model');
    $model = $wireModel ? $wireModel->value() : null;
@endphp

<div class="mb-3">

    @if ($label)
        <label class="form-label">
            {{ $label }}
        </label>
    @endif

    <select @class([
        'form-select',
        'is-invalid' => $model && $errors->has($model),
    ]) {{ $attributes }}>
        {{ $slot }}
    </select>

    @if ($model)
        @error($model)
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    @endif

</div>
