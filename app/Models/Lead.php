<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    use MassPrunable;

    /** Bewaartermijn (AVG): na afhandeling, en in elk geval na aanmaak. */
    public const KEEP_HANDLED_MONTHS = 12;

    public const KEEP_MAX_MONTHS = 24;

    /** Onderwerpen die de bezoeker in het formulier kan kiezen. */
    public const TYPES = [
        'vraag'        => 'Algemene vraag',
        'bezichtiging' => 'Bezichtiging plannen',
        'proefrit'     => 'Proefrit aanvragen',
        'inruil'       => 'Inruil bespreken',
        'financiering' => 'Financiering aanvragen',
        'zoekopdracht' => 'Zoekopdracht plaatsen',
    ];

    /** Onderwerpen waarbij een voorkeursdatum relevant is (afspraak-achtig). */
    public const DATE_TYPES = ['bezichtiging', 'proefrit'];

    protected $fillable = ['car_id', 'type', 'name', 'email', 'phone', 'message', 'preferred_date', 'source', 'landing_page', 'form_page'];

    protected function casts(): array
    {
        return [
            'handled_at' => 'datetime',
            'preferred_date' => 'date',
        ];
    }

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    /** Leesbaar onderwerp voor mail/beheer. */
    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? 'Aanvraag';
    }

    /** Nog niet afgehandelde aanvragen (de "inbox" van de beheerder). */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('handled_at');
    }

    public function scopeHandled(Builder $query): Builder
    {
        return $query->whereNotNull('handled_at');
    }

    public function isHandled(): bool
    {
        return $this->handled_at !== null;
    }

    /**
     * Wat `model:prune` (dagelijks ingepland) wist: afgehandelde aanvragen na
     * 12 maanden, en álle aanvragen na 24 maanden — persoonsgegevens niet
     * langer bewaren dan nodig (AVG).
     */
    public function prunable(): Builder
    {
        return static::query()
            ->where('handled_at', '<', now()->subMonths(self::KEEP_HANDLED_MONTHS))
            ->orWhere('created_at', '<', now()->subMonths(self::KEEP_MAX_MONTHS));
    }
}
