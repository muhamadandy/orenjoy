<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code')->unique();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->integer('sub_total');
            $table->integer('tax');
            $table->integer('grand_total');
            $table->enum('status', [
                'pending', 'settlement', 'cooked', 'completed', 'cancelled',
            ])->default('pending');
            $table->integer('table_number');
            $table->enum('payment_method', ['tunai', 'qris']);
            $table->text('note')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
