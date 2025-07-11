<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('model_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->foreignId('equipment_category_id')->constrained();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 12, 2)->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('supplier')->nullable();
            $table->date('warranty_expiry')->nullable();
            $table->enum('status', ['operational', 'maintenance', 'repair', 'retired'])->default('operational');
            $table->text('specifications')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('maintenance_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained();
            $table->string('title');
            $table->text('description');
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'quarterly', 'biannually', 'annually', 'custom'])->default('monthly');
            $table->integer('frequency_custom_days')->nullable();
            $table->date('last_maintenance_date')->nullable();
            $table->date('next_maintenance_date');
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained();
            $table->foreignId('maintenance_schedule_id')->nullable()->constrained();
            $table->date('maintenance_date');
            $table->text('work_performed');
            $table->decimal('cost', 10, 2)->default(0);
            $table->foreignId('performed_by')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('equipment_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained();
            $table->foreignId('project_id')->nullable()->constrained();
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->date('assignment_date');
            $table->date('return_date')->nullable();
            $table->text('purpose')->nullable();
            $table->enum('status', ['assigned', 'returned', 'damaged'])->default('assigned');
            $table->text('condition_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_assignments');
        Schema::dropIfExists('maintenance_logs');
        Schema::dropIfExists('maintenance_schedules');
        Schema::dropIfExists('equipment');
        Schema::dropIfExists('equipment_categories');
    }
};