<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('overtime_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            
            // Daily Rules
            $table->boolean('calc_daily')->default(false);
            $table->decimal('daily_min_hours', 5, 2)->default(0)->comment('Min hours before OT starts (e.g., 9)');
            $table->decimal('daily_threshold', 5, 2)->default(0)->comment('Threshold for max OT start (if needed)');
            $table->decimal('max_daily', 5, 2)->nullable()->comment('Max OT hours per day');
            $table->decimal('daily_rate_multiplier', 5, 2)->default(1.0);

            // Weekly Rules
            $table->boolean('calc_weekly')->default(false);
            $table->decimal('weekly_threshold', 5, 2)->default(48);
            $table->decimal('max_weekly', 5, 2)->nullable();
            $table->decimal('weekly_rate_multiplier', 5, 2)->default(1.0);

            // Monthly Rules
            $table->boolean('calc_monthly')->default(false);
            $table->decimal('monthly_threshold', 5, 2)->default(208);
            $table->decimal('max_monthly', 5, 2)->nullable();
            $table->decimal('monthly_rate_multiplier', 5, 2)->default(1.0);

            // Special Rules
            $table->boolean('calc_weekend')->default(false);
            $table->decimal('weekend_rate_multiplier', 5, 2)->default(1.0);
            $table->boolean('calc_holiday')->default(false);
            $table->decimal('holiday_rate_multiplier', 5, 2)->default(1.0);

            // Rate configuration
            $table->enum('rate_type', ['fixed', 'salary_based'])->default('fixed');
            $table->decimal('fixed_rate', 10, 2)->nullable();
            $table->decimal('max_payable_hours_per_month', 5, 2)->nullable();
            
            $table->timestamps();
        });

        Schema::create('overtime_policy_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->constrained('overtime_policies')->onDelete('cascade');
            $table->morphs('assignable'); // For User, Department, EmployeeType
            $table->timestamps();
        });

        Schema::create('overtime_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('date');
            $table->enum('overtime_type', ['daily', 'weekly', 'monthly', 'weekend', 'holiday'])->default('daily');
            $table->decimal('hours', 6, 2)->default(0);
            $table->enum('status', ['pending', 'manager_approved', 'hr_approved', 'rejected', 'processed', 'paid'])->default('pending');
            $table->foreignId('manager_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('hr_id')->nullable()->constrained('users')->onDelete('set null');
            $table->decimal('amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_manual_request')->default(false);
            $table->foreignId('payroll_id')->nullable()->constrained('payrolls')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('overtime_records');
        Schema::dropIfExists('overtime_policy_assignments');
        Schema::dropIfExists('overtime_policies');
    }
};
