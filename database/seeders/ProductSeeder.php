<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'name' => 'Болт М8х20',
                'type' => 'material',
                'unit' => 'шт'
            ],
            [
                'name' => 'Гайка М8',
                'type' => 'material',
                'unit' => 'шт'
            ],
            [
                'name' => 'Шайба 8мм',
                'type' => 'material',
                'unit' => 'шт'
            ],
            [
                'name' => 'Корпус редуктора',
                'type' => 'product',
                'unit' => 'шт'
            ],
            [
                'name' => 'Вал ведущий',
                'type' => 'product',
                'unit' => 'шт'
            ],
            [
                'name' => 'Шестерня ведущая',
                'type' => 'product',
                'unit' => 'шт'
            ],
            [
                'name' => 'Подшипник 6205',
                'type' => 'material',
                'unit' => 'шт'
            ],
            [
                'name' => 'Масло индустриальное',
                'type' => 'material',
                'unit' => 'л'
            ],
            [
                'name' => 'Прокладка уплотнительная',
                'type' => 'material',
                'unit' => 'шт'
            ],
        ];
        foreach ($products as $productData) {
            Product::create($productData);
        }
    }
}
