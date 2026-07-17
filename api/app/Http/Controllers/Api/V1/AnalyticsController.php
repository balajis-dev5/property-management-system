<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Project;
use Illuminate\Http\JsonResponse;

class AnalyticsController extends Controller
{
    /**
     * Inventory by status per project + the booking funnel, in two queries.
     */
    public function summary(): JsonResponse
    {
        $inventory = Project::query()
            ->join('blocks', 'blocks.project_id', '=', 'projects.id')
            ->join('units', 'units.block_id', '=', 'blocks.id')
            ->select('projects.id', 'projects.name')
            ->selectRaw('count(*) as total')
            ->selectRaw("sum(case when units.status = 'available' then 1 else 0 end) as available")
            ->selectRaw("sum(case when units.status = 'held' then 1 else 0 end) as held")
            ->selectRaw("sum(case when units.status = 'booked' then 1 else 0 end) as booked")
            ->selectRaw("sum(case when units.status = 'sold' then 1 else 0 end) as sold")
            ->selectRaw("sum(case when units.status = 'sold' then units.price else 0 end) as sold_value")
            ->groupBy('projects.id', 'projects.name')
            ->orderBy('projects.name')
            ->get();

        $funnel = Booking::query()
            ->selectRaw('count(*) as total')
            ->selectRaw("sum(case when stage in ('booked', 'sold') then 1 else 0 end) as confirmed")
            ->selectRaw("sum(case when stage = 'sold' then 1 else 0 end) as sold")
            ->selectRaw("sum(case when stage = 'cancelled' then 1 else 0 end) as cancelled")
            ->first();

        return response()->json([
            'inventory' => $inventory,
            'funnel' => [
                'holds' => (int) $funnel->total,
                'confirmed' => (int) $funnel->confirmed,
                'sold' => (int) $funnel->sold,
                'cancelled' => (int) $funnel->cancelled,
            ],
        ]);
    }
}
