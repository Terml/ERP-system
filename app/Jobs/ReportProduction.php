<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\ProductionTask;
use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ReportProduction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected string $reportType;
    protected array $parameters;
    protected int $userId;
    protected ?string $fileName;
    public int $tries = 3;
    public int $timeout = 300;
    public int $backoff = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(string $reportType, array $parameters = [], int $userId = null, string $fileName = null)
    {
        $this->reportType = $reportType;
        $this->parameters = $parameters;
        $this->userId = $userId;
        $this->fileName = $fileName;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $reportData = $this->generateReport();
            $report = Report::create([
                'type' => $this->reportType,
                'user_id' => $this->userId,
                'data' => $reportData,
            ]);
        } catch (\Exception $e) {
            Log::error("Ошибка генерации отчета по производству: " . $e->getMessage());
            throw $e;
        }
    }
    protected function generateReport(): array
    {
        return match ($this->reportType) {
            'by_company' => $this->generateCompanyReport(),
            'by_product' => $this->generateProductReport(),
            'statistics' => $this->generateStatisticsReport(),
            default => throw new \Exception("Неизвестный тип отчета: {$this->reportType}"),
        };
    }
    protected function generateCompanyReport(): array
    {
        $companyId = $this->parameters['company_id'];
        $company = Company::findOrFail($companyId);
        $orders = Order::where('company_id', $companyId)
            ->get();
        $tasks = ProductionTask::whereHas('order', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->with(['order', 'user'])->get();
        return [
            'report_type' => 'by_company',
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'description' => $company->description,
            ],
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'summary' => [
                'total_orders' => $orders->count(),
                'total_tasks' => $tasks->count(),
                'completed_tasks' => $tasks->where('status', 'completed')->count(),
                'in_progress_tasks' => $tasks->where('status', 'in_process')->count(),
                'waiting_tasks' => $tasks->where('status', 'wait')->count(),
            ],
            'orders' => $orders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'status' => $order->status,
                    'deadline' => $order->deadline,
                    'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                ];
            }),
        ];
    }
    protected function generateProductReport(): array
    {
        $productId = $this->parameters['product_id'];
        $product = Product::findOrFail($productId);
        $orders = Order::whereHas('productionTasks.components', function ($query) use ($productId) {
            $query->where('product_id', $productId);
        })->with(['company'])
            ->get();
        $tasks = ProductionTask::whereHas('components', function ($query) use ($productId) {
            $query->where('product_id', $productId);
        })->with(['order.company', 'user'])->get();
        return [
            'report_type' => 'by_product',
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'type' => $product->type,
                'unit' => $product->unit,
            ],
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'summary' => [
                'total_orders' => $orders->count(),
                'total_tasks' => $tasks->count(),
                'completed_tasks' => $tasks->where('status', 'completed')->count(),
            ],
            'orders' => $orders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'company_name' => $order->company->name,
                    'status' => $order->status,
                    'deadline' => $order->deadline,
                    'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                ];
            }),
        ];
    }
    protected function generateStatisticsReport(): array
    {
        $totalOrders = Order::count();
        $totalTasks = ProductionTask::count();
        $completedTasks = ProductionTask::where('status', 'completed')->count();
        $totalCompanies = Company::count();
        $totalProducts = Product::count();
        $totalMasters = User::whereHas('roles', function ($query) {
            $query->where('role', 'master');
        })->count();
        $recentOrders = Order::with(['company', 'product'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        return [
            'report_type' => 'statistics',
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'summary' => [
                'total_orders' => $totalOrders,
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 0,
                'total_companies' => $totalCompanies,
                'total_products' => $totalProducts,
                'total_masters' => $totalMasters,
            ],
            'recent_orders' => $recentOrders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'company_name' => $order->company->name,
                    'status' => $order->status,
                    'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                ];
            }),
        ];
    }
    public function backoff(): array
    {
        return [30, 60, 120];
    }
    public function retryUntil(): \DateTime
    {
        return now()->addHours(2);
    }
    public function failed(\Throwable $exception): void
    {
        Log::error("Job ReportProduction failed after {$this->tries} attempts: " . $exception->getMessage(), [
            'report_type' => $this->reportType,
            'user_id' => $this->userId,
            'parameters' => $this->parameters,
            'exception' => $exception->getTraceAsString()
        ]);
    }
}