<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ProductionTask;
use App\Services\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    protected DocumentService $documentService;
    public function __construct(DocumentService $documentService)
    {
        $this->documentService = $documentService;
    }
    public function generateOrderDocument(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'order_id' => 'required|integer|exists:orders,id'
            ]);
            $order = Order::findOrFail($request->order_id);
            $result = $this->documentService->generateOrderDocument($order);
            return response()->json([
                'success' => true,
                'message' => 'Документ заказа сгенерирован',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка генерации документа заказа',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function generateTaskDocument(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'task_id' => 'required|integer|exists:production_tasks,id'
            ]);
            $task = ProductionTask::findOrFail($request->task_id);
            $result = $this->documentService->generateTaskDocument($task);
            return response()->json([
                'success' => true,
                'message' => 'Документ задания сгенерирован',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка генерации документа задания',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getAllDocuments(): JsonResponse
    {
        try {
            $documents = $this->documentService->getAllDocuments();
            return response()->json([
                'success' => true,
                'data' => $documents
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения списка документов',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function downloadDocument(Request $request): Response
    {
        try {
            $request->validate([
                'file' => 'required|string'
            ]);
            $filePath = $request->get('file');
            if (!Storage::disk('local')->exists($filePath)) {
                abort(404, 'Документ не найден');
            }
            $fileName = basename($filePath);
            $content = Storage::disk('local')->get($filePath);
            return response($content, 200, [
                'Content-Type' => 'text/html; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            ]);
        } catch (\Exception $e) {
            abort(500, 'Ошибка скачивания документа: ' . $e->getMessage());
        }
    }
    public function viewDocument(Request $request): Response
    {
        try {
            $request->validate([
                'file' => 'required|string'
            ]);
            $filePath = $request->get('file');
            if (!Storage::disk('local')->exists($filePath)) {
                abort(404, 'Документ не найден');
            }
            $content = Storage::disk('local')->get($filePath);
            return response($content, 200, [
                'Content-Type' => 'text/html; charset=utf-8',
            ]);
        } catch (\Exception $e) {
            abort(500, 'Ошибка просмотра документа: ' . $e->getMessage());
        }
    }
    public function deleteDocument(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => 'required|string'
            ]);
            $filePath = $request->get('file');
            $deleted = $this->documentService->deleteDocument($filePath);
            if ($deleted) {
                return response()->json([
                    'success' => true,
                    'message' => 'Документ удален'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Документ не найден'
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка удаления документа',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
