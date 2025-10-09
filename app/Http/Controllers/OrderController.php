<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService
    ) {}
    public function store(Request $request): JsonResponse
    {
        try {
            $order = $this->orderService->createOrder($request->all());
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
    public function complete(Request $request, int $orderId): JsonResponse
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
}
