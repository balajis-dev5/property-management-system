<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'unit_no' => $this->unit_no,
            'floor' => $this->floor,
            'type' => $this->type,
            'facing' => $this->facing,
            'area_sqft' => $this->area_sqft,
            'price' => $this->price,
            'status' => $this->status,
            'block' => $this->whenLoaded('block', fn () => [
                'id' => $this->block->id,
                'name' => $this->block->name,
                'floors' => $this->block->floors,
            ]),
        ];
    }
}
