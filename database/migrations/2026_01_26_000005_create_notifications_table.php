<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('document_id')->nullable();
            $table->enum('type', ['EXPIRING_SOON'])->default('EXPIRING_SOON');
            $table->enum('channel', ['email', 'push'])->default('email');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('company_id');
            $table->index('document_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
