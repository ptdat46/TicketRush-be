<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->unsignedInteger('available_seats_count')
                ->default(0)
                ->after('total_seats')
                ->comment('Cached count of seats that are currently available.');

            $table->index('available_seats_count');
        });

        DB::table('zones')
            ->join('seats', 'seats.zone_id', '=', 'zones.id')
            ->select(
                'zones.event_id',
                DB::raw("SUM(CASE WHEN seats.status = 'available' THEN 1 ELSE 0 END) as available_count")
            )
            ->groupBy('zones.event_id')
            ->orderBy('zones.event_id')
            ->get()
            ->each(function (object $row): void {
                DB::table('events')
                    ->where('id', $row->event_id)
                    ->update(['available_seats_count' => $row->available_count]);
            });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->dropIndex(['available_seats_count']);
            $table->dropColumn('available_seats_count');
        });
    }
};
