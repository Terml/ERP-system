<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignID('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignID('product_id')->constrained('products')->onDelete('cascade');
            $table->integer('quantity');
            $table->date('deadline');
            $table->enum('status', [
                'wait', // создание заказа и задания в производстве
                'in_process', // мастер взял задание в производство
                'completed', // принятие ОТК
                'rejected', // отвергнуто ОТК
            ])->default('wait');
            $table->timestamps();
            $table->softDeletes();
            
            // Индексы
            $table->index('company_id'); // фильтр по компании
            $table->index('status'); // фильтр по статусу
            $table->index('deadline'); // сортировка по срокам
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
