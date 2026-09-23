<?php

namespace App\Http\Requests;

use App\Models\Lead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadRequest extends FormRequest
{
    /** Publiek formulier: iedereen mag een aanvraag sturen. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'car_id' => ['nullable', 'integer', 'exists:cars,id'],
            'type' => ['required', Rule::in(array_keys(Lead::TYPES))],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'message' => ['nullable', 'string', 'max:2000'],
            'preferred_date' => ['nullable', 'date', 'after_or_equal:today'],

            // Honeypot: echte bezoekers laten dit veld leeg. Bots vullen 'm vaak.
            // Moet dus leeg (of afwezig) zijn.
            'website' => ['prohibited'],
        ];
    }

    /**
     * Bij een fout terug naar het formulier zelf (#contact), niet naar de
     * bovenkant van de pagina — anders ziet de bezoeker de melding niet.
     */
    protected function getRedirectUrl(): string
    {
        return url()->previous() . '#contact';
    }

    public function attributes(): array
    {
        return [
            'name' => 'naam',
            'email' => 'e-mailadres',
            'phone' => 'telefoonnummer',
            'message' => 'bericht',
            'type' => 'onderwerp',
            'preferred_date' => 'voorkeursdatum',
        ];
    }

    public function messages(): array
    {
        return [
            'website.prohibited' => 'Je aanvraag kon niet worden verzonden.',
        ];
    }
}
