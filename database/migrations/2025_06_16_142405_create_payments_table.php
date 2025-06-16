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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('reff')->unique();
            $table->decimal('amount', 15, 2);
            $table->decimal('original_amount', 15, 2);
            $table->string('name');
            $table->string('hp');
            $table->string('code');
            $table->timestamp('expired');
            $table->timestamp('paid_at')->nullable();
            $table->enum('status', ['pending', 'paid', 'expired'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
