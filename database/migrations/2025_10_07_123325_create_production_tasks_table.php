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
        Schema::create('production_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignID('order_id')->constrained('orders')->onDelete('cascade');;
            $table->foreignID('user_id')->constrained('users')->onDelete('cascade');;
            $table->integer('quantity');
            $table->enum('status', [
                'wait', // создание заказа и задания в производстве
                'in_process', // мастер взял задание в производство
                'completed', // принятие ОТК
                'rejected', // отвергнуто ОТК
            ]);
            $table->timestamps();
            $table->softDeletes();

            // Индексы
            $table->index('order_id'); // принадлежность заказа
            $table->index('user_id'); // мастер выполняющий таск
            $table->index('status'); // фильтр по статусу
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_tasks');
    }
};
