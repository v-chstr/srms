<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('annotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('research_paper_id')->constrained('research_papers')->cascadeOnDelete();
            $table->foreignId('adviser_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('page');
            $table->enum('type', ['highlight', 'draw', 'text', 'note']);
            $table->float('x');
            $table->float('y');
            $table->float('w')->nullable();
            $table->float('h')->nullable();
            $table->text('content')->nullable();
            $table->string('color', 20)->default('#facc15');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['research_paper_id', 'page']);
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('annotations');
        Schema::enableForeignKeyConstraints();
    }
};
