<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\DocumentController;

Route::get('/', function () {
    return view('welcome');
});
Route::prefix('api/products')->group(function () {
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
Route::prefix('api/orders')->group(function () {
    Route::get('/statistics', [OrderController::class, 'statistics']);
    Route::get('/statistics/by-status', [OrderController::class, 'statisticsByStatus']);
    Route::get('/statistics/by-month', [OrderController::class, 'statisticsByMonth']);
    Route::get('/statistics/by-company', [OrderController::class, 'statisticsByCompany']);
    Route::get('/performance-metrics', [OrderController::class, 'performanceMetrics']);
    Route::get('/cache-info', [OrderController::class, 'cacheInfo']);
    Route::post('/clear-cache', [OrderController::class, 'clearCache']);
});
Route::prefix('api/roles')->group(function () {
    Route::get('/', [RoleController::class, 'index']);
    Route::get('/select', [RoleController::class, 'select']);
    Route::get('/statistics', [RoleController::class, 'statistics']);
    Route::get('/cache-info', [RoleController::class, 'cacheInfo']);
    Route::post('/clear-cache', [RoleController::class, 'clearCache']);
    Route::get('/{id}', [RoleController::class, 'show']);
    Route::get('/by-name', [RoleController::class, 'getByName']);
});
Route::prefix('api/companies')->group(function () {
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
Route::prefix('api/import')->group(function () {
    Route::post('/products', [ImportController::class, 'importProducts']);
    Route::get('/template', [ImportController::class, 'getImportTemplate']);
    Route::get('/download-template', [ImportController::class, 'downloadTemplate'])->name('import.download-template');
    Route::post('/validate', [ImportController::class, 'validateFile']);
    Route::get('/status', [ImportController::class, 'getImportStatus']);
    Route::post('/cancel', [ImportController::class, 'cancelImport']);
});
Route::prefix('api/notifications')->group(function () {
    Route::post('/order-created', [NotificationController::class, 'sendOrderCreatedNotification'])->name('notifications.order-created');
    Route::post('/order-status-changed', [NotificationController::class, 'sendOrderStatusChangedNotification'])->name('notifications.order-status-changed');
    Route::post('/deadline-notifications', [NotificationController::class, 'sendDeadlineNotifications'])->name('notifications.deadline');
    Route::post('/test', [NotificationController::class, 'sendTestNotification'])->name('notifications.test');
    Route::get('/managers', [NotificationController::class, 'getManagers'])->name('notifications.managers');
    Route::get('/stats', [NotificationController::class, 'getNotificationStats'])->name('notifications.stats');
    Route::post('/send-to-manager', [NotificationController::class, 'sendNotificationToManager'])->name('notifications.send-to-manager');
    Route::get('/user-notifications', [NotificationController::class, 'getUserNotifications'])->name('notifications.user-notifications');
    Route::post('/mark-as-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-as-read');
    Route::post('/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-as-read');
});
Route::prefix('api/documents')->group(function () {
    Route::post('/order', [DocumentController::class, 'generateOrderDocument']);
    Route::post('/task', [DocumentController::class, 'generateTaskDocument']);
    Route::get('/list', [DocumentController::class, 'getAllDocuments']);
    Route::get('/download', [DocumentController::class, 'downloadDocument'])->name('documents.download');
    Route::get('/view', [DocumentController::class, 'viewDocument']);
    Route::delete('/delete', [DocumentController::class, 'deleteDocument']);
});
Route::prefix('api/reports')->group(function () {
    Route::post('/company', [ReportController::class, 'generateCompanyReport'])->name('reports.company');
    Route::post('/product', [ReportController::class, 'generateProductReport'])->name('reports.product');
    Route::post('/statistics', [ReportController::class, 'generateReport'])->name('reports.statistics');
    Route::get('/', [ReportController::class, 'getReports'])->name('reports.list');
    Route::get('/statistics', [ReportController::class, 'getReportsStatistics'])->name('reports.statistics');
    Route::get('/{id}', [ReportController::class, 'getReport'])->name('reports.show');
    Route::get('/content', [ReportController::class, 'getReportContent'])->name('reports.content');
    Route::delete('/delete', [ReportController::class, 'deleteReport'])->name('reports.delete');
});
