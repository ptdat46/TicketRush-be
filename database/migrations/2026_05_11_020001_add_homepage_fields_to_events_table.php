<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->string('category')->default('music')->after('description')->comment('Event category for homepage tabs and filtering: music, dj, theater, sport, workshop, etc.');
            $table->string('thumbnail_url')->nullable()->after('category')->comment('Card image displayed in event lists.');
            $table->string('banner_url')->nullable()->after('thumbnail_url')->comment('Large promotional image displayed on homepage or event detail.');
            $table->boolean('is_featured')->default(false)->after('banner_url')->comment('Marks event as featured for homepage special sections.');
            $table->unsignedInteger('sort_order')->default(0)->after('is_featured')->comment('Manual display priority for homepage ordering.');

            $table->index(['status', 'category']);
            $table->index(['status', 'is_featured', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->dropIndex(['status', 'category']);
            $table->dropIndex(['status', 'is_featured', 'sort_order']);
            $table->dropColumn(['category', 'thumbnail_url', 'banner_url', 'is_featured', 'sort_order']);
        });
    }
};
