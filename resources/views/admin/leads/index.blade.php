<x-layouts.admin title="Aanvragen">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="kicker">Beheer</p>
            <h1 class="mt-2 font-display text-3xl font-bold tracking-tight text-cream">Aanvragen</h1>
            <p class="mt-1 text-sm text-cream/70">Proefritten, bezichtigingen, inruil en andere vragen van de site.</p>
        </div>
    </div>

    {{-- Tabs --}}
    @php
        $tabs = ['open' => 'Open', 'afgehandeld' => 'Afgehandeld', 'alle' => 'Alle'];
    @endphp
    <nav class="mt-8 flex gap-1 border-b border-hairline" aria-label="Filter aanvragen">
        @foreach ($tabs as $key => $label)
            <a href="{{ route('admin.leads.index', ['tab' => $key]) }}"
               @if ($tab === $key) aria-current="page" @endif
               class="-mb-px inline-flex items-center gap-2 border-b-2 px-4 py-2.5 text-sm transition
                      {{ $tab === $key ? 'border-brass-500 font-medium text-cream' : 'border-transparent text-cream/65 hover:text-cream' }}">
                {{ $label }}
                <span class="rounded-full px-2 py-0.5 font-mono text-[0.7rem] tabular
                             {{ $key === 'open' && $counts['open'] > 0 ? 'bg-brass-500 text-cream' : 'bg-graphite-700 text-cream/70' }}">
                    {{ $counts[$key] }}
                </span>
            </a>
        @endforeach
    </nav>

    @if ($leads->isEmpty())
        <div class="mt-8 flex flex-col items-center justify-center rounded-[4px] border border-hairline px-6 py-20 text-center">
            <span class="flex h-14 w-14 items-center justify-center rounded-full border border-hairline text-cream/60"><x-icon name="inbox" class="h-6 w-6" /></span>
            <h2 class="mt-5 font-display text-xl font-semibold text-cream">
                {{ $tab === 'open' ? 'Geen open aanvragen' : 'Geen aanvragen' }}
            </h2>
            <p class="mt-2 max-w-sm text-sm text-cream/65">
                {{ $tab === 'open' ? 'Alles is afgehandeld. Nieuwe aanvragen van de site verschijnen hier en komen ook per mail binnen.' : 'Hier verschijnen de aanvragen die via de site binnenkomen.' }}
            </p>
        </div>
    @else
        <ul class="mt-6 space-y-4">
            @foreach ($leads as $lead)
                @php
                    $replySubject = 'Re: ' . $lead->typeLabel() . ($lead->car ? ' — ' . $lead->car->title() : '');
                @endphp
                <li class="surface overflow-hidden {{ $lead->isHandled() ? 'opacity-70' : '' }}">
                    <div class="flex flex-col gap-5 p-5 lg:flex-row lg:items-start lg:justify-between">
                        {{-- Kern --}}
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-[3px] bg-brass-500/15 px-2 py-0.5 font-mono text-[0.7rem] uppercase tracking-wider text-brass-300">
                                    {{ $lead->typeLabel() }}
                                </span>
                                @if ($lead->preferred_date)
                                    <span class="inline-flex items-center gap-1.5 rounded-[3px] border border-hairline px-2 py-0.5 text-xs text-cream">
                                        <x-icon name="calendar" class="h-3.5 w-3.5 text-brass-400" />
                                        Voorkeur: {{ $lead->preferred_date->translatedFormat('D j M Y') }}
                                    </span>
                                @endif
                                @if ($lead->isHandled())
                                    <span class="inline-flex items-center gap-1 rounded-[3px] bg-emerald-500/10 px-2 py-0.5 text-xs text-emerald-300">
                                        <x-icon name="check" class="h-3.5 w-3.5" /> Afgehandeld
                                    </span>
                                @endif
                                <time datetime="{{ $lead->created_at->toIso8601String() }}"
                                      title="{{ $lead->created_at->format('d-m-Y H:i') }}"
                                      class="ml-auto font-mono text-xs text-cream/60 lg:ml-0">
                                    {{ $lead->created_at->diffForHumans() }}
                                </time>
                            </div>

                            <p class="mt-3 font-display text-lg font-semibold text-cream">{{ $lead->name }}</p>
                            <div class="mt-1 flex flex-wrap gap-x-5 gap-y-1 text-sm">
                                <a href="mailto:{{ $lead->email }}?subject={{ rawurlencode($replySubject) }}"
                                   class="inline-flex items-center gap-1.5 text-cream/75 transition hover:text-brass-300">
                                    <x-icon name="mail" class="h-4 w-4" /> {{ $lead->email }}
                                </a>
                                @if ($lead->phone)
                                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $lead->phone) }}"
                                       class="inline-flex items-center gap-1.5 text-cream/75 transition hover:text-brass-300">
                                        <x-icon name="phone" class="h-4 w-4" /> {{ $lead->phone }}
                                    </a>
                                @endif
                            </div>

                            @if ($lead->message)
                                <p class="mt-4 whitespace-pre-line rounded-[3px] border-l-2 border-brass-500/40 bg-graphite-800/60 px-4 py-3 text-sm leading-relaxed text-cream/85">{{ $lead->message }}</p>
                            @endif
                        </div>

                        {{-- Auto --}}
                        @if ($lead->car)
                            <div class="flex w-full shrink-0 items-center gap-3 rounded-[3px] border border-hairline bg-graphite-800/60 p-3 lg:w-72">
                                <div class="h-12 w-16 shrink-0 overflow-hidden rounded-[3px] bg-graphite-800">
                                    @if ($lead->car->primaryImage)
                                        <img src="{{ $lead->car->primaryImage->xsUrl() }}" alt="" class="h-full w-full object-cover" loading="lazy">
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-cream">{{ $lead->car->shortTitle() }}</p>
                                    <p class="font-mono text-xs text-cream/65">{{ $lead->car->formattedPrice() }} · {{ $lead->car->status->label() }}</p>
                                    <div class="mt-1 flex gap-3 text-xs">
                                        <a href="{{ route('cars.show', $lead->car) }}" target="_blank" rel="noopener" class="text-brass-300 hover:text-brass-200">Op site</a>
                                        <a href="{{ route('admin.cars.edit', $lead->car) }}" class="text-brass-300 hover:text-brass-200">Bewerken</a>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Acties --}}
                    <div class="flex flex-wrap items-center gap-2 border-t border-hairline bg-graphite-800/40 px-5 py-3">
                        <a href="mailto:{{ $lead->email }}?subject={{ rawurlencode($replySubject) }}" class="btn btn-primary px-4 py-2">
                            <x-icon name="mail" class="h-4 w-4" /> Beantwoorden
                        </a>
                        <form method="POST" action="{{ route('admin.leads.toggle', $lead) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-outline px-4 py-2">
                                @if ($lead->isHandled())
                                    <x-icon name="rotate-ccw" class="h-4 w-4" /> Heropenen
                                @else
                                    <x-icon name="check" class="h-4 w-4" /> Markeer als afgehandeld
                                @endif
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.leads.destroy', $lead) }}" class="ml-auto"
                              onsubmit="return confirm({{ \Illuminate\Support\Js::from('Aanvraag van ' . $lead->name . ' definitief verwijderen?') }});">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-ghost px-3 py-2 text-cream/65 hover:text-rose-300" aria-label="Aanvraag van {{ $lead->name }} verwijderen">
                                <x-icon name="trash" class="h-4 w-4" />
                            </button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>

        {{-- Eenvoudige paginering in de huisstijl --}}
        @if ($leads->hasPages())
            <nav class="mt-6 flex items-center justify-between text-sm" aria-label="Paginering">
                @if ($leads->previousPageUrl())
                    <a href="{{ $leads->previousPageUrl() }}" class="btn btn-outline"><x-icon name="chevron-left" class="h-4 w-4" /> Nieuwer</a>
                @else
                    <span></span>
                @endif
                <span class="font-mono text-xs text-cream/60">Pagina {{ $leads->currentPage() }} van {{ $leads->lastPage() }}</span>
                @if ($leads->nextPageUrl())
                    <a href="{{ $leads->nextPageUrl() }}" class="btn btn-outline">Ouder <x-icon name="chevron-right" class="h-4 w-4" /></a>
                @else
                    <span></span>
                @endif
            </nav>
        @endif
    @endif
</x-layouts.admin>
