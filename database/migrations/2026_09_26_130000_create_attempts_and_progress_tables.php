<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('attempt_number');
            $table->string('status', 16)->default('in_progress');
            $table->timestamp('started_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->boolean('timed_out')->default(false);
            $table->unsignedInteger('time_spent_seconds')->nullable();
            $table->decimal('score', 8, 2)->nullable();
            $table->decimal('max_score', 8, 2)->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->boolean('passed')->nullable();
            // Order fixed at start, so a reload never reshuffles the test.
            $table->json('question_order');
            $table->timestamps();

            $table->unique(['quiz_id', 'user_id', 'attempt_number']);
            $table->index(['quiz_id', 'status']);
            $table->index(['user_id', 'finished_at']);
            $table->index(['status', 'expires_at']);
        });

        Schema::create('quiz_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->restrictOnDelete();
            // The question exactly as it was when the attempt started (text, options, correct
            // answers, points). Grading and review use only the snapshot.
            $table->json('question_snapshot');
            $table->json('response')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->decimal('points_awarded', 8, 2)->default(0);
            $table->decimal('max_points', 8, 2);
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->unique(['quiz_attempt_id', 'question_id']);
            $table->index(['question_id', 'is_correct']);
        });

        // Selected options of choice questions - makes "most common wrong answer" a plain GROUP BY.
        Schema::create('quiz_answer_option', function (Blueprint $table) {
            $table->foreignId('quiz_answer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_option_id')->constrained()->cascadeOnDelete();

            $table->primary(['quiz_answer_id', 'question_option_id']);
            $table->index('question_option_id');
        });

        Schema::create('chapter_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chapter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'chapter_id']);
            $table->index(['course_id', 'user_id']);
        });

        Schema::create('course_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('completed_chapters')->default(0);
            $table->unsignedInteger('total_chapters')->default(0);
            $table->decimal('percentage', 5, 2)->default(0);
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_activity_at');
            $table->timestamps();

            $table->unique(['user_id', 'course_id']);
            $table->index(['course_id', 'percentage']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_progress');
        Schema::dropIfExists('chapter_progress');
        Schema::dropIfExists('quiz_answer_option');
        Schema::dropIfExists('quiz_answers');
        Schema::dropIfExists('quiz_attempts');
    }
};
