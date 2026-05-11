<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Seat extends Model
{
    use HasFactory;

    protected $fillable = [
        'zone_id',
        'row_index',
        'col_index',
        'status',
        'locked_by',
        'locked_at',
    ];

    protected $casts = [
        'row_index' => 'integer',
        'col_index' => 'integer',
        'locked_at' => 'datetime',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }
}
