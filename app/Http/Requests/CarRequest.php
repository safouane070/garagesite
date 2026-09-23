<?php

namespace App\Http\Requests;

use App\Enums\CarStatus;
use App\Models\Car;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CarRequest extends FormRequest
{
    /** Alleen ingelogde beheerders komen hier (route zit achter auth). */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validatieregels. Dezelfde regels gelden voor toevoegen én bewerken.
     */
    public function rules(): array
    {
        $currentYear = (int) date('Y');

        return [
            'brand' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'variant' => ['nullable', 'string', 'max:100'],
            'year' => ['required', 'integer', "between:1950,{$currentYear}"],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'mileage' => ['required', 'integer', 'min:0', 'max:2000000'],
            'fuel_type' => ['required', Rule::in(Car::FUEL_TYPES)],
            'transmission' => ['required', Rule::in(Car::TRANSMISSIONS)],
            'color' => ['required', 'string', 'max:100'],
            'body_type' => ['nullable', Rule::in(Car::BODY_TYPES)],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::enum(CarStatus::class)],
            'is_featured' => ['boolean'],

            // Losse spec-velden komen binnen als specs[...] en zijn optioneel.
            'specs' => ['nullable', 'array'],
            'specs.*' => ['nullable', 'string', 'max:100'],

            // Uitrusting/opties: aangevinkte checkboxes komen binnen als array.
            'options' => ['nullable', 'array', 'max:400'],
            'options.*' => ['string', 'max:100'],

            // Meerdere foto's tegelijk uploaden. Ruime limiet (telefoonfoto's zijn
            // vaak 5–10 MB); ImageOptimizer verkleint ze bij het opslaan.
            'images' => ['nullable', 'array', 'max:12'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:12288'],
        ];
    }

    /** Checkbox stuurt niks door als hij uit staat: normaliseer naar boolean. */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_featured' => $this->boolean('is_featured'),
        ]);
    }

    public function attributes(): array
    {
        return [
            'brand' => 'merk',
            'model' => 'model',
            'year' => 'bouwjaar',
            'price' => 'prijs',
            'mileage' => 'kilometerstand',
            'fuel_type' => 'brandstoftype',
            'transmission' => 'transmissie',
            'color' => 'kleur',
            'body_type' => 'carrosserie',
            'images' => "foto's",
            'images.*' => 'foto',
        ];
    }
}
