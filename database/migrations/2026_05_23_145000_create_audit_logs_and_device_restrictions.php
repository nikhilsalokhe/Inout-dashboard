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
        // Add device_id to users table
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'device_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('device_id', 255)->nullable()->after('status');
            });
        }

        // Create audit_logs table
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('module', 100); // settings, leaves, shifts, face_recognition
            $table->string('action', 100); // create, update, delete, approve, reset
            $table->json('old_data')->nullable();
            $table->json('new_data')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('device_info', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('audit_logs');

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'device_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('device_id');
            });
        }
    }
};
