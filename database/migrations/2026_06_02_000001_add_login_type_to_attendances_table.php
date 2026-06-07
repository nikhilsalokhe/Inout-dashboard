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
        if (Schema::hasTable('attendances') && !Schema::hasColumn('attendances', 'login_type')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->enum('login_type', ['office', 'remote'])->default('office')->after('location');
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
        if (Schema::hasTable('attendances') && Schema::hasColumn('attendances', 'login_type')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->dropColumn('login_type');
            });
        }
    }
};
