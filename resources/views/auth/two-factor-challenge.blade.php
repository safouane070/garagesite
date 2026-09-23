<x-guest-layout>
    <div class="mb-6">
        <p class="kicker">Beheer</p>
        <h1 class="mt-2 font-display text-2xl font-bold text-cream">Bevestig dat jij het bent</h1>
        <p class="mt-1 text-sm text-cream/70">Vul de 6 cijfers in uit je authenticator-app. Telefoon niet bij de hand? Gebruik een van je herstelcodes.</p>
    </div>

    <form method="POST" action="{{ route('two-factor.challenge') }}" class="space-y-5">
        @csrf

        <div>
            <label class="field-label" for="code">Code of herstelcode</label>
            <input id="code" name="code" required autofocus autocomplete="one-time-code" inputmode="text"
                   class="field-input font-mono tracking-widest" placeholder="123 456"
                   @error('code') aria-invalid="true" aria-describedby="code-error" @enderror>
            @error('code') <p id="code-error" class="field-hint text-rose-300">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="btn btn-primary w-full">
            Inloggen <x-icon name="arrow-right" class="h-4 w-4" />
        </button>
    </form>

    <p class="mt-6 text-center text-sm">
        <a href="{{ route('login') }}" class="text-cream/70 transition hover:text-brass-300">Terug naar inloggen</a>
    </p>
</x-guest-layout>
