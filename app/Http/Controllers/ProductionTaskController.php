<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ProductionTaskService;
use Illuminate\Http\JsonResponse;

class ProductionTaskController extends Controller
{
    public function __construct(
        private ProductionTaskService $taskService
    ) {}
    public function index(): JsonResponse
    {
        try {
            $tasks = $this->taskService->getAllTasks();
            return response()->json([
                'success' => true,
                'data' => $tasks
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения заданий',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function store(Request $request): JsonResponse
    {
        try {
            $task = $this->taskService->createTask($request->all());
            return response()->json([
                'success' => true,
                'message' => 'Задание создано успешно',
                'data' => $task
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка создания задания',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function show(int $taskId): JsonResponse
    {
        try {
            $task = $this->taskService->getTaskWithComponents($taskId);
            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Задание не найдено'
                ], 404);
            }
            return response()->json([
                'success' => true,
                'data' => $task
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения задания',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function takeTask(Request $request, int $taskId): JsonResponse
    {
        try {
            $result = $this->taskService->takeTask($taskId, $request->input('user_id'));
            return response()->json([
                'success' => $result,
                'message' => $result ? 'Задание взято в работу' : 'Ошибка взятия задания'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    public function sendForInspection(Request $request, int $taskId): JsonResponse
    {
        try {
            $result = $this->taskService->sendForInspection($taskId, $request->input('notes'));
            return response()->json([
                'success' => $result,
                'message' => $result ? 'Задание отправлено на проверку' : 'Ошибка отправки'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    public function acceptByOTK(Request $request, int $taskId): JsonResponse
    {
        try {
            $result = $this->taskService->acceptByOTK($taskId, $request->input('otk_user_id'), $request->input('notes'));
            return response()->json([
                'success' => $result,
                'message' => $result ? 'Задание принято ОТК' : 'Ошибка принятия'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    public function rejectByOTK(Request $request, int $taskId): JsonResponse
    {
        try {
            $result = $this->taskService->rejectByOTK($taskId, $request->input('otk_user_id'), $request->input('rejection_reason'));
            return response()->json([
                'success' => $result,
                'message' => $result ? 'Задание отклонено ОТК' : 'Ошибка отклонения'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    public function addComponent(Request $request, int $taskId): JsonResponse
    {
        try {
            $component = $this->taskService->addComponent($taskId, $request->all());
            return response()->json([
                'success' => true,
                'message' => 'Компонент добавлен успешно',
                'data' => $component
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка добавления компонента',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function updateComponent(Request $request, int $componentId): JsonResponse
    {
        try {
            $result = $this->taskService->updateComponent($componentId, $request->all());
            return response()->json([
                'success' => $result,
                'message' => $result ? 'Компонент обновлен' : 'Ошибка обновления'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    public function removeComponent(int $componentId): JsonResponse
    {
        try {
            $result = $this->taskService->removeComponent($componentId);
            return response()->json([
                'success' => $result,
                'message' => $result ? 'Компонент удален' : 'Ошибка удаления'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    public function getTasksByStatus(string $status): JsonResponse
    {
        try {
            $tasks = $this->taskService->getTasksByStatus($status);
            return response()->json([
                'success' => true,
                'data' => $tasks,
                'count' => $tasks->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения заданий',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
