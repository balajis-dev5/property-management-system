<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReleaseExpiredHolds extends Command
{
    protected $signature = 'holds:release';

    protected $description = 'Cancel expired holds and free their units';

    /**
     * Runs every minute from the scheduler. The (stage, hold_expires_at)
     * index makes the scan cheap regardless of table size.
     */
    public function handle(): int
    {
        $expired = Booking::query()
            ->where('stage', 'hold')
            ->where('hold_expires_at', '<', now())
            ->with('unit')
            ->get();

        foreach ($expired as $booking) {
            DB::transaction(function () use ($booking) {
                $booking->transitionTo('cancelled', 'Hold expired');
                $booking->unit->update(['status' => 'available']);
            });
        }

        $this->info("Released {$expired->count()} expired hold(s).");

        return self::SUCCESS;
    }
}
