<?php

namespace Database\Seeders;

use App\Enums\QuestionType;
use App\Enums\QuizPurpose;
use App\Enums\QuizStatus;
use App\Enums\ResultVisibility;
use App\Models\Course;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Topic;
use App\Services\Questions\QuestionService;
use Illuminate\Database\Seeder;

/**
 * Question bank and quizzes of the demo course: a paired pre/post test and chapter quizzes.
 */
class DemoQuizSeeder extends Seeder
{
    public function run(QuestionService $service): void
    {
        $course = Course::where('slug', 'zaklady-kybernetickej-bezpecnosti')->firstOrFail();

        if ($course->quizzes()->exists()) {
            return;
        }

        $topics = Topic::pluck('id', 'slug');
        $chapters = $course->chapters()->pluck('id', 'title');

        /** @var array<string, Question> $questions */
        $questions = [];

        foreach ($this->questions() as $key => [$type, $topic, $chapter, $body, $options, $explanation]) {
            $question = new Question;
            $question->school_id = $course->school_id;
            $question->author_id = $course->author_id;

            $questions[$key] = $service->save($question, [
                'course_id' => $course->id,
                'chapter_id' => $chapters[$chapter] ?? null,
                'type' => $type,
                'body' => $body,
                'explanation' => $explanation,
                'default_points' => 1,
            ], $options, [$topics[$topic]], '');
        }

        $pick = fn (string ...$keys): array => array_values(array_intersect_key($questions, array_flip($keys)));

        $pre = $this->quiz($course, 'Vstupný test – čo už vieš o bezpečnosti?', QuizPurpose::PreTest, null, array_values($questions), [
            'max_attempts' => 1, 'show_correct_answers' => ResultVisibility::Never,
        ]);
        $post = $this->quiz($course, 'Výstupný test – čo si sa naučil', QuizPurpose::PostTest, null, array_values($questions), [
            'max_attempts' => 1, 'shuffle_questions' => true, 'paired_quiz_id' => $pre->id,
        ]);
        $pre->forceFill(['paired_quiz_id' => $post->id])->save();

        $this->quiz($course, 'Kvíz: Bezpečné heslá', QuizPurpose::Practice, $chapters['Bezpečné heslá'] ?? null, $pick('pw1', 'pw2', 'pw3', 'pw4'), [
            'shuffle_options' => true,
        ]);
        $this->quiz($course, 'Test: Phishing a sociálne inžinierstvo', QuizPurpose::Graded, $chapters['Phishing'] ?? null, $pick('ph1', 'ph2', 'ph3', 'se1', 'se2'), [
            'max_attempts' => 2, 'time_limit_minutes' => 15, 'pass_percentage' => 70, 'due_at' => now()->addWeeks(2),
        ]);
    }

    /**
     * @param  list<Question>  $questions
     * @param  array<string, mixed>  $settings
     */
    private function quiz(Course $course, string $title, QuizPurpose $purpose, ?int $chapterId, array $questions, array $settings): Quiz
    {
        $quiz = new Quiz([
            'course_id' => $course->id,
            'chapter_id' => $chapterId,
            'title' => $title,
            'purpose' => $purpose,
            'pass_percentage' => 60,
            ...$settings,
        ]);
        $quiz->school_id = $course->school_id;
        $quiz->author_id = $course->author_id;
        $quiz->status = QuizStatus::Published;
        $quiz->published_at = now();
        $quiz->save();

        foreach ($questions as $position => $question) {
            $quiz->questions()->attach($question->id, ['position' => $position]);
        }

        return $quiz;
    }

