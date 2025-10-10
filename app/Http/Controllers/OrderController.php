<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Requests\CompleteOrderRequest;
use App\Http\Requests\RejectOrderRequest;
use App\Factories\OrderDTOFactory;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService
    ) {}
    public function store(CreateOrderRequest $request): JsonResponse
    {
        try {
            $orderDTO = OrderDTOFactory::createFromRequest($request);
            $order = $this->orderService->createOrder($orderDTO);
            return response()->json([
                'success' => true,
                'message' => 'Заказ создан успешно',
                'data' => $order
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка создания заказа',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function index(): JsonResponse
    {
        try {
            $orders = $this->orderService->getAllOrders();
            return response()->json([
                'success' => true,
                'data' => $orders,
                'count' => $orders->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения заказов',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function show(int $orderId): JsonResponse
    {
        try {
            $order = $this->orderService->getOrder($orderId);
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Заказ не найден'
                ], 404);
            }
            return response()->json([
                'success' => true,
                'data' => $order
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения заказа',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function update(UpdateOrderRequest $request, int $orderId): JsonResponse
    {
        try {
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
            $result = $this->orderService->completeOrder($orderId, $request->input('completion_note'));
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
            $result = $this->orderService->rejectOrder($orderId, $request->input('rejection_reason'));

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
}
