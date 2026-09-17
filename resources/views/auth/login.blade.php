<x-guest-layout>
    <div class="mb-6">
        <p class="kicker">Beheer</p>
        <h1 class="mt-2 font-display text-2xl font-bold text-cream">Inloggen</h1>
        <p class="mt-1 text-sm text-cream/70">Log in om de voorraad te beheren.</p>
    </div>

    {{-- Demo-inloggegevens (handig tijdens testen) --}}
    <div class="mb-6 rounded-[4px] border border-brass-500/20 bg-brass-500/[0.06] p-3 font-mono text-[0.7rem] text-cream/70">
        <span class="text-brass-300">Demo:</span> admin@kroon.test · password
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-[4px] border border-emerald-500/20 bg-emerald-500/10 p-3 text-sm text-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <label class="field-label" for="email">E-mailadres</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                   autocomplete="username" class="field-input" placeholder="jij@garage.be">
            @error('email') <p class="field-hint text-rose-300">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="field-label" for="password">Wachtwoord</label>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                   class="field-input" placeholder="••••••••">
            @error('password') <p class="field-hint text-rose-300">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex cursor-pointer items-center gap-2 text-sm text-cream/70">
                <input id="remember_me" type="checkbox" name="remember"
                       class="h-4 w-4 rounded-[3px] border-graphite-500 bg-graphite-800 text-brass-500 focus:ring-brass-500 focus:ring-offset-ink">
                Onthoud mij
            </label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-sm text-cream/70 transition hover:text-brass-300">
                    Wachtwoord vergeten?
                </a>
            @endif
        </div>

        <button type="submit" class="btn btn-primary w-full">
            Inloggen <x-icon name="arrow-right" class="h-4 w-4" />
        </button>
    </form>
</x-guest-layout>
