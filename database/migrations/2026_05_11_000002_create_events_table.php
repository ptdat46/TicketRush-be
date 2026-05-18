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
        Schema::create('events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organizer_id')->comment('User account that owns and manages this event.')->constrained('users')->cascadeOnDelete();
            $table->string('name')->comment('Public event name displayed to customers.');
            $table->text('description')->nullable()->comment('Optional event description shown on the event detail page.');
            $table->string('venue')->nullable()->comment('Physical or virtual venue information for the event.');
            $table->timestamp('starts_at')->nullable()->comment('Scheduled start time of the event.');
            $table->timestamp('ends_at')->nullable()->comment('Scheduled end time of the event.');
            $table->string('status')->default('pending')->comment('Admin approval workflow status: pending, approved, or rejected.');
            $table->string('display_type')->comment('Master map template type: rectangular or stadium.');
            $table->unsignedInteger('master_width')->comment('Total width of the master map measured in grid slots.');
            $table->unsignedInteger('master_length')->comment('Total length of the master map measured in grid slots.');
            $table->timestamps();

            $table->index(['organizer_id', 'status']);
            $table->index('display_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
