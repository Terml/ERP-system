<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\CheckingOrderRequest;
use App\Http\Requests\CreateProductionTaskRequest;
use App\Http\Requests\CreateProductionTaskWithComponentsRequest;
use App\Http\Requests\TakeTaskRequest;
use App\Http\Requests\AddComponentRequest;
use App\Http\Requests\OTKDecisionWithCompletionRequest;
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
        $tasks = $this->taskService->getAllTasks($request);
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
            $result = $this->taskService->takeTask($taskId, auth()->id());
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
            $result = $this->taskService->acceptByOTK($taskId, $request->input('otk_user_id'));
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
            $result = $this->taskService->rejectByOTK($taskId, $request->input('otk_user_id'));
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
    public function storeWithComponents(CreateProductionTaskWithComponentsRequest $request): JsonResponse
    {
        try {
            $this->authorize('create', ProductionTask::class);
            $taskDTO = ProductionTaskDTOFactory::createFromRequestWithComponents($request);
            $componentsData = $request->input('components', []);
            
            $task = $this->taskService->createTaskWithComponents($taskDTO, $componentsData);
            return (new ProductionTaskResource($task))
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка создания задания с компонентами',
                'error' => $e->getMessage()
            ], 400);
        }
    }
    public function addMultipleComponents(Request $request, int $taskId): JsonResponse
    {
        try {
            $task = $this->taskService->getTask($taskId);
            $this->authorize('addComponent', $task);
            $componentsData = $request->input('components', []);
            if (empty($componentsData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Необходимо указать хотя бы один компонент'
                ], 400);
            }
            $components = $this->taskService->addMultipleComponents($taskId, $componentsData);
            return response()->json([
                'success' => true,
                'message' => 'Компоненты добавлены успешно',
                'data' => $components
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка добавления компонентов',
                'error' => $e->getMessage()
            ], 400);
        }
    }
    public function acceptByOTKWithCompletion(OTKDecisionWithCompletionRequest $request, int $taskId): JsonResponse
    {
        try {
            $task = $this->taskService->getTask($taskId);
            $this->authorize('acceptByOTK', $task);
            if ($request->input('decision') !== 'accepted') {
                return response()->json([
                    'success' => false,
                    'message' => 'Используйте метод rejectByOTK для отклонения'
                ], 400);
            }
            $result = $this->taskService->acceptByOTKWithOrderCompletion(
                $taskId,
                $request->input('otk_user_id')
            );
            return response()->json([
                'success' => true,
                'message' => 'Задание принято ОТК' . ($result['order_completed'] ? ', заказ автоматически завершен' : ''),
                'data' => [
                    'task' => new ProductionTaskResource($result['task']),
                    'order' => $result['order'],
                    'order_completed' => $result['order_completed']
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    public function updateComponentWithLock(Request $request, int $componentId): JsonResponse
    {
        try {
            $updateData = $request->only(['quantity']);
            $component = $this->taskService->updateComponentWithLock($componentId, $updateData);
            return response()->json([
                'success' => true,
                'message' => 'Компонент обновлен успешно',
                'data' => $component
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    public function removeComponentWithLock(int $componentId): JsonResponse
    {
        try {
            $result = $this->taskService->removeComponentWithLock($componentId);
            return response()->json([
                'success' => $result,
                'message' => $result ? 'Компонент удален успешно' : 'Ошибка удаления компонента'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    public function updateComponents(Request $request, int $taskId): JsonResponse
    {
        try {
            $task = $this->taskService->getTask($taskId);
            $this->authorize('addComponent', $task);
            
            $components = $request->input('components', []);
            $result = $this->taskService->updateComponents($taskId, $components);
            
            return response()->json([
                'success' => $result,
                'message' => $result ? 'Компоненты обновлены успешно. Задание отправлено на проверку.' : 'Ошибка обновления компонентов'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
