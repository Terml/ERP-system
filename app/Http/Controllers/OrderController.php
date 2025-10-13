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

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService
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
        $orders = $this->orderService->getAllOrders();
        if ($request->has('status')) {
            $orders = $orders->where('status', $request->input('status'));
        }
        if ($request->has('company_id')) {
            $orders = $orders->where('company_id', $request->input('company_id'));
        }
        if ($request->has('product_id')) {
            $orders = $orders->where('product_id', $request->input('product_id'));
        }
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
}
