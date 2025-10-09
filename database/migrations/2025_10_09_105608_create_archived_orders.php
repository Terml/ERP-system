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
        Schema::create('archived_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('original_id');
            $table->foreignID('company_id')->constrained('companies');
            $table->foreignID('product_id')->constrained('products');
            $table->integer('quantity');
            $table->date('deadline');
            $table->enum('status', ['wait', 'in_process', 'completed', 'rejected']);
            $table->timestamp('archived_at');
            $table->timestamps();

            // индексы
            $table->index('original_id');
            $table->index('archived_at');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archived_orders');
    }
};
