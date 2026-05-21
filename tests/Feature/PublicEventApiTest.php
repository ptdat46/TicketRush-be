<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PublicEventApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_event_list_uses_cached_seat_counts(): void
    {
        $organizer = User::factory()->create(['role' => 'organizer']);

        Event::create([
            'organizer_id' => $organizer->id,
            'name' => 'TicketRush Live',
            'description' => 'A demo event.',
            'category' => 'music',
            'venue' => 'Main Hall',
            'starts_at' => now()->addDays(10),
            'ends_at' => now()->addDays(10)->addHours(3),
            'status' => 'approved',
            'display_type' => 'rectangular',
            'master_width' => 20,
            'master_length' => 30,
            'total_seats' => 100,
            'available_seats_count' => 25,
            'ticket_sale_starts_at' => now()->subDay(),
            'ticket_sale_ends_at' => now()->addDays(5),
        ]);

        DB::enableQueryLog();

        $this->getJson('/api/events')
            ->assertOk()
            ->assertJsonPath('data.0.total_seats', 100)
            ->assertJsonPath('data.0.available_seats_count', 25)
            ->assertJsonPath('data.0.is_sold_out', false)
            ->assertJsonPath('data.0.ticket_sale_status', 'on_sale');

        $queries = collect(DB::getQueryLog())
            ->pluck('query')
            ->implode(' ');

        $this->assertStringNotContainsString('from "seats"', strtolower($queries));
        $this->assertStringNotContainsString('join "seats"', strtolower($queries));
    }

    public function test_homepage_can_filter_sold_out_events_from_cached_counts(): void
    {
        $organizer = User::factory()->create(['role' => 'organizer']);

        $soldOutEvent = $this->createEvent($organizer, [
            'name' => 'Sold Out',
            'total_seats' => 10,
            'available_seats_count' => 0,
        ]);

        $this->createEvent($organizer, [
            'name' => 'Still Available',
            'total_seats' => 10,
            'available_seats_count' => 5,
        ]);

        $this->getJson('/api/events?ticket_status=sold_out')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $soldOutEvent->id);
    }

    private function createEvent(User $organizer, array $overrides = []): Event
    {
        return Event::create([
            'organizer_id' => $organizer->id,
            'name' => 'TicketRush Live',
            'description' => 'A demo event.',
            'category' => 'music',
            'venue' => 'Main Hall',
            'starts_at' => now()->addDays(10),
            'ends_at' => now()->addDays(10)->addHours(3),
            'status' => 'approved',
            'display_type' => 'rectangular',
            'master_width' => 20,
            'master_length' => 30,
            'total_seats' => 10,
            'available_seats_count' => 10,
            'ticket_sale_starts_at' => now()->subDay(),
            'ticket_sale_ends_at' => now()->addDays(5),
            ...$overrides,
        ]);
    }
}
