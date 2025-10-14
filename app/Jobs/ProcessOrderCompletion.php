<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\ProductionTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessOrderCompletion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected Order $order;

    /**
     * Create a new job instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $tasks = ProductionTask::where('order_id', $this->order->id)->get();
            $completedTasks = $tasks->where('status', 'completed')->count();
            $totalTasks = $tasks->count();
            if ($completedTasks === $totalTasks && $totalTasks > 0) {
                $this->order->update([
                    'status' => 'completed'
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Ошибка при обработке завершения заказа #{$this->order->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    public function failed(\Throwable $exception): void
    {
        Log::error("Job ProcessOrderCompletion failed for order #{$this->order->id}", [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}