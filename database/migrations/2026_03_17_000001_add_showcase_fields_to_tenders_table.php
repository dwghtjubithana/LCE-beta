<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            if (!Schema::hasColumn('tenders', 'submission_deadline')) {
                $table->date('submission_deadline')->nullable()->after('date');
            }
            if (!Schema::hasColumn('tenders', 'location')) {
                $table->string('location')->nullable()->after('client');
            }
            if (!Schema::hasColumn('tenders', 'sector')) {
                $table->string('sector')->nullable()->after('location');
            }
            if (!Schema::hasColumn('tenders', 'reference_code')) {
                $table->string('reference_code')->nullable()->after('sector');
            }
            if (!Schema::hasColumn('tenders', 'contract_type')) {
                $table->string('contract_type')->nullable()->after('reference_code');
            }
            if (!Schema::hasColumn('tenders', 'budget_label')) {
                $table->string('budget_label')->nullable()->after('contract_type');
            }
            if (!Schema::hasColumn('tenders', 'eligibility')) {
                $table->text('eligibility')->nullable()->after('budget_label');
            }
            if (!Schema::hasColumn('tenders', 'source_name')) {
                $table->string('source_name')->nullable()->after('details_url');
            }
            if (!Schema::hasColumn('tenders', 'source_url')) {
                $table->string('source_url')->nullable()->after('source_name');
            }
            if (!Schema::hasColumn('tenders', 'cover_image_url')) {
                $table->string('cover_image_url')->nullable()->after('source_url');
            }
            if (!Schema::hasColumn('tenders', 'issuer_logo_url')) {
                $table->string('issuer_logo_url')->nullable()->after('cover_image_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $columns = [
                'submission_deadline',
                'location',
                'sector',
                'reference_code',
                'contract_type',
                'budget_label',
                'eligibility',
                'source_name',
                'source_url',
                'cover_image_url',
                'issuer_logo_url',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('tenders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
