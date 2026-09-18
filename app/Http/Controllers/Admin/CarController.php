<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CarStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CarRequest;
use App\Models\Car;
use App\Models\CarImage;
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
        $cars = Car::with('primaryImage')->latest()->get();

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

        return view('admin.cars.index', compact('cars', 'stats', 'items'));
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

    /** Eén foto verwijderen. */
    public function destroyImage(Car $car, CarImage $image): RedirectResponse
    {
        abort_unless($image->car_id === $car->id, 404);

        Storage::disk('public')->delete($image->path);
        $wasPrimary = $image->is_primary;
        $image->delete();

        // Was dit de omslagfoto? Promoveer dan de eerstvolgende.
        if ($wasPrimary && $next = $car->images()->first()) {
            $next->update(['is_primary' => true]);
        }

        return back()->with('status', 'Foto verwijderd.');
    }

    /** Een foto als omslagfoto instellen. */
    public function setPrimaryImage(Car $car, CarImage $image): RedirectResponse
    {
        abort_unless($image->car_id === $car->id, 404);

        $car->images()->update(['is_primary' => false]);
        $image->update(['is_primary' => true]);

        return back()->with('status', 'Omslagfoto ingesteld.');
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

        // Opties: vrije tekst → nette array (één per regel, dubbele eruit).
        $options = collect(preg_split('/\r\n|\r|\n/', (string) $request->validated('options')))
            ->map(fn ($line) => trim($line))
            ->reject(fn ($line) => $line === '')
            ->unique()
            ->values()
            ->all();

        $data['options'] = $options ?: null;

        return $data;
    }

    /** Geüploade foto's opslaan en als CarImage koppelen. */
    private function storeImages(Car $car, CarRequest $request): void
    {
        if (! $request->hasFile('images')) {
            return;
        }

        $hasPrimary = $car->images()->where('is_primary', true)->exists();
        $order = (int) $car->images()->max('sort_order');

        foreach ($request->file('images') as $file) {
            $path = $file->store("cars/{$car->slug}", 'public');

            $car->images()->create([
                'path' => $path,
                'is_primary' => ! $hasPrimary, // eerste foto ooit wordt omslag
                'sort_order' => ++$order,
            ]);

            $hasPrimary = true;
        }
    }
}
