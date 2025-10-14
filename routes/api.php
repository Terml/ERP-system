<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductionTaskController;

Route::post('/users/register', [UserController::class, 'register']);
Route::middleware('auth:api')->group(function () {
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('/{userId}', [UserController::class, 'show']);
    });
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::post('/', [OrderController::class, 'store'])->middleware('role:manager');
        Route::post('/with-lock', [OrderController::class, 'storeWithLock'])->middleware('role:manager');
        Route::get('/{orderId}', [OrderController::class, 'show']);
        Route::put('/{orderId}', [OrderController::class, 'update'])->middleware('role:manager');
        Route::put('/{orderId}/with-lock', [OrderController::class, 'updateWithLock'])->middleware('role:manager');
        Route::post('/{orderId}/complete', [OrderController::class, 'complete'])->middleware('role:manager');
        Route::post('/{orderId}/complete-with-lock', [OrderController::class, 'completeWithLock'])->middleware('role:manager');
        Route::post('/{orderId}/reject', [OrderController::class, 'reject'])->middleware('role:manager');
        Route::post('/{orderId}/reject-with-lock', [OrderController::class, 'rejectWithLock'])->middleware('role:manager');
    });
    Route::prefix('production-tasks')->group(function () {
        Route::get('/', [ProductionTaskController::class, 'index']);
        Route::post('/', [ProductionTaskController::class, 'store'])->middleware('role:dispatcher');
        Route::post('/with-components', [ProductionTaskController::class, 'storeWithComponents'])->middleware('role:dispatcher');
        Route::get('/{taskId}', [ProductionTaskController::class, 'show']);
        Route::post('/{taskId}/take', [ProductionTaskController::class, 'takeTask'])->middleware('role:master');
        Route::post('/{taskId}/send-for-inspection', [ProductionTaskController::class, 'sendForInspection'])->middleware('role:master');
        Route::post('/{taskId}/accept', [ProductionTaskController::class, 'acceptByOTK'])->middleware('role:otk');
        Route::post('/{taskId}/accept-with-completion', [ProductionTaskController::class, 'acceptByOTKWithCompletion'])->middleware('role:otk');
        Route::post('/{taskId}/reject', [ProductionTaskController::class, 'rejectByOTK'])->middleware('role:otk');
        Route::post('/{taskId}/components', [ProductionTaskController::class, 'addComponent'])->middleware('role:master');
        Route::post('/{taskId}/components/multiple', [ProductionTaskController::class, 'addMultipleComponents'])->middleware('role:master');
        Route::put('/components/{componentId}', [ProductionTaskController::class, 'updateComponent'])->middleware('role:master');
        Route::put('/components/{componentId}/with-lock', [ProductionTaskController::class, 'updateComponentWithLock'])->middleware('role:master');
        Route::delete('/components/{componentId}', [ProductionTaskController::class, 'removeComponent'])->middleware('role:master');
        Route::delete('/components/{componentId}/with-lock', [ProductionTaskController::class, 'removeComponentWithLock'])->middleware('role:master');
        Route::get('/status/{status}', [ProductionTaskController::class, 'getTasksByStatus']);
    });
});
