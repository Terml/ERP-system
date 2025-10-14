<?php

namespace App\Services;

use App\Models\TaskComponent;
use App\Models\ProductionTask;
use App\DTOs\CreateProductionTaskDTO;
use App\DTOs\CheckingDTO;
use App\DTOs\TaskComponentDTO;
use App\Jobs\ProcessTaskStatusChange;
use Illuminate\Support\Facades\DB;
use App\Models\ArchiveProductionTask;

class ProductionTaskService extends BaseService
{
    public function __construct(ProductionTask $productionTask)
    {
        parent::__construct($productionTask);
    }
    public function createTask(CreateProductionTaskDTO $taskDTO): ProductionTask
    {
        return DB::transaction(function () use ($taskDTO) {
            $task = $this->create($taskDTO->toArray());
            return $task->load(['order', 'user']);
        });
    }
    public function getTask(int $taskId): ProductionTask
    {
        return $this->findOrFail($taskId);
    }
    public function addComponent(int $taskId, TaskComponentDTO $componentDTO): TaskComponent
    {
        return DB::transaction(function () use ($taskId, $componentDTO) {
            $task = $this->findOrFail($taskId);
            $component = TaskComponent::create([
                'production_task_id' => $taskId,
                'product_id' => $componentDTO->productId,
                'quantity' => $componentDTO->quantity
            ]);
            return $component->load('product');
        });
    }
    public function takeTask(int $taskId, int $userId): bool
    {
        return DB::transaction(function () use ($taskId, $userId) {
            $task = $this->findOrFail($taskId);
            $oldStatus = $task->status;
            // проверка
            if ($task->status !== 'wait') {
                throw new \Exception('Задание можно взять в работу только в статусе "wait"');
            }
            // обновление статуса
            $result = $task->update([
                'user_id' => $userId,
                'status' => 'in_process'
            ]);
            if ($result) {
                ProcessTaskStatusChange::dispatch($task, $oldStatus, 'in_process');
            }
            return $result;
        });
    }
    public function sendForInspection(int $taskId, CheckingDTO $checkingDTO): bool
    {
        return DB::transaction(function () use ($taskId, $checkingDTO) {
            $task = $this->findOrFail($taskId);
            $oldStatus = $task->status;
            // проверка
            if ($task->status !== 'in_process') {
                throw new \Exception('Задание можно отправить на проверку только в статусе "in_process"');
            }
            // обновление статуса
            $result = $task->update(['status' => 'checking']);
            if ($result) {
                ProcessTaskStatusChange::dispatch($task, $oldStatus, 'checking');
            }
            return $result;
        });
    }
    public function acceptByOTK(int $taskId, int $otkUserId): bool
    {
        return DB::transaction(function () use ($taskId, $otkUserId) {
            $task = $this->findOrFail($taskId);
            $oldStatus = $task->status;
            // проверка
            if ($task->status !== 'checking') {
                throw new \Exception('Задание можно принять только в статусе "checking"');
            }
            // обновление статуса
            $result = $task->update([
                'status' => 'completed'
            ]);
            if ($result) {
                ProcessTaskStatusChange::dispatch($task, $oldStatus, 'completed');
            }
            return $result;
        });
    }
    public function rejectByOTK(int $taskId, int $otkUserId): bool
    {
        return DB::transaction(function () use ($taskId, $otkUserId) {
            $task = $this->findOrFail($taskId);
            $oldStatus = $task->status;
            // проверка
            if ($task->status !== 'checking') {
                throw new \Exception('Задание можно отклонить только в статусе "checking"');
            }
            // обновление статуса
            $result = $task->update([
                'status' => 'rejected'
            ]);
            if ($result) {
                ProcessTaskStatusChange::dispatch($task, $oldStatus, 'rejected');
            }
            return $result;
        });
    }
    public function updateComponent(int $componentId, array $updateData): bool
    { // обновление компонентов
        return DB::transaction(function () use ($componentId, $updateData) {
            $component = TaskComponent::find($componentId);
            return $component->update($updateData);
        });
    }
    public function removeComponent(int $componentId): bool
    { // удаление компонентов
        return DB::transaction(function () use ($componentId) {
            $component = TaskComponent::find($componentId);
            return $component->delete();
        });
    }
    public function getAllTasks($request = null)
    {
        $query = $this->model->with([
            'order:id,company_id,deadline,status',
            'order.company:id,name',
            'user:id,login',
            'components.product:id,name,type,unit'
        ]);
        if ($request) {
            if ($request->has('status') && $request->input('status') !== '') {
                $query->where('status', $request->input('status'));
            }
            if ($request->has('user_id') && $request->input('user_id') !== '') {
                $query->where('user_id', $request->input('user_id'));
            }
            if ($request->has('order_id') && $request->input('order_id') !== '') {
                $query->where('order_id', $request->input('order_id'));
            }
            if ($request->has('search') && $request->input('search') !== '') {
                $search = $request->input('search');
                $query->where(function($q) use ($search) {
                    $q->where('id', 'ilike', "%{$search}%")
                      ->orWhereHas('order', function($orderQuery) use ($search) {
                          $orderQuery->where('id', 'ilike', "%{$search}%");
                      });
                });
            }
        }
        return $query->paginate(15);
    }
    public function archiveTask(int $taskId): bool
    {
        return DB::transaction(function () use ($taskId) {
            $task = $this->model->find($taskId);
            ArchiveProductionTask::create([
                'original_id' => $task->id,
                'original_order_id' => $task->order_id,
                'user_id' => $task->user_id,
                'quantity' => $task->quantity,
                'status' => $task->status,
                'archived_at' => now(),
                'created_at' => $task->created_at,
                'updated_at' => $task->updated_at,
            ]);
            // удаление таска
            return $task->delete();
        });
    }
    public function restoreTasksByOrder(int $archivedOrderId, int $newOrderId): int
    {
        return DB::transaction(function () use ($archivedOrderId, $newOrderId) {
            $archivedTasks = ArchiveProductionTask::where('original_order_id', $archivedOrderId)->get();
            $restoredTasks = [];
            $errors = [];
            foreach ($archivedTasks as $archivedTask) {
                try {
                    $restoredTask = ProductionTask::create([
                        'order_id' => $newOrderId,
                        'user_id' => $archivedTask->user_id,
                        'quantity' => $archivedTask->quantity,
                        'status' => $archivedTask->status,
                        'created_at' => $archivedTask->created_at,
                        'updated_at' => now(),
                    ]);
                    $archivedTask->delete();
                    $restoredTasks[] = $restoredTask;
                } catch (\Exception $e) {
                    $errors[] = [
                        'task_id' => $archivedTask->id,
                        'error' => $e->getMessage()
                    ];
                }
            }
            return count($restoredTasks);
        });
    }
    public function getTaskWithComponents(int $taskId): ProductionTask
    {
        return $this->model->with([
            'order:id,company_id,deadline,status',
            'order.company:id,name,phone',
            'user:id,login',
            'components.product:id,name,type,unit'
        ])->findOrFail($taskId);
    }
    public function getTasksByStatus(string $status): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->where('status', $status)
            ->with(['order.company:id,name', 'user:id,login'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
    public function createTaskWithComponents(
        CreateProductionTaskDTO $taskDTO, 
        array $componentsData
    ): ProductionTask {
        return DB::transaction(function () use ($taskDTO, $componentsData) {
            // блок заказа
            $order = \App\Models\Order::lockForUpdate()->findOrFail($taskDTO->orderId);
            // проверка статуса
            if (!in_array($order->status, ['wait', 'in_process'])) {
                throw new \Exception('Заказ должен быть в статусе "wait" или "in_process"');
            }
            // создание задания
            $taskData = $taskDTO->toArray();
            $task = $this->create($taskData);
            
            if (!$task) {
                throw new \Exception('Не удалось создать задание');
            }
            
            // добавление компонентов
            foreach ($componentsData as $componentData) {
                TaskComponent::create([
                    'production_task_id' => $task->id,
                    'product_id' => $componentData['product_id'],
                    'quantity' => $componentData['quantity']
                ]);
            }
            // обновление статуса
            if ($order->status === 'wait') {
                $order->update(['status' => 'in_process']);
            }
            return $task->load(['order', 'user', 'components.product']);
        });
    }
    public function addMultipleComponents(int $taskId, array $componentsData): array {
        return DB::transaction(function () use ($taskId, $componentsData) {
            // блок задания
            $task = ProductionTask::lockForUpdate()->findOrFail($taskId);
            // проверка статуса
            if (!in_array($task->status, ['wait', 'in_process'])) {
                throw new \Exception('Компоненты можно добавлять только к заданиям в статусе "wait" или "in_process"');
            }
            $createdComponents = [];
            foreach ($componentsData as $componentData) {
                $component = TaskComponent::create([
                    'production_task_id' => $taskId,
                    'product_id' => $componentData['product_id'],
                    'quantity' => $componentData['quantity']
                ]);
                $createdComponents[] = $component->load('product');
            }
            return $createdComponents;
        });
    }
    public function acceptByOTKWithOrderCompletion(
        int $taskId, 
        int $otkUserId
    ): array {
        return DB::transaction(function () use ($taskId, $otkUserId) {
            // блок задания и заказа
            $task = ProductionTask::lockForUpdate()->findOrFail($taskId);
            $order = \App\Models\Order::lockForUpdate()->findOrFail($task->order_id);
            $oldTaskStatus = $task->status; 
            // проверка статуса
            if ($task->status !== 'checking') {
                throw new \Exception('Задание можно принять только в статусе "checking"');
            }
            // обновление статуса
            $task->update([
                'status' => 'completed'
            ]);
            // проверка завершенности
            $allTasksCompleted = ProductionTask::where('order_id', $order->id)
                ->where('status', '!=', 'completed')
                ->doesntExist();
            $orderCompleted = false;
            if ($allTasksCompleted && $order->status !== 'completed') {
                $order->update([
                    'status' => 'completed',
                    'completed_at' => now()
                ]);
                $orderCompleted = true;
            }
            // завершение в фоне
            ProcessTaskStatusChange::dispatch($task, $oldTaskStatus, 'completed');
            return [
                'task' => $task->fresh(),
                'order' => $order->fresh(),
                'order_completed' => $orderCompleted
            ];
        });
    }
    public function updateComponentWithLock(int $componentId, array $updateData): TaskComponent {
        return DB::transaction(function () use ($componentId, $updateData) {
            // блок задания и компонента
            $component = TaskComponent::lockForUpdate()->findOrFail($componentId);
            $task = ProductionTask::lockForUpdate()->findOrFail($component->production_task_id);
            // проверка статуса
            if (!in_array($task->status, ['wait', 'in_process'])) {
                throw new \Exception('Компоненты можно обновлять только для заданий в статусе "wait" или "in_process"');
            }
            $component->update($updateData);
            return $component->fresh()->load('product');
        });
    }
    public function removeComponentWithLock(int $componentId): bool {
        return DB::transaction(function () use ($componentId) {
            $component = TaskComponent::lockForUpdate()->findOrFail($componentId);
            $task = ProductionTask::lockForUpdate()->findOrFail($component->production_task_id);
            if (!in_array($task->status, ['wait', 'in_process'])) {
                throw new \Exception('Компоненты можно удалять только для заданий в статусе "wait" или "in_process"');
            }
            return $component->delete();
        });
    }
    public function updateComponents(int $taskId, array $componentsData): bool {
        return DB::transaction(function () use ($taskId, $componentsData) {
            $task = ProductionTask::lockForUpdate()->findOrFail($taskId);
            if ($task->status !== 'in_process') {
                throw new \Exception('Компоненты можно обновлять только для заданий в статусе "in_process"');
            }
            foreach ($componentsData as $componentData) {
                $component = TaskComponent::findOrFail($componentData['id']);
                $component->update([
                    'used_quantity' => $componentData['used_quantity']
                ]);
            }
            $task->update(['status' => 'checking']);
            return true;
        });
    }
}
