<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomepageSetting extends Model
{
    protected $fillable = [
        'hero_slides',
        'section_texts',
        'rotating_phrases',
    ];

    protected function casts(): array
    {
        return [
            'hero_slides' => 'array',
            'section_texts' => 'array',
            'rotating_phrases' => 'array',
        ];
    }
}
