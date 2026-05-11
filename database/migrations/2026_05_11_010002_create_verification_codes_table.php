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
        Schema::create('verification_codes', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->comment('Email nhận mã xác thực.');
            $table->string('code', 6)->comment('Mã xác thực gồm 6 chữ số.');
            $table->string('type')->default('register')->comment('Loại mã: register, password_reset...');
            $table->timestamp('expires_at')->comment('Thời điểm mã xác thực hết hiệu lực.');
            $table->timestamps();

            $table->index(['email', 'type', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verification_codes');
    }
};
