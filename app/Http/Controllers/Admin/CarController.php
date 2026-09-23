<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CarStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CarRequest;
use App\Models\Car;
use App\Models\CarImage;
use App\Support\CarDescription;
use App\Support\ImageOptimizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CarController extends Controller
{
    /**
     * Dashboard: overzicht van alle auto's + kerncijfers. Het zoeken/filteren
     * gebeurt client-side (Alpine) zodat het instant is terwijl je typt; we
     * laden daarom de volledige voorraad in één keer.
     */
    public function index(): View
    {
        $cars = Car::with('primaryImage')->withCount('leads')->latest()->get();

        // Inzicht: welke auto's trekken de meeste aandacht (weergaven + aanvragen).
        $popular = $cars->where('status', '!=', CarStatus::Sold)
            ->sortByDesc(fn (Car $c) => [$c->views, $c->leads_count])
            ->take(5)->values();

        $stats = [
            'total' => $cars->count(),
            'available' => $cars->where('status', CarStatus::Available)->count(),
            'reserved' => $cars->where('status', CarStatus::Reserved)->count(),
            'sold' => $cars->where('status', CarStatus::Sold)->count(),
        ];

        // Lichtgewicht index voor de live filter/telling: zoektekst + status.
        $items = $cars->map(fn (Car $car) => [
            't' => $car->searchText(),
            's' => $car->status->value,
        ])->values();

        return view('admin.cars.index', compact('cars', 'stats', 'items', 'popular'));
    }

    /** Formulier voor een nieuwe auto. */
    public function create(): View
    {
        return view('admin.cars.create', ['car' => new Car()]);
    }

    /** Nieuwe auto opslaan. */
    public function store(CarRequest $request): RedirectResponse
    {
        $car = Car::create($this->carData($request));
        $this->describeIfEmpty($car);

        $this->storeImages($car, $request);

        return redirect()
            ->route('admin.cars.edit', $car)
            ->with('status', "“{$car->title()}” is toegevoegd. Voeg gerust nog foto's toe.");
    }

    /** Bewerkformulier. */
    public function edit(Car $car): View
    {
        $car->load('images');

        return view('admin.cars.edit', compact('car'));
    }

    /** Bestaande auto bijwerken. De slug laten we bewust ongewijzigd (URL blijft geldig). */
    public function update(CarRequest $request, Car $car): RedirectResponse
    {
        $car->update($this->carData($request));
        $this->describeIfEmpty($car);

        $this->storeImages($car, $request);

        return redirect()
            ->route('admin.cars.edit', $car)
            ->with('status', "“{$car->title()}” is bijgewerkt.");
    }

    /** Auto verwijderen, inclusief de foto's op schijf. */
    public function destroy(Car $car): RedirectResponse
    {
        Storage::disk('public')->deleteDirectory("cars/{$car->slug}");
        $car->delete(); // car_images-rijen verdwijnen via de cascade

        return redirect()
            ->route('admin.dashboard')
            ->with('status', "“{$car->title()}” is verwijderd.");
    }

    /** Snelle statuswijziging vanuit het dashboard. */
    public function updateStatus(Request $request, Car $car): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', \Illuminate\Validation\Rule::enum(CarStatus::class)],
        ]);

        $car->update(['status' => $validated['status']]);

        return back()->with('status', "Status van “{$car->title()}” is gewijzigd.");
    }

    /** Eén foto verwijderen (incl. miniatuur); de volgorde blijft sluitend. */
    public function destroyImage(Car $car, CarImage $image): RedirectResponse
    {
        abort_unless($image->car_id === $car->id, 404);

        $image->deleteFiles();
        $image->delete();
        $this->reorder($car, $car->images()->get());

        return back()->with('status', 'Foto verwijderd.');
    }

    /** Een foto als omslag instellen = vooraan zetten (de eerste foto ís de omslag). */
    public function setPrimaryImage(Car $car, CarImage $image): RedirectResponse
    {
        abort_unless($image->car_id === $car->id, 404);

        $images = $car->images()->get();
        $this->reorder($car, $images->reject(fn ($i) => $i->id === $image->id)->prepend($image));

        return back()->with('status', 'Omslagfoto ingesteld.');
    }

    /** Foto één plek naar links of rechts schuiven. */
    public function moveImage(Request $request, Car $car, CarImage $image): RedirectResponse
    {
        abort_unless($image->car_id === $car->id, 404);
        $direction = $request->validate(['direction' => ['required', 'in:left,right']])['direction'];

        $images = $car->images()->get()->values();
        $from = $images->search(fn ($i) => $i->id === $image->id);
        $to = $direction === 'left' ? $from - 1 : $from + 1;

        if ($to >= 0 && $to < $images->count()) {
            $order = $images->all();
            [$order[$from], $order[$to]] = [$order[$to], $order[$from]];
            $this->reorder($car, collect($order));
        }

        return back()->with('status', 'Volgorde aangepast.');
    }

    // ----- Interne helpers -----------------------------------------------

    /**
     * Zet de gevalideerde request om naar kolomwaarden. Lege spec-velden
     * gooien we weg; numerieke waarden slaan we als getal op.
     */
    private function carData(CarRequest $request): array
    {
        $data = $request->safe()->except(['images', 'specs', 'options']);

        $specs = collect($request->validated('specs') ?? [])
            ->map(fn ($v) => is_string($v) ? trim($v) : $v)
            ->reject(fn ($v) => $v === null || $v === '')
            ->map(fn ($v) => is_numeric($v) ? $v + 0 : $v)
            ->all();

        $data['specs'] = $specs ?: null;

        // Opties: aangevinkte checkboxes (+ eigen toevoegingen) → nette array.
        $options = collect($request->validated('options') ?? [])
            ->map(fn ($o) => trim((string) $o))
            ->reject(fn ($o) => $o === '')
            ->unique()
            ->values()
            ->all();

        $data['options'] = $options ?: null;

        return $data;
    }

    /** Geüploade foto's opslaan en als CarImage koppelen. */
    /** Beschrijving leeg gelaten: maak er een uit de echte gegevens (uniek per auto, goed voor Google). */
    private function describeIfEmpty(Car $car): void
    {
        if (CarDescription::isReplaceable($car->description)) {
            $car->update(['description' => CarDescription::for($car)]);
        }
    }

    private function storeImages(Car $car, CarRequest $request): void
    {
        if (! $request->hasFile('images')) {
            return;
        }

        $hasPrimary = $car->images()->where('is_primary', true)->exists();
        $order = (int) $car->images()->max('sort_order');

        foreach ($request->file('images') as $file) {
            // Verkleind + rechtgedraaid opslaan, met miniatuur (zie ImageOptimizer).
            $car->images()->create(ImageOptimizer::store($file, "cars/{$car->slug}") + [
                'is_primary' => ! $hasPrimary, // eerste foto ooit wordt omslag
                'sort_order' => ++$order,
            ]);

            $hasPrimary = true;
        }
    }

    /**
     * Legt een volgorde vast: sort_order 0..n-1 en alleen de eerste is omslag.
     * Zo zijn "volgorde" en "omslag" nooit met elkaar in tegenspraak.
     */
    private function reorder(Car $car, \Illuminate\Support\Collection $images): void
    {
        foreach ($images->values() as $i => $img) {
            $img->update(['sort_order' => $i, 'is_primary' => $i === 0]);
        }
    }
}
