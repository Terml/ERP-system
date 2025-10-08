<?php

namespace App\Services;
use App\Models\ProductionTask;

class ProductionTaskService extends BaseService{
    public function __construct(ProductionTask $productionTask){
        parent::__construct($productionTask);
    }
    public function getAllProductionTasks(){
        return $this->model->all();
    }
}