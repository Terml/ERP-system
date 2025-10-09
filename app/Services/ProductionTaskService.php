<?php

namespace App\Services;

use App\Models\ProductionTask;
use Illuminate\Support\Facades\DB;
use App\Models\ArchiveProductionTask;

class ProductionTaskService extends BaseService
{
    public function __construct(ProductionTask $productionTask)
    {
        parent::__construct($productionTask);
    }
    public function getAllProductionTasks()
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
}
