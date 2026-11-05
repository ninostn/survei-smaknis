<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('answer_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('answer_id')->constrained('answers')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->restrictOnDelete();
            $table->foreignId('option_id')->constrained('question_options')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['answer_id', 'option_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('answer_options');
    }
};
