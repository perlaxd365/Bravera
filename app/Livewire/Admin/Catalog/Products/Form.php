<?php

namespace App\Livewire\Admin\Catalog\Products;

use App\Enums\ProductType;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CourseAsset;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Modules\Product\Forms\ProductForm;
use App\Modules\Product\Forms\ProductVariantForm;
use App\Modules\Product\Forms\SupplierVariantForm;
use App\Modules\Product\Repositories\ProductRepository;
use App\Modules\Product\Services\CourseProductService;
use App\Modules\Product\Services\ProductReadinessService;
use App\Modules\Product\Services\ProductService;
use App\Modules\Product\Services\ProductTranslationService;
use App\Modules\Product\Services\ProductVariantService;
use App\Modules\Product\Services\SupplierVariantService;
use App\Services\CloudinaryImageService;
use App\Services\MetaAdsService;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

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

    public $metaAdVideo;

    public ?string $metaAdVideoUrl = null;

    public ?string $metaAdVideoPublicId = null;

    public $courseFile;

    public string $courseAssetTitle = '';

    public string $courseAssetUrl = '';

    public string $courseAssetLocale = 'es';

    /**
     * Configuración del anuncio de Meta para este producto.
     */
    public bool $metaAdsConfigured = false;

    public array $metaCampaigns = [];

    public array $metaAdSets = [];

    public string $metaCampaignId = '';

    public string $metaAdSetId = '';

    public string $metaMediaType = 'image';

    public string $metaPrimaryText = '';

    public string $metaHeadline = '';

    public string $metaDescription = '';

    public string $metaCta = 'SHOP_NOW';

    public string $metaDestinationUrl = '';

    public ?string $metaAdsError = null;

    /**
     * Mostrar modal.
     */
    public bool $show = false;

    /**
     * Paso visible del asistente. Solo controla la presentación del formulario.
     */
    public int $currentStep = 1;

    /**
     * Repositorio de productos.
     */
    protected ProductRepository $repository;

    /**
     * Servicio de productos.
     */
    protected ProductService $service;

    protected ProductTranslationService $translationService;

    protected ProductReadinessService $readiness;

    /**
     * Servicio de variantes.
     */
    protected ProductVariantService $variantService;

    /**
     * Servicio de proveedores de variantes.
     */
    protected SupplierVariantService $supplierVariantService;

    protected MetaAdsService $metaAdsService;

    /**
     * Inicializar dependencias.
     */
    public function boot(
        ProductRepository $repository,
        ProductService $service,
        ProductTranslationService $translationService,
        ProductReadinessService $readiness,
        ProductVariantService $variantService,
        SupplierVariantService $supplierVariantService,
        MetaAdsService $metaAdsService
    ): void {
        $this->repository = $repository;

        $this->service = $service;

        $this->translationService = $translationService;

        $this->readiness = $readiness;

        $this->variantService = $variantService;

        $this->supplierVariantService = $supplierVariantService;

        $this->metaAdsService = $metaAdsService;
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

        $this->reset([
            'productVideo',
            'productVideoUrl',
            'productVideoPublicId',
            'metaAdVideo',
            'metaAdVideoUrl',
            'metaAdVideoPublicId',
            'courseFile',
            'courseAssetTitle',
            'courseAssetUrl',
            'courseAssetLocale',
        ]);

        $this->resetMetaForm();

        $this->resetValidation();

        $this->currentStep = 1;
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
        $this->metaAdVideoUrl = $product->meta_ad_video_url;
        $this->metaAdVideoPublicId = $product->meta_ad_video_public_id;
        $this->metaAdVideo = null;

        $this->resetMetaForm();
        $this->metaPrimaryText = $product->short_description ?: $product->description ?: $product->name;
        $this->metaHeadline = $product->name;
        $this->metaDestinationUrl = route('store.product', ['slug' => $product->slug]);

        if ($this->metaAdsConfigured) {
            $this->loadMetaCampaigns(false);
        }

        $this->variantForm->resetForm();

        $this->supplierVariantForm->resetForm();

        $this->resetValidation();

        $this->currentStep = 1;
        $this->show = true;
    }

    public function goToStep(int $step): void
    {
        $this->currentStep = max(1, min(6, $step));
        $this->resetValidation();
        $this->dispatch('product-step-changed');
    }

    public function updated(string $property, mixed $value): void
    {
        if ($property === 'form.product_type' && $value === ProductType::COURSE->value) {
            $this->form->allows_shalom_cod = false;
        }

        $pricingField = match ($property) {
            'variantForm.cost_price' => 'cost',
            'variantForm.sale_price' => 'base',
            'variantForm.discount_percent' => 'discount',
            'variantForm.retail_price' => 'retail',
            'variantForm.wholesale_price' => 'wholesale',
            default => null,
        };

        if ($pricingField !== null) {
            $this->variantForm->applyAutomaticPricing($pricingField);
        }
    }

    /**
     * Guardar producto.
     */
    public function save(): void
    {
        try {
            $this->form->validate();
        } catch (ValidationException $exception) {
            $this->currentStep = 1;

            throw $exception;
        }

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
            $this->translationService->sync($product->fresh(), $this->form->translations);

            $message = 'Producto actualizado correctamente.';
        } else {
            $product = $this->service->create(
                $this->form->toDto()
            );
            $this->translationService->sync($product, $this->form->translations);

            /*
             * Cargamos el ID del producto recién creado.
             *
             * Esto permite continuar trabajando con sus
             * variantes sin cerrar el modal.
             */
            $this->form->fromModel($product);

            $message = 'Producto creado correctamente.';
            $this->currentStep = 2;
            $this->dispatch('product-step-changed');
        }

        $freshProduct = $product->fresh();
        app(CourseProductService::class)->ensureDigitalVariant($freshProduct);

        if ($freshProduct->isCourse()
            && $this->form->is_visible
            && ! $freshProduct->courseAssets()->exists()) {
            $freshProduct->update(['is_visible' => false]);
            $this->form->is_visible = false;
            $this->addError('form.is_visible', 'Agrega al menos un archivo o enlace antes de publicar el curso.');
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $message,
        ]);

        $this->dispatch('product-saved');

        $this->resetValidation();
    }

    public function saveCourseFile(): void
    {
        $product = $this->courseProduct();

        if (! $product) {
            return;
        }

        $this->validate([
            'courseAssetTitle' => ['required', 'string', 'max:120'],
            'courseFile' => ['required', 'file', 'max:51200'],
            'courseAssetLocale' => ['required', 'in:es,en'],
        ], [
            'courseAssetTitle.required' => 'Escribe un nombre para el archivo.',
            'courseFile.required' => 'Selecciona un archivo.',
            'courseFile.max' => 'El archivo no puede superar los 50 MB.',
            'courseAssetLocale.in' => 'Selecciona español o inglés para el material.',
        ]);

        $originalName = $this->courseFile->getClientOriginalName();
        $extension = strtolower($this->courseFile->getClientOriginalExtension());
        $safeName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) ?: 'material';
        $storedName = Str::uuid().'-'.$safeName.($extension !== '' ? '.'.$extension : '');
        $path = Storage::disk('local')->putFileAs(
            'courses/'.$product->id,
            $this->courseFile,
            $storedName,
        );

        if (! $path) {
            $this->addError('courseFile', 'No se pudo guardar el archivo. Inténtalo nuevamente.');

            return;
        }

        CourseAsset::create([
            'product_id' => $product->id,
            'locale' => $this->courseAssetLocale,
            'title' => trim($this->courseAssetTitle),
            'type' => 'file',
            'file_path' => $path,
            'file_name' => $originalName,
            'mime_type' => $this->courseFile->getMimeType(),
            'size_bytes' => $this->courseFile->getSize(),
            'sort_order' => (int) $product->courseAssets()->max('sort_order') + 1,
            'is_active' => true,
        ]);

        $this->reset('courseFile', 'courseAssetTitle');
        $this->resetValidation();
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Archivo del curso agregado.']);
    }

    public function saveCourseLink(): void
    {
        $product = $this->courseProduct();

        if (! $product) {
            return;
        }

        $validated = $this->validate([
            'courseAssetTitle' => ['required', 'string', 'max:120'],
            'courseAssetUrl' => ['required', 'url', 'max:2048'],
            'courseAssetLocale' => ['required', 'in:es,en'],
        ], [
            'courseAssetTitle.required' => 'Escribe un nombre para el enlace.',
            'courseAssetUrl.required' => 'Escribe la URL del contenido.',
            'courseAssetUrl.url' => 'La URL del contenido no es válida.',
            'courseAssetLocale.in' => 'Selecciona español o inglés para el material.',
        ]);

        CourseAsset::create([
            'product_id' => $product->id,
            'locale' => $validated['courseAssetLocale'],
            'title' => trim($validated['courseAssetTitle']),
            'type' => 'link',
            'url' => trim($validated['courseAssetUrl']),
            'sort_order' => (int) $product->courseAssets()->max('sort_order') + 1,
            'is_active' => true,
        ]);

        $this->reset('courseAssetTitle', 'courseAssetUrl');
        $this->resetValidation();
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Enlace del curso agregado.']);
    }

    public function deleteCourseAsset(int $assetId): void
    {
        $product = $this->courseProduct();
        $asset = $product?->courseAssets()->find($assetId);

        if (! $asset) {
            return;
        }

        $asset->deleteStoredFile();
        $asset->delete();
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Material del curso eliminado.']);
    }

    private function courseProduct(): ?Product
    {
        if (! $this->form->id || $this->form->product_type !== ProductType::COURSE->value) {
            return null;
        }

        return Product::query()->find($this->form->id);
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
        } catch (Throwable $exception) {
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

    public function updatedMetaAdVideo(): void
    {
        $this->validate([
            'metaAdVideo' => ['required', 'file', 'mimetypes:video/mp4,video/quicktime,video/webm', 'max:51200'],
        ], [
            'metaAdVideo.mimetypes' => 'El video del anuncio debe estar en formato MP4, MOV o WebM.',
            'metaAdVideo.max' => 'El video del anuncio no puede superar los 50 MB.',
        ]);

        $this->metaMediaType = 'video';
    }

    public function saveMetaAdVideo(CloudinaryImageService $cloudinary): void
    {
        if (! $this->form->id) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'Guarda el producto antes de subir el video del anuncio.',
            ]);

            return;
        }

        $this->validate([
            'metaAdVideo' => ['required', 'file', 'mimetypes:video/mp4,video/quicktime,video/webm', 'max:51200'],
        ], [
            'metaAdVideo.mimetypes' => 'El video del anuncio debe estar en formato MP4, MOV o WebM.',
            'metaAdVideo.max' => 'El video del anuncio no puede superar los 50 MB.',
        ]);

        $product = Product::findOrFail($this->form->id);
        $data = null;

        try {
            $data = $cloudinary->uploadAdVideo($this->metaAdVideo);
            $previousPublicId = $product->meta_ad_video_public_id;

            $product->update([
                'meta_ad_video_url' => $data['secure_url'],
                'meta_ad_video_public_id' => $data['public_id'],
            ]);
        } catch (Throwable $exception) {
            if (is_array($data) && filled($data['public_id'] ?? null)) {
                $cloudinary->delete($data['public_id'], 'video');
            }

            report($exception);
            $this->addError('metaAdVideo', 'No se pudo subir el video. Revisa el archivo e inténtalo nuevamente.');
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'No se pudo subir el video para el anuncio.',
            ]);

            return;
        }

        if ($previousPublicId) {
            $cloudinary->delete($previousPublicId, 'video');
        }

        $this->metaAdVideoUrl = $data['secure_url'];
        $this->metaAdVideoPublicId = $data['public_id'];
        $this->metaAdVideo = null;
        $this->resetValidation('metaAdVideo');

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Video para anuncio guardado.',
        ]);
    }

    public function deleteMetaAdVideo(CloudinaryImageService $cloudinary): void
    {
        if (! $this->form->id) {
            return;
        }

        $product = Product::findOrFail($this->form->id);
        $publicId = $product->meta_ad_video_public_id;
        $product->update([
            'meta_ad_video_url' => null,
            'meta_ad_video_public_id' => null,
        ]);

        if ($publicId) {
            $cloudinary->delete($publicId, 'video');
        }

        $this->metaAdVideoUrl = null;
        $this->metaAdVideoPublicId = null;

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Video para anuncio eliminado.',
        ]);
    }

    /**
     * Carga las campañas existentes de la cuenta publicitaria.
     */
    public function loadMetaCampaigns(bool $notify = true): void
    {
        $this->metaAdsError = null;
        $this->metaAdsConfigured = $this->metaAdsService->isConfigured();

        if (! $this->metaAdsConfigured) {
            $this->metaCampaigns = [];

            return;
        }

        try {
            $this->metaCampaigns = $this->metaAdsService->campaigns();

            if ($notify) {
                $this->dispatch('notify', [
                    'type' => 'success',
                    'message' => 'Campañas de Meta cargadas.',
                ]);
            }
        } catch (Throwable $exception) {
            report($exception);
            $this->metaCampaigns = [];
            $this->metaAdsError = $exception->getMessage();

            if ($notify) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => $this->metaAdsError,
                ]);
            }
        }
    }

    public function updatedMetaCampaignId($value): void
    {
        $this->metaAdSetId = '';
        $this->metaAdSets = [];
        $this->metaAdsError = null;

        if (! is_string($value) || trim($value) === '') {
            return;
        }

        try {
            $this->metaAdSets = $this->metaAdsService->adSets($value);
        } catch (Throwable $exception) {
            report($exception);
            $this->metaAdsError = $exception->getMessage();
        }
    }

    /**
     * Crea un anuncio activo con la foto principal o el video del producto.
     */
    public function createMetaAd(): void
    {
        $this->validate([
            'metaCampaignId' => ['required', 'string', 'max:64'],
            'metaAdSetId' => ['required', 'string', 'max:64'],
            'metaMediaType' => ['required', 'in:image,video'],
            'metaPrimaryText' => ['required', 'string', 'max:2000'],
            'metaHeadline' => ['required', 'string', 'max:40'],
            'metaDescription' => ['nullable', 'string', 'max:200'],
            'metaCta' => ['required', 'string', 'max:40'],
            'metaDestinationUrl' => ['required', 'url', 'max:2048'],
        ], [
            'metaCampaignId.required' => 'Selecciona una campaña de Meta.',
            'metaAdSetId.required' => 'Selecciona un conjunto de anuncios.',
            'metaPrimaryText.required' => 'Escribe el texto principal del anuncio.',
            'metaHeadline.required' => 'Escribe el titular del anuncio.',
            'metaHeadline.max' => 'El titular no puede superar los 40 caracteres.',
            'metaDestinationUrl.required' => 'Indica la URL de destino del anuncio.',
        ]);

        $product = $this->metaProduct();

        if (! $product) {
            $this->metaAdsError = 'Guarda el producto antes de crear su anuncio.';

            return;
        }

        $campaignName = data_get(
            collect($this->metaCampaigns)->firstWhere('id', $this->metaCampaignId),
            'name'
        );
        $adSetName = data_get(
            collect($this->metaAdSets)->firstWhere('id', $this->metaAdSetId),
            'name'
        );

        try {
            $record = $this->metaAdsService->createAd($product, [
                'campaign_id' => $this->metaCampaignId,
                'campaign_name' => $campaignName,
                'ad_set_id' => $this->metaAdSetId,
                'ad_set_name' => $adSetName,
                'media_type' => $this->metaMediaType,
                'primary_text' => $this->metaPrimaryText,
                'headline' => $this->metaHeadline,
                'description' => $this->metaDescription,
                'cta' => $this->metaCta,
                'destination_url' => $this->metaDestinationUrl,
            ], auth()->id());

            $this->metaAdsError = null;
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Anuncio creado y activo (ID '.$record->ad_id.'). Meta comenzará a procesarlo.',
            ]);
        } catch (Throwable $exception) {
            report($exception);
            $this->metaAdsError = $exception->getMessage();
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $this->metaAdsError,
            ]);
        }
    }

    public function refreshMetaAd(int $id): void
    {
        $product = $this->metaProduct();
        $record = $product?->metaAds()->find($id);

        if (! $record) {
            return;
        }

        try {
            $this->metaAdsService->refreshStatus($record);
            $this->metaAdsError = null;
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Estado del anuncio actualizado.',
            ]);
        } catch (Throwable $exception) {
            report($exception);
            $this->metaAdsError = $exception->getMessage();
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $this->metaAdsError,
            ]);
        }
    }

    private function resetMetaForm(): void
    {
        $this->metaAdsError = null;
        $this->metaCampaigns = [];
        $this->metaAdSets = [];
        $this->metaCampaignId = '';
        $this->metaAdSetId = '';
        $this->metaMediaType = 'image';
        $this->metaPrimaryText = '';
        $this->metaHeadline = '';
        $this->metaDescription = '';
        $this->metaCta = (string) config('meta.ads.default_cta', 'SHOP_NOW');
        $this->metaDestinationUrl = '';
        $this->metaAdsConfigured = $this->metaAdsService->isConfigured();
    }

    private function metaProduct(): ?Product
    {
        if (! $this->form->id) {
            return null;
        }

        return Product::with(['variants.images'])->find($this->form->id);
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

        $this->currentStep = 4;
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

            $supplierVariant = $this->supplierVariantService->update(
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

            $supplierVariant = $this->supplierVariantService->create(
                $this->supplierVariantForm->toDto()
            );

            $message = 'Proveedor de variante creado correctamente.';
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $message,
        ]);

        $this->supplierVariantForm->fromModel($supplierVariant);

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
        $product = $this->form->id
            ? $this->repository->find($this->form->id)
            : null;

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

        $metaAds = $product?->metaAds()->latest()->get() ?? collect();
        $courseAssets = $product?->isCourse()
            ? $product->courseAssets()->get()
            : collect();
        $metaImage = $product?->coverImage();
        $metaVideo = $product?->meta_ad_video_url ?: $product?->video_url;
        $metaAdsReady = $product !== null
            && $product->status
            && $product->is_visible
            && $product->hasStock()
            && filled($metaImage);

        $readiness = $this->readiness->assess(
            $product ?? new Product,
            [
                'name' => $this->form->name,
                'category_id' => $this->form->category_id,
                'slug' => $this->form->slug,
                'short_description' => $this->form->short_description,
                'description' => $this->form->description,
                'seo_title' => $this->form->seo_title,
                'seo_description' => $this->form->seo_description,
                'status' => $this->form->status,
                'is_visible' => $this->form->is_visible,
            ],
        );

        if ($this->currentStep === 6 && $product) {
            $product->loadCount(['reviews' => fn ($query) => $query->approved()]);
            $product->loadAvg(['reviews' => fn ($query) => $query->approved()], 'rating');
        }

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

                'product' => $product,

                'previewProduct' => $this->currentStep === 6 ? $product : null,

                'metaAds' => $metaAds,

                'courseAssets' => $courseAssets,

                'metaImage' => $metaImage,

                'metaVideo' => $metaVideo,

                'metaAdsReady' => $metaAdsReady,

                'readiness' => $readiness,
            ]
        );
    }
}
