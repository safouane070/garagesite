<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CarImage extends Model
{
    /** @use HasFactory<\Database\Factories\CarImageFactory> */
    use HasFactory;

    protected $fillable = ['car_id', 'path', 'thumb_path', 'width', 'height', 'is_primary', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'sort_order' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    /** De foto hoort bij één auto (andere kant van de one-to-many). */
    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    /**
     * Publieke URL naar de afbeelding. Externe URLs (bv. dummy-foto's in de
     * seeder) laten we ongewijzigd; lokale paden gaan via de 'public' disk.
     */
    public function url(): string
    {
        if (Str::startsWith($this->path, ['http://', 'https://'])) {
            return $this->path;
        }

        return Storage::disk('public')->url($this->path);
    }

    /** Miniatuur voor kaartjes/lijsten; valt terug op de volledige foto. */
    public function thumbUrl(): string
    {
        return $this->thumb_path ? Storage::disk('public')->url($this->thumb_path) : $this->url();
    }

    /**
     * srcset voor responsieve kaartjes: de browser kiest zelf miniatuur of
     * volledige foto (bv. een scherp telefoonscherm krijgt de grote).
     */
    public function srcset(): ?string
    {
        if (! $this->thumb_path || ! $this->width) {
            return null;
        }

        return $this->thumbUrl() . ' ' . \App\Support\ImageOptimizer::THUMB_WIDTH . 'w, ' . $this->url() . ' ' . $this->width . 'w';
    }

    /** Foto en miniatuur van schijf verwijderen. */
    public function deleteFiles(): void
    {
        Storage::disk('public')->delete(array_filter([$this->path, $this->thumb_path]));
    }
}
