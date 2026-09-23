<!DOCTYPE html>
<html lang="nl" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Beheerderslogin · {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('images/favicon.svg') }}" type="image/svg+xml">


    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-ink text-cream antialiased">
    <div class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-10">
        {{-- Achtergrondgloed --}}
        <div class="pointer-events-none absolute inset-0 -z-10">
            <div class="absolute left-1/2 top-0 h-96 w-[40rem] -translate-x-1/2 rounded-full bg-brass-500/[0.08] blur-[120px]"></div>
        </div>

        <div class="w-full max-w-md">
            <div class="mb-8 flex justify-center">
                <a href="{{ route('home') }}"><x-brand-mark /></a>
            </div>

            <div class="surface p-8">
                {{ $slot }}
            </div>

            <p class="mt-6 text-center font-mono text-[0.7rem] uppercase tracking-wider text-cream/70">
                <a href="{{ route('home') }}" class="transition hover:text-brass-300">← Terug naar de site</a>
            </p>
        </div>
    </div>
</body>
</html>
