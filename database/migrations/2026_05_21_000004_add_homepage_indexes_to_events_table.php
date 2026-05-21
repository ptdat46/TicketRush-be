<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->index(['status', 'is_featured', 'sort_order', 'starts_at'], 'events_homepage_default_idx');
            $table->index(['status', 'is_special', 'sort_order', 'starts_at'], 'events_homepage_special_idx');
            $table->index(['status', 'ticket_sale_starts_at', 'ticket_sale_ends_at', 'available_seats_count'], 'events_sale_status_idx');
            $table->index(['status', 'starts_at'], 'events_status_starts_idx');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->dropIndex('events_homepage_default_idx');
            $table->dropIndex('events_homepage_special_idx');
            $table->dropIndex('events_sale_status_idx');
            $table->dropIndex('events_status_starts_idx');
        });
    }
};
