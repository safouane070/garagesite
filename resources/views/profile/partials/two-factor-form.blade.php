@php
    $user = auth()->user();
    $pending = $user->two_factor_secret && ! $user->two_factor_confirmed_at;
    $codes = session('two_factor_codes');
    $bag = $errors->getBag('twoFactor');
@endphp

<section id="tweestaps" class="scroll-mt-24">
    <header>
        <h2 class="flex items-center gap-2 text-lg font-medium text-cream">
            Tweestapsverificatie
            @if ($user->hasTwoFactor())
                <span class="rounded-full bg-emerald-700 px-2 py-0.5 text-xs font-medium text-white">Aan</span>
            @endif
        </h2>
        <p class="mt-1 text-sm text-cream/70">
            Naast je wachtwoord vraagt het inloggen dan om een code uit een app op je telefoon
            (bv. Google Authenticator, Microsoft Authenticator of 1Password). Een gestolen wachtwoord alleen is dan niet genoeg.
        </p>
    </header>

    @if ($codes)
        {{-- Herstelcodes: alleen nu zichtbaar. --}}
        <div class="mt-6 rounded-[4px] border border-brass-500/40 bg-brass-500/10 p-4" role="status">
            <p class="text-sm font-medium text-cream">Bewaar deze herstelcodes op een veilige plek (bv. uitprinten).</p>
            <p class="mt-1 text-sm text-cream/75">Elke code werkt één keer, voor als je je telefoon kwijt bent. Ze worden maar één keer getoond.</p>
            <ul class="mt-3 grid grid-cols-2 gap-2 font-mono text-sm text-cream">
                @foreach ($codes as $code)
                    <li class="rounded-[3px] bg-graphite-800 px-3 py-1.5 text-center">{{ $code }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($user->hasTwoFactor())
        <p class="mt-6 text-sm text-cream/75">
            Staat aan sinds {{ $user->two_factor_confirmed_at->translatedFormat('j F Y') }} ·
            {{ count($user->two_factor_recovery_codes ?? []) }} herstelcodes over.
        </p>
        <form method="POST" class="mt-4 flex flex-wrap items-end gap-3">
            @csrf
            <div class="min-w-[220px] flex-1">
                <label class="field-label" for="tf-password">Huidig wachtwoord</label>
                <input id="tf-password" name="current_password" type="password" required autocomplete="current-password" class="field-input">
            </div>
            <button formaction="{{ route('two-factor.recovery-codes') }}" class="btn btn-outline">Nieuwe herstelcodes</button>
            <button formaction="{{ route('two-factor.disable') }}" name="_method" value="DELETE" class="btn btn-ghost text-rose-300">Uitschakelen</button>
        </form>
        @if ($bag->has('current_password'))
            <p class="field-hint text-rose-300">{{ $bag->first('current_password') }}</p>
        @endif
    @elseif ($pending)
        @php $uri = \App\Support\Totp::uri($user->two_factor_secret, $user->email, config('app.name')); @endphp
        <ol class="mt-6 space-y-4 text-sm text-cream/80">
            <li>
                <span class="font-medium text-cream">1. Scan deze code</span> met je authenticator-app.
                <div class="mt-3 w-fit rounded-[4px] bg-white p-3">{!! \App\Support\Totp::qrSvg($uri) !!}</div>
                <p class="mt-2 text-xs text-cream/60">Lukt scannen niet? Voer deze sleutel handmatig in:
                    <span class="block select-all break-all font-mono text-cream">{{ trim(chunk_split($user->two_factor_secret, 4, ' ')) }}</span></p>
            </li>
            <li>
                <span class="font-medium text-cream">2. Vul de code uit de app in</span> om het af te ronden.
                <form method="POST" action="{{ route('two-factor.confirm') }}" class="mt-3 flex flex-wrap items-end gap-3">
                    @csrf
                    <input name="code" required inputmode="numeric" autocomplete="one-time-code" aria-label="Code uit de app"
                           placeholder="123 456" class="field-input max-w-[10rem] font-mono tracking-widest"
                           @if ($bag->has('code')) aria-invalid="true" @endif>
                    <button class="btn btn-primary">Bevestigen</button>
                </form>
                @if ($bag->has('code'))
                    <p class="field-hint text-rose-300">{{ $bag->first('code') }}</p>
                @endif
            </li>
        </ol>
    @else
        <form method="POST" action="{{ route('two-factor.enable') }}" class="mt-6">
            @csrf
            <button class="btn btn-primary">Inschakelen</button>
        </form>
    @endif
</section>
