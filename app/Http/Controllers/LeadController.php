<?php

namespace App\Http\Controllers;

use App\Http\Middleware\RememberLeadSource;
use App\Http\Requests\StoreLeadRequest;
use App\Mail\LeadConfirmation;
use App\Mail\LeadReceived;
use App\Models\Lead;
use App\Models\LeadInboxSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LeadController extends Controller
{
    public function store(StoreLeadRequest $request): RedirectResponse
    {
        $data = $request->safe()->only([
            'car_id', 'type', 'name', 'email', 'phone', 'message', 'preferred_date',
        ]);

        // Een datum hoort alleen bij afspraak-onderwerpen. Wisselt de bezoeker na
        // het kiezen van een datum naar bv. "Algemene vraag", dan negeren we 'm.
        if (! in_array($data['type'], Lead::DATE_TYPES, true)) {
            $data['preferred_date'] = null;
        }

        // Herkomst (zie RememberLeadSource) + de pagina waarop het formulier stond.
        $referer = parse_url((string) $request->headers->get('referer'));
        $data += ($request->session()->get(RememberLeadSource::KEY) ?? ['source' => 'Onbekend', 'landing_page' => null]) + [
            'form_page' => ($referer['host'] ?? null) === $request->getHost() ? ($referer['path'] ?? '/') : null,
        ];

        $lead = Lead::create($data);

        // De inbox ontvangt altijd, ook zonder maildienst of als die hapert.
        $recipient = LeadInboxSettings::current()->notification_email;
        if ($recipient && LeadInboxSettings::usesDeliveryMailer()) {
            try {
                Mail::to($recipient)->queue(new LeadReceived($lead));
            } catch (\Throwable $e) {
                Log::warning('Aanvraagmelding niet ingepland.', ['lead_id' => $lead->id, 'exception' => $e::class]);
            }

            // De Render-preview stuurt geen bevestiging uit naam van de echte zaak naar bezoekers.
            if (! config('brand.demo')) {
                try {
                    Mail::to($lead->email)->queue(new LeadConfirmation($lead));
                } catch (\Throwable $e) {
                    Log::warning('Aanvraagbevestiging niet ingepland.', ['lead_id' => $lead->id, 'exception' => $e::class]);
                }
            }
        }

        return back()
            ->with('lead_sent', true)
            ->withFragment('contact');
    }
}
