<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Support\ImageOptimizer;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CarImage extends Model
{
    /** @use HasFactory<\Database\Factories\CarImageFactory> */
    use HasFactory;

    protected $fillable = ['car_id', 'path', 'xs_path', 'thumb_path', 'md_path', 'width', 'height', 'is_primary', 'sort_order'];

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

    /** Kleinste variant (240 px) voor fotostrookjes en lijstjes. */
    public function xsUrl(): string
    {
        return $this->xs_path ? Storage::disk('public')->url($this->xs_path) : $this->thumbUrl();
    }

    /**
     * srcset voor responsieve foto's: de browser kiest zelf de kleinste maat die
     * scherp genoeg is (telefoon → 640/1024, groot scherm → volledige foto).
     */
    public function srcset(): ?string
    {
        if (! $this->width) {
            return null;
        }

        $set = [];
        foreach (ImageOptimizer::VARIANTS as $column => [$width]) {
            if ($this->{$column} && $column !== 'xs_path') {
                $set[] = Storage::disk('public')->url($this->{$column}) . " {$width}w";
            }
        }

        return $set === [] ? null : implode(', ', [...$set, $this->url() . " {$this->width}w"]);
    }

    /** Foto en alle verkleinde varianten van schijf verwijderen. */
    public function deleteFiles(): void
    {
        Storage::disk('public')->delete(array_filter([$this->path, ...array_map(fn ($c) => $this->{$c}, array_keys(ImageOptimizer::VARIANTS))]));
    }
}
