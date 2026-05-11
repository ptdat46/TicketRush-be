<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organizer_id' => $this->organizer_id,
            'organizer' => $this->whenLoaded('organizer', fn () => [
                'id' => $this->organizer->id,
                'name' => $this->organizer->name,
                'organizer_name' => $this->organizer->organizer_name,
            ]),
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'thumbnail_url' => $this->thumbnail_url,
            'banner_url' => $this->banner_url,
            'is_featured' => (bool) $this->is_featured,
            'is_special' => (bool) $this->is_special,
            'sort_order' => $this->sort_order,
            'venue' => $this->venue,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'status' => $this->status,
            'display_type' => $this->display_type,
            'master_width' => $this->master_width,
            'master_length' => $this->master_length,
            'ticket_sale_starts_at' => $this->ticket_sale_starts_at?->toIso8601String(),
            'ticket_sale_ends_at' => $this->ticket_sale_ends_at?->toIso8601String(),
            'bank_name' => $this->bank_name,
            'bank_account_number' => $this->bank_account_number,
            'bank_account_name' => $this->bank_account_name,
            'is_sold_out' => $this->isSoldOut(),
            'ticket_sale_status' => $this->ticketSaleStatus(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
