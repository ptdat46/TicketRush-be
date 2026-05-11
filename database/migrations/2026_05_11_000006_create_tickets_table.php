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
        Schema::create('tickets', function (Blueprint $table): void {
            $table->id();
            $table->string('ticket_code')->unique()->comment('Unique business code for this issued ticket.');
            $table->foreignId('order_id')->comment('Paid order that generated this ticket.')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('event_id')->comment('Event this ticket grants access to.')->constrained('events')->cascadeOnDelete();
            $table->foreignId('seat_id')->comment('Sold seat assigned to this ticket.')->constrained('seats')->restrictOnDelete();
            $table->foreignId('customer_id')->comment('Customer account that owns this ticket.')->constrained('users')->cascadeOnDelete();
            $table->string('qr_code')->unique()->comment('Unique QR payload or token used for ticket validation at check-in.');
            $table->string('status')->default('valid')->comment('Ticket usage status: valid, used, or void; refund status is not supported.');
            $table->timestamp('issued_at')->useCurrent()->comment('Timestamp when the ticket was generated after payment confirmation.');
            $table->timestamp('checked_in_at')->nullable()->comment('Timestamp when the ticket QR was successfully checked in.');
            $table->timestamps();

            $table->unique(['order_id', 'seat_id']);
            $table->unique('seat_id');
            $table->index(['customer_id', 'status']);
            $table->index(['event_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
