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
        Schema::table('holidays', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('location_id')->constrained('departments')->onDelete('set null');
            $table->foreignId('employee_id')->nullable()->after('department_id')->constrained('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('holidays', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropForeign(['department_id']);
            $table->dropColumn(['employee_id', 'department_id']);
        });
    }
};
