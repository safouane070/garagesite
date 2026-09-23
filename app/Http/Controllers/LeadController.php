<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeadRequest;
use App\Mail\LeadConfirmation;
use App\Mail\LeadReceived;
use App\Models\Lead;
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

        $lead = Lead::create($data);

        // De lead staat veilig in de database (en in /admin/aanvragen). Mails gaan
        // via de wachtrij, zodat de bezoeker nooit op de mailserver wacht; lukt
        // zelfs het inplannen niet, dan blokkeert dat de bevestiging niet.
        try {
            Mail::to(config('brand.contact.email'))->queue(new LeadReceived($lead));
            Mail::to($lead->email, $lead->name)->queue(new LeadConfirmation($lead));
        } catch (\Throwable $e) {
            Log::warning('Lead-mail niet ingepland: ' . $e->getMessage(), ['lead_id' => $lead->id]);
        }

        return back()
            ->with('lead_sent', true)
            ->withFragment('contact');
    }
}
