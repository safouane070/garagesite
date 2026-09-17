<?php

namespace Database\Seeders;

use App\Models\Car;
use App\Support\PlaceholderImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Vult de demo met echte occasions van één Haagse/Rijswijkse handelaar
 * (Bs Rijswijk Automotive BV — autobedrijfrijswijk.nl). Zo zijn de foto's
 * consistent geschoten (zelfde showroom-achtergrond) en kloppen de specs.
 *
 * Per auto lezen we de openbare listing-pagina uit: de pagina bevat een
 * `window.__CONFIG__`-blok met alle kenmerken en foto-URL's. Lukt het ophalen
 * niet (geen internet, advertentie verlopen), dan valt die auto terug op nette
 * SVG-placeholders in de huisstijl.
 */
class CarSeeder extends Seeder
{
    private const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120 Safari/537.36';

    /** Bekende merknamen om "merk + model" netjes te splitsen. */
    private const MAKES = [
        'Mercedes-Benz', 'Alfa Romeo', 'Land Rover', 'Volkswagen', 'BMW', 'Audi',
        'Volvo', 'Tesla', 'Cupra', 'Kia', 'Peugeot', 'Honda', 'SEAT', 'Škoda',
        'Ford', 'Renault', 'Opel', 'Toyota', 'Nissan', 'Mazda', 'Hyundai', 'MINI',
    ];

    public function run(): void
    {
        Car::query()->delete();
        Storage::disk('public')->deleteDirectory('cars');

        $urls = json_decode((string) file_get_contents(database_path('seeders/rijswijk_listings.json')), true) ?: [];

        // Fase 1: alle listings ophalen.
        $cars = [];
        foreach ($urls as $path) {
            $data = $this->fetchListing('https://www.marktplaats.nl' . $path);
            if ($data === null) {
                $this->command?->warn("Overslaan (niet bereikbaar): {$path}");
                continue;
            }
            $cars[] = $data;
        }

        // Fase 2: de 8 duurste wagens krijgen "uitgelicht".
        usort($cars, fn ($a, $b) => $b['price'] <=> $a['price']);

        foreach ($cars as $i => $data) {
            $car = Car::create([
                'brand' => $data['brand'],
                'model' => $data['model'],
                'variant' => $data['variant'],
                'year' => $data['year'],
                'price' => $data['price'],
                'mileage' => $data['mileage'],
                'fuel_type' => $data['fuel_type'],
                'transmission' => $data['transmission'],
                'color' => $data['color'],
                'body_type' => $data['body_type'],
                'status' => $data['reserved'] ? 'reserved' : 'available',
                'is_featured' => $i < 8,
                'description' => $data['description'],
                'specs' => $data['specs'],
            ]);

            $this->attachImages($car, $data['images'], '#d90429');
        }

        $this->command?->info(count($cars) . ' occasions geladen van Autobedrijf Rijswijk.');
    }

    /**
     * Haalt één listing op en mapt de Marktplaats-kenmerken naar onze velden.
     *
     * @return array<string, mixed>|null
     */
    private function fetchListing(string $url): ?array
    {
        try {
            $html = Http::withHeaders(['User-Agent' => self::UA, 'Accept' => 'text/html'])
                ->timeout(25)->retry(2, 400)->get($url)->body();
        } catch (\Throwable $e) {
            return null;
        }

        $config = $this->extractConfig($html);
        $listing = $config['listing'] ?? null;
        if (! $listing) {
            return null;
        }

        // Kenmerken plat slaan (laatste waarde wint: de "Basics"-groep heeft de
        // schone merk/uitvoering-splitsing).
        $attr = [];
        foreach ($listing['carAttributes']['groupedWithIcons'] ?? [] as $group) {
            foreach ($group['attributes'] ?? [] as $a) {
                $attr[$a['key']] = $a['value'] ?? null;
            }
        }

        $fullBrand = (string) ($attr['brand'] ?? $listing['title'] ?? '');
        [$brand, $model] = $this->splitBrand($fullBrand);
        $model = $this->normalizeModel($model);

        // Uitvoering: neem het deel vóór de eerste slash en strip marketing-taal.
        $variant = trim(explode('/', (string) ($attr['trim'] ?? ''))[0]);
        $variant = trim(preg_replace('/\bVol\s*[Oo]pties!?/', '', $variant));
        $variant = trim($variant, " !-");

        $images = collect($listing['gallery']['imageUrls'] ?? [])
            ->take(5)
            ->map(fn ($u) => 'https:' . str_replace('$_#.jpg', '$_86.jpg', $u))
            ->all();

        $year = (int) $this->digits($attr['constructionYear'] ?? 0);
        $mileage = (int) $this->digits($attr['mileage'] ?? 0);
        $pk = (int) $this->digits($attr['powerInHorsePower'] ?? 0);
        $fuel = $this->mapFuel((string) ($attr['fuel'] ?? ''));
        $body = $this->mapBody((string) ($attr['vehicleType'] ?? ''));
        $trans = Str::contains((string) ($attr['transmission'] ?? ''), 'Automaat') ? 'Automaat' : 'Handgeschakeld';
        $color = (string) ($attr['color'] ?? 'Onbekend');

        return [
            'brand' => $brand,
            'model' => $model,
            'variant' => $variant ?: null,
            'year' => $year ?: 2020,
            'price' => (int) round(($listing['priceInfo']['priceCents'] ?? 0) / 100),
            'mileage' => $mileage,
            'fuel_type' => $fuel,
            'transmission' => $trans,
            'color' => $color,
            'body_type' => $body,
            'reserved' => (bool) ($listing['isReserved'] ?? false),
            'description' => $this->describe($brand, $model, $variant, $body, $year, $mileage, $pk, $fuel, $trans),
            'specs' => array_filter([
                'vermogen_pk' => $pk ?: null,
                'cilinders' => (int) $this->digits($attr['numberOfCylinders'] ?? 0) ?: null,
                'koppel_nm' => (int) $this->digits($attr['torque'] ?? 0) ?: null,
                'topsnelheid_kmh' => (int) $this->digits($attr['topSpeed'] ?? 0) ?: null,
                'verbruik_km_l' => $this->decimal($attr['fuelConsumption'] ?? null),
                'co2_g_km' => (int) $this->digits($attr['co2emission'] ?? 0) ?: null,
                'deuren' => (int) $this->digits($attr['numberOfDoors'] ?? 0) ?: null,
                'zitplaatsen' => (int) $this->digits($attr['numberOfSeats'] ?? 0) ?: null,
                'aandrijving' => $attr['powerWheelDriver'] ?? null,
                'bekleding' => $attr['upholstery'] ?? null,
            ], fn ($v) => $v !== null && $v !== ''),
            'images' => $images,
        ];
    }

