@props(['title' => 'Beheer'])

<!DOCTYPE html>
<html lang="nl" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('images/favicon.svg') }}" type="image/svg+xml">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-ink text-cream antialiased">

    <header class="sticky top-0 z-40 border-b border-hairline bg-ink/85 backdrop-blur-lg">
        <div class="container-x flex h-16 items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.dashboard') }}" aria-label="Dashboard"><x-brand-mark compact /></a>
                <span class="hidden font-mono text-[0.7rem] uppercase tracking-[0.2em] text-brass-300 sm:inline">Beheer</span>
            </div>

            <nav class="flex items-center gap-2">
                <a href="{{ route('home') }}" target="_blank" rel="noopener"
                   class="btn btn-ghost gap-2 text-sm">
                    <x-icon name="arrow-up-right" class="h-4 w-4" /> <span class="hidden sm:inline">Bekijk site</span>
                </a>
                <span class="hidden items-center gap-2 border-l border-hairline pl-3 text-sm text-cream/70 md:flex">
                    {{ auth()->user()->name }}
                </span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-ghost gap-2 text-sm">
                        <x-icon name="log-out" class="h-4 w-4" /> <span class="hidden sm:inline">Uitloggen</span>
                    </button>
                </form>
            </nav>
        </div>
    </header>

    {{-- Flashmelding --}}
    @if (session('status'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
             x-transition class="border-b border-emerald-500/20 bg-emerald-500/10">
            <div class="container-x flex items-center justify-between gap-4 py-3">
                <p class="flex items-center gap-2 text-sm text-emerald-200">
                    <x-icon name="check" class="h-4 w-4" /> {{ session('status') }}
                </p>
                <button @click="show = false" class="text-emerald-200/70 hover:text-emerald-100"><x-icon name="x" class="h-4 w-4" /></button>
            </div>
        </div>
    @endif

    <main class="container-x py-8 lg:py-10">
        {{ $slot }}
    </main>
</body>
</html>
