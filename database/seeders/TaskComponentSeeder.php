<?php

namespace Database\Seeders;

use App\Models\TaskComponent;
use App\Models\ProductionTask;
use App\Models\Product;
use Illuminate\Database\Seeder;

class TaskComponentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tasks = ProductionTask::all();
        $materials = Product::where('type', 'material')->get();
        if ($tasks->isEmpty() || $materials->isEmpty()) {
            $this->command->warn('Необходимо сначала запустить ProductionTaskSeeder и ProductSeeder');
            return;
        }
        foreach ($tasks as $task) {
            $componentCount = rand(2, 4);
            $selectedMaterials = $materials->random($componentCount);
            foreach ($selectedMaterials as $material) {
                TaskComponent::create([
                    'production_task_id' => $task->id,
                    'product_id' => $material->id,
                    'quantity' => rand(1, 10)
                ]);
            }
        }
    }
}
