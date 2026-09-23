<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/** Noodgeval: telefoon én herstelcodes kwijt. Zet tweestapsverificatie uit voor één account. */
class DisableTwoFactor extends Command
{
    protected $signature = 'user:2fa-off {email}';

    protected $description = 'Zet tweestapsverificatie uit voor een account (telefoon en herstelcodes kwijt).';

    public function handle(): int
    {
        $user = User::firstWhere('email', $this->argument('email'));
        if (! $user) {
            $this->error('Geen account met dat e-mailadres.');

            return self::FAILURE;
        }

        $user->forceFill(['two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null])->save();
        $this->info("Tweestapsverificatie staat uit voor {$user->email}. Laat de eigenaar het opnieuw instellen via Profiel.");

        return self::SUCCESS;
    }
}
