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
        $this->task->update([
            'status_changed_at' => now()->toDateTimeString()
        ]);
    }
    private function handleTaskSentForInspection(): void
    {
        $this->task->update([
            'status_changed_at' => now()->toDateTimeString()
        ]);
    }
    private function handleTaskCompleted(): void
    {
        $this->task->update([
            'status_changed_at' => now()->toDateTimeString()
        ]);
        ProcessOrderCompletion::dispatch($this->task->order);
    }
    private function handleTaskRejected(): void
    {
        $this->task->update([
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
