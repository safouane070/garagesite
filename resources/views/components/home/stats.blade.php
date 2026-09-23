@php
    // Uitsluitend echte, uit de data afgeleide cijfers — niets verzonnen.
    // Zelfde telling als de aanbodpagina: alles wat niet verkocht is.
    $available = \App\Models\Car::where('status', '!=', \App\Enums\CarStatus::Sold->value)->count();
    $reviews = (int) config('brand.reviews.count');
    $warranty = (int) config('brand.trust.warranty_months');

    $tiles = [
        ['value' => $available, 'suffix' => '',     'label' => 'Occasions op voorraad', 'count' => true],
        ['value' => $reviews,   'suffix' => '',     'label' => 'Google-reviews',        'count' => true],
        ['value' => $warranty,  'suffix' => ' mnd', 'label' => 'BOVAG-garantie',        'count' => true],
        ['value' => '€0',       'suffix' => '',     'label' => 'Afleverkosten',          'count' => false],
    ];
@endphp

<section {{ $attributes->merge(['class' => 'border-y border-hairline bg-graphite-800']) }}>
    <dl class="container-x grid grid-cols-2 gap-x-8 gap-y-10 py-12 text-center lg:grid-cols-4 lg:py-14">
        @foreach ($tiles as $t)
            @if ($t['count'])
                {{-- dt vóór dd (geldige HTML); flex-col-reverse zet het getal visueel bovenaan. --}}
                <div class="flex flex-col-reverse" x-data="counter({{ (int) $t['value'] }})" x-init="observe()">
                    <dt class="mt-2 font-mono text-[0.7rem] uppercase tracking-[0.15em] text-cream/60">{{ $t['label'] }}</dt>
                    <dd class="font-display text-4xl font-bold text-white tabular sm:text-5xl">
                        {{-- Echt getal staat al in de HTML (zonder JS / voor zoekmachines); JS telt het op. --}}
                        <span x-text="display">{{ number_format((int) $t['value'], 0, ',', '.') }}</span>{{ $t['suffix'] }}
                    </dd>
                </div>
            @else
                <div class="flex flex-col-reverse">
                    <dt class="mt-2 font-mono text-[0.7rem] uppercase tracking-[0.15em] text-cream/60">{{ $t['label'] }}</dt>
                    <dd class="font-display text-4xl font-bold text-white tabular sm:text-5xl">{{ $t['value'] }}</dd>
                </div>
            @endif
        @endforeach
    </dl>

    <script>
        document.addEventListener('alpine:init', () => {
            if (window.__counterDefined) return; // component mag maar één keer geregistreerd worden
            window.__counterDefined = true;
            Alpine.data('counter', (target) => ({
                target,
                display: target.toLocaleString('nl-NL'),
                done: false,
                observe() {
                    // Geen animatie bij "verminderde beweging": het echte getal blijft staan.
                    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
                    this.display = '0';
                    const io = new IntersectionObserver((entries) => {
                        entries.forEach((e) => {
                            if (e.isIntersecting && !this.done) {
                                this.done = true;
                                this.run();
                                io.disconnect();
                            }
                        });
                    }, { threshold: 0.4 });
                    io.observe(this.$el);
                },
                run() {
                    const dur = 1200, start = performance.now();
                    const tick = (now) => {
                        const p = Math.min((now - start) / dur, 1);
                        const eased = 1 - Math.pow(1 - p, 3);
                        this.display = Math.round(this.target * eased).toLocaleString('nl-NL');
                        if (p < 1) requestAnimationFrame(tick);
                    };
                    requestAnimationFrame(tick);
                },
            }));
        });
    </script>
</section>
