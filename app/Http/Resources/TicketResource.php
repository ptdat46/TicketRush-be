<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_code' => $this->ticket_code,
            'qr_code' => $this->qr_code,
            'status' => $this->status,
            'issued_at' => $this->issued_at?->toIso8601String(),
            'checked_in_at' => $this->checked_in_at?->toIso8601String(),
            'event' => $this->whenLoaded('event', fn () => [
                'id' => $this->event->id,
                'name' => $this->event->name,
                'thumbnail_url' => $this->event->thumbnail_url,
                'starts_at' => $this->event->starts_at?->toIso8601String(),
                'venue' => $this->event->venue,
            ]),
            'seat' => $this->whenLoaded('seat', fn () => [
                'id' => $this->seat->id,
                'row_index' => $this->seat->row_index,
                'col_index' => $this->seat->col_index,
                'zone' => $this->whenLoaded('seat.zone', fn () => [
                    'id' => $this->seat->zone->id,
                    'name' => $this->seat->zone->name,
                    'price' => $this->seat->zone->price,
                ]),
            ]),
            'order' => $this->whenLoaded('order', fn () => [
                'id' => $this->order->id,
                'order_code' => $this->order->order_code,
                'total_amount' => $this->order->total_amount,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
