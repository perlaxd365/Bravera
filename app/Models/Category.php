<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'path',
        'description',
        'image',
        'icon',
        'position',
        'is_visible',
        'is_featured',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'is_featured' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }
}
