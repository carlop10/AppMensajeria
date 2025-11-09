
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\PlaceController;
use App\Http\Controllers\courierController;
use App\Http\Controllers\CourierTrackingController;
use App\Http\Controllers\DeliveryController;

Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

Route::middleware('auth:sanctum')->group(function () {
    // Usuarios
    Route::apiResource('users', UserController::class);
    Route::get('/user', [UserController::class, 'me']); // Cambié '/users' por '/user' que es más estándar

    // Lugares
    Route::apiResource('places', PlaceController::class);

    // **RUTAS PARA DELIVERIES - AGREGAR ESTAS**
    Route::apiResource('deliveries', DeliveryController::class);
    Route::post('/deliveries/{delivery}/assign', [DeliveryController::class, 'assignNearest']);

    // Rutas para mensajeros
    Route::post('/couriers/{courier}/location', [CourierTrackingController::class, 'updateLocation']);
    Route::get('/couriers/{courier}/location', [CourierTrackingController::class, 'getCurrentLocation']);
    Route::get('/couriers/{courier}/location-history', [CourierTrackingController::class, 'getLocationHistory']);
    Route::get('couriers/active', [CourierTrackingController::class, 'getActiveCouriers']);

    // Entregas del mensajero
    Route::get('/my-deliveries', [DeliveryController::class, 'myDeliveries']);
    Route::patch('/deliveries/{delivery}/status', [DeliveryController::class, 'updateStatus']);

    // Estado del mensajero
    Route::put('/couriers/{courier}', [courierController::class, 'update']);

    // Rutas CRUD para mensajeros
    Route::apiResource('couriers', \App\Http\Controllers\courierController::class);
    Route::get('/couriers/map', [CourierController::class, 'getCouriersWithLocation']);
});

// Rutas públicas para tracking (si necesitas compartir ubicación)
Route::get('/couriers/{courier}/public-tracking', [CourierTrackingController::class, 'getPublicTracking']);
