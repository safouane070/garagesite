<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeadRequest;
use App\Mail\LeadReceived;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LeadController extends Controller
{
    public function store(StoreLeadRequest $request): RedirectResponse
    {
        $lead = Lead::create($request->safe()->only([
            'car_id', 'type', 'name', 'email', 'phone', 'message', 'preferred_date',
        ]));

        // De lead staat veilig in de database; mail is "best effort". Een
        // mailprobleem (SMTP onbereikbaar) mag de bevestiging niet blokkeren.
        try {
            Mail::to(config('brand.contact.email'))->send(new LeadReceived($lead));
        } catch (\Throwable $e) {
            Log::warning('Lead-mail niet verzonden: ' . $e->getMessage(), ['lead_id' => $lead->id]);
        }

        return back()
            ->with('lead_sent', true)
            ->withFragment('contact');
    }
}
