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
        if (!Schema::hasColumn('users', 'attendance_method')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('attendance_method')->nullable()->after('face_image');
            });
        }

        if (!Schema::hasColumn('departments', 'attendance_method')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->string('attendance_method')->nullable();
            });
        }

        if (!Schema::hasColumn('attendances', 'method_used')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->string('method_used')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('attendance_method');
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('attendance_method');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('method_used');
        });
    }
};
