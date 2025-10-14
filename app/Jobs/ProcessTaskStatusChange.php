<?php

namespace App\Jobs;

use App\Models\ProductionTask;
use App\Jobs\ProcessOrderCompletion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessTaskStatusChange implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected ProductionTask $task;
    protected string $oldStatus;
    protected string $newStatus;
    public function __construct(ProductionTask $task, string $oldStatus, string $newStatus)
    {
        $this->task = $task;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }
    public function handle(): void
    {
        try {
            Log::info("Обработка изменения статуса задания #{$this->task->id}", [
                'task_id' => $this->task->id,
                'order_id' => $this->task->order_id,
                'old_status' => $this->oldStatus,
                'new_status' => $this->newStatus,
                'user_id' => $this->task->user_id
            ]);
            switch ($this->newStatus) {
                case 'in_process':
                    $this->handleTaskStarted();
                    break;
                case 'checking':
                    $this->handleTaskSentForInspection();
                    break;
                case 'completed':
                    $this->handleTaskCompleted();
                    break;
                case 'rejected':
                    $this->handleTaskRejected();
                    break;
            }
        } catch (\Exception $e) {
            Log::error("Ошибка при обработке изменения статуса задания #{$this->task->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }
    private function handleTaskStarted(): void
    {
        Log::info("Задание #{$this->task->id} взято в работу", [
            'status_changed_at' => now()->toDateTimeString()
        ]);
    }
    private function handleTaskSentForInspection(): void
    {
        Log::info("Задание #{$this->task->id} отправлено на проверку", [
            'status_changed_at' => now()->toDateTimeString()
        ]);
    }
    private function handleTaskCompleted(): void
    {
        Log::info("Задание #{$this->task->id} принято ОТК", [
            'status_changed_at' => now()->toDateTimeString()
        ]);
        ProcessOrderCompletion::dispatch($this->task->order);
    }
    private function handleTaskRejected(): void
    {
        Log::info("Задание #{$this->task->id} отклонено ОТК", [
            'status_changed_at' => now()->toDateTimeString()
        ]);
    }
    public function failed(\Throwable $exception): void
    {
        Log::error("Job ProcessTaskStatusChange failed for task #{$this->task->id}", [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
