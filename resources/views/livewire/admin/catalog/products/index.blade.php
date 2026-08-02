<div>

    <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center">

            <h3 class="card-title">

                Productos

            </h3>

            <button class="btn btn-primary" wire:click="$dispatch('product-create')">

                <i class="bi bi-plus"></i>

                Nuevo

            </button>

        </div>

        <div class="card-body">

            <div class="row mb-3">

                <div class="col-md-4">

                    <input type="text" class="form-control" placeholder="Buscar..."
                        wire:model.live.debounce.300ms="search">

                </div>

                <div class="col-md-3">

                    <select class="form-select" wire:model.live="categoryId">

                        <option value="">

                            Todas las categorías

                        </option>

                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">

                                {{ $category->name }}

                            </option>
                        @endforeach

                    </select>

                </div>

                <div class="col-md-3">

                    <select class="form-select" wire:model.live="brandId">

                        <option value="">

                            Todas las marcas

                        </option>

                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}">

                                {{ $brand->name }}

                            </option>
                        @endforeach

                    </select>

                </div>

                <div class="col-md-2">

                    <select class="form-select" wire:model.live="perPage">

                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>

                    </select>

                </div>

            </div>

            @include('livewire.admin.catalog.products.table')

        </div>

    </div>

    <div>
        <livewire:admin.catalog.products.form />
    </div>
</div>
