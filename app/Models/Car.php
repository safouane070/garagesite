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
        'versnellingen' => 'Aantal versnellingen',
        'motorinhoud_l' => 'Motorinhoud (l)',
        'energielabel' => 'Energielabel',
        'euronorm' => 'Emissieklasse',
        'hybride_type' => 'Type hybride',
        'bekleding' => 'Bekleding',
        'gewicht_kg' => 'Leeggewicht (kg)',
        'trekgewicht_geremd_kg' => 'Trekgewicht geremd (kg)',
        'trekgewicht_ongeremd_kg' => 'Trekgewicht ongeremd (kg)',
        'lengte_m' => 'Lengte (m)',
        'breedte_m' => 'Breedte (m)',
        'hoogte_m' => 'Hoogte (m)',
        'tankinhoud_l' => 'Tankinhoud (l)',
        'apk_tot' => 'APK geldig tot',
        'onderhoud' => 'Onderhoud',
        'eigenaren' => 'Aantal eigenaren',
        'eerste_toelating_nl' => 'Eerste toelating NL',
        'nieuwprijs' => 'Nieuwprijs (€)',
        'kenteken' => 'Kenteken',
    ];

    /**
     * Groepen voor de specificaties op de detailpagina, met eenheid per veld.
     * Volgorde = volgorde op de pagina; lege velden vallen weg.
     */
    private const SPEC_SECTIONS = [
        'Motor & aandrijving' => [
            'vermogen_pk' => ' pk', 'motorinhoud_l' => ' liter', 'cilinderinhoud_cc' => ' cc', 'cilinders' => '',
            'koppel_nm' => ' Nm', 'versnellingen' => '', 'aandrijving' => '', 'hybride_type' => '', 'accu_kwh' => ' kWh',
        ],
        'Prestaties & verbruik' => [
            'topsnelheid_kmh' => ' km/u', 'acceleratie_0_100' => ' s', 'verbruik_km_l' => ' km/l', 'actieradius_km' => ' km',
            'co2_g_km' => ' g/km', 'energielabel' => '', 'euronorm' => '',
        ],
        'Afmetingen & gewicht' => [
            'lengte_m' => ' m', 'breedte_m' => ' m', 'hoogte_m' => ' m', 'gewicht_kg' => ' kg',
            'trekgewicht_geremd_kg' => ' kg', 'trekgewicht_ongeremd_kg' => ' kg', 'tankinhoud_l' => ' liter',
            'deuren' => '', 'zitplaatsen' => '', 'bekleding' => '',
        ],
        'Historie' => [
            'apk_tot' => '', 'onderhoud' => '', 'eigenaren' => '', 'eerste_toelating_nl' => '', 'nieuwprijs' => '', 'kenteken' => '',
        ],
    ];

    /**
     * Specificaties per groep, opgemaakt: ['Algemeen' => ['Bouwjaar' => '2025', …], …].
     *
     * @return array<string, array<string, string>>
     */
    public function specSections(): array
    {
        $specs = (array) $this->specs;
        $out = ['Algemeen' => array_filter([
            'Bouwjaar' => (string) $this->year,
            'Kilometerstand' => $this->formattedMileage(),
            'Brandstof' => $this->fuel_type,
            'Transmissie' => $this->transmission,
            'Carrosserie' => $this->body_type,
            'Kleur' => str_ireplace(' of ', ' / ', (string) $this->color),
        ])];

        foreach (self::SPEC_SECTIONS as $title => $fields) {
            foreach ($fields as $key => $unit) {
                $value = $specs[$key] ?? null;
                if ($value === null || $value === '') {
                    continue;
                }
                $label = preg_replace('/\s*\([^)]*\)$/', '', self::SPEC_FIELDS[$key]);
                $out[$title][$label] = match ($key) {
                    'nieuwprijs' => '€ ' . number_format((float) $value, 0, ',', '.'),
                    // Motorinhoud schrijft de branche altijd met één decimaal: "2,0 liter".
                    'motorinhoud_l' => number_format((float) $value, 1, ',', '.') . $unit,
                    default => self::formatSpec($value) . $unit,
                };
            }
        }

        // Wat de beheerder buiten de bekende velden invulde, komt er los onder.
        foreach (array_diff_key($specs, self::SPEC_FIELDS) as $key => $value) {
            if ($value !== null && $value !== '') {
                $out['Overig'][Str::ucfirst(str_replace('_', ' ', $key))] = self::formatSpec($value);
            }
        }

        return array_filter($out);
    }

    /** NL-notatie: 1766 → "1.766", 9.2 → "9,2"; tekst blijft tekst. */
    private static function formatSpec(mixed $value): string
    {
        if (! is_numeric($value)) {
            return (string) $value;
        }
        $n = (float) $value;
        $decimals = floor($n) == $n ? 0 : (strlen(rtrim(substr(strrchr((string) $value, '.') ?: '', 1), '0')) ?: 1);

        return number_format($n, min($decimals, 2), ',', '.');
    }

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

        // Verkoopmoment bijhouden, ongeacht wie de status zet (sync of beheerder).
        static::saving(function (Car $car) {
            if ($car->isDirty('status')) {
                $car->sold_at = $car->status === CarStatus::Sold ? ($car->sold_at ?? now()) : null;
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
        'specs', 'options', 'status', 'is_featured', 'vat_deductible',
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
            'vat_deductible' => 'boolean',
            'sold_at' => 'datetime',
            'enriched_at' => 'datetime',
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

    /** Aanvragen die over deze auto gingen. */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
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

    // ----- Presentatie-helpers -------------------------------------------

    /** Prijs als "€ 24.950". */
    public function formattedPrice(): string
    {
        return '€ ' . number_format((float) $this->price, 0, ',', '.');
    }

    /**
     * Financial-leasebedrag per maand zoals de calculator van de leasepartner het
     * standaard berekent (annuïteit, slottermijn; zie config('brand.finance.lease')).
     * Null onder de minimumprijs.
     */
    public function monthlyFrom(): ?int
    {
        $c = config('brand.finance.lease');
        // Zonder bouwjaar (het rekenvoorbeeld op /financial-lease) telt alleen de prijs.
        if ((float) $this->price < $c['min_price'] || ($this->year && now()->year - (int) $this->year > $c['max_age'])) {
            return null;
        }

        $n = $c['months'];
        $r = $c['rate'] / 100 / 12;
        $financed = $this->leaseAmount(); // geen aanbetaling, net als de calculator van de leasepartner
        $closingPv = $c['closing'] / (1 + $r) ** $n;

        return (int) round(($financed - $closingPv) * $r / (1 - (1 + $r) ** -$n));
    }

    /**
     * Het bedrag dat je kunt leasen: bij een BTW-auto is dat de prijs exclusief
     * btw (die vordert de ondernemer terug), anders de volle prijs.
     */
    public function leaseAmount(): int
    {
        $price = (float) $this->price;

        return (int) round($this->isVatDeductible() ? $price / 1.21 : $price);
    }

    /** Link naar de calculator van de leasepartner, al ingevuld voor deze auto. */
    public function leaseCalculatorUrl(): string
    {
        $c = config('brand.finance.lease');

        return 'https://www.financiallease.nl/dealer/calculator?' . http_build_query([
            'calculator_id' => $c['calculator_id'],
            'voertuig' => 'p',
            'aanschafwaarde' => $this->leaseAmount(),
            'leasePrice' => $this->leaseAmount(),
            'runTime' => $c['months'],
            'closingPrice' => $c['closing'],
        ]);
    }

    /** BTW-auto (btw aftrekbaar voor ondernemers): gezet door de beheerder of de sync. */
    public function isVatDeductible(): bool
    {
        return (bool) $this->vat_deductible;
    }

    /** De dealer markeert BTW-auto's met "BTW" in de titel ("… Competition BTW!"). */
    public static function mentionsVat(string $text): bool
    {
        return (bool) preg_match('/\bBTW\b/i', $text);
    }

    /** Uitvoering zonder de "BTW"-markering; die tonen we als eigen label. */
    public function cleanVariant(): string
    {
        return trim(preg_replace('/\s*\bBTW\b\s*/i', ' ', (string) $this->variant));
    }

    /**
     * Optienaam zoals de feed hem soms aanlevert ("\"Lichtmetalen velgen 22\"")
     * terug naar leesbaar: geen aanhalingsteken vooraan, één inch-teken achter een maat.
     */
    public static function cleanOption(string $option): string
    {
        $o = trim(preg_replace('/"{2,}/', '"', $option));
        $o = ltrim($o, "\"' ");

        return preg_match('/\d"?$/', $o) && str_ends_with($o, '"') ? $o : rtrim($o, "\"' ");
    }

    /**
     * Uitrusting voor de detailpagina: hoogtepunten bovenaan, de rest per groep.
     *
     * @return array{highlights: list<string>, groups: array<string, list<string>>, count: int}
     */
    public function equipment(): array
    {
        $all = \App\Support\Equipment::clean((array) $this->options);

        return ['highlights' => \App\Support\CarDescription::highlights($all), 'groups' => \App\Support\Equipment::groups($all), 'count' => count($all)];
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
