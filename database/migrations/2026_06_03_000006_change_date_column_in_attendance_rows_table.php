<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_rows', function (Blueprint $table) {
            $table->string('date', 100)->change();
        });
    }

    public function down(): void
    {
        Schema::table('attendance_rows', function (Blueprint $table) {
            $table->date('date')->change();
        });
    }
};
