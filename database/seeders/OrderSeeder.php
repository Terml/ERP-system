<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Company;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = Company::all();
        $products = Product::all();
        if ($companies->isEmpty() || $products->isEmpty()) {
            $this->command->warn('Необходимо сначала запустить CompanySeeder и ProductSeeder');
            return;
        }
        $orders = [
            [
                'company_id' => $companies->random()->id,
                'product_id' => $products->random()->id,
                'quantity' => rand(10, 100),
                'deadline' => Carbon::now()->addDays(15),
                'status' => 'wait'
            ],
            [
                'company_id' => $companies->random()->id,
                'product_id' => $products->random()->id,
                'quantity' => rand(10, 100),
                'deadline' => Carbon::now()->addDays(20),
                'status' => 'in_process'
            ],
            [
                'company_id' => $companies->random()->id,
                'product_id' => $products->random()->id,
                'quantity' => rand(10, 100),
                'deadline' => Carbon::now()->addDays(10),
                'status' => 'completed'
            ],
            [
                'company_id' => $companies->random()->id,
                'product_id' => $products->random()->id,
                'quantity' => rand(10, 100),
                'deadline' => Carbon::now()->addDays(25),
                'status' => 'wait'
            ],
            [
                'company_id' => $companies->random()->id,
                'product_id' => $products->random()->id,
                'quantity' => rand(10, 100),
                'deadline' => Carbon::now()->addDays(30),
                'status' => 'in_process'
            ],
            [
                'company_id' => $companies->random()->id,
                'product_id' => $products->random()->id,
                'quantity' => rand(10, 100),
                'deadline' => Carbon::now()->addDays(5),
                'status' => 'completed'
            ],
            [
                'company_id' => $companies->random()->id,
                'product_id' => $products->random()->id,
                'quantity' => rand(10, 100),
                'deadline' => Carbon::now()->addDays(35),
                'status' => 'wait'
            ],
            [
                'company_id' => $companies->random()->id,
                'product_id' => $products->random()->id,
                'quantity' => rand(10, 100),
                'deadline' => Carbon::now()->addDays(18),
                'status' => 'in_process'
            ]
        ];

        foreach ($orders as $orderData) {
            Order::create($orderData);
        }
    }
}
