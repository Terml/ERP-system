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
            $result = $task->update(['status' => 'waiting_inspection']);
            if ($result) {
                ProcessTaskStatusChange::dispatch($task, $oldStatus, 'waiting_inspection');
            }
            return $result;
        });
    }
    public function acceptByOTK(int $taskId, int $otkUserId, ?string $notes = null): bool
    {
        return DB::transaction(function () use ($taskId, $otkUserId, $notes) {
            $task = $this->findOrFail($taskId);
            $oldStatus = $task->status;
            // проверка
            if ($task->status !== 'waiting_inspection') {
                throw new \Exception('Задание можно принять только в статусе "waiting_inspection"');
            }
            // обновление статуса
            $result = $task->update([
                'status' => 'completed',
                'otk_user_id' => $otkUserId,
                'otk_notes' => $notes
            ]);
            if ($result) {
                ProcessTaskStatusChange::dispatch($task, $oldStatus, 'completed');
            }
            return $result;
        });
    }
    public function rejectByOTK(int $taskId, int $otkUserId, string $rejectionReason): bool
    {
        return DB::transaction(function () use ($taskId, $otkUserId, $rejectionReason) {
            $task = $this->findOrFail($taskId);
            $oldStatus = $task->status;
            // проверка
            if ($task->status !== 'waiting_inspection') {
                throw new \Exception('Задание можно отклонить только в статусе "waiting_inspection"');
            }
            // обновление статуса
            $result = $task->update([
                'status' => 'rejected',
                'otk_user_id' => $otkUserId,
                'rejection_reason' => $rejectionReason
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
