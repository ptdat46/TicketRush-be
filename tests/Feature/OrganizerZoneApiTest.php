<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizerZoneApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_seating_zone_updates_event_total_seats(): void
    {
        $organizer = User::factory()->create(['role' => 'organizer']);
        $event = Event::create([
            'organizer_id' => $organizer->id,
            'name' => 'TicketRush Live',
            'description' => 'A demo event.',
            'category' => 'music',
            'venue' => 'Main Hall',
            'starts_at' => now()->addDays(10),
            'ends_at' => now()->addDays(10)->addHours(3),
            'status' => 'pending',
            'display_type' => 'rectangular',
            'master_width' => 20,
            'master_length' => 30,
            'total_seats' => 0,
            'available_seats_count' => 0,
        ]);

        $this->actingAs($organizer, 'sanctum')
            ->postJson("/api/organizer/events/{$event->id}/zones", [
                'name' => 'VIP',
                'price' => 1500000,
                'color' => '#FF4444',
                'pos_x' => 1,
                'pos_y' => 1,
                'width' => 3,
                'length' => 2,
                'is_seating' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.seats_count', 6);

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'total_seats' => 6,
            'available_seats_count' => 6,
        ]);
    }
}
