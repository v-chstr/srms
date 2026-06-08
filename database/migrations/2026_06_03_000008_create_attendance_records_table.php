<?php

use App\Models\AttendanceRecord;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_row_id')->constrained('attendance_rows')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('status', 20)->nullable();
            $table->timestamps();

            $table->unique(['attendance_row_id', 'user_id']);
        });

        $rows = DB::table('attendance_rows')->select('id', 'group_id', 'attendance', 'created_at', 'updated_at')->get();

        foreach ($rows as $row) {
            $memberIds = DB::table('attendance_group_members')
                ->where('group_id', $row->group_id)
                ->pluck('user_id');

            $status = AttendanceRecord::normalizeStatus($row->attendance);

            foreach ($memberIds as $memberId) {
                DB::table('attendance_records')->insert([
                    'attendance_row_id' => $row->id,
                    'user_id' => $memberId,
                    'status' => $status,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ]);
            }
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('attendance_records');
        Schema::enableForeignKeyConstraints();
    }
};
