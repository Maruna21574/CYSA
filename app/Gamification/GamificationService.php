<?php

namespace App\Gamification;

use App\Enums\UserRole;
use App\Models\Badge;
use App\Models\User;
use App\Models\UserStat;
use App\Notifications\BadgeEarnedNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Optional gamification layer: experience points, levels, daily streaks and badges.
 * The rest of the application does not depend on it - it only listens to domain events.
 */
class GamificationService
{
    public const XP_CHAPTER = 10;

    public const XP_QUIZ_PASSED = 20;

    public const XP_PERFECT = 10;

    public const XP_COURSE = 50;

    public const XP_CERTIFICATE = 30;

    public function __construct(private BadgeRules $rules) {}

    public function enabledFor(?User $user): bool
    {
        return $user !== null
            && $user->role === UserRole::Student
            && ($user->school?->gamificationEnabled() ?? false);
    }

    /**
     * Level n starts at 50 * (n - 1)^2 XP: 0, 50, 200, 450, 800, ...
     */
    public static function levelFor(int $xp): int
    {
        return (int) floor(sqrt(max(0, $xp) / 50)) + 1;
    }

    public static function xpForLevel(int $level): int
    {
        return 50 * ($level - 1) ** 2;
    }

    public function stats(User $user): UserStat
    {
        return UserStat::firstOrNew(['user_id' => $user->id]);
    }

    /**
     * Adds XP once per (reason, source). Returns false when it was already awarded.
     * Learning activities also extend the daily streak; rewards (badges) do not.
     */
    public function award(User $user, int $amount, string $reason, ?Model $source = null, bool $isLearningActivity = true): bool
    {
        $inserted = DB::table('xp_transactions')->insertOrIgnore([
            'user_id' => $user->id,
            'amount' => $amount,
            'reason' => $reason,
            'source_type' => $source?->getMorphClass() ?? '',
            'source_id' => $source?->getKey() ?? 0,
            'created_at' => now(),
        ]);

        if ($inserted === 0) {
            return false;
        }

        $stats = $isLearningActivity ? $this->recordActivity($user) : $this->stats($user);
        $stats->xp_total += $amount;
        $stats->level = self::levelFor($stats->xp_total);
        $stats->save();

        return true;
    }

    /**
     * Daily streak: consecutive days with at least one learning activity.
     */
    public function recordActivity(User $user): UserStat
    {
        $stats = $this->stats($user);
        $today = now()->startOfDay();
        $last = $stats->last_activity_date?->copy()->startOfDay();

        if ($last === null || ! $last->equalTo($today)) {
            $stats->current_streak = $last !== null && $last->equalTo($today->copy()->subDay()) ? $stats->current_streak + 1 : 1;
            $stats->longest_streak = max($stats->longest_streak, $stats->current_streak);
            $stats->last_activity_date = $today;
        }

        $stats->save();

        return $stats;
    }

    /**
     * Awards every badge whose rule is now met.
     *
     * @return list<Badge> newly earned badges
     */
    public function checkBadges(User $user): array
    {
        $owned = DB::table('user_badges')->where('user_id', $user->id)->pluck('badge_id')->all();
        $earned = [];

        foreach (Badge::whereNotIn('id', $owned)->get() as $badge) {
            if (! $this->rules->isMet($badge->key, $user, $this->stats($user))) {
                continue;
            }

            $inserted = DB::table('user_badges')->insertOrIgnore([
                'user_id' => $user->id,
                'badge_id' => $badge->id,
                'awarded_at' => now(),
            ]);

            if ($inserted > 0) {
                $earned[] = $badge;

                if ($badge->xp_reward > 0) {
                    $this->award($user, $badge->xp_reward, 'badge', $badge, isLearningActivity: false);
                }

                $user->notify(new BadgeEarnedNotification($badge));
            }
        }

        return $earned;
    }
}
