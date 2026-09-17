<x-layouts.admin title="Profiel">
    <div class="mb-8">
        <p class="kicker">Account</p>
        <h1 class="mt-2 font-display text-2xl font-bold text-cream sm:text-3xl">Profiel</h1>
        <p class="mt-1 text-sm text-cream/70">Beheer je inloggegevens en accountinstellingen.</p>
    </div>

    <div class="mx-auto max-w-2xl space-y-6">
        <div class="surface p-6 sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="surface p-6 sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="surface p-6 sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-layouts.admin>
