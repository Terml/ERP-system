<?php

namespace App\Services;

use App\Models\TaskComponent;
use App\Models\ProductionTask;
use Illuminate\Support\Facades\DB;
use App\Models\ArchiveProductionTask;

class ProductionTaskService extends BaseService
{
    public function __construct(ProductionTask $productionTask)
    {
        parent::__construct($productionTask);
    }
    public function createTask(array $taskData): ProductionTask
    {
        // валидация
        $validated = validator($taskData, [
            'order_id' => 'required|exists:orders,id',
            'quantity' => 'required|integer|min:1',
            'user_id' => 'nullable|exists:users,id'
        ])->validate();
        return DB::transaction(function () use ($validated) {
            $task = $this->create($validated);
            return $task->load(['order', 'user']);
        });
    }
    public function addComponent(int $taskId, array $componentData): TaskComponent
    {
        // валидация
        $validated = validator($componentData, [
            'product_id' => 'required|exists:products,id',
            'material_quantity' => 'required|integer|min:1'
        ])->validate();
        return DB::transaction(function () use ($taskId, $validated) {
            $task = $this->findOrFail($taskId);
            $component = TaskComponent::create([
                'production_task_id' => $taskId,
                'product_id' => $validated['product_id'],
                'material_quantity' => $validated['material_quantity']
            ]);
            return $component->load('product');
        });
    }
    public function takeTask(int $taskId, int $userId): bool
    {
        return DB::transaction(function () use ($taskId, $userId) {
            $task = $this->findOrFail($taskId);
            // проверка
            if ($task->status !== 'wait') {
                throw new \Exception('Задание можно взять в работу только в статусе "wait"');
            }
            // обновление статуса
            $result = $task->update([
                'user_id' => $userId,
                'status' => 'in_process'
            ]);
            return $result;
        });
    }
    public function sendForInspection(int $taskId, ?string $notes = null): bool
    {
        return DB::transaction(function () use ($taskId, $notes) {
            $task = $this->findOrFail($taskId);
            // проверка
            if ($task->status !== 'in_process') {
                throw new \Exception('Задание можно отправить на проверку только в статусе "in_process"');
            }
            // обновление статуса
            $result = $task->update(['status' => 'waiting_inspection']);
            return $result;
        });
    }
    public function acceptByOTK(int $taskId, int $otkUserId, ?string $notes = null): bool
    {
        return DB::transaction(function () use ($taskId, $otkUserId, $notes) {
            $task = $this->findOrFail($taskId);
            // проверка
            if ($task->status !== 'waiting_inspection') {
                throw new \Exception('Задание можно принять только в статусе "waiting_inspection"');
            }
            // обновление статуса
            $result = $task->update(['status' => 'completed']);
            return $result;
        });
    }
    public function rejectByOTK(int $taskId, int $otkUserId, string $rejectionReason): bool
    {
        return DB::transaction(function () use ($taskId, $otkUserId, $rejectionReason) {
            $task = $this->findOrFail($taskId);
            // проверка
            if ($task->status !== 'waiting_inspection') {
                throw new \Exception('Задание можно отклонить только в статусе "waiting_inspection"');
            }
            // обновление статуса
            $result = $task->update(['status' => 'rejected']);
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
    public function getAllTasks()
    {
        return $this->model->all();
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

    public function getTaskWithComponents(int $taskId): ?ProductionTask
    {
        return $this->model->with([
            'order:id,company_id,product_id,quantity,deadline,status',
            'order.company:id,name',
            'order.product:id,name,type,unit',
            'user:id,login',
            'taskComponents' => function ($query) {
                $query->with('product:id,name,type,unit,price');
            }
        ])->find($taskId);
    }
    public function getTasksByStatus(string $status): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->where('status', $status)
            ->with(['order.company:id,name', 'order.product:id,name,type,unit', 'user:id,login'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
