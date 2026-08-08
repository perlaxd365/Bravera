<div class="modal fade @if ($show) show d-block @endif" tabindex="-1"
    @if ($show) style="background: rgba(0,0,0,.5);" @endif>

    <div class="modal-dialog modal-xl">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">

                    {{ $form->id ? 'Editar Producto' : 'Nuevo Producto' }}

                </h5>

                <button type="button" class="btn-close" wire:click="$set('show', false)"></button>

            </div>

            <form wire:submit="save">

                <div class="modal-body">

                    {{-- ===================================================== --}}
                    {{-- INFORMACIÓN DEL PRODUCTO --}}
                    {{-- ===================================================== --}}

                    <div class="row">

                        <div class="col-md-8 mb-3">

                            <label class="form-label">
                                Nombre
                            </label>

                            <input type="text" class="form-control" wire:model.blur="form.name">

                            @error('form.name')
                                <small class="text-danger">
                                    {{ $message }}
                                </small>
                            @enderror

                        </div>

                        <div class="col-md-4 mb-3">

                            <x-select label="Categoría" wire:model.live="form.category_id">

                                <option value="">
                                    Seleccione...
                                </option>

                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">
                                        {{ $category->name }}
                                    </option>
                                @endforeach

                            </x-select>

                            @error('form.category_id')
                                <small class="text-danger">
                                    {{ $message }}
                                </small>
                            @enderror

                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-4 mb-3">

                            <x-select label="Marca" wire:model.live="form.brand_id">

                                <option value="">
                                    Seleccione...
                                </option>

                                @foreach ($brands as $brand)
                                    <option value="{{ $brand->id }}">
                                        {{ $brand->name }}
                                    </option>
                                @endforeach

                            </x-select>

                            @error('form.brand_id')
                                <small class="text-danger">
                                    {{ $message }}
                                </small>
                            @enderror

                        </div>

                        <div class="col-md-4 mb-3">

                            <label class="form-label">
                                Slug
                            </label>

                            <input type="text" class="form-control" wire:model="form.slug">

                            @error('form.slug')
                                <small class="text-danger">
                                    {{ $message }}
                                </small>
                            @enderror

                        </div>

                        <div class="col-md-4">

                            <div class="form-check mt-4">

                                <input class="form-check-input" type="checkbox" wire:model="form.status">

                                <label class="form-check-label">
                                    Activo
                                </label>

                            </div>

                        </div>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            Descripción corta
                        </label>

                        <textarea rows="2" class="form-control" wire:model="form.short_description"></textarea>

                        @error('form.short_description')
                            <small class="text-danger">
                                {{ $message }}
                            </small>
                        @enderror

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            Descripción
                        </label>

                        <textarea rows="5" class="form-control" wire:model="form.description"></textarea>

                        @error('form.description')
                            <small class="text-danger">
                                {{ $message }}
                            </small>
                        @enderror

                    </div>

                    <div class="row">

                        <div class="col-md-6">

                            <div class="form-check">

                                <input class="form-check-input" type="checkbox" wire:model="form.is_featured">

                                <label class="form-check-label">
                                    Producto destacado
                                </label>

                            </div>

                        </div>

                        <div class="col-md-6">

                            <div class="form-check">

                                <input class="form-check-input" type="checkbox" wire:model="form.is_visible">

                                <label class="form-check-label">
                                    Visible en tienda
                                </label>

                            </div>

                        </div>

                    </div>


                    {{-- ===================================================== --}}
                    {{-- VARIANTES --}}
                    {{-- ===================================================== --}}

                    @if ($form->id)

                        <hr class="my-4">

                        <div class="d-flex justify-content-between align-items-center mb-3">

                            <div>

                                <h5 class="mb-1">
                                    Variantes
                                </h5>

                                <small class="text-muted">
                                    Administra las variantes de este producto.
                                </small>

                            </div>

                            <button type="button" class="btn btn-outline-primary btn-sm" wire:click="createVariant">
                                <i class="bi bi-plus-lg"></i>
                                Nueva variante
                            </button>

                        </div>


                        {{-- ================================================= --}}
                        {{-- FORMULARIO DE VARIANTE --}}
                        {{-- ================================================= --}}

                        <div class="card border mb-4">

                            <div class="card-header bg-light">

                                <strong>
                                    {{ $variantForm->id ? 'Editar variante' : 'Nueva variante' }}
                                </strong>

                            </div>

                            <div class="card-body">

                                <div class="row">

                                    <div class="col-md-4 mb-3">

                                        <label class="form-label">
                                            SKU
                                        </label>

                                        <input type="text" class="form-control" wire:model.blur="variantForm.sku">

                                        @error('variantForm.sku')
                                            <small class="text-danger">
                                                {{ $message }}
                                            </small>
                                        @enderror

                                    </div>

                                    <div class="col-md-4 mb-3">

                                        <label class="form-label">
                                            Código de barras
                                        </label>

                                        <input type="text" class="form-control"
                                            wire:model.blur="variantForm.barcode">

                                        @error('variantForm.barcode')
                                            <small class="text-danger">
                                                {{ $message }}
                                            </small>
                                        @enderror

                                    </div>

                                    <div class="col-md-4 mb-3">

                                        <label class="form-label">
                                            Precio de venta
                                        </label>

                                        <input type="number" step="0.01" min="0" class="form-control"
                                            wire:model.blur="variantForm.sale_price">

                                        @error('variantForm.sale_price')
                                            <small class="text-danger">
                                                {{ $message }}
                                            </small>
                                        @enderror

                                    </div>

                                </div>

                                <div class="row">

                                    <div class="col-md-4 mb-3">

                                        <label class="form-label">
                                            Costo de adquisición
                                        </label>

                                        <input type="number" step="0.01" min="0" class="form-control"
                                            wire:model.blur="variantForm.cost_price">

                                        @error('variantForm.cost_price')
                                            <small class="text-danger">
                                                {{ $message }}
                                            </small>
                                        @enderror

                                    </div>

                                    <div class="col-md-4 mb-3">

                                        <label class="form-label">
                                            Precio referencial
                                        </label>

                                        <input type="number" step="0.01" min="0" class="form-control"
                                            wire:model.blur="variantForm.compare_price">

                                        @error('variantForm.compare_price')
                                            <small class="text-danger">
                                                {{ $message }}
                                            </small>
                                        @enderror

                                    </div>

                                </div>
                                {{-- ================================================= --}}
                                {{-- ATRIBUTOS DE LA VARIANTE --}}
                                {{-- ================================================= --}}

                                @if ($attributes->isNotEmpty())

                                    <div class="row">

                                        @foreach ($attributes as $attribute)
                                            <div class="col-md-4 mb-3">

                                                <label class="form-label">
                                                    {{ $attribute->name }}

                                                    @if ($attribute->is_required)
                                                        <span class="text-danger">*</span>
                                                    @endif
                                                </label>

                                                <select class="form-select"
                                                    wire:model.live="variantForm.attribute_values.{{ $attribute->id }}">

                                                    <option value="">
                                                        Seleccione...
                                                    </option>

                                                    @foreach ($attribute->values as $value)
                                                        <option value="{{ $value->id }}">
                                                            {{ $value->value }}
                                                        </option>
                                                    @endforeach

                                                </select>

                                                @error("variantForm.attribute_values.{$attribute->id}")
                                                    <small class="text-danger">
                                                        {{ $message }}
                                                    </small>
                                                @enderror

                                            </div>
                                        @endforeach

                                    </div>

                                @endif

                                <div class="row">

                                    <div class="col-md-3 mb-3">

                                        <label class="form-label">
                                            Peso (kg)
                                        </label>

                                        <input type="number" step="0.01" min="0" class="form-control"
                                            wire:model.blur="variantForm.weight">

                                    </div>

                                    <div class="col-md-3 mb-3">

                                        <label class="form-label">
                                            Largo (cm)
                                        </label>

                                        <input type="number" step="0.01" min="0" class="form-control"
                                            wire:model.blur="variantForm.length">

                                    </div>

                                    <div class="col-md-3 mb-3">

                                        <label class="form-label">
                                            Ancho (cm)
                                        </label>

                                        <input type="number" step="0.01" min="0" class="form-control"
                                            wire:model.blur="variantForm.width">

                                    </div>

                                    <div class="col-md-3 mb-3">

                                        <label class="form-label">
                                            Alto (cm)
                                        </label>

                                        <input type="number" step="0.01" min="0" class="form-control"
                                            wire:model.blur="variantForm.height">

                                    </div>

                                </div>


                                <div class="row">

                                    <div class="col-md-4">

                                        <div class="form-check">

                                            <input class="form-check-input" type="checkbox"
                                                wire:model="variantForm.sync_enabled">

                                            <label class="form-check-label">
                                                Sincronizar proveedores
                                            </label>

                                        </div>

                                    </div>

                                    <div class="col-md-4">

                                        <div class="form-check">

                                            <input class="form-check-input" type="checkbox"
                                                wire:model="variantForm.is_default">

                                            <label class="form-check-label">
                                                Variante principal
                                            </label>

                                        </div>

                                    </div>

                                    <div class="col-md-4">

                                        <div class="form-check">

                                            <input class="form-check-input" type="checkbox"
                                                wire:model="variantForm.is_active">

                                            <label class="form-check-label">
                                                Activa
                                            </label>

                                        </div>

                                    </div>

                                </div>

                            </div>

                            <div class="card-footer d-flex justify-content-end gap-2">

                                <button type="button" class="btn btn-light" wire:click="createVariant">
                                    Limpiar
                                </button>

                                <button type="button" class="btn btn-primary" wire:click="saveVariant">
                                    {{ $variantForm->id ? 'Actualizar variante' : 'Guardar variante' }}
                                </button>

                            </div>

                        </div>


                        {{-- ================================================= --}}
                        {{-- LISTADO DE VARIANTES --}}
                        {{-- ================================================= --}}

                        <div class="table-responsive">

                            <table class="table table-sm table-hover align-middle">

                                <thead class="table-light">

                                    <tr>

                                        <th>
                                            SKU
                                        </th>

                                        <th>
                                            Código
                                        </th>

                                        <th class="text-end">
                                            Precio
                                        </th>

                                        <th class="text-center">
                                            Principal
                                        </th>

                                        <th class="text-center">
                                            Estado
                                        </th>

                                        <th class="text-end">
                                            Acciones
                                        </th>

                                    </tr>

                                </thead>

                                <tbody>

                                    @forelse ($variants as $variant)
                                        <tr>

                                            <td>

                                                <strong>
                                                    {{ $variant->sku }}
                                                </strong>

                                            </td>

                                            <td>

                                                {{ $variant->barcode ?: '—' }}

                                            </td>

                                            <td class="text-end">

                                                S/
                                                {{ number_format((float) $variant->sale_price, 2) }}

                                            </td>

                                            <td class="text-center">

                                                @if ($variant->is_default)
                                                    <span class="badge bg-primary">
                                                        Principal
                                                    </span>
                                                @else
                                                    <span class="text-muted">
                                                        —
                                                    </span>
                                                @endif

                                            </td>

                                            <td class="text-center">

                                                @if ($variant->is_active)
                                                    <span class="badge bg-success">
                                                        Activa
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary">
                                                        Inactiva
                                                    </span>
                                                @endif

                                            </td>

                                            <td class="text-end">

                                                <div class="btn-group btn-group-sm">

                                                    <button type="button" class="btn btn-outline-primary"
                                                        wire:click="editVariant({{ $variant->id }})">
                                                        <i class="bi bi-pencil"></i>
                                                        Editar
                                                    </button>

                                                    <button type="button" class="btn btn-outline-info"
                                                        wire:click="createSupplierVariant({{ $variant->id }})">
                                                        <i class="bi bi-truck"></i>
                                                        Proveedores
                                                    </button>

                                                    <button type="button" class="btn btn-outline-secondary"
                                                        wire:click="toggleVariantStatus({{ $variant->id }})">
                                                        {{ $variant->is_active ? 'Desactivar' : 'Activar' }}
                                                    </button>

                                                    <button type="button" class="btn btn-outline-danger"
                                                        wire:click="deleteVariant({{ $variant->id }})"
                                                        wire:confirm="¿Deseas eliminar esta variante?">
                                                        <i class="bi bi-trash"></i>
                                                        Eliminar
                                                    </button>

                                                </div>

                                            </td>

                                        </tr>

                                    @empty

                                        <tr>

                                            <td colspan="6" class="text-center text-muted py-4">

                                                Este producto todavía no tiene variantes.

                                            </td>

                                        </tr>
                                    @endforelse

                                </tbody>

                            </table>

                        </div>
                        {{-- ================================================= --}}
                        {{-- PROVEEDORES DE LA VARIANTE --}}
                        {{-- ================================================= --}}

                        @if ($supplierVariantVariantId)

                            <hr class="my-4">

                            <div class="d-flex justify-content-between align-items-center mb-3">

                                <div>
                                    <h5 class="mb-1">
                                        <i class="bi bi-truck me-1"></i>
                                        Proveedores de la variante
                                    </h5>

                                    <small class="text-muted">
                                        Administra los proveedores asociados a esta variante.
                                    </small>
                                </div>

                                <button type="button" class="btn btn-outline-primary btn-sm"
                                    wire:click="createSupplierVariant({{ $supplierVariantVariantId }})">
                                    <i class="bi bi-plus-lg me-1"></i>
                                    Nuevo proveedor
                                </button>

                            </div>

                            {{-- FORMULARIO --}}
                            <div class="card border mb-4">

                                <div class="card-header bg-light">

                                    <strong>
                                        {{ $supplierVariantForm->id ? 'Editar proveedor' : 'Nuevo proveedor' }}
                                    </strong>

                                </div>

                                <div class="card-body">

                                    <div class="row">

                                        {{-- Proveedor --}}
                                        <div class="col-md-6 mb-3">

                                            <label class="form-label">
                                                Proveedor
                                            </label>

                                            <select class="form-select"
                                                wire:model.live="supplierVariantForm.supplier_id">

                                                <option value="">
                                                    Seleccione un proveedor...
                                                </option>

                                                @foreach ($suppliers as $supplier)
                                                    <option value="{{ $supplier->id }}">
                                                        {{ $supplier->trade_name ?: $supplier->business_name }}
                                                    </option>
                                                @endforeach

                                            </select>

                                            @error('supplierVariantForm.supplier_id')
                                                <small class="text-danger">
                                                    {{ $message }}
                                                </small>
                                            @enderror

                                        </div>

                                        {{-- SKU proveedor --}}
                                        <div class="col-md-6 mb-3">

                                            <label class="form-label">
                                                SKU del proveedor
                                            </label>

                                            <input type="text" class="form-control"
                                                wire:model.blur="supplierVariantForm.supplier_sku">

                                            @error('supplierVariantForm.supplier_sku')
                                                <small class="text-danger">
                                                    {{ $message }}
                                                </small>
                                            @enderror

                                        </div>

                                        {{-- URL --}}
                                        <div class="col-md-12 mb-3">

                                            <label class="form-label">
                                                URL del producto
                                            </label>

                                            <input type="url" class="form-control"
                                                wire:model.blur="supplierVariantForm.supplier_product_url"
                                                placeholder="https://proveedor.com/producto">

                                            @error('supplierVariantForm.supplier_product_url')
                                                <small class="text-danger">
                                                    {{ $message }}
                                                </small>
                                            @enderror

                                        </div>

                                    </div>

                                    <div class="row">

                                        {{-- Costo --}}
                                        <div class="col-md-4 mb-3">

                                            <label class="form-label">
                                                Costo
                                            </label>

                                            <input type="number" step="0.01" min="0" class="form-control"
                                                wire:model.blur="supplierVariantForm.cost_price">

                                        </div>

                                        {{-- Envío --}}
                                        <div class="col-md-4 mb-3">

                                            <label class="form-label">
                                                Costo de envío
                                            </label>

                                            <input type="number" step="0.01" min="0" class="form-control"
                                                wire:model.blur="supplierVariantForm.shipping_cost">

                                        </div>

                                        {{-- Precio proveedor --}}
                                        <div class="col-md-4 mb-3">

                                            <label class="form-label">
                                                Precio del proveedor
                                            </label>

                                            <input type="number" step="0.01" min="0" class="form-control"
                                                wire:model.blur="supplierVariantForm.supplier_sale_price">

                                        </div>

                                    </div>

                                    <div class="row">

                                        {{-- Stock --}}
                                        <div class="col-md-4 mb-3">

                                            <label class="form-label">
                                                Stock
                                            </label>

                                            <input type="number" min="0" class="form-control"
                                                wire:model.blur="supplierVariantForm.stock">

                                        </div>

                                        {{-- Stock reservado --}}
                                        <div class="col-md-4 mb-3">

                                            <label class="form-label">
                                                Stock reservado
                                            </label>

                                            <input type="number" min="0" class="form-control"
                                                wire:model.blur="supplierVariantForm.reserved_stock">

                                        </div>

                                        {{-- Stock mínimo --}}
                                        <div class="col-md-4 mb-3">

                                            <label class="form-label">
                                                Stock mínimo
                                            </label>

                                            <input type="number" min="0" class="form-control"
                                                wire:model.blur="supplierVariantForm.minimum_stock">

                                        </div>

                                    </div>

                                    <div class="row">

                                        {{-- Días despacho --}}
                                        <div class="col-md-4 mb-3">

                                            <label class="form-label">
                                                Días de despacho
                                            </label>

                                            <input type="number" min="0" class="form-control"
                                                wire:model.blur="supplierVariantForm.estimated_dispatch_days">

                                        </div>

                                        {{-- Principal --}}
                                        <div class="col-md-4">

                                            <div class="form-check mt-4">

                                                <input class="form-check-input" type="checkbox"
                                                    wire:model="supplierVariantForm.is_default">

                                                <label class="form-check-label">
                                                    Proveedor principal
                                                </label>

                                            </div>

                                        </div>

                                        {{-- Activo --}}
                                        <div class="col-md-4">

                                            <div class="form-check mt-4">

                                                <input class="form-check-input" type="checkbox"
                                                    wire:model="supplierVariantForm.is_active">

                                                <label class="form-check-label">
                                                    Proveedor activo
                                                </label>

                                            </div>

                                        </div>

                                    </div>

                                    {{-- Notas --}}
                                    <div class="mb-3">

                                        <label class="form-label">
                                            Notas internas
                                        </label>

                                        <textarea rows="3" class="form-control" wire:model.blur="supplierVariantForm.internal_notes"></textarea>

                                    </div>

                                </div>

                                <div class="card-footer d-flex justify-content-end gap-2">

                                    <button type="button" class="btn btn-light"
                                        wire:click="createSupplierVariant({{ $supplierVariantVariantId }})">
                                        Limpiar
                                    </button>

                                    <button type="button" class="btn btn-primary" wire:click="saveSupplierVariant">
                                        {{ $supplierVariantForm->id ? 'Actualizar proveedor' : 'Guardar proveedor' }}
                                    </button>

                                </div>

                            </div>


                            {{-- LISTADO DE PROVEEDORES --}}

                            <div class="table-responsive">

                                <table class="table table-sm table-hover align-middle">

                                    <thead class="table-light">

                                        <tr>

                                            <th>
                                                Proveedor
                                            </th>

                                            <th>
                                                SKU
                                            </th>

                                            <th class="text-end">
                                                Costo
                                            </th>

                                            <th class="text-end">
                                                Stock
                                            </th>

                                            <th class="text-center">
                                                Principal
                                            </th>

                                            <th class="text-center">
                                                Estado
                                            </th>

                                            <th class="text-end">
                                                Acciones
                                            </th>

                                        </tr>

                                    </thead>

                                    <tbody>

                                        @forelse ($supplierVariants as $supplierVariant)
                                            <tr>

                                                <td>

                                                    <strong>
                                                        {{ $supplierVariant->supplier->trade_name ?: $supplierVariant->supplier->business_name }}
                                                    </strong>

                                                </td>

                                                <td>
                                                    {{ $supplierVariant->supplier_sku ?: '—' }}
                                                </td>

                                                <td class="text-end">
                                                    S/
                                                    {{ number_format((float) $supplierVariant->cost_price, 2) }}
                                                </td>

                                                <td class="text-end">
                                                    {{ $supplierVariant->stock }}
                                                </td>

                                                <td class="text-center">

                                                    @if ($supplierVariant->is_default)
                                                        <span class="badge bg-primary">
                                                            Principal
                                                        </span>
                                                    @else
                                                        <span class="text-muted">
                                                            —
                                                        </span>
                                                    @endif

                                                </td>

                                                <td class="text-center">

                                                    @if ($supplierVariant->is_active)
                                                        <span class="badge bg-success">
                                                            Activo
                                                        </span>
                                                    @else
                                                        <span class="badge bg-secondary">
                                                            Inactivo
                                                        </span>
                                                    @endif

                                                </td>

                                                <td class="text-end">

                                                    <div class="btn-group btn-group-sm">

                                                        <button type="button" class="btn btn-outline-primary"
                                                            wire:click="editSupplierVariant({{ $supplierVariant->id }})">
                                                            <i class="bi bi-pencil"></i>
                                                            Editar
                                                        </button>

                                                        <button type="button" class="btn btn-outline-secondary"
                                                            wire:click="toggleSupplierVariantStatus({{ $supplierVariant->id }})">
                                                            {{ $supplierVariant->is_active ? 'Desactivar' : 'Activar' }}
                                                        </button>

                                                        <button type="button" class="btn btn-outline-danger"
                                                            wire:click="deleteSupplierVariant({{ $supplierVariant->id }})"
                                                            wire:confirm="¿Deseas eliminar este proveedor de la variante?">
                                                            <i class="bi bi-trash"></i>
                                                        </button>

                                                    </div>

                                                </td>

                                            </tr>

                                        @empty

                                            <tr>

                                                <td colspan="7" class="text-center text-muted py-4">
                                                    Esta variante todavía no tiene proveedores.
                                                </td>

                                            </tr>
                                        @endforelse

                                    </tbody>

                                </table>

                            </div>

                        @endif
                    @else
                        <div class="alert alert-info mt-4 mb-0">

                            <i class="bi bi-info-circle me-1"></i>

                            Guarda primero el producto para poder agregar sus variantes.

                        </div>

                    @endif

                </div>


                {{-- ========================================================= --}}
                {{-- FOOTER PRODUCTO --}}
                {{-- ========================================================= --}}

                <div class="modal-footer">

                    <button type="button" class="btn btn-secondary" wire:click="$set('show', false)">
                        Cancelar
                    </button>

                    <button class="btn btn-primary" type="submit">
                        Guardar producto
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>
