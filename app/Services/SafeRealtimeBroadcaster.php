<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Throwable;

class SafeRealtimeBroadcaster
{
    public function dispatch(object $event): void
    {
        try {
            event($event);
        } catch (Throwable $exception) {
            Log::warning('Realtime broadcast failed.', [
                'event' => $event::class,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
