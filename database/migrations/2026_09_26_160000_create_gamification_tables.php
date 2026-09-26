<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ledger of experience points; the unique key makes every award idempotent.
        Schema::create('xp_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('amount');
            $table->string('reason', 48);
            $table->string('source_type', 64)->default('');
            $table->unsignedBigInteger('source_id')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'reason', 'source_type', 'source_id']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->string('key', 48)->unique();
            $table->string('name', 100);
            $table->string('description');
            $table->string('icon', 32)->default('badge');
            $table->unsignedSmallInteger('xp_reward')->default(0);
            $table->timestamps();
        });

        Schema::create('user_badges', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('badge_id')->constrained()->cascadeOnDelete();
            $table->timestamp('awarded_at');

            $table->primary(['user_id', 'badge_id']);
        });

        Schema::create('user_stats', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('xp_total')->default(0);
            $table->unsignedSmallInteger('level')->default(1);
            $table->unsignedSmallInteger('current_streak')->default(0);
            $table->unsignedSmallInteger('longest_streak')->default(0);
            $table->date('last_activity_date')->nullable();
            $table->timestamps();
        });

        $now = now();
        DB::table('badges')->insert(array_map(fn (array $badge): array => [...$badge, 'created_at' => $now, 'updated_at' => $now], [
            ['key' => 'first_pass', 'name' => 'Prvý úspešný test', 'description' => 'Úspešne si absolvoval(a) svoj prvý test.', 'icon' => 'check-circle', 'xp_reward' => 10],
            ['key' => 'perfect_score', 'name' => '100 % úspešnosť', 'description' => 'Test bez jedinej chyby.', 'icon' => 'sparkles', 'xp_reward' => 20],
            ['key' => 'phishing_expert', 'name' => 'Expert na phishing', 'description' => 'Aspoň 90 % správnych odpovedí v téme phishing (min. 5 otázok).', 'icon' => 'shield', 'xp_reward' => 30],
            ['key' => 'password_pro', 'name' => 'Bezpečné heslá', 'description' => 'Aspoň 90 % správnych odpovedí v téme heslá (min. 5 otázok).', 'icon' => 'lock', 'xp_reward' => 30],
            ['key' => 'five_courses', 'name' => '5 kurzov dokončených', 'description' => 'Dokončil(a) si päť kurzov.', 'icon' => 'trophy', 'xp_reward' => 50],
            ['key' => 'streak_7', 'name' => '7 dní v rade', 'description' => 'Učil(a) si sa sedem dní po sebe.', 'icon' => 'fire', 'xp_reward' => 30],
            ['key' => 'first_certificate', 'name' => 'Prvý certifikát', 'description' => 'Získal(a) si svoj prvý certifikát.', 'icon' => 'badge', 'xp_reward' => 20],
        ]));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_stats');
        Schema::dropIfExists('user_badges');
        Schema::dropIfExists('badges');
        Schema::dropIfExists('xp_transactions');
    }
};
