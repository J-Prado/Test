<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('specialist_id')->constrained()->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->string('status')->default('available'); // available | booked
            // Soft link back to the appointment holding this slot (no FK to avoid
            // a circular constraint with the appointments table).
            $table->unsignedBigInteger('appointment_id')->nullable()->index();
            $table->timestamps();

            $table->unique(['specialist_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slots');
    }
};
