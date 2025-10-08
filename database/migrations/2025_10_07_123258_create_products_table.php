<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', [
                'product', // готовый продукт
                'material', // материал из которого состоит продукт
            ]);
            $table->string('unit');
            $table->timestamps();

            // Индексы
            $table->index('name'); // поиск по названию
            $table->index('type'); // фильтр по типу
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
