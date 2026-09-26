<?php

namespace Database\Seeders;

use App\Enums\CourseStatus;
use App\Enums\Difficulty;
use App\Enums\MaterialType;
use App\Models\Category;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo course "Základy kybernetickej bezpečnosti" with five chapters, assigned to class 4.A.
 */
class DemoCourseSeeder extends Seeder
{
    public function run(): void
    {
        $teacher = User::where('email', 'ucitel@cysa.test')->firstOrFail();
        $classroom = Classroom::where('school_id', $teacher->school_id)->where('name', '4.A')->firstOrFail();

        $course = Course::where('school_id', $teacher->school_id)->where('slug', 'zaklady-kybernetickej-bezpecnosti')->first();

        if ($course) {
            return;
        }

        $course = new Course([
            'title' => 'Základy kybernetickej bezpečnosti',
            'description' => 'Praktický kurz o tom, ako sa chrániť na internete: heslá, phishing, sociálne inžinierstvo, sociálne siete a osobné údaje.',
            'difficulty' => Difficulty::Beginner,
            'category_id' => Category::whereNull('school_id')->where('slug', 'kyberneticka-bezpecnost')->value('id'),
            'sequential_chapters' => true,
        ]);
        $course->school_id = $teacher->school_id;
        $course->author_id = $teacher->id;
        $course->status = CourseStatus::Published;
        $course->published_at = now();
        $course->save();

        $modules = [
            'Ochrana účtov' => [
                ['Bezpečné heslá', $this->passwords(), 'https://www.nbu.gov.sk'],
            ],
            'Podvody a manipulácia' => [
                ['Phishing', $this->phishing(), null],
                ['Sociálne inžinierstvo', $this->socialEngineering(), null],
            ],
            'Súkromie' => [
                ['Bezpečnosť sociálnych sietí', $this->socialNetworks(), null],
                ['Ochrana osobných údajov', $this->personalData(), 'https://www.dataprotection.gov.sk'],
            ],
        ];

        $modulePosition = 0;

        foreach ($modules as $moduleTitle => $chapters) {
            $module = $course->modules()->create(['title' => $moduleTitle, 'position' => $modulePosition++]);

            foreach ($chapters as $position => [$title, $content, $link]) {
                $chapter = $module->chapters()->make([
                    'title' => $title,
                    'content' => $content,
                    'position' => $position,
                    'estimated_minutes' => 15,
                    'requires_previous' => true,
                    'is_published' => true,
                ]);
                $chapter->course_id = $course->id;
                $chapter->save();

                if ($link) {
                    $chapter->materials()->create([
                        'type' => MaterialType::Link,
                        'title' => str_contains($link, 'nbu') ? 'Národný bezpečnostný úrad – kybernetická bezpečnosť' : 'Úrad na ochranu osobných údajov SR',
                        'url' => $link,
                        'position' => 0,
                        'uploaded_by' => $teacher->id,
                    ]);
                }
            }
        }

        $course->assignments()->create(['classroom_id' => $classroom->id, 'assigned_by' => $teacher->id, 'due_at' => now()->addMonth()]);
    }

    private function passwords(): string
    {
        return <<<'HTML'
            <h1>Prečo na hesle záleží</h1>
            <p>Heslo je kľúč k tvojim účtom. Ak ho niekto uhádne alebo získa, môže čítať tvoje správy, meniť nastavenia alebo sa za teba vydávať.</p>
            <h2>Ako vyzerá silné heslo</h2>
            <ul><li>má aspoň <strong>12 znakov</strong> – dĺžka je dôležitejšia ako zložitosť,</li><li>neobsahuje meno, dátum narodenia ani slová, ktoré o tebe ľudia vedia,</li><li>dobre funguje <strong>veta z niekoľkých slov</strong>, napr. <code>ModryKocurJeNaStreche7!</code>,</li><li>pre každý účet je <strong>iné</strong>.</li></ul>
            <h2>Správca hesiel a dvojfaktorové overenie</h2>
            <p>Správca hesiel si heslá pamätá za teba. Zapni si aj <strong>dvojfaktorové overenie (2FA)</strong> – aj keď niekto heslo získa, bez druhého kroku sa neprihlási.</p>
            <blockquote>Heslo nikdy nikomu neprezrádzaj – ani kamarátovi, ani „technickej podpore“.</blockquote>
            HTML;
    }

