<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Zone extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'name',
        'price',
        'color',
        'icon_url',
        'pos_x',
        'pos_y',
        'width',
        'length',
        'is_seating',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'pos_x' => 'integer',
        'pos_y' => 'integer',
        'width' => 'integer',
        'length' => 'integer',
        'is_seating' => 'boolean',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function seats(): HasMany
    {
        return $this->hasMany(Seat::class);
    }
}