    /**
     * @return array<string, array{QuestionType, string, string, string, list<array<string, mixed>>, string}>
     */
    private function questions(): array
    {
        $choice = fn (array $texts, int ...$correct): array => array_map(
            fn (string $text, int $i): array => ['body' => $text, 'is_correct' => in_array($i, $correct, true)],
            $texts, array_keys($texts),
        );

        return [
            'pw1' => [QuestionType::SingleChoice, 'hesla-a-autentifikacia', 'Bezpečné heslá', 'Ktoré heslo je najbezpečnejšie?',
                $choice(['Janko2010', 'qwerty123', 'ModryKocurSkaceCezPlot!7', 'Heslo1234'], 2),
                'Dlhá veta z viacerých slov je ťažko uhádnuteľná a dá sa ľahko zapamätať.'],
            'pw2' => [QuestionType::TrueFalse, 'hesla-a-autentifikacia', 'Bezpečné heslá', 'Je v poriadku používať rovnaké heslo pre viac účtov, ak je dostatočne dlhé.',
                $choice(['Pravda', 'Nepravda'], 1),
                'Ak unikne z jednej služby, útočník ho vyskúša aj na ostatných účtoch.'],
            'pw3' => [QuestionType::MultipleChoice, 'hesla-a-autentifikacia', 'Bezpečné heslá', 'Čo ti pomôže chrániť účty? (vyber všetky správne)',
                $choice(['Dvojfaktorové overenie', 'Správca hesiel', 'Heslo napísané na papieriku na monitore', 'Rôzne heslá pre rôzne služby'], 0, 1, 3),
                'Papierik na monitore uvidí každý, kto prejde okolo.'],
            'pw4' => [QuestionType::FillBlank, 'hesla-a-autentifikacia', 'Bezpečné heslá', 'Odporúča sa, aby heslo malo aspoň [[1]] znakov.',
                [['body' => '12', 'blank_index' => 1], ['body' => 'dvanásť', 'blank_index' => 1]],
                'Dĺžka hesla je dôležitejšia než jeho zložitosť.'],
            'ph1' => [QuestionType::SingleChoice, 'phishing', 'Phishing', 'Prišiel ti e-mail „Váš účet bude zablokovaný, prihláste sa do 1 hodiny“ s odkazom. Čo urobíš?',
                $choice(['Kliknem a prihlásim sa', 'Stránku otvorím sám cez známu adresu alebo aplikáciu', 'Pošlem heslo odpoveďou na e-mail', 'Prepošlem e-mail kamarátom'], 1),
                'Naliehavosť je typický znak phishingu – nikdy neklikaj na odkaz v takej správe.'],
            'ph2' => [QuestionType::MultipleChoice, 'phishing', 'Phishing', 'Ktoré znaky môžu prezrádzať phishing?',
                $choice(['Adresa odosielateľa sa len podobá na skutočnú', 'Žiadosť o heslo alebo kód z SMS', 'Naliehavý tón a hrozba', 'Správa od učiteľa cez školský systém o zajtrajšej písomke'], 0, 1, 2),
                'Dôveryhodná inštitúcia ťa nikdy nepožiada o heslo e-mailom.'],
            'ph3' => [QuestionType::ShortAnswer, 'phishing', 'Phishing', 'Ako sa nazýva podvodná správa, ktorá sa snaží vylákať heslo alebo platobné údaje?',
                [['body' => 'phishing'], ['body' => 'fišing']],
                'Phishing – z anglického „fishing“, rybárčenie.'],
            'se1' => [QuestionType::TrueFalse, 'socialne-inzinierstvo', 'Sociálne inžinierstvo', 'Ak ti niekto zavolá a predstaví sa ako pracovník banky, môžeš mu nadiktovať kód z SMS.',
                $choice(['Pravda', 'Nepravda'], 1),
                'Kód z SMS nikdy nikomu nediktuj – banka ho od teba nikdy nepýta.'],
            'se2' => [QuestionType::Matching, 'socialne-inzinierstvo', 'Sociálne inžinierstvo', 'Priraď trik útočníka k jeho popisu.',
                [['body' => 'Vydávanie sa za autoritu', 'match_body' => 'Volám z IT oddelenia školy'], ['body' => 'Návnada', 'match_body' => 'Nájdený USB kľúč s lákavým nápisom'], ['body' => 'Protislužba', 'match_body' => 'Pomôžem ti, keď mi povieš kód']],
                'Útočník zneužíva dôveru, zvedavosť a ochotu pomôcť.'],
            'sn1' => [QuestionType::SingleChoice, 'socialne-siete', 'Bezpečnosť sociálnych sietí', 'Čo je bezpečné zverejniť na verejnom profile?',
                $choice(['Adresu bydliska', 'Fotku z dovolenky až po návrate domov', 'Rozvrh hodín a názov školy', 'Aktuálnu polohu v reálnom čase'], 1),
                'Informácie o polohe a rozvrhu prezrádzajú, kde ťa nájsť.'],
            'pd1' => [QuestionType::MultipleChoice, 'osobne-udaje-a-gdpr', 'Ochrana osobných údajov', 'Ktoré z nasledujúcich sú osobné údaje?',
                $choice(['Meno a priezvisko', 'Fotografia tváre', 'IP adresa', 'Obľúbená farba bez ďalších údajov'], 0, 1, 2),
                'Osobný údaj je každá informácia, podľa ktorej ťa možno identifikovať.'],
        ];
    }
}
