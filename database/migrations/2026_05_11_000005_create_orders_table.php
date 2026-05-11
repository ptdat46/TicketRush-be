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
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_code')->unique()->comment('Unique business code shown to customers for order lookup and support.');
            $table->foreignId('customer_id')->comment('Customer account that placed this order.')->constrained('users')->cascadeOnDelete();
            $table->foreignId('event_id')->comment('Event for which this order purchases tickets.')->constrained('events')->cascadeOnDelete();
            $table->decimal('subtotal_amount', 12, 2)->default(0)->comment('Sum of ticket prices before fees or discounts.');
            $table->decimal('total_amount', 12, 2)->default(0)->comment('Final amount paid by the customer for this order.');
            $table->string('currency', 3)->default('VND')->comment('Currency code used for all monetary values in this order.');
            $table->string('status')->default('pending')->comment('Order lifecycle status: pending, paid, cancelled, or expired; refunds are intentionally unsupported.');
            $table->string('payment_method')->default('mock')->comment('Payment method used for the simulated checkout flow.');
            $table->string('payment_reference')->nullable()->comment('External or simulated payment transaction reference.');
            $table->timestamp('paid_at')->nullable()->comment('Timestamp when payment was confirmed and seats became sold.');
            $table->timestamp('expires_at')->nullable()->comment('Time when an unpaid order should expire and release locked seats.');
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index(['event_id', 'status']);
            $table->index('paid_at');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
