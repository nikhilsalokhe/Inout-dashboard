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
        // 1. Add coordinates to locations table
        if (Schema::hasTable('locations') && !Schema::hasColumn('locations', 'latitude')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->decimal('latitude', 10, 7)->nullable()->after('location_name');
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            });
        }

        // 2. Add distance field to attendances table
        if (Schema::hasTable('attendances') && !Schema::hasColumn('attendances', 'distance_km')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->decimal('distance_km', 6, 3)->nullable()->after('location');
            });
        }

        // 3. Create face_recognition_logs table
        if (!Schema::hasTable('face_recognition_logs')) {
            Schema::create('face_recognition_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
                $table->string('captured_image')->nullable();
                $table->decimal('confidence_score', 5, 2)->default(0.00);
                $table->boolean('liveness_passed')->default(false);
                $table->enum('status', ['success', 'failed_match', 'low_confidence', 'liveness_failed', 'multiple_faces'])->default('success');
                $table->enum('action_type', ['check_in', 'check_out', 'register'])->default('check_in');
                $table->text('remarks')->nullable();
                $table->timestamp('created_at')->useCurrent();
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
        Schema::dropIfExists('face_recognition_logs');

        if (Schema::hasTable('attendances') && Schema::hasColumn('attendances', 'distance_km')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->dropColumn('distance_km');
            });
        }

        if (Schema::hasTable('locations') && Schema::hasColumn('locations', 'latitude')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->dropColumn(['latitude', 'longitude']);
            });
        }
    }
};
