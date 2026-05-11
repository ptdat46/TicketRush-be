<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ZoneResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'name' => $this->name,
            'price' => $this->price,
            'color' => $this->color,
            'icon_url' => $this->icon_url,
            'pos_x' => $this->pos_x,
            'pos_y' => $this->pos_y,
            'width' => $this->width,
            'length' => $this->length,
            'is_seating' => (bool) $this->is_seating,
            'seats_count' => $this->whenCounted('seats'),
            'seats' => $this->whenLoaded('seats', fn () => $this->seats->map(fn ($seat) => [
                'id' => $seat->id,
                'row_index' => $seat->row_index,
                'col_index' => $seat->col_index,
                'status' => $seat->status,
            ])),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
