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
            $table->unsignedInteger('total_seats')
                ->default(0)
                ->comment('Cached count of sellable generated seats for waiting room capacity.');

            $table->index('total_seats');
        });

        DB::table('zones')
            ->join('seats', 'seats.zone_id', '=', 'zones.id')
            ->select('zones.event_id', DB::raw('COUNT(seats.id) as seats_count'))
            ->groupBy('zones.event_id')
            ->orderBy('zones.event_id')
            ->get()
            ->each(function (object $row): void {
                DB::table('events')
                    ->where('id', $row->event_id)
                    ->update(['total_seats' => $row->seats_count]);
            });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->dropIndex(['total_seats']);
            $table->dropColumn('total_seats');
        });
    }
};
