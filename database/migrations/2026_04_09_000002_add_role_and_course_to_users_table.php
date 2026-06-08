<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name');
            $table->string('first_name')->after('id');
            $table->string('last_name')->after('first_name');
            $table->enum('role', ['admin', 'adviser', 'student'])->default('student')->after('password');
            $table->boolean('is_adviser')->default(false)->after('role');
            $table->foreignId('course_id')->nullable()->after('is_adviser')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->dropColumn(['first_name', 'last_name', 'role', 'is_adviser', 'course_id']);
            $table->string('name')->after('id');
        });
    }
};
