<?php

namespace Tests\Feature;

use App\Events\EventSeatStatusUpdated;
use App\Events\EventWaitingRoomEntryUpdated;
use App\Events\EventWaitingRoomSummaryUpdated;
use App\Models\Event;
use App\Models\Order;
use App\Models\Seat;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event as EventFacade;
use Tests\TestCase;

class CustomerBookingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_lock_available_seats(): void
    {
        [$event, $seats] = $this->createApprovedEventWithSeats();
        $customer = User::factory()->create(['role' => 'customer']);

        $this->joinWaitingRoom($customer, $event)
            ->assertOk()
            ->assertJsonPath('data.status', 'active');

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson("/api/customer/events/{$event->id}/seats/lock", [
                'seat_ids' => [$seats[0]->id, $seats[1]->id],
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Seats locked successfully.')
            ->assertJsonCount(2, 'data');

        $this->assertDatabaseHas('seats', [
            'id' => $seats[0]->id,
            'status' => 'locked',
            'locked_by' => $customer->id,
        ]);
    }

    public function test_customer_cannot_lock_a_seat_locked_by_another_customer(): void
    {
        [$event, $seats] = $this->createApprovedEventWithSeats();
        $customer = User::factory()->create(['role' => 'customer']);
        $otherCustomer = User::factory()->create(['role' => 'customer']);

        $this->joinWaitingRoom($customer, $event)
            ->assertOk()
            ->assertJsonPath('data.status', 'active');

        $seats[0]->update([
            'status' => 'locked',
            'locked_by' => $otherCustomer->id,
            'locked_at' => now(),
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson("/api/customer/events/{$event->id}/seats/lock", [
                'seat_ids' => [$seats[0]->id],
            ]);

        $response->assertStatus(409)
            ->assertJsonPath('message', 'One or more selected seats are already locked.');
    }

    public function test_customer_can_checkout_locked_seats(): void
    {
        [$event, $seats] = $this->createApprovedEventWithSeats();
        $customer = User::factory()->create(['role' => 'customer']);

        $this->joinWaitingRoom($customer, $event)
            ->assertOk()
            ->assertJsonPath('data.status', 'active');

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/customer/events/{$event->id}/seats/lock", [
                'seat_ids' => [$seats[0]->id],
            ])
            ->assertOk();

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson("/api/customer/events/{$event->id}/orders", [
                'seat_ids' => [$seats[0]->id],
                'payment_method' => 'mock',
            ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Checkout completed successfully.')
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonCount(1, 'data.tickets');

        $this->assertDatabaseHas('seats', [
            'id' => $seats[0]->id,
            'status' => 'sold',
            'locked_by' => null,
        ]);

        $this->assertDatabaseHas('tickets', [
            'event_id' => $event->id,
            'seat_id' => $seats[0]->id,
            'customer_id' => $customer->id,
            'status' => 'valid',
        ]);
    }

    public function test_checkout_ignores_payment_method_and_defaults_to_mock(): void
    {
        [$event, $seats] = $this->createApprovedEventWithSeats();
        $customer = User::factory()->create(['role' => 'customer']);

        $this->joinWaitingRoom($customer, $event)
            ->assertOk()
            ->assertJsonPath('data.status', 'active');

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/customer/events/{$event->id}/seats/lock", [
                'seat_ids' => [$seats[0]->id],
            ])
            ->assertOk();

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/customer/events/{$event->id}/orders", [
                'seat_ids' => [$seats[0]->id],
                'payment_method' => 'Mock payment',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.payment_method', 'mock');
    }

    public function test_customer_must_lock_seats_before_checkout(): void
    {
        [$event, $seats] = $this->createApprovedEventWithSeats();
        $customer = User::factory()->create(['role' => 'customer']);

        $this->joinWaitingRoom($customer, $event)
            ->assertOk()
            ->assertJsonPath('data.status', 'active');

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson("/api/customer/events/{$event->id}/orders", [
                'seat_ids' => [$seats[0]->id],
                'payment_method' => 'mock',
            ]);

        $response->assertStatus(409)
            ->assertJsonPath('message', 'Please lock all selected seats before checkout.');
    }

    public function test_customer_must_wait_for_booking_turn_before_locking_seats(): void
    {
        [$event, $seats] = $this->createApprovedEventWithSeats(1);
        $customer = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson("/api/customer/events/{$event->id}/seats/lock", [
                'seat_ids' => [$seats[0]->id],
            ]);

        $response->assertStatus(409)
            ->assertJsonPath('message', 'Please wait until your queue turn before booking this event.');
    }

    public function test_customer_can_load_seat_map_after_entering_booking_turn(): void
    {
        [$event, $seats] = $this->createApprovedEventWithSeats(2);
        $customer = User::factory()->create(['role' => 'customer']);
        $otherCustomer = User::factory()->create(['role' => 'customer']);

        $this->joinWaitingRoom($customer, $event)
            ->assertOk()
            ->assertJsonPath('data.status', 'active');

        $seats[0]->update([
            'status' => 'locked',
            'locked_by' => $customer->id,
            'locked_at' => now(),
        ]);

        $seats[1]->update([
            'status' => 'locked',
            'locked_by' => $otherCustomer->id,
            'locked_at' => now(),
        ]);

        $this->actingAs($customer, 'sanctum')
            ->getJson("/api/customer/events/{$event->id}/seat-map")
            ->assertOk()
            ->assertJsonPath('data.event.id', $event->id)
            ->assertJsonPath('data.event.master_width', 20)
            ->assertJsonPath('data.lock_minutes', 10)
            ->assertJsonPath('data.max_selectable_seats', 10)
            ->assertJsonCount(1, 'data.zones')
            ->assertJsonCount(2, 'data.zones.0.seats')
            ->assertJsonPath('data.zones.0.seats.0.is_locked_by_me', true)
            ->assertJsonPath('data.zones.0.seats.1.is_locked_by_me', false);
    }

    public function test_customer_must_enter_booking_turn_before_loading_seat_map(): void
    {
        [$event] = $this->createApprovedEventWithSeats(1);
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer, 'sanctum')
            ->getJson("/api/customer/events/{$event->id}/seat-map")
            ->assertStatus(409)
            ->assertJsonPath('message', 'Please wait until your queue turn before booking this event.');
    }

    public function test_waiting_room_limits_active_customers_to_total_event_seats(): void
    {
        EventFacade::fake([
            EventWaitingRoomEntryUpdated::class,
            EventWaitingRoomSummaryUpdated::class,
        ]);

        [$event] = $this->createApprovedEventWithSeats(1);
        $firstCustomer = User::factory()->create(['role' => 'customer']);
        $secondCustomer = User::factory()->create(['role' => 'customer']);

        $this->joinWaitingRoom($firstCustomer, $event)
            ->assertOk()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.can_enter_booking', true)
            ->assertJsonPath('data.capacity', 1);

        $this->joinWaitingRoom($secondCustomer, $event)
            ->assertOk()
            ->assertJsonPath('data.status', 'waiting')
            ->assertJsonPath('data.can_enter_booking', false)
            ->assertJsonPath('data.position', 1)
            ->assertJsonPath('data.waiting_count', 1);

        $this->actingAs($firstCustomer, 'sanctum')
            ->deleteJson("/api/customer/events/{$event->id}/waiting-room")
            ->assertOk()
            ->assertJsonPath('data.status', 'left');

        $this->actingAs($secondCustomer, 'sanctum')
            ->getJson("/api/customer/events/{$event->id}/waiting-room")
            ->assertOk()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.can_enter_booking', true);

        EventFacade::assertDispatched(EventWaitingRoomSummaryUpdated::class);
        EventFacade::assertDispatched(EventWaitingRoomEntryUpdated::class);
    }

    public function test_locking_seats_broadcasts_seat_status_updates(): void
    {
        EventFacade::fake([EventSeatStatusUpdated::class]);

        [$event, $seats] = $this->createApprovedEventWithSeats(1);
        $customer = User::factory()->create(['role' => 'customer']);

        $this->joinWaitingRoom($customer, $event)
            ->assertOk()
            ->assertJsonPath('data.status', 'active');

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/customer/events/{$event->id}/seats/lock", [
                'seat_ids' => [$seats[0]->id],
            ])
            ->assertOk();

        EventFacade::assertDispatched(EventSeatStatusUpdated::class);
    }

    public function test_lock_and_release_keep_event_available_seats_count_in_sync(): void
    {
        [$event, $seats] = $this->createApprovedEventWithSeats(2);
        $customer = User::factory()->create(['role' => 'customer']);

        $this->joinWaitingRoom($customer, $event)
            ->assertOk()
            ->assertJsonPath('data.status', 'active');

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/customer/events/{$event->id}/seats/lock", [
                'seat_ids' => [$seats[0]->id],
            ])
            ->assertOk();

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'available_seats_count' => 1,
        ]);

        $this->actingAs($customer, 'sanctum')
            ->deleteJson("/api/customer/events/{$event->id}/seats/lock", [
                'seat_ids' => [$seats[0]->id],
            ])
            ->assertOk();

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'available_seats_count' => 2,
        ]);
    }

    public function test_customer_can_unlock_selected_seats_with_explicit_unlock_endpoint(): void
    {
        [$event, $seats] = $this->createApprovedEventWithSeats(2);
        $customer = User::factory()->create(['role' => 'customer']);

        $this->joinWaitingRoom($customer, $event)
            ->assertOk()
            ->assertJsonPath('data.status', 'active');

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/customer/events/{$event->id}/seats/lock", [
                'seat_ids' => [$seats[0]->id],
            ])
            ->assertOk();

        $this->actingAs($customer, 'sanctum')
            ->deleteJson("/api/customer/events/{$event->id}/seats/unlock", [
                'seat_ids' => [$seats[0]->id],
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Seats released successfully.')
            ->assertJsonPath('data.0.status', 'available');
    }

    public function test_mock_payment_success_creates_paid_order_for_locked_seats(): void
    {
        [$event, $seats] = $this->createApprovedEventWithSeats(2);
        $customer = User::factory()->create(['role' => 'customer']);

        $this->joinWaitingRoom($customer, $event)
            ->assertOk()
            ->assertJsonPath('data.status', 'active');

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/customer/events/{$event->id}/seats/lock", [
                'seat_ids' => [$seats[0]->id],
            ])
            ->assertOk();

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/customer/events/{$event->id}/payments/mock-success", [
                'seat_ids' => [$seats[0]->id],
                'payment_reference' => 'MOCK-WEBHOOK-OK',
            ])
            ->assertCreated()
            ->assertJsonPath('message', 'Mock payment confirmed successfully.')
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.payment_reference', 'MOCK-WEBHOOK-OK')
            ->assertJsonCount(1, 'data.tickets');
    }

    public function test_release_expired_locks_command_releases_stale_locks(): void
    {
        [$event, $seats] = $this->createApprovedEventWithSeats(1);
        $customer = User::factory()->create(['role' => 'customer']);

        $seats[0]->update([
            'status' => 'locked',
            'locked_by' => $customer->id,
            'locked_at' => now()->subMinutes(11),
        ]);

        $event->update(['available_seats_count' => 0]);

        $this->artisan('seats:release-expired-locks')
            ->expectsOutput('Expired seat locks released.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('seats', [
            'id' => $seats[0]->id,
            'status' => 'available',
            'locked_by' => null,
            'locked_at' => null,
        ]);

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'available_seats_count' => 1,
        ]);
    }

    public function test_customer_can_filter_tickets_by_valid_used_and_expired_status(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $validTicket = $this->createTicketForCustomer($customer, [
            'starts_at' => now()->addDays(5),
            'ends_at' => now()->addDays(5)->addHours(2),
        ]);
        $usedTicket = $this->createTicketForCustomer($customer, [
            'starts_at' => now()->addDays(3),
            'ends_at' => now()->addDays(3)->addHours(2),
        ], ['status' => 'used', 'checked_in_at' => now()]);
        $expiredTicket = $this->createTicketForCustomer($customer, [
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->subDay(),
        ]);

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/customer/tickets?status=valid')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $validTicket->id)
            ->assertJsonPath('data.0.display_status', 'valid');

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/customer/tickets?status=used')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $usedTicket->id)
            ->assertJsonPath('data.0.display_status', 'used');

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/customer/tickets?status=expired')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $expiredTicket->id)
            ->assertJsonPath('data.0.display_status', 'expired');
    }

    private function createApprovedEventWithSeats(int $seatCount = 2): array
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
            'status' => 'approved',
            'display_type' => 'rectangular',
            'master_width' => 20,
            'master_length' => 30,
            'total_seats' => $seatCount,
            'available_seats_count' => $seatCount,
            'ticket_sale_starts_at' => now()->subDay(),
            'ticket_sale_ends_at' => now()->addDays(5),
        ]);

        $zone = Zone::create([
            'event_id' => $event->id,
            'name' => 'VIP',
            'price' => 1500000,
            'color' => '#FF4444',
            'pos_x' => 1,
            'pos_y' => 1,
            'width' => $seatCount,
            'length' => 1,
            'is_seating' => true,
        ]);

        $seats = collect(range(1, $seatCount))
            ->map(fn (int $index): Seat => Seat::create([
                'zone_id' => $zone->id,
                'row_index' => 1,
                'col_index' => $index,
                'status' => 'available',
            ]));

        return [$event, $seats->values()];
    }

    private function joinWaitingRoom(User $customer, Event $event)
    {
        return $this->actingAs($customer, 'sanctum')
            ->postJson("/api/customer/events/{$event->id}/waiting-room");
    }

    private function createTicketForCustomer(User $customer, array $eventOverrides = [], array $ticketOverrides = []): Ticket
    {
        [$event, $seats] = $this->createApprovedEventWithSeats(1);
        $event->update($eventOverrides);
        $seat = $seats[0];
        $seat->update(['status' => 'sold']);

        $order = Order::create([
            'order_code' => 'ORD-'.$customer->id.'-'.$seat->id,
            'customer_id' => $customer->id,
            'event_id' => $event->id,
            'subtotal_amount' => 1500000,
            'total_amount' => 1500000,
            'currency' => 'VND',
            'status' => 'paid',
            'payment_method' => 'mock',
            'payment_reference' => 'MOCK-'.$seat->id,
            'paid_at' => now(),
        ]);

        return Ticket::create([
            'ticket_code' => 'TICK-'.$customer->id.'-'.$seat->id,
            'order_id' => $order->id,
            'event_id' => $event->id,
            'seat_id' => $seat->id,
            'customer_id' => $customer->id,
            'qr_code' => 'QR-'.$customer->id.'-'.$seat->id,
            'status' => 'valid',
            'issued_at' => now(),
            ...$ticketOverrides,
        ]);
    }
}
