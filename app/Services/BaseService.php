<?php

namespace App\Services;
use Illuminate\Database\Eloquent\Model;

abstract class BaseService{
    protected Model $model;
    public function __construct(Model $model){
        $this->model = $model;
    }
    public function getAll(){
        return $this->model->all();
    }
    public function find(int $id){
        return $this->model->find($id);
    }
    public function create(array $data){
        return $this->model->create($data);
    }
    public function update(int $id, array $data){
        $model = $this->model->findOrFail($id);
        return $model->update($data);
    }
    public function delete(int $id){
        $model = $this->model->findOrFail($id);
        return $model->delete();
    }
}