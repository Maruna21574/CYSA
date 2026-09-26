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
        Schema::table('courses', function (Blueprint $table) {
            $table->boolean('certificate_enabled')->default(false)->after('sequential_chapters');
            $table->unsignedTinyInteger('certificate_min_percentage')->default(70)->after('certificate_enabled');
        });

        // Explicit conditions of the certificate. No rows = all chapters and all graded quizzes count.
        Schema::create('course_certificate_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chapter_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('quiz_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['course_id', 'chapter_id']);
            $table->unique(['course_id', 'quiz_id']);
        });

        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 24)->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('course_id')->constrained()->restrictOnDelete();
            // Snapshot at the time of issue: the certificate never changes when a name is edited later.
            $table->string('holder_name');
            $table->string('course_title');
            $table->string('school_name')->nullable();
            $table->string('teacher_name')->nullable();
            $table->decimal('final_percentage', 5, 2)->nullable();
            $table->timestamp('issued_at');
            $table->timestamp('revoked_at')->nullable();
            $table->string('revoked_reason')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'course_id']);
            $table->index(['course_id', 'issued_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
        Schema::dropIfExists('course_certificate_requirements');

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['certificate_enabled', 'certificate_min_percentage']);
        });
    }
};
