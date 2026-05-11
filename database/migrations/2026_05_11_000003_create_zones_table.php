<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('zones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->comment('Event master map that contains this zone.')->constrained('events')->cascadeOnDelete();
            $table->string('name')->comment('Zone display name configured by the organizer.');
            $table->decimal('price', 12, 2)->default(0)->comment('Ticket price for seats in this zone; aisle zones should normally be zero.');
            $table->string('color')->comment('Display color used to render this zone on the event map.');
            $table->string('icon_url')->nullable()->comment('Optional icon URL used to visually represent this zone.');
            $table->unsignedInteger('pos_x')->comment('Horizontal position of the zone relative to the master grid.');
            $table->unsignedInteger('pos_y')->comment('Vertical position of the zone relative to the master grid.');
            $table->unsignedInteger('width')->comment('Zone width measured in grid slots.');
            $table->unsignedInteger('length')->comment('Zone length measured in grid slots.');
            $table->boolean('is_seating')->default(true)->comment('True for seating zones that generate seats; false for aisle or walkway zones.');
            $table->timestamps();

            $table->index(['event_id', 'is_seating']);
            $table->index(['pos_x', 'pos_y']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zones');
    }
};
