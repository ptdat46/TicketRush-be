<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SeatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'zone_id' => $this->zone_id,
            'row_index' => $this->row_index,
            'col_index' => $this->col_index,
            'status' => $this->status,
            'locked_by' => $this->locked_by,
            'locked_at' => $this->locked_at?->toIso8601String(),
            'locked_until' => $this->locked_at?->copy()->addMinutes(10)->toIso8601String(),
            'zone' => $this->whenLoaded('zone', fn () => [
                'id' => $this->zone->id,
                'name' => $this->zone->name,
                'price' => $this->zone->price,
                'color' => $this->zone->color,
            ]),
        ];
    }
}
