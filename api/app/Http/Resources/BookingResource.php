<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'stage' => $this->stage,
            'price_snapshot' => $this->price_snapshot,
            'hold_expires_at' => $this->hold_expires_at?->toIso8601String(),
            'unit' => $this->whenLoaded('unit', fn () => [
                'id' => $this->unit->id,
                'unit_no' => $this->unit->unit_no,
                'floor' => $this->unit->floor,
                'type' => $this->unit->type,
                'status' => $this->unit->status,
                'block' => $this->unit->relationLoaded('block') ? [
                    'name' => $this->unit->block->name,
                    'project' => $this->unit->block->relationLoaded('project')
                        ? ['id' => $this->unit->block->project->id, 'name' => $this->unit->block->project->name]
                        : null,
                ] : null,
            ]),
            'events' => $this->whenLoaded('events', fn () => $this->events->map(fn ($e) => [
                'from_stage' => $e->from_stage,
                'to_stage' => $e->to_stage,
                'note' => $e->note,
                'created_at' => $e->created_at?->toIso8601String(),
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
