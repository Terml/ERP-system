<?php

namespace Database\Seeders;

use App\Models\ProductionTask;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductionTaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $orders = Order::all();
        $users = User::all();
        if ($orders->isEmpty() || $users->isEmpty()) {
            $this->command->warn('Необходимо сначала запустить OrderSeeder и UserSeeder');
            return;
        }
        $tasks = [
            [
                'order_id' => $orders->random()->id,
                'user_id' => $users->where('login', 'master')->first()?->id ?? $users->random()->id,
                'status' => 'wait'
            ],
            [
                'order_id' => $orders->random()->id,
                'user_id' => $users->where('login', 'master')->first()?->id ?? $users->random()->id,
                'status' => 'in_process'
            ],
            [
                'order_id' => $orders->random()->id,
                'user_id' => $users->where('login', 'master')->first()?->id ?? $users->random()->id,
                'status' => 'completed'
            ],
            [
                'order_id' => $orders->random()->id,
                'user_id' => $users->where('login', 'master')->first()?->id ?? $users->random()->id,
                'status' => 'completed'
            ],
            [
                'order_id' => $orders->random()->id,
                'user_id' => $users->where('login', 'master')->first()?->id ?? $users->random()->id,
                'status' => 'wait'
            ],
            [
                'order_id' => $orders->random()->id,
                'user_id' => $users->where('login', 'master')->first()?->id ?? $users->random()->id,
                'status' => 'in_process'
            ],
            [
                'order_id' => $orders->random()->id,
                'user_id' => $users->where('login', 'master')->first()?->id ?? $users->random()->id,
                'status' => 'completed'
            ],
            [
                'order_id' => $orders->random()->id,
                'user_id' => $users->where('login', 'master')->first()?->id ?? $users->random()->id,
                'status' => 'completed'
            ]
        ];
        foreach ($tasks as $taskData) {
            ProductionTask::create($taskData);
        }
    }
}
