<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CourseAsset extends Model
{
    protected $fillable = [
        'product_id',
        'locale',
        'title',
        'type',
        'file_path',
        'file_name',
        'url',
        'mime_type',
        'size_bytes',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isFile(): bool
    {
        return $this->type === 'file';
    }

    public function sizeLabel(): ?string
    {
        if (! $this->size_bytes) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = (float) $this->size_bytes;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return number_format($size, $unit === 0 ? 0 : 1, ',', '.').' '.$units[$unit];
    }

    public function deleteStoredFile(): void
    {
        if ($this->isFile() && filled($this->file_path)) {
            Storage::disk('local')->delete($this->file_path);
        }
    }
}
