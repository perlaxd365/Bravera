<?php

namespace App\Livewire\Pages;

use App\Support\Seo\StructuredData;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('store.layouts.app')]
class About extends Component
{
    public function render()
    {
        $company = config('brevare.company');
        $seo = config('seo');
        $breadcrumbUrl = route('about').'#breadcrumb';

        $breadcrumbStructuredData = StructuredData::breadcrumbs([
            ['name' => 'Inicio', 'url' => route('home')],
            ['name' => 'Nosotros', 'url' => route('about')],
        ], $breadcrumbUrl);

        $seoTitle = 'Sobre Brevare | Fundadores y origen en Lima';
        $seoDescription = 'Conoce Brevare, tienda online peruana fundada el 17 de octubre de 2026 por Raúl Baca y Perla García. Oficinas en Lima y envíos a todo el Perú.';

        return view('livewire.pages.about', compact('company', 'seo'))
            ->title($seoTitle)
            ->layoutData([
                'seoDescription' => $seoDescription,
                'seoImage' => asset('favicon-192.png'),
                'canonicalUrl' => route('about'),
                'breadcrumbStructuredData' => $breadcrumbStructuredData,
                'pageStructuredData' => [StructuredData::aboutPage()],
            ]);
    }
}
