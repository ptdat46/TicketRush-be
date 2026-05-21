<?php

namespace App\Repositories\Customer;

use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderRepository
{
    public function paginateForCustomer(User $customer, array $filters): LengthAwarePaginator
    {
        return Order::query()
            ->select([
                'id',
                'order_code',
                'customer_id',
                'event_id',
                'subtotal_amount',
                'total_amount',
                'currency',
                'status',
                'payment_method',
                'payment_reference',
                'paid_at',
                'expires_at',
                'created_at',
            ])
            ->where('customer_id', $customer->id)
            ->with(['event:id,name,thumbnail_url,starts_at,venue'])
            ->withCount('tickets')
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 12));
    }
}
