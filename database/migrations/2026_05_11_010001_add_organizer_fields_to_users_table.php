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
        Schema::table('users', function (Blueprint $table): void {
            $table->string('organizer_name')->nullable()->after('birthday')->comment('Tên tổ chức của Organizer; chỉ áp dụng cho role organizer.');
            $table->string('tax_code')->nullable()->after('organizer_name')->comment('Mã số thuế của Organizer; dùng để xác minh tổ chức sự kiện.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['organizer_name', 'tax_code']);
        });
    }
};