    /** Isoleert het `window.__CONFIG__ = {...}` JSON-object uit de HTML. */
    private function extractConfig(string $html): ?array
    {
        $marker = 'window.__CONFIG__ = ';
        $start = strpos($html, $marker);
        if ($start === false) {
            return null;
        }

        $i = $start + strlen($marker);
        $len = strlen($html);
        $depth = 0;
        $inStr = false;
        $esc = false;
        $json = '';

        for (; $i < $len; $i++) {
            $ch = $html[$i];
            $json .= $ch;

            if ($inStr) {
                if ($esc) {
                    $esc = false;
                } elseif ($ch === '\\') {
                    $esc = true;
                } elseif ($ch === '"') {
                    $inStr = false;
                }
            } else {
                if ($ch === '"') {
                    $inStr = true;
                } elseif ($ch === '{') {
                    $depth++;
                } elseif ($ch === '}') {
                    $depth--;
                    if ($depth === 0) {
                        break;
                    }
                }
            }
        }

        return json_decode($json, true) ?: null;
    }

    private function attachImages(Car $car, array $urls, string $accent): void
    {
        $order = 0;
        foreach ($urls as $url) {
            $binary = $this->download($url);
            if ($binary === null) {
                continue;
            }

            $path = "cars/{$car->slug}/{$order}.jpg";
            Storage::disk('public')->put($path, $binary);

            $car->images()->create([
                'path' => $path,
                'is_primary' => $order === 0,
                'sort_order' => $order,
            ]);
            $order++;
        }

        if ($order === 0) {
            foreach (PlaceholderImage::VIEWS as $i => $view) {
                $path = "cars/{$car->slug}/{$i}.svg";
                Storage::disk('public')->put($path, PlaceholderImage::svg($car->brand, $car->title(), $view, $accent));
                $car->images()->create(['path' => $path, 'is_primary' => $i === 0, 'sort_order' => $i]);
            }
        }
    }

    private function download(string $url): ?string
    {
        try {
            $response = Http::withHeaders(['User-Agent' => self::UA])->timeout(30)->get($url);

            return $response->ok() ? $response->body() : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** @return array{0:string,1:string} [merk, model] */
    private function splitBrand(string $full): array
    {
        foreach (self::MAKES as $make) {
            if (Str::startsWith(Str::lower($full), Str::lower($make))) {
                return [$make, trim(Str::substr($full, Str::length($make)))];
            }
        }

        // Onbekend merk: eerste woord als merk.
        $parts = explode(' ', trim($full), 2);

        return [$parts[0] ?? $full, $parts[1] ?? ''];
    }

    /** "TIGUAN" -> "Tiguan", "T-ROC" -> "T-Roc" (laat codes als X5, GLC-klasse staan). */
    private function normalizeModel(string $model): string
    {
        return implode(' ', array_map(
            fn ($w) => preg_match('/^[A-Z][A-Z-]{3,}$/', $w) ? ucwords(strtolower($w), " -") : $w,
            explode(' ', $model)
        ));
    }

    private function mapFuel(string $f): string
    {
        return match (true) {
            Str::contains($f, 'Hybride') => 'Hybride',
            Str::contains($f, 'Elektr') => 'Elektrisch',
            Str::contains($f, 'Diesel') => 'Diesel',
            default => 'Benzine',
        };
    }

    private function mapBody(string $v): string
    {
        return match (true) {
            Str::contains($v, ['SUV', 'Terrein']) => 'SUV',
            Str::contains($v, 'Station') => 'Stationwagen',
            Str::contains($v, 'MPV') => 'MPV',
            Str::contains($v, ['Coupé', 'Coupe']) => 'Coupé',
            Str::contains($v, 'Cabrio') => 'Cabriolet',
            Str::contains($v, 'Hatch') => 'Hatchback',
            $v === '' => 'Onbekend',
            default => $v,
        };
    }

    private function describe(string $brand, string $model, string $variant, string $body, int $year, int $mileage, int $pk, string $fuel, string $trans): string
    {
        $km = number_format($mileage, 0, ',', '.');
        $power = $pk ? "{$pk} pk " : '';

        return trim("{$brand} {$model} {$variant} uit {$year}. {$body} met {$km} km op de teller, {$power}{$fuel} en {$trans}. "
            . 'Afgeleverd met onderhoudshistoriek, keuringsattest en BOVAG-garantie.');
    }

    private function digits(mixed $v): int
    {
        return (int) preg_replace('/\D/', '', (string) $v);
    }

    private function decimal(mixed $v): ?float
    {
        if ($v === null) {
            return null;
        }
        $n = str_replace(',', '.', preg_replace('/[^0-9,.]/', '', (string) $v));

        return $n === '' ? null : (float) $n;
    }

}
