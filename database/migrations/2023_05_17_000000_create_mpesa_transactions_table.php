<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mpesa_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('checkout_request_id');
            $table->string('merchant_request_id');
            $table->decimal('amount', 10, 2);
            $table->string('phone_number');
            $table->string('reference');
            $table->string('description')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->string('result_code')->nullable();
            $table->string('result_description')->nullable();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('rental_agreement_id')->constrained('rental_agreements');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mpesa_transactions');
    }
};