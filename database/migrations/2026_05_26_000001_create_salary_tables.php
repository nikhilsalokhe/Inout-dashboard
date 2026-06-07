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
        // 1. salary_structures Table
        Schema::create('salary_structures', function (Blueprint $table) {
            $table->id();
            $table->string('structure_name', 100);
            $table->decimal('basic_percentage', 5, 2)->default(50.00);
            $table->decimal('hra_percentage', 5, 2)->default(20.00);
            $table->decimal('da_percentage', 5, 2)->default(10.00);
            $table->decimal('travel_allowance', 10, 2)->default(0.00);
            $table->boolean('pf_enabled')->default(true);
            $table->boolean('esic_enabled')->default(true);
            $table->decimal('professional_tax', 8, 2)->default(200.00);
            $table->string('status', 20)->default('active'); // active, inactive
            $table->timestamps();
        });

        // 2. employee_salaries Table
        Schema::create('employee_salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('salary_structure_id')->constrained('salary_structures')->onDelete('cascade');
            $table->decimal('gross_salary', 12, 2);
            $table->date('effective_from');
            $table->string('status', 20)->default('active'); // active, inactive
            $table->timestamps();
        });

        // 3. salary_revisions Table
        Schema::create('salary_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->decimal('previous_gross_salary', 12, 2);
            $table->decimal('new_gross_salary', 12, 2);
            $table->foreignId('previous_structure_id')->constrained('salary_structures')->onDelete('cascade');
            $table->foreignId('new_structure_id')->constrained('salary_structures')->onDelete('cascade');
            $table->foreignId('revised_by')->nullable()->constrained('users')->onDelete('set null');
            $table->date('effective_date');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        // 4. payrolls Table
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->unsignedTinyInteger('month');
            $table->unsignedInteger('year');
            $table->decimal('gross_salary', 12, 2);
            $table->decimal('basic_salary', 12, 2);
            $table->decimal('hra', 12, 2);
            $table->decimal('da', 12, 2);
            $table->decimal('travel_allowance', 12, 2);
            $table->decimal('special_allowance', 12, 2);
            $table->decimal('overtime_hours', 6, 2)->default(0.00);
            $table->decimal('overtime_amount', 12, 2)->default(0.00);
            $table->decimal('bonus', 12, 2)->default(0.00);
            $table->decimal('incentives', 12, 2)->default(0.00);
            $table->decimal('absent_deduction', 12, 2)->default(0.00);
            $table->decimal('half_day_deduction', 12, 2)->default(0.00);
            $table->decimal('late_penalty', 12, 2)->default(0.00);
            $table->decimal('pf', 12, 2)->default(0.00);
            $table->decimal('esic', 12, 2)->default(0.00);
            $table->decimal('professional_tax', 8, 2)->default(0.00);
            $table->decimal('tds', 12, 2)->default(0.00);
            $table->decimal('loan_deduction', 12, 2)->default(0.00);
            $table->decimal('advance_salary', 12, 2)->default(0.00);
            $table->decimal('total_earnings', 12, 2);
            $table->decimal('total_deductions', 12, 2);
            $table->decimal('net_salary', 12, 2);
            $table->decimal('payable_days', 4, 1);
            $table->decimal('paid_days', 4, 1);
            $table->string('status', 20)->default('Draft'); // Draft, Generated, Approved, Paid, Cancelled
            $table->timestamp('generated_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'month', 'year']);
        });

        // 5. notifications Table
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('title', 255);
            $table->text('description');
            $table->string('type', 50)->default('general'); // shift, leave, salary, announcement, general
            $table->boolean('unread')->default(true);
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
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('payrolls');
        Schema::dropIfExists('salary_revisions');
        Schema::dropIfExists('employee_salaries');
        Schema::dropIfExists('salary_structures');
    }
};
