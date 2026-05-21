<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminEventApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_pending_events(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $organizer = User::factory()->create(['role' => 'organizer']);

        $pendingEvent = $this->createEvent($organizer, ['status' => 'pending']);
        $this->createEvent($organizer, ['status' => 'approved']);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/events/pending');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $pendingEvent->id)
            ->assertJsonPath('data.0.status', 'pending');
    }

    public function test_admin_can_review_a_pending_event(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $organizer = User::factory()->create(['role' => 'organizer']);
        $event = $this->createEvent($organizer, ['status' => 'pending']);

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/events/{$event->id}/review", [
                'status' => 'approved',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'status' => 'approved',
        ]);
    }

    public function test_review_endpoint_only_accepts_pending_events(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $organizer = User::factory()->create(['role' => 'organizer']);
        $event = $this->createEvent($organizer, ['status' => 'approved']);

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/events/{$event->id}/review", [
                'status' => 'rejected',
            ]);

        $response->assertStatus(422);

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'status' => 'approved',
        ]);
    }

    public function test_admin_can_update_homepage_flags(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $organizer = User::factory()->create(['role' => 'organizer']);
        $event = $this->createEvent($organizer);

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/events/{$event->id}/homepage", [
                'is_featured' => true,
                'is_special' => true,
                'sort_order' => 5,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.is_featured', true)
            ->assertJsonPath('data.is_special', true)
            ->assertJsonPath('data.sort_order', 5);
    }

    public function test_admin_update_rejects_bank_and_map_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $organizer = User::factory()->create(['role' => 'organizer']);
        $event = $this->createEvent($organizer, [
            'bank_name' => 'Original Bank',
            'display_type' => 'rectangular',
            'master_width' => 20,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/events/{$event->id}", [
                'name' => 'Updated by Admin',
                'bank_name' => 'Changed Bank',
                'display_type' => 'stadium',
                'master_width' => 99,
                'total_seats' => 999,
                'available_seats_count' => 999,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['bank_name', 'display_type', 'master_width', 'total_seats', 'available_seats_count']);

        $event->refresh();

        $this->assertSame('Original Bank', $event->bank_name);
        $this->assertSame('rectangular', $event->display_type);
        $this->assertSame(20, $event->master_width);
    }

    public function test_admin_can_update_allowed_event_information(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $organizer = User::factory()->create(['role' => 'organizer']);
        $event = $this->createEvent($organizer);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/events/{$event->id}", [
                'name' => 'Updated Event Name',
                'venue' => 'Updated Venue',
                'status' => 'approved',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Event Name')
            ->assertJsonPath('data.venue', 'Updated Venue')
            ->assertJsonPath('data.status', 'approved');
    }

    private function createEvent(User $organizer, array $overrides = []): Event
    {
        return Event::create([
            'organizer_id' => $organizer->id,
            'name' => 'TicketRush Live',
            'description' => 'A demo event.',
            'category' => 'music',
            'thumbnail_url' => 'https://example.com/thumb.jpg',
            'banner_url' => 'https://example.com/banner.jpg',
            'is_featured' => false,
            'is_special' => false,
            'sort_order' => 0,
            'venue' => 'Main Hall',
            'starts_at' => now()->addDays(10),
            'ends_at' => now()->addDays(10)->addHours(3),
            'status' => 'pending',
            'display_type' => 'rectangular',
            'master_width' => 20,
            'master_length' => 30,
            'ticket_sale_starts_at' => now()->addDay(),
            'ticket_sale_ends_at' => now()->addDays(9),
            'bank_name' => 'Ticket Bank',
            'bank_account_number' => '123456789',
            'bank_account_name' => 'TicketRush Organizer',
            ...$overrides,
        ]);
    }
}
