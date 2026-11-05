<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('category_id')->constrained('survey_categories')->restrictOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('student_target_mode', ['none', 'all', 'classes'])->default('none');
            $table->enum('teacher_target_mode', ['none', 'all', 'selected'])->default('none');
            $table->enum('status', ['draft', 'published', 'closed'])->default('draft');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_result_published')->default(false);
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surveys');
    }
};
