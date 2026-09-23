@props(['title' => 'Beheer'])

<!DOCTYPE html>
<html lang="nl" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('images/favicon.svg') }}" type="image/svg+xml">


    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-ink text-cream antialiased">

    @php
        $openLeads = \App\Models\Lead::open()->count();
        $sections = [
            ['label' => 'Voorraad', 'icon' => 'car', 'route' => 'admin.dashboard', 'active' => request()->routeIs('admin.dashboard', 'admin.cars.*')],
            ['label' => 'Aanvragen', 'icon' => 'inbox', 'route' => 'admin.leads.index', 'active' => request()->routeIs('admin.leads.*'), 'badge' => $openLeads],
            ['label' => 'Profiel', 'icon' => 'user', 'route' => 'profile.edit', 'active' => request()->routeIs('profile.*')],
            ['label' => 'Hulp', 'icon' => 'file-text', 'route' => 'admin.help', 'active' => request()->routeIs('admin.help')],
        ];
    @endphp
    <header class="sticky top-0 z-40 border-b border-hairline bg-ink/85 backdrop-blur-lg">
        <div class="container-x flex h-16 items-center justify-between gap-3">
            <div class="flex min-w-0 items-center gap-2 sm:gap-6">
                <a href="{{ route('admin.dashboard') }}" aria-label="Naar de voorraad" class="shrink-0"><x-brand-mark compact /></a>

                <nav class="flex items-center gap-1" aria-label="Beheer">
                    @foreach ($sections as $s)
                        <a href="{{ route($s['route']) }}" @if ($s['active']) aria-current="page" @endif
                           class="relative inline-flex items-center gap-2 rounded-[3px] px-2.5 py-2 text-sm transition sm:px-3
                                  {{ $s['active'] ? 'bg-brass-500/10 text-cream' : 'text-cream/70 hover:bg-white/5 hover:text-cream' }}">
                            <x-icon name="{{ $s['icon'] }}" class="h-4 w-4" />
                            <span class="hidden sm:inline">{{ $s['label'] }}</span>
                            <span class="sr-only sm:hidden">{{ $s['label'] }}</span>
                            @if (! empty($s['badge']))
                                <span class="rounded-full bg-brass-500 px-1.5 py-0.5 font-mono text-[0.65rem] font-semibold leading-none text-cream tabular"
                                      aria-label="{{ $s['badge'] }} open">{{ $s['badge'] }}</span>
                            @endif
                        </a>
                    @endforeach
                </nav>
            </div>

            <div class="flex items-center gap-1">
                <a href="{{ route('home') }}" target="_blank" rel="noopener" class="btn btn-ghost gap-2 px-3 text-sm">
                    <x-icon name="arrow-up-right" class="h-4 w-4" /> <span class="hidden md:inline">Bekijk site</span>
                    <span class="sr-only md:hidden">Bekijk site</span>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-ghost gap-2 px-3 text-sm">
                        <x-icon name="log-out" class="h-4 w-4" /> <span class="hidden md:inline">Uitloggen</span>
                        <span class="sr-only md:hidden">Uitloggen</span>
                    </button>
                </form>
            </div>
        </div>
    </header>

    {{-- Flashmelding. Breeze geeft technische sleutels terug (bv. "profile-updated");
         die vertalen we naar een leesbare melding. --}}
    @php
        $flash = match (session('status')) {
            'profile-updated' => 'Je profiel is opgeslagen.',
            'password-updated' => 'Je wachtwoord is gewijzigd.',
            'verification-link-sent' => 'Er is een nieuwe verificatielink verstuurd.',
            default => session('status'),
        };
    @endphp
    @if ($flash)
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)"
             x-transition role="status" class="border-b border-emerald-500/20 bg-emerald-500/10">
            <div class="container-x flex items-center justify-between gap-4 py-3">
                <p class="flex items-center gap-2 text-sm text-emerald-200">
                    <x-icon name="check" class="h-4 w-4" /> {{ $flash }}
                </p>
                <button type="button" @click="show = false" aria-label="Melding sluiten" class="text-emerald-200/70 hover:text-emerald-100"><x-icon name="x" class="h-4 w-4" /></button>
            </div>
        </div>
    @endif

    <main class="container-x py-8 lg:py-10">
        {{ $slot }}
    </main>
</body>
</html>
