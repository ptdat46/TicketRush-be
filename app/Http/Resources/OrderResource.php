<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_code' => $this->order_code,
            'event' => $this->whenLoaded('event', fn () => [
                'id' => $this->event->id,
                'name' => $this->event->name,
                'thumbnail_url' => $this->event->thumbnail_url,
                'starts_at' => $this->event->starts_at?->toIso8601String(),
                'venue' => $this->event->venue,
            ]),
            'subtotal_amount' => $this->subtotal_amount,
            'total_amount' => $this->total_amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'payment_reference' => $this->payment_reference,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'ticket_count' => $this->whenCounted('tickets'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
