<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Category;
use App\Models\HomepageSetting as HomepageSettingModel;
use App\Services\CloudinaryImageService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('admin.layouts.app')]
class Homepage extends Component
{
    use WithFileUploads;

    public array $slides = [];

    public array $texts = [];

    public array $phrases = [];

    public array $slideUploads = [];

    public array $categoryUploads = [];

    public function mount(): void
    {
        $saved = HomepageSettingModel::query()->first();
        $this->slides = $saved?->hero_slides ?: config('homepage.slides', []);
        $this->texts = array_merge(config('homepage.texts', []), $saved?->section_texts ?? []);
        $this->phrases = $saved?->rotating_phrases ?: config('homepage.rotating_phrases', []);
    }

    public function addSlide(): void
    {
        $this->slides[] = [
            'image' => '',
            'eyebrow' => 'Nueva campaña',
            'title' => 'Título principal',
            'highlight' => 'texto destacado',
            'subtitle' => 'Describe brevemente la campaña.',
            'cta' => 'Descubrir',
            'url' => '/buscar',
            'tone' => 'default',
        ];
    }

    public function removeSlide(int $index): void
    {
        if (count($this->slides) <= 1) {
            $this->dispatch('notify', ['type' => 'warning', 'message' => 'La portada debe tener al menos una campaña.']);

            return;
        }

        unset($this->slides[$index], $this->slideUploads[$index]);
        $this->slides = array_values($this->slides);
        $this->slideUploads = [];
    }

    public function addPhrase(): void
    {
        $this->phrases[] = '';
    }

    public function removePhrase(int $index): void
    {
        unset($this->phrases[$index]);
        $this->phrases = array_values($this->phrases);
    }

    public function save(CloudinaryImageService $cloudinary): void
    {
        $this->validate([
            'slides' => ['required', 'array', 'min:1', 'max:12'],
            'slides.*.image' => ['nullable', 'string', 'max:2048'],
            'slides.*.eyebrow' => ['required', 'string', 'max:80'],
            'slides.*.title' => ['required', 'string', 'max:120'],
            'slides.*.highlight' => ['nullable', 'string', 'max:120'],
            'slides.*.subtitle' => ['required', 'string', 'max:240'],
            'slides.*.cta' => ['required', 'string', 'max:60'],
            'slides.*.url' => ['required', 'string', 'max:500', function (string $attribute, mixed $value, $fail) {
                if (! is_string($value) || (str_starts_with($value, '//') || (! str_starts_with($value, '/') && ! str_starts_with($value, 'https://')))) {
                    $fail('Usa una ruta interna que empiece con / o un enlace https seguro.');
                }
            }],
            'slideUploads.*' => ['nullable', 'image', 'max:5120'],
            'texts.primary_categories' => ['required', 'string', 'max:80'],
            'texts.categories' => ['required', 'string', 'max:80'],
            'texts.featured' => ['required', 'string', 'max:80'],
            'texts.latest' => ['required', 'string', 'max:80'],
            'phrases' => ['required', 'array', 'min:1', 'max:20'],
            'phrases.*' => ['required', 'string', 'max:100'],
            'categoryUploads.*' => ['nullable', 'image', 'max:5120'],
        ]);

        foreach ($this->slides as $index => $slide) {
            if (isset($this->slideUploads[$index])) {
                $uploaded = $cloudinary->upload($this->slideUploads[$index], 'portadas');
                $this->slides[$index]['image'] = $uploaded['secure_url'];
            }

            if (blank($this->slides[$index]['image'] ?? null)) {
                throw ValidationException::withMessages([
                    "slides.{$index}.image" => 'Agrega una imagen para esta portada.',
                ]);
            }

            unset($this->slides[$index]['id']);
        }

        $categoryImages = [];
        foreach ($this->categoryUploads as $categoryId => $imageFile) {
            if ($imageFile) {
                $uploaded = $cloudinary->upload($imageFile, CloudinaryImageService::FOLDER_CATEGORIES);
                $categoryImages[(int) $categoryId] = $uploaded['secure_url'];
            }
        }

        DB::transaction(function () use ($categoryImages) {
            HomepageSettingModel::query()->firstOrNew()->fill([
                'hero_slides' => array_values($this->slides),
                'section_texts' => $this->texts,
                'rotating_phrases' => array_values($this->phrases),
            ])->save();

            foreach ($categoryImages as $categoryId => $imageUrl) {
                Category::query()->whereKey($categoryId)->update(['image' => $imageUrl]);
            }
        });

        $this->slideUploads = [];
        $this->categoryUploads = [];

        $this->dispatch('notify', ['type' => 'success', 'message' => 'La portada y sus imágenes se guardaron correctamente.']);
    }

    public function render()
    {
        return view('livewire.admin.settings.homepage', [
            'categories' => Category::query()->whereNull('parent_id')->orderBy('position')->get(),
        ]);
    }
}
