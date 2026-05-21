<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->index(['status', 'category', 'starts_at'], 'events_public_category_starts_idx');
            $table->index(['status', 'category', 'created_at'], 'events_admin_category_created_idx');
            $table->index(['organizer_id', 'created_at'], 'events_organizer_created_idx');
            $table->index(['organizer_id', 'status', 'created_at'], 'events_organizer_status_created_idx');
            $table->index(['organizer_id', 'category', 'created_at'], 'events_organizer_category_created_idx');
        });

        Schema::table('seats', function (Blueprint $table): void {
            $table->index(['zone_id', 'status'], 'seats_zone_status_idx');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->index(['customer_id', 'created_at'], 'orders_customer_created_idx');
        });

        Schema::table('tickets', function (Blueprint $table): void {
            $table->index(['customer_id', 'issued_at'], 'tickets_customer_issued_idx');
            $table->index(['customer_id', 'status', 'issued_at'], 'tickets_customer_status_issued_idx');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->dropIndex('events_public_category_starts_idx');
            $table->dropIndex('events_admin_category_created_idx');
            $table->dropIndex('events_organizer_created_idx');
            $table->dropIndex('events_organizer_status_created_idx');
            $table->dropIndex('events_organizer_category_created_idx');
        });

        Schema::table('seats', function (Blueprint $table): void {
            $table->dropIndex('seats_zone_status_idx');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex('orders_customer_created_idx');
        });

        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropIndex('tickets_customer_issued_idx');
            $table->dropIndex('tickets_customer_status_issued_idx');
        });
    }
};
