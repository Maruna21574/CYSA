<?php

namespace App\Enums;

enum QuestionType: string
{
    case SingleChoice = 'single_choice';
    case MultipleChoice = 'multiple_choice';
    case TrueFalse = 'true_false';
    case ShortAnswer = 'short_answer';
    case FillBlank = 'fill_blank';
    case Matching = 'matching';

    public function label(): string
    {
        return match ($this) {
            self::SingleChoice => __('Jedna správna odpoveď'),
            self::MultipleChoice => __('Viac správnych odpovedí'),
            self::TrueFalse => __('Pravda / nepravda'),
            self::ShortAnswer => __('Krátka odpoveď'),
            self::FillBlank => __('Doplňovanie'),
            self::Matching => __('Priraďovanie'),
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::SingleChoice => __('Študent vyberie práve jednu možnosť.'),
            self::MultipleChoice => __('Študent označí všetky správne možnosti.'),
            self::TrueFalse => __('Študent rozhodne, či je tvrdenie pravdivé.'),
            self::ShortAnswer => __('Študent napíše odpoveď; zadajte všetky akceptované znenia.'),
            self::FillBlank => __('V texte označte medzery ako [[1]], [[2]]… a ku každej zadajte akceptované odpovede.'),
            self::Matching => __('Študent priradí každej položke vľavo správnu položku vpravo.'),
        };
    }

    /**
     * Types whose options are choices the student clicks on (used by answer statistics).
     */
    public function isChoice(): bool
    {
        return in_array($this, [self::SingleChoice, self::MultipleChoice, self::TrueFalse], true);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])->all();
    }
}
