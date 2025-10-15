<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductionTaskController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\DocumentController;

Route::middleware('web')->group(function () {
    Route::get('/user', function () {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        return response()->json(Auth::user());
    });
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('/{userId}', [UserController::class, 'show']);
        Route::post('/', [UserController::class, 'store']); // Create user
        Route::put('/{userId}', [UserController::class, 'update']); // Update user
        Route::delete('/{userId}', [UserController::class, 'destroy']); // Delete user
        Route::post('/register', [UserController::class, 'register']); // Register user
    });
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::get('/statistics', [ProductController::class, 'statistics']);
        Route::get('/select', [ProductController::class, 'select']);
        Route::get('/cache-info', [ProductController::class, 'cacheInfo']);
        Route::post('/clear-cache', [ProductController::class, 'clearCache']);
        Route::get('/{id}', [ProductController::class, 'show']);
        Route::post('/', [ProductController::class, 'store']);
        Route::put('/{id}', [ProductController::class, 'update']);
        Route::delete('/{id}', [ProductController::class, 'destroy']);
    });
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::post('/', [OrderController::class, 'store'])->middleware('role:manager');
        Route::post('/with-lock', [OrderController::class, 'storeWithLock'])->middleware('role:manager');
        Route::get('/{orderId}', [OrderController::class, 'show']);
        Route::put('/{orderId}', [OrderController::class, 'update'])->middleware('role:admin,manager');
        Route::put('/{orderId}/with-lock', [OrderController::class, 'updateWithLock'])->middleware('role:admin,manager');
        Route::post('/{orderId}/complete', [OrderController::class, 'complete'])->middleware('role:admin,manager');
        Route::post('/{orderId}/complete-with-lock', [OrderController::class, 'completeWithLock'])->middleware('role:admin,manager');
        Route::post('/{orderId}/reject', [OrderController::class, 'reject'])->middleware('role:admin,manager');
        Route::post('/{orderId}/reject-with-lock', [OrderController::class, 'rejectWithLock'])->middleware('role:admin,manager');
        Route::get('/statistics', [OrderController::class, 'statistics']);
        Route::get('/statistics/by-status', [OrderController::class, 'statisticsByStatus']);
        Route::get('/statistics/by-month', [OrderController::class, 'statisticsByMonth']);
        Route::get('/statistics/by-company', [OrderController::class, 'statisticsByCompany']);
        Route::get('/performance-metrics', [OrderController::class, 'performanceMetrics']);
        Route::get('/cache-info', [OrderController::class, 'cacheInfo']);
        Route::post('/clear-cache', [OrderController::class, 'clearCache']);
    });
    Route::prefix('production-tasks')->group(function () {
        Route::get('/', [ProductionTaskController::class, 'index']);
        Route::post('/', [ProductionTaskController::class, 'store'])->middleware('role:admin,dispatcher');
        Route::post('/with-components', [ProductionTaskController::class, 'storeWithComponents'])->middleware('role:admin,dispatcher');
        Route::get('/{taskId}', [ProductionTaskController::class, 'show']);
        Route::post('/{taskId}/take', [ProductionTaskController::class, 'takeTask'])->middleware('role:admin,master');
        Route::post('/{taskId}/send-for-inspection', [ProductionTaskController::class, 'sendForInspection'])->middleware('role:admin,master');
        Route::post('/{taskId}/accept', [ProductionTaskController::class, 'acceptByOTK'])->middleware('role:admin,otk');
        Route::post('/{taskId}/accept-with-completion', [ProductionTaskController::class, 'acceptByOTKWithCompletion']);
        Route::post('/{taskId}/reject', [ProductionTaskController::class, 'rejectByOTK']);
        Route::post('/{taskId}/components', [ProductionTaskController::class, 'addComponent'])->middleware('role:admin,master');
        Route::post('/{taskId}/components/multiple', [ProductionTaskController::class, 'addMultipleComponents'])->middleware('role:admin,master');
        Route::put('/{taskId}/components', [ProductionTaskController::class, 'updateComponents']);
        Route::put('/components/{componentId}', [ProductionTaskController::class, 'updateComponent'])->middleware('role:admin,master');
        Route::put('/components/{componentId}/with-lock', [ProductionTaskController::class, 'updateComponentWithLock'])->middleware('role:admin,master');
        Route::delete('/components/{componentId}', [ProductionTaskController::class, 'removeComponent'])->middleware('role:admin,master');
        Route::delete('/components/{componentId}/with-lock', [ProductionTaskController::class, 'removeComponentWithLock'])->middleware('role:admin,master');
        Route::get('/status/{status}', [ProductionTaskController::class, 'getTasksByStatus']);
        Route::get('/statistics', [ProductionTaskController::class, 'statistics']);
    });
    Route::prefix('companies')->group(function () {
        Route::get('/', [CompanyController::class, 'index']);
        Route::get('/select', [CompanyController::class, 'select']);
        Route::get('/search', [CompanyController::class, 'search']);
        Route::get('/with-orders', [CompanyController::class, 'withOrders']);
        Route::get('/statistics', [CompanyController::class, 'statistics']);
        Route::get('/cache-info', [CompanyController::class, 'cacheInfo']);
        Route::post('/clear-cache', [CompanyController::class, 'clearCache']);
        Route::get('/{id}', [CompanyController::class, 'show']);
        Route::get('/by-name', [CompanyController::class, 'getByName']);
    });
    Route::prefix('roles')->group(function () {
        Route::get('/', [RoleController::class, 'index']);
        Route::get('/select', [RoleController::class, 'select']);
        Route::get('/statistics', [RoleController::class, 'statistics']);
        Route::get('/cache-info', [RoleController::class, 'cacheInfo']);
        Route::post('/clear-cache', [RoleController::class, 'clearCache']);
        Route::get('/{id}', [RoleController::class, 'show']);
        Route::get('/by-name', [RoleController::class, 'getByName']);
    });
    Route::prefix('import')->group(function () {
        Route::post('/products', [ImportController::class, 'importProducts']);
        Route::get('/template', [ImportController::class, 'getImportTemplate']);
        Route::get('/download-template', [ImportController::class, 'downloadTemplate']);
        Route::post('/validate', [ImportController::class, 'validateFile']);
        Route::get('/status', [ImportController::class, 'getImportStatus']);
        Route::post('/cancel', [ImportController::class, 'cancelImport']);
    });
    Route::prefix('notifications')->group(function () {
        Route::post('/order-created', [NotificationController::class, 'sendOrderCreatedNotification']);
        Route::post('/order-status-changed', [NotificationController::class, 'sendOrderStatusChangedNotification']);
        Route::post('/deadline-notifications', [NotificationController::class, 'sendDeadlineNotifications']);
        Route::post('/test', [NotificationController::class, 'sendTestNotification']);
        Route::get('/managers', [NotificationController::class, 'getManagers']);
        Route::get('/stats', [NotificationController::class, 'getNotificationStats']);
        Route::post('/send-to-manager', [NotificationController::class, 'sendNotificationToManager']);
        Route::get('/user-notifications', [NotificationController::class, 'getUserNotifications']);
        Route::post('/mark-as-read', [NotificationController::class, 'markAsRead']);
        Route::post('/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
    });
    Route::prefix('documents')->group(function () {
        Route::post('/order', [DocumentController::class, 'generateOrderDocument']);
        Route::post('/task', [DocumentController::class, 'generateTaskDocument']);
        Route::get('/list', [DocumentController::class, 'getAllDocuments']);
        Route::get('/download', [DocumentController::class, 'downloadDocument']);
        Route::get('/view', [DocumentController::class, 'viewDocument']);
        Route::delete('/delete', [DocumentController::class, 'deleteDocument']);
    });
    Route::prefix('reports')->group(function () {
        Route::post('/company', [ReportController::class, 'generateCompanyReport']);
        Route::post('/product', [ReportController::class, 'generateProductReport']);
        Route::post('/statistics', [ReportController::class, 'generateReport']);
        Route::get('/', [ReportController::class, 'getReports']);
        Route::get('/statistics', [ReportController::class, 'getReportsStatistics']);
        Route::get('/{id}', [ReportController::class, 'getReport']);
        Route::get('/content', [ReportController::class, 'getReportContent']);
        Route::delete('/delete', [ReportController::class, 'deleteReport']);
    });
});