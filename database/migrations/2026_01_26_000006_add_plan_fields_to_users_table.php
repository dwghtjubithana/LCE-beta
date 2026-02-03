<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'plan')) {
                $table->enum('plan', ['FREE', 'PRO', 'BUSINESS'])->default('FREE');
            }
            if (!Schema::hasColumn('users', 'plan_status')) {
                $table->enum('plan_status', ['ACTIVE', 'PENDING_PAYMENT', 'EXPIRED'])->default('ACTIVE');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'plan_status')) {
                $table->dropColumn('plan_status');
            }
            if (Schema::hasColumn('users', 'plan')) {
                $table->dropColumn('plan');
            }
        });
    }
};
