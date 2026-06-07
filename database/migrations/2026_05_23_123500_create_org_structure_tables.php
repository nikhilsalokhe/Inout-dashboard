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
        // 1. Create departments table
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('department_name');
            $table->string('status')->default('active'); // active, inactive
            $table->timestamps();
        });

        // 2. Create locations table
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('location_name');
            $table->string('status')->default('active'); // active, inactive
            $table->timestamps();
        });

        // 3. Create positions table
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->string('position_name');
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->string('status')->default('active'); // active, inactive
            $table->timestamps();
        });

        // 4. Update users table with organization columns
        Schema::table('users', function (Blueprint $table) {
            $table->string('mobile')->nullable()->after('email');
            $table->foreignId('department_id')->nullable()->after('employee_code')->constrained('departments')->onDelete('set null');
            $table->foreignId('location_id')->nullable()->after('department_id')->constrained('locations')->onDelete('set null');
            $table->foreignId('position_id')->nullable()->after('location_id')->constrained('positions')->onDelete('set null');
            $table->foreignId('reporting_manager_id')->nullable()->after('position_id')->constrained('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['reporting_manager_id']);
            $table->dropForeign(['position_id']);
            $table->dropForeign(['location_id']);
            $table->dropForeign(['department_id']);
            $table->dropColumn(['mobile', 'department_id', 'location_id', 'position_id', 'reporting_manager_id']);
        });

        Schema::dropIfExists('positions');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('departments');
    }
};
