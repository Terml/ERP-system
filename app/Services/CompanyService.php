<?php

namespace App\Services;
use App\Models\Company;

class CompanyService extends BaseService{
    public function __construct(Company $company){
        parent::__construct($company);
    }
    public function getAllCompanies(){
        return $this->model->all();
    }
}