<?php

use App\Http\Controllers\Api\Admin\EventController as AdminEventController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Customer\MockPaymentController;
use App\Http\Controllers\Api\Customer\OrderController;
use App\Http\Controllers\Api\Customer\SeatLockController;
use App\Http\Controllers\Api\Customer\SeatMapController;
use App\Http\Controllers\Api\Customer\TicketController;
use App\Http\Controllers\Api\Customer\WaitingRoomController;
use App\Http\Controllers\Api\Organizer\EventController as OrganizerEventController;
use App\Http\Controllers\Api\Organizer\ZoneController;
use App\Http\Controllers\Api\Public\EventController as PublicEventController;
use Illuminate\Support\Facades\Route;

Route::get('/events', [PublicEventController::class, 'index']);
Route::get('/events/{event}', [PublicEventController::class, 'show']);
Route::get('/categories', [PublicEventController::class, 'categoriesList']);

Route::post('/auth/register/customer', [AuthController::class, 'registerCustomer']);
Route::post('/auth/register/organizer', [AuthController::class, 'registerOrganizer']);
Route::post('/auth/verify', [AuthController::class, 'verifyEmail']);
Route::post('/auth/resend-code', [AuthController::class, 'resendCode']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function (): void {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);

    Route::middleware(['role:admin'])->group(function (): void {
        Route::get('/admin/ping', fn () => response()->json([
            'success' => true,
            'message' => 'Admin access granted.',
        ]));

        Route::get('/admin/events', [AdminEventController::class, 'index']);
        Route::get('/admin/events/pending', [AdminEventController::class, 'pending']);
        Route::get('/admin/events/{event}', [AdminEventController::class, 'show']);
        Route::put('/admin/events/{event}', [AdminEventController::class, 'update']);
        Route::patch('/admin/events/{event}/review', [AdminEventController::class, 'review']);
        Route::patch('/admin/events/{event}/homepage', [AdminEventController::class, 'homepage']);
    });

    Route::middleware(['role:organizer'])->group(function (): void {
        Route::get('/organizer/ping', fn () => response()->json([
            'success' => true,
            'message' => 'Organizer access granted.',
        ]));

        Route::apiResource('/organizer/events', OrganizerEventController::class);

        Route::get('/organizer/events/{event}/zones', [ZoneController::class, 'index']);
        Route::post('/organizer/events/{event}/zones', [ZoneController::class, 'store']);
        Route::get('/organizer/events/{event}/zones/{zone}', [ZoneController::class, 'show']);
        Route::put('/organizer/events/{event}/zones/{zone}', [ZoneController::class, 'update']);
        Route::delete('/organizer/events/{event}/zones/{zone}', [ZoneController::class, 'destroy']);
    });

    Route::middleware(['role:customer'])->group(function (): void {
        Route::get('/customer/ping', fn () => response()->json([
            'success' => true,
            'message' => 'Customer access granted.',
        ]));

        Route::post('/customer/events/{event}/waiting-room', [WaitingRoomController::class, 'store']);
        Route::get('/customer/events/{event}/waiting-room', [WaitingRoomController::class, 'show']);
        Route::delete('/customer/events/{event}/waiting-room', [WaitingRoomController::class, 'destroy']);
        Route::get('/customer/events/{event}/seat-map', [SeatMapController::class, 'show']);
        Route::post('/customer/events/{event}/seats/lock', [SeatLockController::class, 'store']);
        Route::delete('/customer/events/{event}/seats/lock', [SeatLockController::class, 'destroy']);
        Route::delete('/customer/events/{event}/seats/unlock', [SeatLockController::class, 'destroy']);
        Route::post('/customer/events/{event}/payments/mock-success', [MockPaymentController::class, 'success']);
        Route::post('/customer/events/{event}/orders', [OrderController::class, 'store']);
        Route::apiResource('/customer/orders', OrderController::class)->only(['index', 'show']);
        Route::apiResource('/customer/tickets', TicketController::class)->only(['index', 'show']);
    });
});
