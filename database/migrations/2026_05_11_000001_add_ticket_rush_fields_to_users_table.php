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
            $table->string('role')->default('customer')->after('password')->comment('Business role of the account: admin approves events, organizer creates events, customer buys tickets.');
            $table->string('gender')->nullable()->after('role')->comment('Optional gender profile information for customer analytics and personalization.');
            $table->date('birthday')->nullable()->after('gender')->comment('Optional birthday profile information for age-based reporting and personalization.');

            $table->index('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'gender', 'birthday']);
        });
    }
};
