<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('attendance_groups')->cascadeOnDelete();
            $table->date('date');
            $table->text('activities')->nullable();
            $table->string('attendance', 100)->nullable();
            $table->string('remarks', 191)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_rows');
    }
};
