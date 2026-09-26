<?php

namespace App\Services\Certificates;

/**
 * Decides whether a student meets the certificate conditions of a course. Pure logic:
 * all data is passed in, so the rules are easy to unit test.
 *
 * Conditions (all must hold):
 *  1. the course issues certificates,
 *  2. every required chapter is completed,
 *  3. every required quiz is passed (best attempt counts),
 *  4. the average of the best results of the required quizzes reaches the minimum percentage.
 */
class CertificateEligibility
{
    /**
     * @param  array<int, string>  $requiredChapters  chapter ID => title
     * @param  list<int>  $completedChapterIds
     * @param  array<int, string>  $requiredQuizzes  quiz ID => title
     * @param  array<int, array{percentage: float, passed: bool}>  $bestResults  quiz ID => best attempt
     */
    public function evaluate(
        bool $enabled,
        int $minPercentage,
        array $requiredChapters,
        array $completedChapterIds,
        array $requiredQuizzes,
        array $bestResults,
    ): EligibilityResult {
        if (! $enabled) {
            return new EligibilityResult(false, [__('Tento kurz nevydáva certifikát.')], null);
        }

        $missing = [];

        foreach ($requiredChapters as $id => $title) {
            if (! in_array($id, $completedChapterIds, true)) {
                $missing[] = __('Dokonči kapitolu „:title“.', ['title' => $title]);
            }
        }

        foreach ($requiredQuizzes as $id => $title) {
            if (! ($bestResults[$id]['passed'] ?? false)) {
                $missing[] = __('Úspešne absolvuj test „:title“.', ['title' => $title]);
            }
        }

        $percentages = array_map(fn (int $id): float => (float) ($bestResults[$id]['percentage'] ?? 0), array_keys($requiredQuizzes));
        $final = $percentages === [] ? null : round(array_sum($percentages) / count($percentages), 2);

        if ($final !== null && $final < $minPercentage) {
            $missing[] = __('Dosiahni priemernú úspešnosť aspoň :min % (teraz :current %).', [
                'min' => $minPercentage,
                'current' => rtrim(rtrim(number_format($final, 1, ',', ''), '0'), ','),
            ]);
        }

        return new EligibilityResult($missing === [], $missing, $final);
    }
}
