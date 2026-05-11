<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->timestamp('ticket_sale_starts_at')->nullable()->after('ends_at')->comment('Thời điểm bắt đầu mở bán vé.');
            $table->timestamp('ticket_sale_ends_at')->nullable()->after('ticket_sale_starts_at')->comment('Thời điểm kết thúc mở bán vé.');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->dropColumn(['ticket_sale_starts_at', 'ticket_sale_ends_at']);
        });
    }
};
