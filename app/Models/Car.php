<?php

namespace App\Models;

use App\Enums\CarStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Car extends Model
{
    /** @use HasFactory<\Database\Factories\CarFactory> */
    use HasFactory;

    /** Toegestane keuzes — gedeeld door formulier én validatie. */
    public const FUEL_TYPES = ['Benzine', 'Diesel', 'Elektrisch', 'Hybride', 'LPG'];
    public const TRANSMISSIONS = ['Handgeschakeld', 'Automaat'];
    public const BODY_TYPES = ['Hatchback', 'Sedan', 'Stationwagen', 'SUV', 'Coupé', 'Cabrio', 'MPV'];

    /** Labels voor de losse spec-velden in het adminformulier. */
    public const SPEC_FIELDS = [
        'vermogen_pk' => 'Vermogen (pk)',
        'cilinderinhoud_cc' => 'Cilinderinhoud (cc)',
        'deuren' => 'Aantal deuren',
        'zitplaatsen' => 'Aantal zitplaatsen',
        'cilinders' => 'Cilinders',
        'koppel_nm' => 'Koppel (Nm)',
        'topsnelheid_kmh' => 'Topsnelheid (km/u)',
        'acceleratie_0_100' => '0-100 km/u (s)',
        'verbruik_km_l' => 'Verbruik (km/l)',
        'co2_g_km' => 'CO₂ (g/km)',
        'actieradius_km' => 'Actieradius (km)',
        'accu_kwh' => 'Accucapaciteit (kWh)',
        'aandrijving' => 'Aandrijving',
    ];

    /**
     * Genereert automatisch een unieke slug bij het aanmaken,
     * zolang er nog geen expliciet is meegegeven.
     */
    protected static function booted(): void
    {
        static::creating(function (Car $car) {
            if (empty($car->slug)) {
                $base = $car->title() ?: ('auto ' . $car->year);
                $car->slug = static::makeUniqueSlug($base);
            }
        });
    }

    /**
     * Kolommen die massaal ingevuld mogen worden (mass assignment).
     * Alles wat hier NIET staat, kan niet zomaar via een formulier gezet worden.
     */
    protected $fillable = [
        'slug', 'brand', 'model', 'variant', 'year', 'price', 'mileage',
        'fuel_type', 'transmission', 'color', 'body_type', 'description',
        'specs', 'options', 'status', 'is_featured',
        'dealer_slug', 'dealer_modified_at',
    ];

    /**
     * "Casts" vertalen database-waarden naar handige PHP-types en terug.
     * - specs (JSON) -> array
     * - status (string) -> CarStatus enum
     */
    protected function casts(): array
    {
        return [
            'specs' => 'array',
            'options' => 'array',
            'dealer_modified_at' => 'datetime',
            'status' => CarStatus::class,
            'is_featured' => 'boolean',
            'year' => 'integer',
            'mileage' => 'integer',
            'price' => 'decimal:2',
        ];
    }

    /**
     * Gebruik de slug (i.p.v. het id) in URLs: /autos/bmw-320d-2021.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * De volledige, echte optie-woordenschat: alle opties die op auto's
     * voorkomen, gesorteerd op hoe vaak ze voorkomen (meest gangbare eerst).
     * Voedt de aanvink-lijst in de beheeromgeving — geen verzonnen lijst, puur
     * wat er in de voorraad zit. Groeit vanzelf mee als een beheerder een eigen
     * optie toevoegt aan een auto.
     *
     * @return list<string>
     */
    public static function knownOptions(): array
    {
        $freq = [];
        foreach (static::query()->whereNotNull('options')->pluck('options') as $opts) {
            foreach ((array) $opts as $o) {
                $o = trim((string) $o);
                if ($o !== '') {
                    $freq[$o] = ($freq[$o] ?? 0) + 1;
                }
            }
        }
        arsort($freq);

        return array_keys($freq);
    }

    // ----- Relaties -------------------------------------------------------

    /** Eén auto heeft meerdere foto's (one-to-many). */
    public function images(): HasMany
    {
        return $this->hasMany(CarImage::class)
            ->orderByDesc('is_primary')
            ->orderBy('sort_order');
    }

    /** De omslagfoto: de primaire foto, anders de eerste. */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(CarImage::class)
            ->orderByDesc('is_primary')
            ->orderBy('sort_order');
    }

    // ----- Query scopes (herbruikbare filterlogica) -----------------------

    /** Alleen auto's die te koop staan. */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', CarStatus::Available->value);
    }

    /** Uitgelichte auto's voor de homepage. */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Past de publieke filters toe. Lege filters worden genegeerd,
     * zodat we ze veilig rechtstreeks vanuit de request kunnen doorgeven.
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        // Vrij zoeken op merk / model / uitvoering
        $query->when($filters['search'] ?? null, function (Builder $q, string $term) {
            $q->where(function (Builder $sub) use ($term) {
                $sub->where('brand', 'like', "%{$term}%")
                    ->orWhere('model', 'like', "%{$term}%")
                    ->orWhere('variant', 'like', "%{$term}%");
            });
        });

        $query->when($filters['brand'] ?? null, fn (Builder $q, $v) => $q->where('brand', $v));
        $query->when($filters['fuel_type'] ?? null, fn (Builder $q, $v) => $q->where('fuel_type', $v));

        $query->when($filters['year_min'] ?? null, fn (Builder $q, $v) => $q->where('year', '>=', $v));
        $query->when($filters['year_max'] ?? null, fn (Builder $q, $v) => $q->where('year', '<=', $v));

        $query->when($filters['price_min'] ?? null, fn (Builder $q, $v) => $q->where('price', '>=', $v));
        $query->when($filters['price_max'] ?? null, fn (Builder $q, $v) => $q->where('price', '<=', $v));

        return $query;
    }

    /**
     * Vertaalt een sorteersleutel naar een ORDER BY. Whitelist voorkomt
     * dat iemand willekeurige kolommen via de URL kan sorteren.
     */
    public function scopeSort(Builder $query, ?string $sort): Builder
    {
        return match ($sort) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'mileage_asc' => $query->orderBy('mileage'),
            'year_desc' => $query->orderByDesc('year'),
            default => $query->latest(), // nieuwste eerst
        };
    }

    // ----- Presentatie-helpers -------------------------------------------

    /** Prijs als "€ 24.950". */
    public function formattedPrice(): string
    {
        return '€ ' . number_format((float) $this->price, 0, ',', '.');
    }

    /** Kilometerstand als "84.500 km". */
    public function formattedMileage(): string
    {
        return number_format($this->mileage, 0, ',', '.') . ' km';
    }

    /** Volledige titel: "BMW 320d M Sport". */
    public function title(): string
    {
        return trim("{$this->brand} {$this->model} {$this->variant}");
    }

    /** Korte titel zonder uitvoering: "BMW 320d" — voor compacte bijschriften. */
    public function shortTitle(): string
    {
        return trim("{$this->brand} {$this->model}");
    }

    /**
     * Vrij doorzoekbare tekst (lowercase) voor de admin-filter die client-side
     * met Alpine werkt. Breed opgezet zodat merk, model, uitvoering, bouwjaar,
     * carrosserie, brandstof én kleur meetellen.
     */
    public function searchText(): string
    {
        return Str::lower(implode(' ', array_filter([
            $this->brand, $this->model, $this->variant, $this->year,
            $this->body_type, $this->fuel_type, $this->color,
        ])));
    }

    /**
     * Pad naar een uitgeknipte hero-afbeelding (transparante PNG op donker),
     * als die voor deze auto bestaat — anders null. Zo blijft de hero altijd
     * kloppen: alleen auto's mét cutout komen als blikvanger in aanmerking.
     */
    public function cutoutUrl(): ?string
    {
        $relative = "images/cutouts/{$this->slug}.png";

        return is_file(public_path($relative)) ? asset($relative) : null;
    }

    /**
     * Maakt automatisch een unieke slug wanneer er nog geen is.
     * Wordt aangeroepen door de controller vóór het opslaan.
     */
    public static function makeUniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = Str::slug($base);
        $original = $slug;
        $i = 2;

        while (static::where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = "{$original}-{$i}";
            $i++;
        }

        return $slug;
    }
}
