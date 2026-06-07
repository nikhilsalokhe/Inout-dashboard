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
        // 1. Add columns to users table
        Schema::table('users', function (Blueprint $table) {
            $table->enum('employee_type', ['permanent', 'contract', 'temporary', 'trainee'])->default('permanent')->after('role');
            $table->enum('employment_status', ['active', 'notice_period', 'terminated', 'resigned', 'inactive'])->default('active')->after('employee_type');
            $table->date('joining_date')->nullable()->after('employment_status');
            $table->date('probation_end_date')->nullable()->after('joining_date');
            $table->date('contract_start_date')->nullable()->after('probation_end_date');
            $table->date('contract_end_date')->nullable()->after('contract_start_date');
        });

        // 2. Create employee_terminations table
        Schema::create('employee_terminations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->enum('termination_type', ['resigned', 'terminated', 'absconded', 'retired', 'contract_completed']);
            $table->text('termination_reason');
            $table->date('last_working_date');
            $table->integer('notice_period_days')->default(0);
            $table->enum('exit_status', ['initiated', 'in_progress', 'completed'])->default('initiated');
            $table->enum('final_settlement_status', ['pending', 'processed', 'paid'])->default('pending');
            $table->decimal('pending_salary', 12, 2)->default(0.00);
            $table->decimal('leave_encashment', 12, 2)->default(0.00);
            $table->enum('asset_return_status', ['pending', 'partial', 'completed'])->default('pending');
            $table->enum('exit_interview_status', ['pending', 'scheduled', 'completed', 'skipped'])->default('pending');
            $table->text('exit_interview_notes')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('terminated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('terminated_at');
            $table->timestamps();
        });

        // 3. Create employee_contracts table
        Schema::create('employee_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->date('contract_start_date');
            $table->date('contract_end_date');
            $table->boolean('renewal_option')->default(false);
            $table->enum('contract_status', ['active', 'expired', 'renewed', 'terminated'])->default('active');
            $table->text('remarks')->nullable();
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
        Schema::dropIfExists('employee_contracts');
        Schema::dropIfExists('employee_terminations');
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'employee_type',
                'employment_status',
                'joining_date',
                'probation_end_date',
                'contract_start_date',
                'contract_end_date'
            ]);
        });
    }
};
