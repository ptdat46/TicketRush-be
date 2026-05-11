<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->string('bank_name')->nullable()->after('ticket_sale_ends_at')->comment('Tên ngân hàng nhận doanh thu.');
            $table->string('bank_account_number')->nullable()->after('bank_name')->comment('Số tài khoản ngân hàng.');
            $table->string('bank_account_name')->nullable()->after('bank_account_number')->comment('Tên chủ tài khoản ngân hàng.');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->dropColumn(['bank_name', 'bank_account_number', 'bank_account_name']);
        });
    }
};
