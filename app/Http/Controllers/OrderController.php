<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Requests\CompleteOrderRequest;
use App\Http\Requests\RejectOrderRequest;
use App\Factories\OrderDTOFactory;
use App\Services\OrderService;
use App\Models\Order;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderCollection;
use Illuminate\Http\JsonResponse;
use App\Services\CacheService;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private CacheService $cacheService
    ) {}
    public function store(CreateOrderRequest $request): JsonResponse
    {
        try {
            // проверка прав
            $this->authorize('create', Order::class);
            $orderDTO = OrderDTOFactory::createFromRequest($request);
            $order = $this->orderService->createOrder($orderDTO);
            return (new OrderResource($order))
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка создания заказа',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function index(Request $request): OrderCollection
    {
        $orders = $this->orderService->getAllOrders($request, $request->user());
        return new OrderCollection($orders);
    }
    public function show(int $orderId): OrderResource
    {
        $order = $this->orderService->getOrder($orderId);
        return new OrderResource($order);
    }
    public function update(UpdateOrderRequest $request, int $orderId): JsonResponse
    {
        try {
            // получение ордера
            $order = $this->orderService->getOrder($orderId);
            // проверка прав
            $this->authorize('update', $order);
            $updateDTO = OrderDTOFactory::createUpdateFromRequest($request);
            $result = $this->orderService->updateOrder($orderId, $updateDTO);
            return response()->json([
                'success' => $result,
                'message' => $result ? 'Заказ обновлен успешно' : 'Ошибка обновления заказа'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function complete(CompleteOrderRequest $request, int $orderId): JsonResponse
    {
        try {
            // получение ордера
            $order = $this->orderService->getOrder($orderId);
            // проверка прав
            $this->authorize('complete', $order);
            $result = $this->orderService->completeOrder($orderId);
            return response()->json([
                'success' => $result,
                'message' => $result ? 'Заказ завершен успешно' : 'Ошибка завершения заказа'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    public function reject(RejectOrderRequest $request, int $orderId): JsonResponse
    {
        try {
            $result = $this->orderService->rejectOrder($orderId);

            return response()->json([
                'success' => $result,
                'message' => $result ? 'Заказ отклонен успешно' : 'Ошибка отклонения заказа'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    public function storeWithLock(CreateOrderRequest $request): JsonResponse
    {
        try {
            $this->authorize('create', Order::class);
            $orderDTO = OrderDTOFactory::createFromRequest($request);
            $order = $this->orderService->createOrderWithLock($orderDTO);
            return (new OrderResource($order))
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка создания заказа',
                'error' => $e->getMessage()
            ], 400);
        }
    }
    public function updateWithLock(UpdateOrderRequest $request, int $orderId): JsonResponse
    {
        try {
            $order = $this->orderService->getOrder($orderId);
            $this->authorize('update', $order);
            $updateDTO = OrderDTOFactory::createUpdateFromRequest($request);
            $result = $this->orderService->updateOrderWithLock($orderId, $updateDTO);
            return response()->json([
                'success' => $result,
                'message' => $result ? 'Заказ обновлен успешно' : 'Ошибка обновления заказа'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    public function completeWithLock(CompleteOrderRequest $request, int $orderId): JsonResponse
    {
        try {
            $order = $this->orderService->getOrder($orderId);
            $this->authorize('complete', $order);
            $result = $this->orderService->completeOrderWithLock($orderId);
            return response()->json([
                'success' => $result['completed'],
                'message' => $result['completed'] ? 'Заказ завершен успешно' : 'Ошибка завершения заказа',
                'data' => new OrderResource($result['order'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    public function rejectWithLock(RejectOrderRequest $request, int $orderId): JsonResponse
    {
        try {
            $order = $this->orderService->getOrder($orderId);
            $this->authorize('reject', $order);
            $result = $this->orderService->rejectOrderWithLock($orderId);
            return response()->json([
                'success' => $result['rejected'],
                'message' => $result['rejected'] ? 
                    "Заказ отклонен успешно. Отклонено заданий: {$result['rejected_tasks_count']}" : 
                    'Ошибка отклонения заказа',
                'data' => [
                    'order' => new OrderResource($result['order']),
                    'rejected_tasks_count' => $result['rejected_tasks_count']
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    public function statistics(): JsonResponse
    {
        try {
            $statistics = $this->orderService->getOrderStatistics();
            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения статистики заказов',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function statisticsByStatus(): JsonResponse
    {
        try {
            $statistics = $this->orderService->getOrdersByStatusStatistics();
            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения статистики по статусам',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function statisticsByMonth(Request $request): JsonResponse
    {
        try {
            $months = $request->input('months', 12);
            $statistics = $this->orderService->getOrdersByMonthStatistics($months);
            return response()->json([
                'success' => true,
                'data' => $statistics,
                'period_months' => $months
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения статистики по месяцам',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function statisticsByCompany(): JsonResponse
    {
        try {
            $statistics = $this->orderService->getOrdersByCompanyStatistics();
            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения статистики по компаниям',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function performanceMetrics(): JsonResponse
    {
        try {
            $metrics = $this->orderService->getOrderPerformanceMetrics();
            return response()->json([
                'success' => true,
                'data' => $metrics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения метрик производительности',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function clearCache(): JsonResponse
    {
        try {
            $this->orderService->clearOrderCache();
            return response()->json([
                'success' => true,
                'message' => 'Кеш статистики заказов очищен'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка очистки кеша',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function cacheInfo(): JsonResponse
    {
        try {
            $cacheKeys = [
                'orders:statistics',
                'orders:by_status',
                'orders:by_month:12',
                'orders:by_company',
                'orders:performance_metrics',
            ];
            $cacheInfo = [];
            foreach ($cacheKeys as $key) {
                $cacheInfo[$key] = [
                    'exists' => $this->cacheService->has($key),
                    'ttl' => $this->cacheService->ttl($key),
                ];
            }
            return response()->json([
                'success' => true,
                'data' => $cacheInfo
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения информации о кеше',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
