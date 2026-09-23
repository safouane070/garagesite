<?php

namespace App\Enums;

/**
 * De verkoopstatus van een auto.
 *
 * Een "backed enum": elke case heeft een string-waarde die in de database komt.
 * De helpers houden labels en UI-kleuren op één centrale plek.
 */
enum CarStatus: string
{
    case Available = 'available';
    case Reserved = 'reserved';
    case Sold = 'sold';

    /** Nederlands label voor in de UI. */
    public function label(): string
    {
        return match ($this) {
            self::Available => 'Beschikbaar',
            self::Reserved => 'Gereserveerd',
            self::Sold => 'Verkocht',
        };
    }

    /**
     * Tailwind-kleurtokens per status (tekst / achtergrond / rand).
     * Zo blijft de badge-styling consistent door de hele site.
     */
    public function badgeClasses(): string
    {
        // Gevulde pillen met witte tekst: leesbaar op wit én over foto's.
        return match ($this) {
            // -700: wit haalt daarop de WCAG-contrasteis (4,5:1); -600 bleef steken op ±3,8.
            self::Available => 'text-white bg-emerald-700 ring-emerald-800/40',
            self::Reserved => 'text-white bg-amber-700 ring-amber-800/40',
            self::Sold => 'text-white bg-rose-600 ring-rose-700/40',
        };
    }

    /** Handig voor <select>-opties in het adminformulier. */
    public static function options(): array
    {
        return array_map(
            fn (self $s) => ['value' => $s->value, 'label' => $s->label()],
            self::cases()
        );
    }
}
