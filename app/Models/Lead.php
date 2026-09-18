<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
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

    protected $fillable = ['car_id', 'type', 'name', 'email', 'phone', 'message', 'preferred_date'];

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
}
