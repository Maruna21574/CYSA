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
        // Text extracted from uploaded study materials (input for AI question generation).
        Schema::create('material_texts', function (Blueprint $table) {
            $table->foreignId('material_id')->primary()->constrained()->cascadeOnDelete();
            $table->longText('content');
            $table->text('summary')->nullable();
            $table->json('keywords')->nullable();
            $table->timestamp('extracted_at');
            $table->timestamps();
        });

        // One request of a teacher to generate question suggestions.
        Schema::create('ai_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chapter_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('material_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 16)->default('pending');
            $table->string('provider', 32);
            $table->string('model', 64);
            $table->unsignedTinyInteger('requested_count');
            $table->json('question_types');
            $table->string('difficulty', 16)->nullable();
            $table->unsignedInteger('input_chars')->nullable();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->unsignedTinyInteger('created_questions')->default(0);
            $table->json('warnings')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('status');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('ai_generation_id')->nullable()->after('source')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ai_generation_id');
        });

        Schema::dropIfExists('ai_generations');
        Schema::dropIfExists('material_texts');
    }
};
