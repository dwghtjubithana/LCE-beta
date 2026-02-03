<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenders')) {
            Schema::create('tenders', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->date('date')->nullable();
                $table->string('client')->nullable();
                $table->string('details_url')->nullable();
                $table->json('attachments')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('tenders', function (Blueprint $table) {
                if (!Schema::hasColumn('tenders', 'title')) {
                    $table->string('title')->nullable();
                }
                if (!Schema::hasColumn('tenders', 'date')) {
                    $table->date('date')->nullable();
                }
                if (!Schema::hasColumn('tenders', 'details_url')) {
                    $table->string('details_url')->nullable();
                }
                if (!Schema::hasColumn('tenders', 'attachments')) {
                    $table->json('attachments')->nullable();
                }
                if (!Schema::hasColumn('tenders', 'description')) {
                    $table->text('description')->nullable();
                }
                if (!Schema::hasColumn('tenders', 'created_at')) {
                    $table->timestamp('created_at')->nullable()->useCurrent();
                }
                if (!Schema::hasColumn('tenders', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
                }
            });
        }
    }

    public function down(): void
    {
        // no-op for legacy table compatibility
    }
};
