<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\HoldUnitRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Unit;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $bookings = Booking::query()
            ->with(['unit.block.project'])
            ->when($request->query('stage'), fn ($q, $stage) => $q->where('stage', $stage))
            ->latest()
            ->paginate(min((int) $request->query('per_page', 25), 200));

        return BookingResource::collection($bookings);
    }

    public function show(Booking $booking): BookingResource
    {
        return BookingResource::make($booking->load(['unit.block.project', 'events']));
    }

    /**
     * Enquiry → hold. The unit's conditional UPDATE is the race guard:
     * two concurrent holds on one unit — exactly one succeeds, the
     * other gets 409 UNIT_NOT_AVAILABLE. Price is snapshotted into the
     * booking because source prices change; contracts don't.
     */
    public function hold(HoldUnitRequest $request, Unit $unit): JsonResponse
    {
        if (! $unit->claim('held')) {
            return ApiResponse::error('Unit is no longer available.', 'UNIT_NOT_AVAILABLE', 409);
        }

        $booking = DB::transaction(function () use ($request, $unit) {
            $booking = Booking::create([
                'unit_id' => $unit->id,
                'customer_name' => $request->validated('customer_name'),
                'customer_phone' => $request->validated('customer_phone'),
                'stage' => 'hold',
                'price_snapshot' => $unit->price,
                'hold_expires_at' => now()->addHours((int) $request->validated('hold_hours', 48)),
            ]);

            $booking->events()->create(['from_stage' => null, 'to_stage' => 'hold', 'note' => 'Hold placed']);

            return $booking;
        });

        return BookingResource::make($booking->load('unit.block.project'))->response()->setStatusCode(201);
    }

    public function confirm(Booking $booking): JsonResponse
    {
        return $this->advance($booking, 'booked', unitStatus: 'booked', note: 'Booking confirmed');
    }

    public function complete(Booking $booking): JsonResponse
    {
        return $this->advance($booking, 'sold', unitStatus: 'sold', note: 'Sale registered');
    }

    public function cancel(Booking $booking): JsonResponse
    {
        if (! $booking->canTransitionTo('cancelled')) {
            return ApiResponse::error("Cannot cancel a {$booking->stage} booking.", 'INVALID_TRANSITION', 422);
        }

        DB::transaction(function () use ($booking) {
            $booking->transitionTo('cancelled', 'Cancelled by user');
            $booking->unit->update(['status' => 'available']);
        });

        return BookingResource::make($booking->load('unit.block.project'))->response();
    }

    private function advance(Booking $booking, string $stage, string $unitStatus, string $note): JsonResponse
    {
        if (! $booking->canTransitionTo($stage)) {
            return ApiResponse::error("Cannot move a {$booking->stage} booking to {$stage}.", 'INVALID_TRANSITION', 422);
        }

        DB::transaction(function () use ($booking, $stage, $unitStatus, $note) {
            $booking->transitionTo($stage, $note);
            $booking->unit->update(['status' => $unitStatus]);
        });

        return BookingResource::make($booking->load('unit.block.project'))->response();
    }
}
