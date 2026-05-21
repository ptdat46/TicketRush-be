<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_code',
        'order_id',
        'event_id',
        'seat_id',
        'customer_id',
        'qr_code',
        'status',
        'issued_at',
        'checked_in_at',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'checked_in_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function seat(): BelongsTo
    {
        return $this->belongsTo(Seat::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function displayStatus(): string
    {
        if ($this->status === 'used') {
            return 'used';
        }

        if ($this->status === 'void') {
            return 'void';
        }

        if ($this->event?->ends_at && now()->gt($this->event->ends_at)) {
            return 'expired';
        }

        return 'valid';
    }

    public function isExpired(): bool
    {
        return $this->displayStatus() === 'expired';
    }
}
