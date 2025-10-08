<?php

namespace App\Services;

use App\Models\Order;

class OrderService extends BaseService
{
    public function __construct(Order $order)
    {
        parent::__construct($order);
    }
    public function getAllOrders()
    {
        return $this->model->all();
    }
    public function getActiveOrders()
    {
        return $this->model->whereIn('status', ['wait', 'in_process'])->get();
    }
    public function getCompleteOrders()
    {
        return $this->model->whereIn('status', ['completed'])->get();
    }
}
