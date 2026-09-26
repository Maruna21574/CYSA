<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Controlled vocabulary of cyber security topics - the unit of the thesis analysis
        // (which topics students struggle with, improvement per topic between pre and post test).
        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->timestamps();
        });

        $now = now();
        DB::table('topics')->insert(collect([
            'Heslá a autentifikácia', 'Phishing', 'Sociálne inžinierstvo', 'Sociálne siete',
            'Osobné údaje a GDPR', 'Malvér a podvodné aplikácie', 'Bezpečnosť zariadení a sietí', 'Kyberšikana',
        ])->map(fn (string $name): array => [
            'name' => $name, 'slug' => Str::slug($name), 'created_at' => $now, 'updated_at' => $now,
        ])->all());

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name', 50);
            $table->string('slug', 60);
            $table->timestamps();

            $table->unique(['school_id', 'slug']);
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('chapter_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 32);
            $table->text('body');
            $table->text('explanation')->nullable();
            $table->string('image_path')->nullable();
            $table->decimal('default_points', 6, 2)->default(1);
            $table->string('difficulty', 16)->nullable();
            // draft = waiting for teacher review (AI suggestions), approved = usable in quizzes
            $table->string('status', 16)->default('approved');
            $table->string('source', 16)->default('manual');
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'status']);
            $table->index(['author_id', 'status']);
            $table->index('course_id');
        });

        Schema::create('question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->text('match_body')->nullable();
            $table->unsignedTinyInteger('blank_index')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['question_id', 'position']);
        });

        Schema::create('question_topic', function (Blueprint $table) {
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('topic_id')->constrained()->cascadeOnDelete();

            $table->primary(['question_id', 'topic_id']);
            $table->index('topic_id');
        });

        Schema::create('question_tag', function (Blueprint $table) {
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();

            $table->primary(['question_id', 'tag_id']);
            $table->index('tag_id');
        });

        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('course_id')->constrained()->restrictOnDelete();
            $table->foreignId('chapter_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('purpose', 16)->default('practice');
            $table->foreignId('paired_quiz_id')->nullable()->constrained('quizzes')->nullOnDelete();
            $table->unsignedTinyInteger('pass_percentage')->default(60);
            $table->unsignedSmallInteger('max_attempts')->nullable();
            $table->unsignedSmallInteger('time_limit_minutes')->nullable();
            $table->timestamp('available_from')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->boolean('shuffle_questions')->default(false);
            $table->boolean('shuffle_options')->default(false);
            $table->string('show_result', 16)->default('immediately');
            $table->string('show_correct_answers', 16)->default('immediately');
            $table->string('status', 16)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['course_id', 'status']);
            $table->index(['school_id', 'purpose']);
            $table->index('author_id');
        });

        Schema::create('quiz_question', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->decimal('points', 6, 2)->nullable();
            $table->timestamps();

            $table->unique(['quiz_id', 'question_id']);
            $table->index('question_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_question');
        Schema::dropIfExists('quizzes');
        Schema::dropIfExists('question_tag');
        Schema::dropIfExists('question_topic');
        Schema::dropIfExists('question_options');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('topics');
    }
};
