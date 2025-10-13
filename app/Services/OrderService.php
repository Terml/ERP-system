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

class OrderService extends BaseService
{
  public function __construct(Order $order)
  {
    parent::__construct($order);
  }
  public function createOrder(CreateOrderDTO $orderDTO): Order
  {
    return DB::transaction(function () use ($orderDTO) {
      $order = $this->create($orderDTO->toArray());
      return $order->load(['company', 'product']);
    });
  }
  public function updateOrder(int $orderId, UpdateOrderDTO $updateDTO): bool
  {
    return DB::transaction(function () use ($orderId, $updateDTO) {
      $order = $this->findOrFail($orderId);
      // проверка
      if (!in_array($order->status, ['wait', 'in_process'])) {
        throw new \Exception('Заказ можно обновить только в статусе "wait" или "in_process"');
      }
      // обновление заказа
      $result = $order->update($updateDTO->toArray());
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
      return $result;
    });
  }
  public function getAllOrders()
  {
    return $this->model->with([
      'company:id,name,contact_person,phone',
      'product:id,name,type,unit',
      'productionTasks:id,order_id,status'
    ])->paginate(15);
  }
  public function getOrder(int $orderId): Order
  {
    return $this->model->with([
      'company:id,name,contact_person,phone,email',
      'product:id,name,type,unit,price',
      'productionTasks' => function ($query) {
        $query->with(['user:id,login', 'components.product:id,name,type,unit']);
      }
    ])->findOrFail($orderId);
  }
  public function getAllDeletedOrders(): Collection
  {
    return $this->model->onlyTrashed()->with([
      'company:id,name',
      'product:id,name,type,unit',
      'productionTasks' => function ($query) {
        $query->onlyTrashed()->with('user:id,login');
      }
    ])->get();
  }
  public function getOrdersWithCompanies(): Collection
  {
    return $this->model->with([
      'company:id,name,contact_person,phone,email,address'
    ])->select('id', 'company_id', 'quantity', 'deadline', 'status', 'created_at')->get();
  }
  public function getOrdersWithProducts(): Collection
  {
    return $this->model->with([
      'product:id,name,type,unit,description,price'
    ])->select('id', 'product_id', 'quantity', 'deadline', 'status', 'created_at')->get();
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
      'company:id,name',
      'product:id,name,type,unit'
    ])->get();
  }
  public function filterByCompany(int $companyId): Collection
  {
    return $this->model->where('company_id', $companyId)
      ->with(['company:id,name', 'product:id,name,type,unit'])->get();
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
        'product_id' => $order->product_id,
        'quantity' => $order->quantity,
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
        'product_id' => $archivedOrder->product_id,
        'quantity' => $archivedOrder->quantity,
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
      // блок компании и продукта
      $company = \App\Models\Company::lockForUpdate()->findOrFail($orderDTO->companyId);
      $product = \App\Models\Product::lockForUpdate()->findOrFail($orderDTO->productId);
      if ($product->type !== 'product') {
        throw new \Exception('Можно создавать заказы только на готовые продукты');
      }
      $order = $this->create($orderDTO->toArray());
      return $order->load(['company', 'product']);
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
}
