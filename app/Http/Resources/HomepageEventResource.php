<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HomepageEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'thumbnail_url' => $this->thumbnail_url,
            'banner_url' => $this->banner_url,
            'venue' => $this->venue,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'ticket_sale_starts_at' => $this->ticket_sale_starts_at?->toIso8601String(),
            'ticket_sale_ends_at' => $this->ticket_sale_ends_at?->toIso8601String(),
            'is_sold_out' => $this->isSoldOut(),
            'ticket_sale_status' => $this->ticketSaleStatus(),
            'display_type' => $this->display_type,
            'total_seats' => $this->total_seats,
            'available_seats_count' => $this->available_seats_count,
            'is_featured' => (bool) $this->is_featured,
            'is_special' => (bool) $this->is_special,
            'organizer' => $this->whenLoaded('organizer', fn () => [
                'id' => $this->organizer->id,
                'name' => $this->organizer->name,
                'organizer_name' => $this->organizer->organizer_name,
            ]),
        ];
    }
}
