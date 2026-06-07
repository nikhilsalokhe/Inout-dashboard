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
        // 1. Create shifts table
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('shift_name');
            $table->string('shift_type')->default('general'); // general, night, rotational, flexible
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->integer('grace_time_minutes')->default(15);
            $table->decimal('half_day_time', 4, 2)->default(4.00); // Minimum hours required for half-day
            $table->decimal('minimum_working_hours', 4, 2)->default(8.00); // Minimum hours for full day
            $table->string('weekly_off_days')->default('Saturday,Sunday'); // Comma-separated days
            $table->string('status')->default('active'); // active, inactive
            $table->timestamps();
        });

        // 2. Create shift_assignments table
        Schema::create('shift_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('shift_id')->constrained('shifts')->onDelete('cascade');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();
        });

        // 3. Update attendances table with shift and status columns
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('shift_id')->nullable()->after('user_id')->constrained('shifts')->onDelete('set null');
            $table->enum('status', ['present', 'late', 'half_day', 'absent', 'weekly_off'])->default('present')->after('working_hours');
            $table->text('remarks')->nullable()->after('image');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['shift_id']);
            $table->dropColumn(['shift_id', 'status', 'remarks']);
        });

        Schema::dropIfExists('shift_assignments');
        Schema::dropIfExists('shifts');
    }
};
