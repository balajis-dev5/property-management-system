<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\UnitResource;
use App\Models\Project;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $projects = Project::query()
            ->with('blocks')
            ->withCount([
                'units',
                'units as available_count' => fn ($q) => $q->where('status', 'available'),
                'units as held_count' => fn ($q) => $q->where('status', 'held'),
                'units as booked_count' => fn ($q) => $q->where('status', 'booked'),
                'units as sold_count' => fn ($q) => $q->where('status', 'sold'),
            ])
            ->orderBy('name')
            ->get();

        return ProjectResource::collection($projects);
    }

    public function show(Project $project): ProjectResource
    {
        $project->load('blocks')->loadCount([
            'units',
            'units as available_count' => fn ($q) => $q->where('status', 'available'),
            'units as held_count' => fn ($q) => $q->where('status', 'held'),
            'units as booked_count' => fn ($q) => $q->where('status', 'booked'),
            'units as sold_count' => fn ($q) => $q->where('status', 'sold'),
        ]);

        return ProjectResource::make($project);
    }

    /**
     * The availability grid: every unit of the project, filterable.
     * The frontend lays them out block × floor; the API stays flat.
     */
    public function grid(Request $request, Project $project): AnonymousResourceCollection
    {
        $units = Unit::query()
            ->whereIn('block_id', $project->blocks()->pluck('id'))
            ->with('block:id,name,floors')
            ->when($request->query('type'), fn ($q, $type) => $q->where('type', $type))
            ->when($request->query('facing'), fn ($q, $facing) => $q->where('facing', $facing))
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('max_price'), fn ($q, $max) => $q->where('price', '<=', (int) $max))
            ->orderBy('block_id')
            ->orderBy('floor')
            ->orderBy('unit_no')
            ->get();

        return UnitResource::collection($units);
    }
}
