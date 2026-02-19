<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('doctor_id', 50);
            $table->foreign('doctor_id')->references('id')->on('doctors')->cascadeOnDelete();
            $table->string('department', 100);
            $table->date('date');
            $table->string('time', 5); // "HH:mm"
            $table->string('purpose', 20)->default('');
            $table->timestamps();

            $table->index('user_id');
            $table->index(['department', 'date', 'time']);
            $table->index(['doctor_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
