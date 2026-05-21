<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Order;
use App\Models\Seat;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerBookingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_lock_available_seats(): void
    {
        [$event, $seats] = $this->createApprovedEventWithSeats();
        $customer = User::factory()->create(['role' => 'customer']);

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

    public function test_customer_must_lock_seats_before_checkout(): void
    {
        [$event, $seats] = $this->createApprovedEventWithSeats();
        $customer = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson("/api/customer/events/{$event->id}/orders", [
                'seat_ids' => [$seats[0]->id],
                'payment_method' => 'mock',
            ]);

        $response->assertStatus(409)
            ->assertJsonPath('message', 'Please lock all selected seats before checkout.');
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
