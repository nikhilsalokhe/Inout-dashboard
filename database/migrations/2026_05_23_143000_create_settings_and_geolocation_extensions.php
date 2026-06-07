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
        // Create settings table
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('group', 50); // attendance, face, geo, leave, security, notifications
            $table->timestamps();
        });

        // Add allowed_radius_meter to locations table
        if (Schema::hasTable('locations') && !Schema::hasColumn('locations', 'allowed_radius_meter')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->integer('allowed_radius_meter')->default(200)->after('longitude');
            });
        }

        // Create employee_geo_locations pivot table
        Schema::create('employee_geo_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('geo_location_id')->constrained('locations')->onDelete('cascade');
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
        Schema::dropIfExists('employee_geo_locations');

        if (Schema::hasTable('locations') && Schema::hasColumn('locations', 'allowed_radius_meter')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->dropColumn('allowed_radius_meter');
            });
        }

        Schema::dropIfExists('settings');
    }
};
