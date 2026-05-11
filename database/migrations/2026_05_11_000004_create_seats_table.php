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
        Schema::create('seats', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('zone_id')->comment('Seating zone that owns this generated seat.')->constrained('zones')->cascadeOnDelete();
            $table->unsignedInteger('row_index')->comment('Seat row index relative to the parent zone grid.');
            $table->unsignedInteger('col_index')->comment('Seat column index relative to the parent zone grid.');
            $table->string('status')->default('available')->comment('Booking status: available, locked during checkout, or sold after payment confirmation.');
            $table->foreignId('locked_by')->nullable()->comment('Customer who temporarily locked this seat during the 10-minute checkout window.')->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable()->comment('Timestamp when the temporary seat lock started; used by cleanup cron to release expired locks.');
            $table->timestamps();

            $table->unique(['zone_id', 'row_index', 'col_index']);
            $table->index('status');
            $table->index('locked_at');
            $table->index(['status', 'locked_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};
