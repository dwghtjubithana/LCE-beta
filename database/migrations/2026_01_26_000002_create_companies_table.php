<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('owner_user_id');
            $table->string('company_name');
            $table->string('sector');
            $table->text('experience')->nullable();
            $table->json('contact')->nullable();
            $table->boolean('bluewave_status')->default(false);
            $table->unsignedTinyInteger('current_score')->default(0);
            $table->enum('verification_level', ['unverified', 'email_verified', 'physical_verified'])->default('unverified');
            $table->timestamps();

            $table->index('owner_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
