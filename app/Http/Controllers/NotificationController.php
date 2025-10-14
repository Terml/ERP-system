<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use App\Services\OrderService;
use App\Jobs\NoticeManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private OrderService $orderService
    ) {}
    public function sendOrderCreatedNotification(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'order_id' => 'required|integer|exists:orders,id',
            ]);
            $order = Order::with(['company'])->findOrFail($request->order_id);
            $this->orderService->sendOrderCreatedNotification($order);
            return response()->json([
                'success' => true,
                'message' => 'Уведомление о создании заказа сохранено',
                'data' => [
                    'order_id' => $order->id,
                    'company_name' => $order->company->name,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка сохранения уведомления',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function sendOrderStatusChangedNotification(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'order_id' => 'required|integer|exists:orders,id',
                'old_status' => 'required|string',
                'new_status' => 'required|string',
            ]);
            $order = Order::with(['company'])->findOrFail($request->order_id);
            $this->orderService->sendOrderUpdatedNotification($order, $request->old_status);
            return response()->json([
                'success' => true,
                'message' => 'Уведомление об изменении статуса заказа сохранено',
                'data' => [
                    'order_id' => $order->id,
                    'old_status' => $request->old_status,
                    'new_status' => $request->new_status,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка сохранения уведомления',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function sendTestNotification(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'order_id' => 'required|integer|exists:orders,id',
                'type' => 'required|string|in:created,updated,completed,rejected,deadline_approaching,deadline_expired',
            ]);

            $order = Order::with(['company'])->findOrFail($request->order_id);
            
            // Отправляем Job для уведомления
            NoticeManager::dispatch($order, $request->type);

            return response()->json([
                'success' => true,
                'message' => 'Тестовое уведомление сохранено',
                'data' => [
                    'order_id' => $order->id,
                    'type' => $request->type,
                    'company_name' => $order->company->name,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка сохранения тестового уведомления',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getManagers(): JsonResponse
    {
        try {
            $managers = User::whereHas('roles', function ($query) {
                $query->where('role', 'manager');
            })->select('id', 'login', 'email')->get();
            return response()->json([
                'success' => true,
                'data' => $managers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения списка менеджеров',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getNotificationStats(): JsonResponse
    {
        try {
            $stats = [
                'total_managers' => User::whereHas('roles', function ($query) {
                    $query->where('role', 'manager');
                })->count(),
                'orders_in_process' => Order::where('status', 'in_process')->count(),
                'orders_approaching_deadline' => Order::where('status', 'in_process')
                    ->where('deadline', '<=', now()->addDay())
                    ->where('deadline', '>', now())
                    ->count(),
                'orders_expired' => Order::where('status', 'in_process')
                    ->where('deadline', '<', now())
                    ->count(),
                'recent_orders' => Order::with(['company'])
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(function ($order) {
                        return [
                            'id' => $order->id,
                            'company_name' => $order->company->name,
                            'status' => $order->status,
                            'deadline' => $order->deadline,
                            'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                        ];
                    }),
            ];
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения статистики уведомлений',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function sendNotificationToManager(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'manager_id' => 'required|integer|exists:users,id',
                'order_id' => 'required|integer|exists:orders,id',
                'type' => 'required|string|in:created,updated,completed,rejected,deadline_approaching,deadline_expired',
            ]);
            $manager = User::findOrFail($request->manager_id);
            $order = Order::with(['company'])->findOrFail($request->order_id);
            if (!$manager->hasRole('manager')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Пользователь не является менеджером'
                ], 403);
            }
            $this->orderService->sendNotificationToSpecificManager(
                $order, 
                $request->type, 
                $request->manager_id
            );
            return response()->json([
                'success' => true,
                'message' => 'Уведомление сохранено для менеджера',
                'data' => [
                    'manager_id' => $manager->id,
                    'manager_login' => $manager->login,
                    'order_id' => $order->id,
                    'type' => $request->type,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка сохранения уведомления для менеджера',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getUserNotifications(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $perPage = $request->get('per_page', 15);
            $unreadOnly = $request->get('unread_only', false);
            $query = $user->notifications()->latest();
            if ($unreadOnly) {
                $query->whereNull('read_at');
            }
            $notifications = $query->paginate($perPage);
            return response()->json([
                'success' => true,
                'data' => $notifications
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения уведомлений',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function markAsRead(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'notification_id' => 'required|string',
            ]);
            $user = $request->user();
            $notification = $user->notifications()->findOrFail($request->notification_id);
            $notification->markAsRead();
            return response()->json([
                'success' => true,
                'message' => 'Уведомление отмечено как прочитанное'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка обновления уведомления',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function markAllAsRead(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $user->unreadNotifications->markAsRead();
            return response()->json([
                'success' => true,
                'message' => 'Все уведомления отмечены как прочитанные'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка обновления уведомлений',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
