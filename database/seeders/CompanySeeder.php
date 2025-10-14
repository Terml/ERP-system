<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = [
            [
                'name' => 'ООО "ТехноПром"',
            ],
            [
                'name' => 'ООО "Машиностроительный завод"',
            ],
            [
                'name' => 'ИП "Сидоров А.В."',
            ],
            [
                'name' => 'ООО "МеталлСервис"',
            ],
            [
                'name' => 'ЗАО "ПромИнвест"',
            ]
        ];
        foreach ($companies as $companyData) {
            Company::create($companyData);
        }
    }
}
