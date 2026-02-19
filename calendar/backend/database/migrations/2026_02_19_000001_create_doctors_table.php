<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctors', function (Blueprint $table) {
            // 文字列 PK（例: doc_cardiology_01）を使い、元プロジェクトの ID 体系を維持
            $table->string('id', 50)->primary();
            $table->string('name', 100);
            $table->string('department', 100)->index();
            // 曜日別勤務時間を JSON で保持: {"mon":["09:00","09:15",...], ...}
            $table->json('schedules');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
