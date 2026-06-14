<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();

            $table->string('origin_city');
            $table->string('origin_address');
            $table->decimal('origin_lat', 10, 7)->nullable();
            $table->decimal('origin_lng', 10, 7)->nullable();

            $table->string('destination_city');
            $table->string('destination_address');
            $table->decimal('destination_lat', 10, 7)->nullable();
            $table->decimal('destination_lng', 10, 7)->nullable();

            $table->dateTime('departure_time');
            $table->dateTime('arrival_time')->nullable();

            $table->decimal('price', 8, 2);
            $table->unsignedTinyInteger('seats_total');
            $table->unsignedTinyInteger('seats_available');
            $table->enum('luggage_size', ['small', 'medium', 'large'])->default('medium');
            $table->boolean('pets_allowed')->default(false);
            $table->boolean('smoking_allowed')->default(false);
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'published', 'in_progress', 'completed', 'cancelled'])
                ->default('draft');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rides');
    }
};











