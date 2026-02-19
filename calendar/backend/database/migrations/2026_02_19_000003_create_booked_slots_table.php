<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booked_slots', function (Blueprint $table) {
            $table->id();
            $table->string('doctor_id', 50);
            $table->foreign('doctor_id')->references('id')->on('doctors')->cascadeOnDelete();
            $table->date('date');
            $table->string('time', 5); // "HH:mm"
            $table->string('department', 100);
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('reservation_id')->nullable();
            $table->timestamps();

            // 同一医師・同一日時の二重予約を DB レベルで防止する UNIQUE 制約
            $table->unique(['doctor_id', 'date', 'time'], 'uq_booked_slots_doctor_date_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booked_slots');
    }
};
