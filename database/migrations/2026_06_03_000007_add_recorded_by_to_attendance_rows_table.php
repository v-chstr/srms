<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::table('attendance_rows', function (Blueprint $table) {
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
        });
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::table('attendance_rows', function (Blueprint $table) {
            $table->dropForeign(['recorded_by']);
            $table->dropColumn('recorded_by');
        });
        Schema::enableForeignKeyConstraints();
    }
};
