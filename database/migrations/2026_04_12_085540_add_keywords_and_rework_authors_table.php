<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add keywords JSON column to research_papers
        Schema::table('research_papers', function (Blueprint $table) {
            $table->json('keywords')->nullable()->after('abstract');
        });

        // 2. Make abstract nullable (optional but encouraged)
        Schema::table('research_papers', function (Blueprint $table) {
            $table->text('abstract')->nullable()->change();
        });

        // 3. Rework research_authors from pivot to standalone table.
        // Drop the old composite-PK pivot and recreate with string name columns.
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('research_authors');

        Schema::create('research_authors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('research_paper_id')->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->boolean('is_submitter')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
        });
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Restore keywords
        Schema::table('research_papers', function (Blueprint $table) {
            $table->dropColumn('keywords');
        });

        // Restore abstract as required
        Schema::table('research_papers', function (Blueprint $table) {
            $table->text('abstract')->nullable(false)->change();
        });

        // Restore old pivot table
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('research_authors');

        Schema::create('research_authors', function (Blueprint $table) {
            $table->foreignId('research_paper_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['research_paper_id', 'user_id']);
        });
        Schema::enableForeignKeyConstraints();
    }
};
