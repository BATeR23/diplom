<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BalanceController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\RideController;
use App\Http\Controllers\RideMessageController;
use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;
Route::any('/test', function () {
    return response()->json(['status' => 'API works!', 'time' => time()]);
});
Route::get('/rides/statistics', [RideController::class, 'driverStatistics']);

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::get('/rides/search', [RideController::class, 'search']);
Route::get('/rides/{ride}', [RideController::class, 'show']);

// Публичные маршруты для просмотра документов (с поддержкой токена через query параметр)
Route::middleware([\App\Http\Middleware\HandleTokenAuth::class, 'auth:sanctum'])->group(function () {
    Route::get('/admin/balance-requests/{request}/receipt', [\App\Http\Controllers\AdminBalanceController::class, 'getReceipt']);
    Route::get('/admin/vehicle-verifications/{vehicle}/document/{type}', [\App\Http\Controllers\AdminVehicleController::class, 'getDocument']);
});

Route::middleware('auth:sanctum')->group(function () {
    // Маршрут для авторизации broadcasting (WebSocket) каналов
    Route::post('/broadcasting/auth', function (\Illuminate\Http\Request $request) {
        return \Illuminate\Support\Facades\Broadcast::auth($request);
    });

    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::get('/auth/profile/statistics/pdf', [AuthController::class, 'downloadProfileStatistics']);

    Route::apiResource('vehicles', VehicleController::class);

    Route::get('/rides', [RideController::class, 'index']);
    Route::get('/chats/rides', [RideController::class, 'getChatRides']);
    Route::post('/rides', [RideController::class, 'store']);
    Route::match(['put', 'patch'], '/rides/{ride}', [RideController::class, 'update']);
    Route::delete('/rides/{ride}', [RideController::class, 'destroy']);
    Route::post('/rides/{ride}/complete', [RideController::class, 'complete']);

    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/rides/{ride}/bookings', [BookingController::class, 'store']);
    Route::patch('/bookings/{booking}', [BookingController::class, 'updateStatus']);

    Route::get('/rides/{ride}/messages', [RideMessageController::class, 'index']);
    Route::post('/rides/{ride}/messages', [RideMessageController::class, 'store']);

    Route::get('/rides/{ride}/reviews', [ReviewController::class, 'index']);
    Route::post('/rides/{ride}/reviews', [ReviewController::class, 'store']);
    Route::get('/reviews/me', [ReviewController::class, 'myReviews']);

    // Notifications routes
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [\App\Http\Controllers\NotificationController::class, 'unreadCount']);
    Route::post('/notifications/{notification}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead']);

    // Balance routes
    Route::get('/balance', [BalanceController::class, 'current']);
    Route::post('/balance/recharge', [BalanceController::class, 'recharge']);
    Route::get('/balance/history', [BalanceController::class, 'history']);

    // Admin routes
    Route::prefix('admin')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\AdminController::class, 'dashboard'])->middleware('role:admin');

        // Users
        Route::get('/users', [\App\Http\Controllers\AdminController::class, 'getUsers'])->middleware('role:admin');
        Route::get('/users/{user}', [\App\Http\Controllers\AdminController::class, 'getUser'])->middleware('role:admin');
        Route::post('/users', [\App\Http\Controllers\AdminController::class, 'createUser'])->middleware('role:admin');
        Route::put('/users/{user}', [\App\Http\Controllers\AdminController::class, 'updateUser'])->middleware('role:admin');
        Route::delete('/users/{user}', [\App\Http\Controllers\AdminController::class, 'deleteUser'])->middleware('role:admin');

        // Rides
        Route::get('/rides', [\App\Http\Controllers\AdminController::class, 'getRides'])->middleware('role:admin');
        Route::get('/rides/{ride}', [\App\Http\Controllers\AdminController::class, 'getRide'])->middleware('role:admin');
        Route::patch('/rides/{ride}', [\App\Http\Controllers\AdminController::class, 'updateRide'])->middleware('role:admin');
        Route::delete('/rides/{ride}', [\App\Http\Controllers\AdminController::class, 'deleteRide'])->middleware('role:admin');

        // Bookings
        Route::get('/bookings', [\App\Http\Controllers\AdminController::class, 'getBookings'])->middleware('role:admin');
        Route::get('/bookings/{booking}', [\App\Http\Controllers\AdminController::class, 'getBooking'])->middleware('role:admin');
        Route::patch('/bookings/{booking}', [\App\Http\Controllers\AdminController::class, 'updateBooking'])->middleware('role:admin');
        Route::delete('/bookings/{booking}', [\App\Http\Controllers\AdminController::class, 'deleteBooking'])->middleware('role:admin');

        // Vehicles
        Route::get('/vehicles', [\App\Http\Controllers\AdminController::class, 'getVehicles'])->middleware('role:admin');
        Route::get('/vehicles/{vehicle}', [\App\Http\Controllers\AdminController::class, 'getVehicle'])->middleware('role:admin');
        Route::delete('/vehicles/{vehicle}', [\App\Http\Controllers\AdminController::class, 'deleteVehicle'])->middleware('role:admin');

        // Vehicle verification
        Route::get('/vehicle-verifications', [\App\Http\Controllers\AdminVehicleController::class, 'getPendingVehicles'])->middleware('role:admin,manager');
        Route::get('/vehicle-verifications/{vehicle}', [\App\Http\Controllers\AdminVehicleController::class, 'getVehicle'])->middleware('role:admin,manager');
        Route::post('/vehicle-verifications/{vehicle}/approve', [\App\Http\Controllers\AdminVehicleController::class, 'approveVehicle'])->middleware('role:admin,manager');
        Route::post('/vehicle-verifications/{vehicle}/reject', [\App\Http\Controllers\AdminVehicleController::class, 'rejectVehicle'])->middleware('role:admin,manager');

        // Statistics
        Route::get('/stats', [\App\Http\Controllers\AdminController::class, 'stats'])->middleware('role:admin');

        // Balance management
        Route::get('/balance-requests', [\App\Http\Controllers\AdminBalanceController::class, 'getRechargeRequests'])->middleware('role:admin,manager');
        Route::get('/balance-requests/{request}', [\App\Http\Controllers\AdminBalanceController::class, 'getRechargeRequest'])->middleware('role:admin,manager');
        Route::post('/balance-requests/{request}/approve', [\App\Http\Controllers\AdminBalanceController::class, 'approveRechargeRequest'])->middleware('role:admin,manager');
        Route::post('/balance-requests/{request}/reject', [\App\Http\Controllers\AdminBalanceController::class, 'rejectRechargeRequest'])->middleware('role:admin,manager');

        // Audit logs
        Route::get('/audit-logs', [\App\Http\Controllers\AdminAuditLogController::class, 'index'])->middleware('role:admin,manager');
    });
});
