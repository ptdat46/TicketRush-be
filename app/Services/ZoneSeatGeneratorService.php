<?php

namespace App\Services;

use App\Models\Seat;
use App\Models\Zone;

class ZoneSeatGeneratorService
{
    public function generateForZone(Zone $zone): void
    {
        if (! $zone->is_seating) {
            return;
        }

        $seats = [];
        $now = now();

        for ($row = 0; $row < $zone->length; $row++) {
            for ($col = 0; $col < $zone->width; $col++) {
                $seats[] = [
                    'zone_id' => $zone->id,
                    'row_index' => $row,
                    'col_index' => $col,
                    'status' => 'available',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        Seat::insert($seats);
    }
}
