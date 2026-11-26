<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadInboxSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Inbox met aanvragen van de site (proefrit, bezichtiging, inruil, …). Standaard
 * zie je de open aanvragen; afgehandelde blijven terug te vinden onder een tab.
 */
class LeadController extends Controller
{
    private const TABS = ['open', 'afgehandeld', 'alle'];

    public function index(Request $request): View
    {
        $tab = in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : 'open';

        $leads = Lead::query()
            ->with('car.primaryImage')
            ->when($tab === 'open', fn ($q) => $q->open())
            ->when($tab === 'afgehandeld', fn ($q) => $q->handled())
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $counts = [
            'open' => Lead::open()->count(),
            'afgehandeld' => Lead::handled()->count(),
        ];
        $counts['alle'] = $counts['open'] + $counts['afgehandeld'];

        $sources = Lead::where('created_at', '>=', now()->subDays(90))->whereNotNull('source')
            ->selectRaw('source, count(*) as total')->groupBy('source')->orderByDesc('total')
            ->pluck('total', 'source');

        $inboxSettings = LeadInboxSettings::current();
        $deliveryMailer = LeadInboxSettings::usesDeliveryMailer();

        return view('admin.leads.index', compact('leads', 'tab', 'counts', 'sources', 'inboxSettings', 'deliveryMailer'));
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'notification_email' => ['nullable', 'email', 'max:254'],
        ], [
            'notification_email.email' => 'Vul een geldig e-mailadres in.',
        ]);

        LeadInboxSettings::updateOrCreate(['id' => 1], $data);

        return to_route('admin.leads.index')->with('status', 'Instellingen voor e-mailmeldingen opgeslagen.');
    }

    /** Afgehandeld ↔ open wisselen. */
    public function toggle(Lead $lead): RedirectResponse
    {
        // Direct toewijzen: handled_at staat bewust niet in $fillable, zodat het
        // publieke formulier het nooit kan zetten.
        $lead->handled_at = $lead->isHandled() ? null : now();
        $lead->save();

        return back()->with('status', $lead->isHandled()
            ? "Aanvraag van {$lead->name} is afgehandeld."
            : "Aanvraag van {$lead->name} staat weer open.");
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        $lead->delete();

        return back()->with('status', "Aanvraag van {$lead->name} is verwijderd.");
    }
}
