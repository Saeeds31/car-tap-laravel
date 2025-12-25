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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('mobile')->unique();
            $table->string('image')->nullable();
            $table->string('national_code')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('birth_certificate_number')->nullable();
            $table->boolean('marital_status')->default(false);
            $table->string('address')->nullable();
            $table->string('postal_code')->nullable();
            $table->foreignId('city_id')->nullable()->constrained('cities')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
