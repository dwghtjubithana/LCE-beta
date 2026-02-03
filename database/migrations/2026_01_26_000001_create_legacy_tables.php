<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('lce_scan_logs')) {
            Schema::create('lce_scan_logs', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('verification_id')->nullable();
                $table->integer('inspector_id');
                $table->integer('supplier_id');
                $table->string('image_path', 255)->nullable();
                $table->string('geo_location', 100)->nullable();
                $table->decimal('ai_confidence', 5, 2)->nullable();
                $table->json('ppe_detected')->nullable();
                $table->enum('result_status', ['PASS', 'FAIL', 'MANUAL_REVIEW'])->default('MANUAL_REVIEW');
                $table->timestamp('scanned_at')->nullable()->useCurrent();
                $table->index('supplier_id');
                $table->index('inspector_id');
            });
        }

        if (!Schema::hasTable('lce_scan_summaries')) {
            Schema::create('lce_scan_summaries', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('scan_log_id');
                $table->string('instruction_file', 255)->nullable();
                $table->string('instruction_version', 50)->nullable();
                $table->string('original_filename', 255)->nullable();
                $table->string('mime_type', 100)->nullable();
                $table->integer('file_size')->nullable();
                $table->longText('ocr_text')->nullable();
                $table->longText('gemini_summary')->nullable();
                $table->json('gemini_raw_json')->nullable();
                $table->string('summary_file_path', 255)->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->index('scan_log_id');
            });
        }

        if (!Schema::hasTable('module_access')) {
            Schema::create('module_access', function (Blueprint $table) {
                $table->integer('user_id')->nullable();
                $table->string('module_name', 50)->nullable();
                $table->enum('access_level', ['READ', 'WRITE', 'NONE'])->nullable();
                $table->index('user_id');
            });
        }

        if (!Schema::hasTable('suppliers')) {
            Schema::create('suppliers', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 255);
                $table->string('type', 100)->nullable();
                $table->string('status', 50)->nullable();
                $table->boolean('lce_certified')->default(false);
                $table->string('contact_email', 255)->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();
            });
        }

        if (!Schema::hasTable('system_logs')) {
            Schema::create('system_logs', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id')->nullable();
                $table->string('action', 255)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('timestamp')->nullable()->useCurrent();
            });
        }

        if (!Schema::hasTable('tbl_a94d5f3c2e1b')) {
            Schema::create('tbl_a94d5f3c2e1b', function (Blueprint $table) {
                $table->increments('account_id');
                $table->string('customer_id', 20)->nullable()->unique();
                $table->string('login_name', 50)->unique();
                $table->string('email', 100)->unique();
                $table->string('password_hash', 255);
                $table->enum('user_type', ['employee', 'consultant', 'customer']);
                $table->string('role', 50);
                $table->string('job_title', 100)->nullable();
                $table->string('first_name', 50);
                $table->string('last_name', 50);
                $table->date('birth_date')->nullable();
                $table->enum('gender', ['male', 'female', 'other'])->default('other');
                $table->string('address_line', 255)->nullable();
                $table->string('postcode', 20)->nullable();
                $table->string('city', 100)->nullable();
                $table->string('country', 100)->nullable();
                $table->string('mobile_number', 20)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
                $table->timestamp('last_login')->nullable();
            });
        }

        if (!Schema::hasTable('tbl_banks')) {
            Schema::create('tbl_banks', function (Blueprint $table) {
                $table->increments('bank_id');
                $table->string('bank_code', 20)->unique();
                $table->string('bank_name', 100);
                $table->string('branch_name', 100)->nullable();
                $table->string('address_line', 255)->nullable();
                $table->string('postcode', 20)->nullable();
                $table->string('city', 100)->nullable();
                $table->string('country', 100)->nullable();
                $table->string('contact_number', 20)->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();
            });
        }

        if (!Schema::hasTable('tbl_roles')) {
            Schema::create('tbl_roles', function (Blueprint $table) {
                $table->increments('role_id');
                $table->string('role_name', 50)->unique();
                $table->string('description', 255)->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();
            });
        }

        if (!Schema::hasTable('tenders')) {
            Schema::create('tenders', function (Blueprint $table) {
                $table->increments('id');
                $table->string('project', 255);
                $table->string('client', 255)->nullable();
                $table->string('status', 50)->default('Pending');
                $table->string('amount', 50)->nullable();
                $table->date('start_date')->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenders');
        Schema::dropIfExists('tbl_roles');
        Schema::dropIfExists('tbl_banks');
        Schema::dropIfExists('tbl_a94d5f3c2e1b');
        Schema::dropIfExists('system_logs');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('module_access');
        Schema::dropIfExists('lce_scan_summaries');
        Schema::dropIfExists('lce_scan_logs');
    }
};
