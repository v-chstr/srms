<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('research_papers', function (Blueprint $table) {
            $table->string('thumbnail_path', 500)->nullable()->after('original_filename');
        });
    }

    public function down(): void
    {
        Schema::table('research_papers', function (Blueprint $table) {
            $table->dropColumn('thumbnail_path');
        });
    }
};
