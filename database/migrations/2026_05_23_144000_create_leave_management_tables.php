<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Modify status enum in attendances table using raw SQL for robustness
        DB::statement("ALTER TABLE attendances MODIFY COLUMN status ENUM('present', 'late', 'half_day', 'absent', 'weekly_off', 'on_leave') DEFAULT 'present'");

        // Create leave_policies table
        Schema::create('leave_policies', function (Blueprint $table) {
            $table->id();
            $table->string('leave_name', 100);
            $table->string('leave_code', 10)->unique();
            $table->string('leave_type', 50)->default('unpaid'); // paid, unpaid
            $table->integer('total_yearly_leave')->default(12);
            $table->decimal('monthly_credit', 3, 2)->default(0.00);
            $table->boolean('carry_forward')->default(false);
            $table->integer('max_carry_forward')->default(0);
            $table->boolean('requires_approval')->default(true);
            $table->string('status', 20)->default('active'); // active, inactive
            $table->timestamps();
        });

        // Create leave_balances table
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('leave_policy_id')->constrained('leave_policies')->onDelete('cascade');
            $table->decimal('total_leave', 6, 2)->default(0.00);
            $table->decimal('used_leave', 6, 2)->default(0.00);
            $table->decimal('remaining_leave', 6, 2)->default(0.00);
            $table->integer('year');
            $table->timestamps();
        });

        // Create leave_applications table
        Schema::create('leave_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('leave_policy_id')->constrained('leave_policies')->onDelete('cascade');
            $table->date('from_date');
            $table->date('to_date');
            $table->integer('total_days');
            $table->text('reason');
            $table->string('attachment', 255)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled', 'partially_approved'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        // Create holidays table
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('holiday_name', 150);
            $table->date('holiday_date');
            $table->foreignId('location_id')->nullable()->constrained('locations')->onDelete('set null');
            $table->string('holiday_type', 30)->default('gazetted'); // gazetted, optional
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
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('leave_applications');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leave_policies');

        // Revert status enum in attendances table
        DB::statement("ALTER TABLE attendances MODIFY COLUMN status ENUM('present', 'late', 'half_day', 'absent', 'weekly_off') DEFAULT 'present'");
    }
};
