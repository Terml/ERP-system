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
        Schema::create('task_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_task_id')->constrained('production_tasks')->onDelete('cascade');;
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');;
            $table->integer('quantity'); // планируемое количество
            $table->integer('used_quantity')->default(0); // фактически использованное количество
            $table->timestamps();

            // Индексы
            $table->index('production_task_id'); // по заданию
            $table->index('product_id'); // использование материалов
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_components');
    }
};
