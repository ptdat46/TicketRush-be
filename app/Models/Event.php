<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'organizer_id',
        'name',
        'description',
        'category',
        'thumbnail_url',
        'banner_url',
        'is_featured',
        'is_special',
        'sort_order',
        'venue',
        'starts_at',
        'ends_at',
        'status',
        'display_type',
        'master_width',
        'master_length',
        'total_seats',
        'available_seats_count',
        'ticket_sale_starts_at',
        'ticket_sale_ends_at',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_featured' => 'boolean',
        'is_special' => 'boolean',
        'sort_order' => 'integer',
        'master_width' => 'integer',
        'master_length' => 'integer',
        'total_seats' => 'integer',
        'available_seats_count' => 'integer',
        'ticket_sale_starts_at' => 'datetime',
        'ticket_sale_ends_at' => 'datetime',
    ];

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function zones(): HasMany
    {
        return $this->hasMany(Zone::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function waitingRoomEntries(): HasMany
    {
        return $this->hasMany(EventWaitingRoomEntry::class);
    }

    public function seats(): HasManyThrough
    {
        return $this->hasManyThrough(Seat::class, Zone::class);
    }

    public function ticketSaleStatus(): string
    {
        $now = now();

        if ($this->ticket_sale_starts_at && $now->lt($this->ticket_sale_starts_at)) {
            return 'not_started';
        }

        if ($this->ticket_sale_ends_at && $now->gt($this->ticket_sale_ends_at)) {
            return 'ended';
        }

        if ($this->isSoldOut()) {
            return 'sold_out';
        }

        return 'on_sale';
    }

    public function isSoldOut(): bool
    {
        if (array_key_exists('available_seats_count', $this->attributes)) {
            return (int) $this->attributes['available_seats_count'] === 0;
        }

        $availableCount = $this->seats()->where('status', 'available')->count();

        return $availableCount === 0;
    }
}
