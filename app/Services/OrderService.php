<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;

class OrderService extends BaseService
{
  public function __construct(Order $order)
  {
    parent::__construct($order);
  }
  public function getAllOrders()
  {
    return $this->model->with([
      'company: id, name',
      'product: id, name, type, unit',
      'productionTasks' => function ($query) {
        $query->select('id', 'order_id', 'user_id', 'quantity', 'status')->with('user:id,login');
      }
    ])->select('id', 'company_id', 'product_id', 'quantity', 'deadline', 'status')->get();
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
      'product: id, name, type, unit, description, price'
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
      'company: id, name',
      'product: id, name, type, unit'
    ])->get();
  }
  public function filterByCompany(int $companyId): Collection
  {
    return $this->model->where('company_id', $companyId)
      ->with(['company: id, name', 'product: id, name, type, unit'])->get();
  }
}
