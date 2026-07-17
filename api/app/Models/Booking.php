<?php

namespace App\Models;

use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['unit_id', 'customer_name', 'customer_phone', 'stage', 'price_snapshot', 'hold_expires_at'])]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    /** stage => allowed next stages */
    public const TRANSITIONS = [
        'hold' => ['booked', 'cancelled'],
        'booked' => ['sold', 'cancelled'],
        'sold' => [],
        'cancelled' => [],
    ];

    protected function casts(): array
    {
        return [
            'price_snapshot' => 'integer',
            'hold_expires_at' => 'datetime',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(BookingEvent::class)->latest('created_at');
    }

    public function canTransitionTo(string $stage): bool
    {
        return in_array($stage, self::TRANSITIONS[$this->stage] ?? [], true);
    }

    public function transitionTo(string $stage, ?string $note = null): void
    {
        $from = $this->stage;

        $this->update(['stage' => $stage]);

        $this->events()->create([
            'from_stage' => $from,
            'to_stage' => $stage,
            'note' => $note,
        ]);
    }
}
