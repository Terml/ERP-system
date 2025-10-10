<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductionTaskController;


// // Публичные маршруты
// Route::post('/users/register', [UserController::class, 'register']);
// // Защищенные маршруты
// Route::middleware('auth:api')->group(function () {
//     // Пользователи
//     Route::prefix('users')->group(function () {
//         Route::get('/', [UserController::class, 'index']);
//         Route::get('/{userId}', [UserController::class, 'show']);
//     });
//     // Заказы
//     Route::prefix('orders')->group(function () {
//         Route::get('/', [OrderController::class, 'index']);
//         Route::post('/', [OrderController::class, 'store']);
//         Route::get('/{orderId}', [OrderController::class, 'show']);
//         Route::put('/{orderId}', [OrderController::class, 'update']);
//         Route::post('/{orderId}/complete', [OrderController::class, 'complete']);
//         Route::post('/{orderId}/reject', [OrderController::class, 'reject']);
//     });
//     // Производственные задания
//     Route::prefix('production-tasks')->group(function () {
//         Route::get('/', [ProductionTaskController::class, 'index']);
//         Route::post('/', [ProductionTaskController::class, 'store']);
//         Route::get('/{taskId}', [ProductionTaskController::class, 'show']);
//         Route::post('/{taskId}/take', [ProductionTaskController::class, 'takeTask']);
//         Route::post('/{taskId}/send-for-inspection', [ProductionTaskController::class, 'sendForInspection']);
//         Route::post('/{taskId}/accept', [ProductionTaskController::class, 'acceptByOTK']);
//         Route::post('/{taskId}/reject', [ProductionTaskController::class, 'rejectByOTK']);
//         Route::post('/{taskId}/components', [ProductionTaskController::class, 'addComponent']);
//         Route::put('/components/{componentId}', [ProductionTaskController::class, 'updateComponent']);
//         Route::delete('/components/{componentId}', [ProductionTaskController::class, 'removeComponent']);
//         Route::get('/status/{status}', [ProductionTaskController::class, 'getTasksByStatus']);
//     });
// });
