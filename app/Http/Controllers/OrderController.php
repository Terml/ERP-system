<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function store(
        Request $request,
        OrderService $orderService,
        Validator $validator,
        DB $db
    ): JsonResponse {
        try {
            // валидация ордера
            $validated = $validator->make($request->all(), [
                'company_id' => 'required|exists:companies,id',
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1',
                'deadline' => 'required|date|after:today'
            ])->validate();
            $order = $db->transaction(function () use ($validated, $orderService) {
                return $orderService->create($validated);
            });
            return response()->json([
                'success' => true,
                'data' => $order
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка создания заказа'
            ], 500);
        }
    }
}
