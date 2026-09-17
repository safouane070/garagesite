@props([
    'title' => null,
    'description' => null,
    'ogImage' => null,
])

@php
    $metaTitle = ($title ? $title . ' · ' : '') . config('app.name');
    $metaDescription = $description
        ?? config('app.name') . ' · Volkswagen-, Audi- en premium Duitse occasions met BOVAG-garantie in ' . config('brand.contact.city') . '. Geen afleverkosten, inruil en financiering mogelijk.';
    $ogImage = $ogImage ? (\Illuminate\Support\Str::startsWith($ogImage, 'http') ? $ogImage : url($ogImage)) : null;

    $organizationLd = [
        '@context' => 'https://schema.org',
        '@type' => 'AutoDealer',
        'name' => config('app.name'),
        'url' => url('/'),
        'telephone' => config('brand.contact.phone'),
        'email' => config('brand.contact.email'),
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => config('brand.contact.address'),
            'addressLocality' => config('brand.contact.city'),
            'addressCountry' => 'BE',
        ],
        'openingHours' => 'Mo-Sa 09:00-18:00',
    ];
@endphp

<!DOCTYPE html>
<html lang="nl" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $metaTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Merk-favicon: de rode "R" van Autobedrijf Rijswijk. --}}
    <link rel="icon" href="{{ asset('images/favicon.svg') }}" type="image/svg+xml">
    <meta name="theme-color" content="#15130f">

    {{-- Open Graph / Twitter --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ url()->current() }}">
    @if ($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
        <meta name="twitter:card" content="summary_large_image">
    @else
        <meta name="twitter:card" content="summary">
    @endif

    {{-- Bedrijfsgegevens voor zoekmachines --}}
    <script type="application/ld+json">
        {!! json_encode($organizationLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen bg-ink text-cream antialiased">

    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded focus:bg-brass-500 focus:px-4 focus:py-2 focus:text-cream">
        Naar hoofdinhoud
    </a>

    <header x-data="{ open: false, scrolled: false }"
            x-init="
                const onScroll = () => scrolled = window.scrollY > 8;
                onScroll();
                let ticking = false;
                window.addEventListener('scroll', () => {
                    if (ticking) return;
                    ticking = true;
                    requestAnimationFrame(() => { onScroll(); ticking = false; });
                }, { passive: true });
            "
            class="sticky top-0 z-40 transition-colors duration-300"
            :class="scrolled ? 'bg-ink/85 backdrop-blur-lg border-b border-hairline' : 'bg-transparent border-b border-transparent'">
        <div class="container-x flex h-[4.5rem] items-center justify-between gap-6">
            <a href="{{ route('home') }}" class="rounded" aria-label="{{ config('app.name') }} home">
                <x-brand-mark />
            </a>

            <nav class="hidden items-center gap-8 md:flex" aria-label="Hoofdnavigatie">
                @php
                    $nav = [
                        ['label' => 'Home', 'route' => 'home', 'active' => request()->routeIs('home')],
                        ['label' => 'Aanbod', 'route' => 'cars.index', 'active' => request()->routeIs('cars.*')],
                    ];
                @endphp
                @foreach ($nav as $item)
                    <a href="{{ route($item['route']) }}"
                       @if($item['active']) aria-current="page" @endif
                       class="relative font-mono text-xs uppercase tracking-[0.15em] transition
                              {{ $item['active'] ? 'text-brass-400' : 'text-cream/70 hover:text-cream' }}">
                        {{ $item['label'] }}
                        @if($item['active'])
                            <span class="absolute -bottom-2 left-0 h-px w-full bg-brass-500"></span>
                        @endif
                    </a>
                @endforeach
            </nav>

            <div class="flex items-center gap-4">
                <a href="tel:{{ config('brand.contact.phone_href') }}" class="hidden items-center gap-2 font-mono text-xs text-cream/70 transition hover:text-brass-300 lg:inline-flex">
                    <x-icon name="phone" class="h-3.5 w-3.5" /> {{ config('brand.contact.phone') }}
                </a>
                @auth
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline hidden sm:inline-flex">Beheer</a>
                @endauth
                <a href="{{ route('cars.index') }}" class="btn btn-primary hidden sm:inline-flex">
                    Aanbod <x-icon name="arrow-right" class="h-4 w-4" />
                </a>

                <button @click="open = !open" type="button"
                        class="btn btn-ghost -mr-2 md:hidden" :aria-expanded="open" aria-label="Menu">
                    <x-icon name="menu" x-show="!open" class="h-5 w-5" />
                    <x-icon name="x" x-show="open" x-cloak class="h-5 w-5" />
                </button>
            </div>
        </div>

        <div x-show="open" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="border-t border-hairline bg-ink/95 md:hidden">
            <nav class="container-x flex flex-col py-3" aria-label="Mobiele navigatie">
                <a href="{{ route('home') }}" class="border-b border-hairline py-3 font-mono text-xs uppercase tracking-[0.15em] text-cream/70">Home</a>
                <a href="{{ route('cars.index') }}" class="border-b border-hairline py-3 font-mono text-xs uppercase tracking-[0.15em] text-cream/70">Aanbod</a>
                @auth
                    <a href="{{ route('admin.dashboard') }}" class="border-b border-hairline py-3 font-mono text-xs uppercase tracking-[0.15em] text-cream/70">Beheer</a>
                @endauth
                <a href="{{ route('cars.index') }}" class="btn btn-primary mt-4">Bekijk het aanbod <x-icon name="arrow-right" class="h-4 w-4" /></a>
            </nav>
        </div>
    </header>

    <main id="main">
        {{ $slot }}
    </main>

    <footer class="mt-28 border-t border-hairline">
        <div class="container-x grid gap-12 py-16 md:grid-cols-12">
            <div class="md:col-span-5">
                <x-brand-mark />
                <p class="mt-5 max-w-sm text-sm leading-relaxed text-cream/65">
                    Volkswagen-, Audi- en premium occasionspecialist in Rijswijk, regio Den Haag.
                    Elke auto met BOVAG-garantie, keuringsattest en zonder afleverkosten.
                </p>
            </div>
            <div class="md:col-span-3">
                <p class="kicker">Navigatie</p>
                <ul class="mt-5 space-y-3 text-sm text-cream/70">
                    <li><a href="{{ route('home') }}" class="transition hover:text-brass-300">Home</a></li>
                    <li><a href="{{ route('cars.index') }}" class="transition hover:text-brass-300">Volledig aanbod</a></li>
                    <li><a href="{{ route('login') }}" class="transition hover:text-brass-300">Beheerderslogin</a></li>
                </ul>
            </div>
            <div class="md:col-span-4">
                <p class="kicker">Showroom</p>
                <ul class="mt-5 space-y-3 text-sm text-cream/70">
                    <li class="flex items-center gap-2.5"><x-icon name="map-pin" class="h-4 w-4 text-brass-500/70" /> {{ config('brand.contact.address') }}</li>
                    <li class="flex items-center gap-2.5"><x-icon name="phone" class="h-4 w-4 text-brass-500/70" /> <a href="tel:{{ config('brand.contact.phone_href') }}" class="hover:text-brass-300">{{ config('brand.contact.phone') }}</a></li>
                    <li class="flex items-center gap-2.5"><x-icon name="mail" class="h-4 w-4 text-brass-500/70" /> <a href="mailto:{{ config('brand.contact.email') }}" class="hover:text-brass-300">{{ config('brand.contact.email') }}</a></li>
                    <li class="flex items-center gap-2.5"><x-icon name="clock" class="h-4 w-4 text-brass-500/70" /> {{ config('brand.contact.hours') }}</li>
                </ul>
            </div>
        </div>
        <div class="border-t border-hairline">
            <div class="container-x flex flex-col items-center justify-between gap-2 py-5 font-mono text-[0.7rem] uppercase tracking-wider text-cream/65 sm:flex-row">
                <span>&copy; {{ date('Y') }} {{ config('app.name') }}</span>
                <span>Laravel · Tailwind · Alpine</span>
            </div>
        </div>
    </footer>
</body>
</html>
