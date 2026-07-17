<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'city' => $this->city,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'description' => $this->description,
            'blocks' => $this->whenLoaded('blocks', fn () => $this->blocks->map(fn ($b) => [
                'id' => $b->id,
                'name' => $b->name,
                'floors' => $b->floors,
            ])),
            'units_count' => $this->whenCounted('units'),
            'available_count' => $this->whenCounted('available_count'),
            'held_count' => $this->whenCounted('held_count'),
            'booked_count' => $this->whenCounted('booked_count'),
            'sold_count' => $this->whenCounted('sold_count'),
        ];
    }
}
