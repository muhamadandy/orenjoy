<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders');
            $table->string('provider', 20)->default('midtrans');
            $table->string('reference', 50)->unique();
            $table->string('transaction_id', 64)->nullable();
            $table->string('status', 20)->default('pending')->index(); // pending, settlement, expire, cancel, deny
            $table->unsignedInteger('amount');
            $table->text('qr_url')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