    private function phishing(): string
    {
        return <<<'HTML'
            <h1>Čo je phishing</h1>
            <p>Phishing je podvodná správa (e-mail, SMS, správa na sociálnej sieti), ktorá sa tvári ako dôveryhodná a snaží sa ťa prinútiť kliknúť na odkaz, zadať heslo alebo stiahnuť súbor.</p>
            <h2>Varovné signály</h2>
            <ul><li>naliehavosť: „Váš účet bude do 24 hodín zablokovaný!“,</li><li>adresa odosielateľa alebo odkazu, ktorá sa len podobá na skutočnú (napr. <code>faceb00k-login.com</code>),</li><li>žiadosť o heslo, kód z SMS alebo platobné údaje,</li><li>nečakaná výhra alebo balík, ktorý si neobjednal,</li><li>gramatické chyby a neosobné oslovenie.</li></ul>
            <h2>Čo robiť</h2>
            <p>Na odkaz neklikaj. Stránku otvor sám cez známu adresu alebo aplikáciu. Podozrivú správu ukáž učiteľovi alebo rodičom a nahlás ju.</p>
            HTML;
    }

    private function socialEngineering(): string
    {
        return <<<'HTML'
            <h1>Sociálne inžinierstvo</h1>
            <p>Útočník nemusí prelomiť počítač – často stačí presvedčiť človeka. Využíva dôveru, strach, zvedavosť alebo ochotu pomôcť.</p>
            <h2>Typické triky</h2>
            <ul><li><strong>Vydávanie sa za autoritu</strong> – „Volám z banky / zo školy / z IT oddelenia.“</li><li><strong>Návnada</strong> – nájdený USB kľúč s nápisom „Známky 2026“.</li><li><strong>Protislužba</strong> – „Pomôžem ti s účtom, len mi povedz kód, ktorý ti prišiel.“</li></ul>
            <p>Zlaté pravidlo: <strong>over si, kto je na druhej strane</strong>, cez iný kanál, než ktorým ťa kontaktoval.</p>
            HTML;
    }

    private function socialNetworks(): string
    {
        return <<<'HTML'
            <h1>Bezpečnosť na sociálnych sieťach</h1>
            <p>Čo raz zverejníš, už nemusíš dostať späť. Fotky a príspevky môžu vidieť aj ľudia, ktorým nie sú určené.</p>
            <ul><li>nastav si <strong>súkromný profil</strong> a skontroluj, kto vidí tvoje príspevky,</li><li>nezverejňuj adresu, školu, rozvrh ani polohu v reálnom čase,</li><li>prijímaj žiadosti o priateľstvo len od ľudí, ktorých poznáš,</li><li>pri nepríjemnej správe alebo šikane urob snímku obrazovky, zablokuj a povedz to dospelému.</li></ul>
            HTML;
    }

    private function personalData(): string
    {
        return <<<'HTML'
            <h1>Osobné údaje</h1>
            <p>Osobný údaj je každá informácia, podľa ktorej ťa možno identifikovať: meno, fotka, adresa, rodné číslo, telefónne číslo, ale aj poloha či IP adresa.</p>
            <h2>Tvoje práva (GDPR)</h2>
            <ul><li>vedieť, kto o tebe aké údaje spracúva a prečo,</li><li>požiadať o ich opravu alebo vymazanie,</li><li>odvolať súhlas, ktorý si dal.</li></ul>
            <p>Pri registrácii vždy zváž, <strong>ktoré údaje sú naozaj potrebné</strong>. Menej zdieľaných údajov = menšie riziko.</p>
            HTML;
    }
}
