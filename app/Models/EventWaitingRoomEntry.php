<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventWaitingRoomEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'customer_id',
        'status',
        'joined_at',
        'admitted_at',
        'last_seen_at',
        'left_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'admitted_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'left_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
