<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->nullable()->unique();
                $table->string('username', 100)->unique();
                $table->string('password_hash', 255);
                $table->string('email', 150)->nullable()->unique();
                $table->string('phone', 30)->nullable()->unique();
                $table->enum('role', ['MASTER', 'EXECUTIVE', 'STAFF'])->default('STAFF');
                $table->string('app_role', 50)->default('user');
                $table->enum('plan', ['FREE', 'PRO', 'BUSINESS'])->default('FREE');
                $table->enum('plan_status', ['ACTIVE', 'PENDING_PAYMENT', 'EXPIRED'])->default('ACTIVE');
                $table->enum('status', ['ACTIVE', 'SUSPENDED'])->default('ACTIVE');
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
                $table->dateTime('last_login')->nullable();
                $table->string('firebase_uid', 255)->nullable()->index();
            });
        } else {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'uuid')) {
                    $table->uuid('uuid')->nullable()->unique();
                }
                if (!Schema::hasColumn('users', 'email')) {
                    $table->string('email', 150)->nullable()->unique();
                }
                if (!Schema::hasColumn('users', 'phone')) {
                    $table->string('phone', 30)->nullable()->unique();
                }
                if (!Schema::hasColumn('users', 'app_role')) {
                    $table->string('app_role', 50)->default('user');
                }
                if (!Schema::hasColumn('users', 'plan')) {
                    $table->enum('plan', ['FREE', 'PRO', 'BUSINESS'])->default('FREE');
                }
                if (!Schema::hasColumn('users', 'plan_status')) {
                    $table->enum('plan_status', ['ACTIVE', 'PENDING_PAYMENT', 'EXPIRED'])->default('ACTIVE');
                }
                if (!Schema::hasColumn('users', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
                }
                if (!Schema::hasColumn('users', 'status')) {
                    $table->enum('status', ['ACTIVE', 'SUSPENDED'])->default('ACTIVE');
                }
                if (!Schema::hasColumn('users', 'firebase_uid')) {
                    $table->string('firebase_uid', 255)->nullable()->index();
                }
                if (!Schema::hasColumn('users', 'last_login')) {
                    $table->dateTime('last_login')->nullable();
                }
                if (!Schema::hasColumn('users', 'password_hash')) {
                    $table->string('password_hash', 255);
                }
                if (!Schema::hasColumn('users', 'username')) {
                    $table->string('username', 100)->unique();
                }
            });
        }

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('sessions');
    }
};
