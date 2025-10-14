<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ArchiveOrder;
use App\DTOs\CreateOrderDTO;
use App\DTOs\UpdateOrderDTO;
use App\Jobs\ProcessOrderCompletion;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\ArchiveProductionTask;
use Illuminate\Support\Facades\Request;
use Illuminate\Database\Eloquent\Collection;
use App\Services\CacheService;
use App\Jobs\NoticeManager;
use Illuminate\Support\Facades\Log;

class OrderService extends BaseService
{
  protected CacheService $cacheService;
  protected int $cacheTtl = 1800;
  public function __construct(Order $order, CacheService $cacheService)
  {
    parent::__construct($order);
    $this->cacheService = $cacheService;
  }
  public function createOrder(CreateOrderDTO $orderDTO): Order
  {
    return DB::transaction(function () use ($orderDTO) {
      $order = $this->create($orderDTO->toArray());
      $order = $order->load(['company']);
      $this->sendOrderCreatedNotification($order);
      return $order;
    });
  }
  public function updateOrder(int $orderId, UpdateOrderDTO $updateDTO): bool
  {
    return DB::transaction(function () use ($orderId, $updateDTO) {
      $order = $this->findOrFail($orderId);
      $oldStatus = $order->status;
      // проверка
      if (!in_array($order->status, ['wait', 'in_process'])) {
        throw new \Exception('Заказ можно обновить только в статусе "wait" или "in_process"');
      }
      // обновление заказа
      $result = $order->update($updateDTO->toArray());
      if ($result) {
        $order = $order->fresh()->load(['company']);
        $this->sendOrderUpdatedNotification($order, $oldStatus);
      }
      return $result;
    });
  }
  public function completeOrder(int $orderId): bool
  {
    return DB::transaction(function () use ($orderId) {
      $order = $this->findOrFail($orderId);
      // проверка
      if ($order->status !== 'in_process') {
        throw new \Exception('Заказ можно завершить только в статусе "in_process"');
      }
      // обновление статуса
      $result = $order->update([
        'status' => 'completed'
      ]);
      if ($result) {
        ProcessOrderCompletion::dispatch($order);
        $order = $order->fresh()->load(['company']);
        $this->sendOrderCompletedNotification($order);
      }
      return $result;
    });
  }
  public function rejectOrder(int $orderId): bool
  {
    return DB::transaction(function () use ($orderId) {
      $order = $this->findOrFail($orderId);
      // проверка
      if (!in_array($order->status, ['wait', 'in_process'])) {
        throw new \Exception('Заказ можно отклонить только в статусе "wait" или "in_process"');
      }
      // обновление статуса
      $result = $order->update([
        'status' => 'rejected'
      ]);
      if ($result) {
        $order = $order->fresh()->load(['company']);
        $this->sendOrderRejectedNotification($order);
      }
      return $result;
    });
  }
  public function getAllOrders($request = null, $user = null)
  {
    $query = $this->model->with([
      'company:id,name',
      'product:id,name,unit',
      'productionTasks:id,order_id,status'
    ]);
    if ($user && $user->hasRole('manager') && !$user->hasRole('admin')) {
      $query->where('status', 'completed');
    }
    if ($request) {
      if ($request->has('status') && $request->input('status') !== '') {
        $query->where('status', $request->input('status'));
      }
      if ($request->has('company_id') && $request->input('company_id') !== '') {
        $query->where('company_id', $request->input('company_id'));
      }
      if ($request->has('search') && $request->input('search') !== '') {
        $search = $request->input('search');
        $query->whereHas('company', function($companyQuery) use ($search) {
          $companyQuery->where('name', 'ilike', "%{$search}%");
        });
      }
    }
    return $query->paginate(15);
  }
  public function getOrder(int $orderId): Order
  {
    return $this->model->with([
      'company:id,name',
      'productionTasks' => function ($query) {
        $query->with(['user:id,login', 'components.product:id,name,type,unit']);
      }
    ])->findOrFail($orderId);
  }
  public function getAllDeletedOrders(): Collection
  {
    return $this->model->onlyTrashed()->with([
      'company:id,name',
      'productionTasks' => function ($query) {
        $query->onlyTrashed()->with('user:id,login');
      }
    ])->get();
  }
  public function getOrdersWithCompanies(): Collection
  {
    return $this->model->with([
      'company:id,name'
    ])->select('id', 'company_id', 'deadline', 'status', 'created_at')->get();
  }
  public function getOrdersWithProducts(): Collection
  {
  return $this->model->select('id', 'deadline', 'status', 'created_at')->get();
  }
  public function getOrdersWithTasks(): Collection
  {
    return $this->model->with([
      'productionTasks' => function ($query) {
        $query->select('id', 'order_id', 'user_id', 'quantity', 'status', 'created_at')->with('user:id,login');
      }
    ])->select('id', 'quantity', 'deadline', 'status', 'created_at')->get();
  }
  public function getOrdersWithCount(): Collection
  {
    return $this->model->withCount([
      'productionTasks',
      'productionTasks as completed_tasks_count' => function ($query) {
        $query->where('status', 'completed');
      },
      'productionTasks as active_tasks_count' => function ($query) {
        $query->whereIn('status', ['wait', 'in_process']);
      }
    ])->with([
      'company:id,name'
    ])->get();
  }
  public function filterByCompany(int $companyId): Collection
  {
    return $this->model->where('company_id', $companyId)
      ->with(['company:id,name'])->get();
  }
  public function archiveOrder(int $orderId): bool
  {
    return DB::transaction(function () use ($orderId) {
      $order = $this->model->with(['productionTasks'])->find($orderId); //eager loading загружаем заказ с заданиями
      $taskService = app(ProductionTaskService::class); //экземпляр сервиса для работы с заданиями
      foreach ($order->productionTasks as $task) { //процесс архивации
        $taskService->archiveTask($task->id);
      }
      ArchiveOrder::create([
        'original_id' => $order->id,
        'company_id' => $order->company_id,
        'deadline' => $order->deadline,
        'status' => $order->status,
        'archived_at' => now(),
        'created_at' => $order->created_at,
        'updated_at' => $order->updated_at,
      ]);
      return $order->delete(); // мягкое удаление
    });
  }
  public function getFromArchive(int $archivedOrderId): array
  {
    return DB::transaction(function () use ($archivedOrderId) {
      $archivedOrder = ArchiveOrder::with(['archivedTasks'])->find($archivedOrderId);
      $restoredOrder = Order::create([ // восстановление ордера
        'company_id' => $archivedOrder->company_id,
        'deadline' => $archivedOrder->deadline,
        'status' => $archivedOrder->status,
        'created_at' => $archivedOrder->created_at,
        'updated_at' => now(),
      ]);
      $taskService = app(ProductionTaskService::class); // востановление тасков ордера
      $restoredTasksCount = $taskService->restoreTasksByOrder($archivedOrder->original_id, $restoredOrder->id);
      $archivedOrder->delete();
      return [
        'success' => true,
        'restored_order_id' => $restoredOrder->id,
        'restored_tasks_count' => $restoredTasksCount,
        'message' => "Восстановлен заказ #{$restoredOrder->id} с {$restoredTasksCount} заданиями"
      ];
    });
  }
  public function createOrderWithLock(CreateOrderDTO $orderDTO): Order {
    return DB::transaction(function () use ($orderDTO) {
      // блок компании
      $company = \App\Models\Company::lockForUpdate()->findOrFail($orderDTO->companyId);
      $order = $this->create($orderDTO->toArray());
      return $order->load(['company']);
    });
  }
  public function updateOrderWithLock(int $orderId, UpdateOrderDTO $updateDTO): bool {
    return DB::transaction(function () use ($orderId, $updateDTO) {
      // блок заказа
      $order = Order::lockForUpdate()->findOrFail($orderId);
      // проверка статуса
      if (!in_array($order->status, ['wait', 'in_process'])) {
        throw new \Exception('Заказ можно обновить только в статусе "wait" или "in_process"');
      }
      // проверка дедлайна
      if ($updateDTO->deadline && $updateDTO->deadline < now()) {
        throw new \Exception('Новый дедлайн не может быть в прошлом');
      }
      return $order->update($updateDTO->toArray());
    });
  }
  public function completeOrderWithLock(int $orderId): array {
    return DB::transaction(function () use ($orderId) {
      // блок заказа
      $order = Order::lockForUpdate()->findOrFail($orderId);
      // проверка статуса
      if ($order->status !== 'in_process') {
        throw new \Exception('Заказ можно завершить только в статусе "in_process"');
      }
      // проверка завершенности
      $incompleteTasks = \App\Models\ProductionTask::where('order_id', $orderId)
        ->where('status', '!=', 'completed')
        ->exists();
      if ($incompleteTasks) {
        throw new \Exception('Нельзя завершить заказ, пока не завершены все производственные задания');
      }
      // обновление статуса заказа
      $result = $order->update([
        'status' => 'completed'
      ]);
      if ($result) {
        ProcessOrderCompletion::dispatch($order);
      }
      return [
        'order' => $order->fresh(),
        'completed' => $result
      ];
    });
  }
  public function rejectOrderWithLock(int $orderId): array {
    return DB::transaction(function () use ($orderId) {
      // блок заказа
      $order = Order::lockForUpdate()->findOrFail($orderId);
      // проверка статуса
      if (!in_array($order->status, ['wait', 'in_process'])) {
        throw new \Exception('Заказ можно отклонить только в статусе "wait" или "in_process"');
      }
      // отклонение всех заданий
      $tasks = \App\Models\ProductionTask::where('order_id', $orderId)
        ->whereIn('status', ['wait', 'in_process', 'checking'])
        ->get();
      foreach ($tasks as $task) {
        $task->update([
          'status' => 'rejected'
        ]);
      }
      // обновление статуса
      $result = $order->update([
        'status' => 'rejected'
      ]);
      return [
        'order' => $order->fresh(),
        'rejected_tasks_count' => $tasks->count(),
        'rejected' => $result
      ];
    });
  }
  public function getOrderStatistics(): array
  {
    $cacheKey = 'orders:statistics';
    $tags = ['orders', 'statistics'];
    return $this->cacheService->rememberWithTags($cacheKey, $tags, function () {
      return [
        'total_orders' => $this->model->count(),
        'orders_by_status' => $this->model->selectRaw('status, COUNT(*) as count')
          ->groupBy('status')
          ->pluck('count', 'status')
          ->toArray(),
        'orders_by_month' => $this->model->selectRaw('TO_CHAR(created_at, \'YYYY-MM\') as month, COUNT(*) as count')
          ->groupBy('month')
          ->orderBy('month')
          ->pluck('count', 'month')
          ->toArray(),
        'orders_by_company' => $this->model->with('company')
          ->selectRaw('company_id, COUNT(*) as count')
          ->groupBy('company_id')
          ->get()
          ->mapWithKeys(function ($order) {
            return [$order->company->name ?? 'Неизвестная компания' => $order->count];
          })
          ->toArray(),
        'recent_orders' => $this->model->with(['company'])
          ->orderBy('created_at', 'desc')
          ->limit(5)
          ->get()
          ->map(function ($order) {
            return [
              'id' => $order->id,
              'company_name' => $order->company->name ?? 'Неизвестная компания',
              'status' => $order->status,
              'created_at' => $order->created_at->format('Y-m-d H:i:s'),
            ];
          })
          ->toArray(),
      ];
    }, $this->cacheTtl);
  }
  public function getOrdersByStatusStatistics(): array
  {
    $cacheKey = 'orders:by_status';
    $tags = ['orders', 'statistics', 'by_status'];
    return $this->cacheService->rememberWithTags($cacheKey, $tags, function () {
      $statuses = ['wait', 'in_process', 'completed', 'rejected'];
      $result = [];
      foreach ($statuses as $status) {
        $result[$status] = [
          'count' => $this->model->where('status', $status)->count(),
          'percentage' => 0,
        ];
      }
      $total = array_sum(array_column($result, 'count'));
      if ($total > 0) {
        foreach ($result as $status => $data) {
          $result[$status]['percentage'] = round(($data['count'] / $total) * 100, 2);
        }
      }
      return $result;
    }, $this->cacheTtl);
  }
  public function getOrdersByMonthStatistics(int $months = 12): array
  {
    $cacheKey = "orders:by_month:{$months}";
    $tags = ['orders', 'statistics', 'by_month'];
    return $this->cacheService->rememberWithTags($cacheKey, $tags, function () use ($months) {
      $startDate = now()->subMonths($months - 1)->startOfMonth();
      return $this->model->selectRaw('TO_CHAR(created_at, \'YYYY-MM\') as month, COUNT(*) as count')
        ->where('created_at', '>=', $startDate)
        ->groupBy('month')
        ->orderBy('month')
        ->get()
        ->mapWithKeys(function ($order) {
          return [$order->month => $order->count];
        })
        ->toArray();
    }, $this->cacheTtl);
  }
  public function getOrdersByCompanyStatistics(): array
  {
    $cacheKey = 'orders:by_company';
    $tags = ['orders', 'statistics', 'by_company'];
    return $this->cacheService->rememberWithTags($cacheKey, $tags, function () {
      return $this->model->with('company')
        ->selectRaw('company_id, COUNT(*) as count, AVG(quantity) as avg_quantity')
        ->groupBy('company_id')
        ->get()
        ->map(function ($order) {
          return [
            'company_name' => $order->company->name ?? 'Неизвестная компания',
            'company_id' => $order->company_id,
            'orders_count' => $order->count,
          ];
        })
        ->sortByDesc('orders_count')
        ->values()
        ->toArray();
    }, $this->cacheTtl);
  }
  public function getOrderPerformanceMetrics(): array
  {
    $cacheKey = 'orders:performance_metrics';
    $tags = ['orders', 'statistics', 'performance'];
    return $this->cacheService->rememberWithTags($cacheKey, $tags, function () {
      $totalOrders = $this->model->count();
      $completedOrders = $this->model->where('status', 'completed')->count();
      $rejectedOrders = $this->model->where('status', 'rejected')->count();
      $inProcessOrders = $this->model->where('status', 'in_process')->count();
      return [
        'completion_rate' => $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100, 2) : 0,
        'rejection_rate' => $totalOrders > 0 ? round(($rejectedOrders / $totalOrders) * 100, 2) : 0,
        'in_process_rate' => $totalOrders > 0 ? round(($inProcessOrders / $totalOrders) * 100, 2) : 0,
        'total_orders' => $totalOrders,
        'completed_orders' => $completedOrders,
        'rejected_orders' => $rejectedOrders,
        'in_process_orders' => $inProcessOrders,
      ];
    }, $this->cacheTtl);
  }
  public function invalidateOrderCache(): void
  {
    $tags = ['orders', 'statistics'];
    $this->cacheService->flushByTags($tags);
  }
  public function clearOrderCache(): void
  {
    $this->invalidateOrderCache();
  }
  public function sendOrderCreatedNotification(Order $order): void
  {
    try {
      NoticeManager::dispatch($order, 'created');
    } catch (\Exception $e) {
      Log::error("Ошибка сохранения уведомления о создании заказа #{$order->id}: " . $e->getMessage());
    }
  }
  public function sendOrderUpdatedNotification(Order $order, string $oldStatus): void
  {
    try {
      $newStatus = $order->status;
      NoticeManager::dispatch($order, 'updated', ['old_status' => $oldStatus]);
      if ($oldStatus !== $newStatus) {
        NoticeManager::dispatch($order, 'status_changed', ['old_status' => $oldStatus, 'new_status' => $newStatus]);
      }
    } catch (\Exception $e) {
      Log::error("Ошибка сохранения уведомления об обновлении заказа #{$order->id}: " . $e->getMessage());
    }
  }
  public function sendOrderCompletedNotification(Order $order): void
  {
    try {
      NoticeManager::dispatch($order, 'completed');
    } catch (\Exception $e) {
      Log::error("Ошибка сохранения уведомления о завершении заказа #{$order->id}: " . $e->getMessage());
    }
  }
  public function sendOrderRejectedNotification(Order $order): void
  {
    try {
      NoticeManager::dispatch($order, 'rejected');
    } catch (\Exception $e) {
      Log::error("Ошибка сохранения уведомления об отклонении заказа #{$order->id}: " . $e->getMessage());
    }
  }
  public function sendNotificationToSpecificManager(Order $order, string $eventType, int $managerId, array $additionalData = []): void
  {
    try {
      NoticeManager::dispatch($order, $eventType, $additionalData, $managerId);
    } catch (\Exception $e) {
      Log::error("Ошибка сохранения уведомления для менеджера #{$managerId} о заказе #{$order->id}: " . $e->getMessage());
    }
  }
  public function getManagers()
  {
    return \App\Models\User::whereHas('roles', function ($query) {
      $query->where('role', 'manager');
    })->get();
  }
}
