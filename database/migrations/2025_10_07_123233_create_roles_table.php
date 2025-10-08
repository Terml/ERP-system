<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('role');
            $table->string('description');
            $table->timestamps();
        });
        DB::table('roles')->insert([ // Добавление ролей
            [
                'role' => 'admin',
                'description' => 'админ'
            ],
            [
                'role' => 'manager',
                'description' => 'работает с заказами от контрагентов',
            ],
            [
                'role' => 'dispatcher',
                'description' => 'создает производственные задания, следит за их статусами',
            ],
            [
                'role' => 'master',
                'description' => 'выполняет производственные задания в цехе',
            ],
            [
                'role' => 'otk',
                'description' => 'проверяет качество выполненных заданий, принимает или отклоняет работу',
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
