<?php

namespace App\Livewire\Admin\Catalog\Products;

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ProductImage;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Modules\Product\Forms\ProductForm;
use App\Modules\Product\Forms\ProductVariantForm;
use App\Modules\Product\Forms\SupplierVariantForm;
use App\Modules\Product\Repositories\ProductRepository;
use App\Modules\Product\Services\ProductService;
use App\Modules\Product\Services\ProductVariantService;
use App\Modules\Product\Services\SupplierVariantService;
use App\Services\CloudinaryImageService;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class Form extends Component
{
    use WithFileUploads;

    /**
     * Form Object del producto.
     */
    public ProductForm $form;

    /**
     * Form Object de la variante.
     */
    public ProductVariantForm $variantForm;

    /**
     * Form Object del proveedor de la variante.
     */
    public SupplierVariantForm $supplierVariantForm;

    /**
     * Variante actualmente seleccionada para administrar proveedores.
     */
    public ?int $supplierVariantVariantId = null;

    /**
     * Variante de la que se muestra la galería de imágenes.
     */
    public ?int $galleryVariantId = null;

    /**
     * Archivos de imagen seleccionados para la galería.
     *
     * @var array
     */
    public $galleryImages = [];

    public $productVideo;

    public ?string $productVideoUrl = null;

    public ?string $productVideoPublicId = null;

    /**
     * Mostrar modal.
     */
    public bool $show = false;

    /**
     * Repositorio de productos.
     */
    protected ProductRepository $repository;

    /**
     * Servicio de productos.
     */
    protected ProductService $service;

    /**
     * Servicio de variantes.
     */
    protected ProductVariantService $variantService;

    /**
     * Servicio de proveedores de variantes.
     */
    protected SupplierVariantService $supplierVariantService;

    /**
     * Inicializar dependencias.
     */
    public function boot(
        ProductRepository $repository,
        ProductService $service,
        ProductVariantService $variantService,
        SupplierVariantService $supplierVariantService
    ): void {
        $this->repository = $repository;

        $this->service = $service;

        $this->variantService = $variantService;

        $this->supplierVariantService = $supplierVariantService;
    }

    /**
     * Nuevo producto.
     */
    #[On('product-create')]
    public function create(): void
    {
        $this->form->resetForm();

        $this->variantForm->resetForm();

        $this->supplierVariantForm->resetForm();

        $this->reset(['productVideo', 'productVideoUrl', 'productVideoPublicId']);

        $this->resetValidation();

        $this->show = true;
    }

    /**
     * Editar producto.
     */
    #[On('product-edit')]
    public function edit(int $id): void
    {
        $product = $this->repository->find($id);

        if (! $product) {
            return;
        }

        $this->form->fromModel($product);

        $this->productVideoUrl = $product->video_url;
        $this->productVideoPublicId = $product->video_public_id;
        $this->productVideo = null;

        $this->variantForm->resetForm();

        $this->supplierVariantForm->resetForm();

        $this->resetValidation();

        $this->show = true;
    }

    /**
     * Guardar producto.
     */
    public function save(): void
    {
        $this->form->validate();

        if ($this->form->is_visible) {
            if (! $this->form->id) {
                $this->addError('form.is_visible', 'Guarda primero el producto oculto, agrega una variante con foto y luego publícalo.');

                return;
            }

            $hasPublicImage = ProductImage::query()
                ->where('is_active', true)
                ->whereHas('variant', fn ($query) => $query
                    ->where('product_id', $this->form->id)
                    ->where('is_active', true))
                ->exists();

            if (! $hasPublicImage) {
                $this->addError('form.is_visible', 'Agrega al menos una foto activa antes de publicar el producto.');

                return;
            }
        }

        if ($this->form->id) {
            $product = $this->repository->find($this->form->id);

            if (! $product) {
                return;
            }

            $this->service->update(
                $product,
                $this->form->toDto()
            );

            $message = 'Producto actualizado correctamente.';
        } else {
            $product = $this->service->create(
                $this->form->toDto()
            );

            /*
             * Cargamos el ID del producto recién creado.
             *
             * Esto permite continuar trabajando con sus
             * variantes sin cerrar el modal.
             */
            $this->form->fromModel($product);

            $message = 'Producto creado correctamente.';
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $message,
        ]);

        $this->dispatch('product-saved');

        $this->resetValidation();
    }

    public function updatedProductVideo(): void
    {
        $this->validate([
            'productVideo' => ['required', 'file', 'mimetypes:video/mp4,video/quicktime,video/webm', 'max:12288'],
        ], [
            'productVideo.mimetypes' => 'El video debe estar en formato MP4, MOV o WebM.',
            'productVideo.max' => 'El video no puede superar los 12 MB.',
        ]);
    }

    public function saveProductVideo(CloudinaryImageService $cloudinary): void
    {
        if (! $this->form->id) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'Guarda el producto antes de subir su video.',
            ]);

            return;
        }

        $this->validate([
            'productVideo' => ['required', 'file', 'mimetypes:video/mp4,video/quicktime,video/webm', 'max:12288'],
        ], [
            'productVideo.mimetypes' => 'El video debe estar en formato MP4, MOV o WebM.',
            'productVideo.max' => 'El video no puede superar los 12 MB.',
        ]);

        $product = Product::findOrFail($this->form->id);
        $data = $cloudinary->uploadVideo($this->productVideo, CloudinaryImageService::FOLDER_PRODUCTS);
        $previousPublicId = $product->video_public_id;

        try {
            $product->update([
                'video_url' => $data['secure_url'],
                'video_public_id' => $data['public_id'],
            ]);
        } catch (\Throwable $exception) {
            $cloudinary->delete($data['public_id'], 'video');
            throw $exception;
        }

        if ($previousPublicId) {
            $cloudinary->delete($previousPublicId, 'video');
        }

        $this->productVideoUrl = $data['secure_url'];
        $this->productVideoPublicId = $data['public_id'];
        $this->productVideo = null;
        $this->resetValidation('productVideo');

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $data['size']
                ? 'Video optimizado y guardado ('.number_format($data['size'] / 1024, 0).' KB).'
                : 'Video optimizado y guardado correctamente.',
        ]);
    }

    public function deleteProductVideo(CloudinaryImageService $cloudinary): void
    {
        if (! $this->form->id) {
            return;
        }

        $product = Product::findOrFail($this->form->id);
        $publicId = $product->video_public_id;
        $product->update(['video_url' => null, 'video_public_id' => null]);

        if ($publicId) {
            $cloudinary->delete($publicId, 'video');
        }

        $this->productVideoUrl = null;
        $this->productVideoPublicId = null;

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Video eliminado correctamente.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | VARIANTES
    |--------------------------------------------------------------------------
    */

    /**
     * Preparar formulario para nueva variante.
     */
    public function createVariant(): void
    {
        if (! $this->form->id) {
            return;
        }

        $this->variantForm->resetForm();

        $this->variantForm->product_id = $this->form->id;

        $this->supplierVariantForm->resetForm();

        $this->resetValidation();
    }

    /**
     * Editar variante.
     */
    public function editVariant(int $id): void
    {
        if (! $this->form->id) {
            return;
        }

        $variant = $this->variantService->find($id);

        if (! $variant) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Seguridad
        |--------------------------------------------------------------------------
        |
        | La variante debe pertenecer al producto actual.
        |
        */

        if ($variant->product_id !== $this->form->id) {
            return;
        }

        $this->variantForm->fromModel($variant);

        $this->supplierVariantForm->resetForm();

        $this->resetValidation();
    }

    /**
     * Guardar o actualizar variante.
     */
    public function saveVariant(): void
    {
        if (! $this->form->id) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'Primero debes guardar el producto.',
            ]);

            return;
        }

        $this->variantForm->product_id = $this->form->id;

        $this->variantForm->validate();

        if ($this->variantForm->id) {
            $variant = $this->variantService->find(
                $this->variantForm->id
            );

            if (! $variant) {
                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Seguridad
            |--------------------------------------------------------------------------
            */

            if ($variant->product_id !== $this->form->id) {
                return;
            }

            $this->variantService->update(
                $variant,
                $this->variantForm->toDto()
            );

            $message = 'Variante actualizada correctamente.';
        } else {
            $this->variantService->create(
                $this->variantForm->toDto()
            );

            $message = 'Variante creada correctamente.';
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $message,
        ]);

        $this->variantForm->resetForm();

        $this->supplierVariantForm->resetForm();

        $this->resetValidation();
    }

    /**
     * Eliminar variante.
     */
    public function deleteVariant(
        CloudinaryImageService $cloudinary,
        int $id
    ): void {
        if (! $this->form->id) {
            return;
        }

        $variant = $this->variantService->find($id);

        if (! $variant) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Seguridad
        |--------------------------------------------------------------------------
        */

        if ($variant->product_id !== $this->form->id) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Eliminar imágenes de Cloudinary
        |--------------------------------------------------------------------------
        */

        foreach ($variant->images ?? [] as $image) {
            $cloudinary->delete($image->public_id);
        }

        $this->variantService->delete($variant);

        if ($this->galleryVariantId === $id) {
            $this->galleryVariantId = null;

            $this->galleryImages = [];
        }

        if ($this->variantForm->id === $id) {
            $this->variantForm->resetForm();
        }

        $this->supplierVariantForm->resetForm();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Variante eliminada correctamente.',
        ]);
    }

    /**
     * Cambiar estado de una variante.
     */
    public function toggleVariantStatus(int $id): void
    {
        if (! $this->form->id) {
            return;
        }

        $variant = $this->variantService->find($id);

        if (! $variant) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Seguridad
        |--------------------------------------------------------------------------
        */

        if ($variant->product_id !== $this->form->id) {
            return;
        }

        $this->variantService->toggleStatus($variant);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Estado de la variante actualizado.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | PROVEEDORES DE VARIANTES
    |--------------------------------------------------------------------------
    */

    /**
     * Preparar formulario para nuevo proveedor.
     */
    /**
     * Preparar formulario para nuevo proveedor.
     */
    public function createSupplierVariant(int $variantId): void
    {
        if (! $this->form->id) {
            return;
        }

        $variant = $this->variantService->find($variantId);

        if (! $variant) {
            return;
        }

        /*
    |--------------------------------------------------------------------------
    | Seguridad
    |--------------------------------------------------------------------------
    |
    | La variante debe pertenecer al producto actual.
    |
    */

        if ($variant->product_id !== $this->form->id) {
            return;
        }

        /*
    |--------------------------------------------------------------------------
    | Variante seleccionada
    |--------------------------------------------------------------------------
    */

        $this->supplierVariantVariantId = $variant->id;

        /*
    |--------------------------------------------------------------------------
    | Preparar formulario
    |--------------------------------------------------------------------------
    */

        $this->supplierVariantForm->resetForm();

        $this->supplierVariantForm->product_variant_id = $variant->id;

        $this->resetValidation();
    }

    /**
     * Editar proveedor de variante.
     */
    public function editSupplierVariant(int $id): void
    {
        if (! $this->form->id) {
            return;
        }

        $supplierVariant = $this->supplierVariantService->find($id);

        if (! $supplierVariant) {
            return;
        }

        $variant = $supplierVariant->productVariant;

        if (! $variant) {
            return;
        }

        /*
    |--------------------------------------------------------------------------
    | Seguridad
    |--------------------------------------------------------------------------
    */

        if ($variant->product_id !== $this->form->id) {
            return;
        }

        $this->supplierVariantVariantId = $variant->id;

        $this->supplierVariantForm->fromModel($supplierVariant);

        $this->resetValidation();
    }

    /**
     * Guardar o actualizar proveedor de variante.
     */
    public function saveSupplierVariant(): void
    {
        if (! $this->form->id) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'Primero debes guardar el producto.',
            ]);

            return;
        }

        if (! $this->supplierVariantForm->product_variant_id) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'Primero debes seleccionar una variante.',
            ]);

            return;
        }

        $variant = $this->variantService->find(
            $this->supplierVariantForm->product_variant_id
        );

        if (! $variant) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Seguridad
        |--------------------------------------------------------------------------
        */

        if ($variant->product_id !== $this->form->id) {
            return;
        }

        $this->supplierVariantForm->validate();

        /*
        |--------------------------------------------------------------------------
        | Actualizar
        |--------------------------------------------------------------------------
        */

        if ($this->supplierVariantForm->id) {
            $supplierVariant = $this->supplierVariantService->find(
                $this->supplierVariantForm->id
            );

            if (! $supplierVariant) {
                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Seguridad adicional
            |--------------------------------------------------------------------------
            |
            | El proveedor debe pertenecer a la variante actual.
            |
            */

            if (
                $supplierVariant->product_variant_id !== $variant->id
            ) {
                return;
            }

            $this->supplierVariantService->update(
                $supplierVariant,
                $this->supplierVariantForm->toDto()
            );

            $message = 'Proveedor de variante actualizado correctamente.';
        } else {
            /*
            |--------------------------------------------------------------------------
            | Crear
            |--------------------------------------------------------------------------
            */

            $this->supplierVariantService->create(
                $this->supplierVariantForm->toDto()
            );

            $message = 'Proveedor de variante creado correctamente.';
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $message,
        ]);

        $this->supplierVariantForm->resetForm();

        $this->resetValidation();
    }

    /**
     * Eliminar proveedor de variante.
     */
    public function deleteSupplierVariant(int $id): void
    {
        if (! $this->form->id) {
            return;
        }

        $supplierVariant = $this->supplierVariantService->find($id);

        if (! $supplierVariant) {
            return;
        }

        $variant = $supplierVariant->productVariant;

        if (! $variant) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Seguridad
        |--------------------------------------------------------------------------
        */

        if ($variant->product_id !== $this->form->id) {
            return;
        }

        $this->supplierVariantService->delete(
            $supplierVariant
        );

        if ($this->supplierVariantForm->id === $id) {
            $this->supplierVariantForm->resetForm();
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Proveedor de variante eliminado correctamente.',
        ]);
    }

    /**
     * Cambiar estado del proveedor de variante.
     */
    public function toggleSupplierVariantStatus(int $id): void
    {
        if (! $this->form->id) {
            return;
        }

        $supplierVariant = $this->supplierVariantService->find($id);

        if (! $supplierVariant) {
            return;
        }

        $variant = $supplierVariant->productVariant;

        if (! $variant) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Seguridad
        |--------------------------------------------------------------------------
        */

        if ($variant->product_id !== $this->form->id) {
            return;
        }

        $this->supplierVariantService->toggleStatus(
            $supplierVariant
        );

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Estado del proveedor actualizado.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | GALERÍA DE IMÁGENES DE LA VARIANTE
    |--------------------------------------------------------------------------
    */

    /**
     * Abre la galería de imágenes de una variante.
     */
    public function openGallery(int $id): void
    {
        if (! $this->form->id) {
            return;
        }

        $variant = $this->variantService->find($id);

        if (! $variant || $variant->product_id !== $this->form->id) {
            return;
        }

        $this->galleryVariantId = $variant->id;

        $this->galleryImages = [];

        $this->resetValidation();
    }

    /**
     * Cierra la galería de imágenes.
     */
    public function closeGallery(): void
    {
        $this->galleryVariantId = null;

        $this->galleryImages = [];

        $this->resetValidation();
    }

    /**
     * Al seleccionar imágenes las valida.
     */
    public function updatedGalleryImages(): void
    {
        $this->validate([
            'galleryImages.*' => ['required', 'image', 'max:5120'],
        ]);
    }

    /**
     * Sube las imágenes de la galería a Cloudinary y las registra.
     */
    public function saveGalleryImages(CloudinaryImageService $cloudinary): void
    {
        $variant = $this->galleryVariant();

        if (! $variant) {
            return;
        }

        if (empty($this->galleryImages)) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'Selecciona al menos una imagen.',
            ]);

            return;
        }

        $this->validate([
            'galleryImages.*' => ['required', 'image', 'max:5120'],
        ]);

        $hasPrimary = $variant->images()
            ->where('is_primary', true)
            ->exists();

        $total = $variant->images()->count();

        foreach (array_values($this->galleryImages) as $index => $file) {
            $data = $cloudinary->upload($file, CloudinaryImageService::FOLDER_PRODUCTS);

            ProductImage::create([
                'product_variant_id' => $variant->id,
                'public_id' => $data['public_id'],
                'file_name' => $data['file_name'],
                'url' => $data['url'],
                'secure_url' => $data['secure_url'],
                'format' => $data['format'],
                'size' => $data['size'],
                'width' => $data['width'],
                'height' => $data['height'],
                'is_primary' => ! $hasPrimary && $index === 0,
                'sort_order' => $total + $index,
                'is_active' => true,
            ]);
        }

        $this->galleryImages = [];

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Imágenes subidas correctamente.',
        ]);
    }

    /**
     * Elimina una imagen de la galería (registro y archivo en Cloudinary).
     */
    public function deleteImage(CloudinaryImageService $cloudinary, int $id): void
    {
        $variant = $this->galleryVariant();

        if (! $variant) {
            return;
        }

        $image = ProductImage::query()
            ->where('product_variant_id', $variant->id)
            ->find($id);

        if (! $image) {
            return;
        }

        $wasPrimary = $image->is_primary;

        $cloudinary->delete($image->public_id);

        $image->delete();

        if ($wasPrimary) {
            $next = ProductImage::query()
                ->where('product_variant_id', $variant->id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->first();

            if ($next) {
                $next->update(['is_primary' => true]);
            }
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Imagen eliminada correctamente.',
        ]);
    }

    /**
     * Establece una imagen como principal de la variante.
     */
    public function setPrimaryImage(int $id): void
    {
        $variant = $this->galleryVariant();

        if (! $variant) {
            return;
        }

        $image = ProductImage::query()
            ->where('product_variant_id', $variant->id)
            ->find($id);

        if (! $image) {
            return;
        }

        ProductImage::query()
            ->where('product_variant_id', $variant->id)
            ->update(['is_primary' => false]);

        $image->update(['is_primary' => true]);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Imagen principal actualizada.',
        ]);
    }

    /**
     * Obtiene la variante de la galería abierta (con validación de propiedad).
     */
    private function galleryVariant(): ?ProductVariant
    {
        if (! $this->galleryVariantId || ! $this->form->id) {
            return null;
        }

        $variant = $this->variantService->find($this->galleryVariantId);

        if (! $variant || $variant->product_id !== $this->form->id) {
            return null;
        }

        return $variant;
    }

    /*
    |--------------------------------------------------------------------------
    | RENDER
    |--------------------------------------------------------------------------
    */

    public function render()
    {
        $variants = collect();

        if ($this->form->id) {
            $variants = $this->variantService->paginate(
                productId: $this->form->id,
                search: null,
                perPage: 50
            );
        }
        $supplierVariants = collect();
        if ($this->supplierVariantVariantId) {
            $supplierVariants = $this->supplierVariantService->paginate(
                productVariantId: $this->supplierVariantVariantId,
                search: null,
                perPage: 50
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Atributos disponibles para las variantes
        |--------------------------------------------------------------------------
        */

        $attributes = Attribute::query()
            ->active()
            ->with([
                'values' => function ($query) {
                    $query
                        ->where('is_active', true)
                        ->orderBy('sort_order')
                        ->orderBy('value');
                },
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Proveedores disponibles
        |--------------------------------------------------------------------------
        |
        | Los proveedores siguen perteneciendo a la aplicación general,
        | no al módulo Product.
        |
        */

        $suppliers = Supplier::query()
            ->active()
            ->orderBy('business_name')
            ->orderBy('trade_name')
            ->get();

        $galleryVariant = $this->galleryVariant();

        return view(
            'livewire.admin.catalog.products.form',
            [
                'supplierVariants' => $supplierVariants,
                'categories' => Category::query()
                    ->where('is_visible', true)
                    ->orderBy('position')
                    ->get(),

                'brands' => Brand::query()
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get(),

                'variants' => $variants,

                'attributes' => $attributes,

                'suppliers' => $suppliers,

                'galleryVariant' => $galleryVariant,
            ]
        );
    }
}
