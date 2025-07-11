<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->decimal('daily_rate', 10, 2);
            $table->decimal('weekly_rate', 10, 2)->nullable();
            $table->decimal('monthly_rate', 10, 2)->nullable();
            $table->decimal('replacement_cost', 12, 2)->nullable();
            $table->enum('status', ['available', 'rented', 'maintenance', 'retired'])->default('available');
            $table->text('condition_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('rental_customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('id_number')->nullable();
            $table->enum('type', ['individual', 'company'])->default('individual');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        Schema::create('rental_agreements', function (Blueprint $table) {
            $table->id();
            $table->string('agreement_number')->unique();
            $table->foreignId('customer_id')->constrained('rental_customers');
            $table->date('start_date');
            $table->date('expected_end_date');
            $table->date('actual_end_date')->nullable();
            $table->decimal('total_amount', 12, 2);
            $table->decimal('deposit_amount', 12, 2)->default(0);
            $table->enum('payment_status', ['pending', 'partial', 'paid'])->default('pending');
            $table->enum('status', ['draft', 'active', 'completed', 'cancelled'])->default('draft');
            $table->foreignId('created_by')->constrained('users');
            $table->text('terms_conditions')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('rental_agreement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_agreement_id')->constrained()->onDelete('cascade');
            $table->foreignId('rental_item_id')->constrained();
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('duration_days');
            $table->decimal('rate', 10, 2);
            $table->decimal('total_cost', 12, 2);
            $table->text('condition_out')->nullable();
            $table->text('condition_in')->nullable();
            $table->boolean('is_returned')->default(false);
            $table->date('return_date')->nullable();
            $table->decimal('damage_charges', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('rental_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_agreement_id')->constrained();
            $table->decimal('amount', 12, 2);
            $table->date('payment_date');
            $table->string('payment_method');
            $table->string('reference_number')->nullable();
            $table->enum('type', ['deposit', 'payment', 'refund'])->default('payment');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_payments');
        Schema::dropIfExists('rental_agreement_items');
        Schema::dropIfExists('rental_agreements');
        Schema::dropIfExists('rental_customers');
        Schema::dropIfExists('rental_items');
    }
};