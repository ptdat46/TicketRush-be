<?php

use App\Http\Controllers\Api\AdminEventController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerOrderController;
use App\Http\Controllers\Api\CustomerSeatLockController;
use App\Http\Controllers\Api\CustomerSeatMapController;
use App\Http\Controllers\Api\CustomerTicketController;
use App\Http\Controllers\Api\CustomerWaitingRoomController;
use App\Http\Controllers\Api\OrganizerEventController;
use App\Http\Controllers\Api\OrganizerZoneController;
use App\Http\Controllers\Api\PublicEventController;
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

        Route::get('/organizer/events/{event}/zones', [OrganizerZoneController::class, 'index']);
        Route::post('/organizer/events/{event}/zones', [OrganizerZoneController::class, 'store']);
        Route::get('/organizer/events/{event}/zones/{zone}', [OrganizerZoneController::class, 'show']);
        Route::put('/organizer/events/{event}/zones/{zone}', [OrganizerZoneController::class, 'update']);
        Route::delete('/organizer/events/{event}/zones/{zone}', [OrganizerZoneController::class, 'destroy']);
    });

    Route::middleware(['role:customer'])->group(function (): void {
        Route::get('/customer/ping', fn () => response()->json([
            'success' => true,
            'message' => 'Customer access granted.',
        ]));

        Route::post('/customer/events/{event}/waiting-room', [CustomerWaitingRoomController::class, 'store']);
        Route::get('/customer/events/{event}/waiting-room', [CustomerWaitingRoomController::class, 'show']);
        Route::delete('/customer/events/{event}/waiting-room', [CustomerWaitingRoomController::class, 'destroy']);
        Route::get('/customer/events/{event}/seat-map', [CustomerSeatMapController::class, 'show']);
        Route::post('/customer/events/{event}/seats/lock', [CustomerSeatLockController::class, 'store']);
        Route::delete('/customer/events/{event}/seats/lock', [CustomerSeatLockController::class, 'destroy']);
        Route::post('/customer/events/{event}/orders', [CustomerOrderController::class, 'store']);
        Route::apiResource('/customer/orders', CustomerOrderController::class)->only(['index', 'show']);
        Route::apiResource('/customer/tickets', CustomerTicketController::class)->only(['index', 'show']);
    });
});
