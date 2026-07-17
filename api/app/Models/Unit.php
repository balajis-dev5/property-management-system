<?php

namespace App\Models;

use Database\Factories\UnitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['block_id', 'unit_no', 'floor', 'type', 'facing', 'area_sqft', 'price', 'status'])]
class Unit extends Model
{
    /** @use HasFactory<UnitFactory> */
    use HasFactory;

    public const TYPES = ['1BHK', '2BHK', '3BHK'];

    public const FACINGS = ['north', 'south', 'east', 'west'];

    public const STATUSES = ['available', 'held', 'booked', 'sold'];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'area_sqft' => 'integer',
            'floor' => 'integer',
        ];
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class)->latest();
    }

    /**
     * Atomic claim: the conditional UPDATE is the double-booking guard.
     * Two concurrent holds race on `status = 'available'`; exactly one
     * affected row wins, the other caller gets false.
     */
    public function claim(string $toStatus, string $fromStatus = 'available'): bool
    {
        return self::whereKey($this->id)
            ->where('status', $fromStatus)
            ->update(['status' => $toStatus, 'updated_at' => now()]) === 1;
    }
}
