<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\CheckingOrderRequest;
use App\Http\Requests\CreateProductionTaskRequest;
use App\Http\Requests\TakeTaskRequest;
use App\Http\Requests\AddComponentRequest;
use App\Factories\ProductionTaskDTOFactory;
use App\Services\ProductionTaskService;
use App\Models\ProductionTask;
use App\Http\Resources\ProductionTaskResource;
use App\Http\Resources\ProductionTaskCollection;
use Illuminate\Http\JsonResponse;

class ProductionTaskController extends Controller
{
    public function __construct(
        private ProductionTaskService $taskService
    ) {}
    public function index(Request $request): ProductionTaskCollection
    {
        $tasks = $this->taskService->getAllTasks();
        if ($request->has('status')) {
            $tasks = $tasks->where('status', $request->input('status'));
        }
        if ($request->has('user_id')) {
            $tasks = $tasks->where('user_id', $request->input('user_id'));
        }
        if ($request->has('order_id')) {
            $tasks = $tasks->where('order_id', $request->input('order_id'));
        }
        return new ProductionTaskCollection($tasks);
    }
    public function store(CreateProductionTaskRequest $request): JsonResponse
    {
        try {
            // проверка прав
            $this->authorize('create', ProductionTask::class);
            $taskDTO = ProductionTaskDTOFactory::createFromRequest($request);
            $task = $this->taskService->createTask($taskDTO);
            return (new ProductionTaskResource($task))
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка создания задания',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function show(int $taskId): ProductionTaskResource
    {
        $task = $this->taskService->getTaskWithComponents($taskId);
        return new ProductionTaskResource($task);
    }
    public function takeTask(TakeTaskRequest $request, int $taskId): JsonResponse
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
    public function sendForInspection(CheckingOrderRequest $request, int $taskId): JsonResponse
    {
        try {
            // получение ордера
            $task = $this->taskService->getTask($taskId);
            // проверка прав
            $this->authorize('sendForInspection', $task);
            $checkingDTO = ProductionTaskDTOFactory::createSendForInspectionFromRequest($request);
            $result = $this->taskService->sendForInspection($taskId, $checkingDTO);

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
    public function addComponent(AddComponentRequest $request, int $taskId): JsonResponse
    {
        try {
            // получение ордера
            $task = $this->taskService->getTask($taskId);
            // проверка прав
            $this->authorize('addComponent', $task);
            $componentDTO = ProductionTaskDTOFactory::createTaskComponentFromRequest($request);
            $component = $this->taskService->addComponent($taskId, $componentDTO);
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
    public function getTasksByStatus(string $status): ProductionTaskCollection
    {
        $tasks = $this->taskService->getTasksByStatus($status);
        return new ProductionTaskCollection($tasks);
    }
}
