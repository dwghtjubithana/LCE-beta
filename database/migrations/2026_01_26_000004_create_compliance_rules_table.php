<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compliance_rules', function (Blueprint $table) {
            $table->id();
            $table->string('document_type');
            $table->json('sector_applicability')->nullable();
            $table->json('required_keywords')->nullable();
            $table->unsignedSmallInteger('max_age_months')->nullable();
            $table->json('constraints')->nullable();
            $table->timestamps();

            $table->index('document_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_rules');
    }
};
