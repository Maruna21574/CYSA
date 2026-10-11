<?php

namespace App\Support;

use App\Models\School;

/**
 * Organization-dependent wording. The application is written with school terms
 * (classroom, student, teacher); inside a company the same screens say department,
 * employee and trainer.
 */
class Terms
{
    /**
     * School phrase => company phrase.
     *
     * @var array<string, string>
     */
    private const COMPANY = [
        'Škola' => 'Firma',
        'Prehľad školy' => 'Prehľad firmy',
        'Prehľad školy za posledných 30 dní.' => 'Prehľad firmy za posledných 30 dní.',
        'Nastavenia školy' => 'Nastavenia firmy',
        'Úspešnosť školy podľa tém' => 'Úspešnosť firmy podľa tém',
        'Používatelia' => 'Zamestnanci',
        'Administrátor školy' => 'Administrátor firmy',
        'Učiteľ' => 'Školiteľ',
        'Učitelia' => 'Školitelia',
        'Učiteľa' => 'Školiteľa',
        'Študent' => 'Zamestnanec',
        'Študenti' => 'Zamestnanci',
        'Študenta' => 'Zamestnanca',
        'Učitelia a študenti školy :school.' => 'Školitelia a zamestnanci firmy :school.',
        '{1} :count študent|[2,4] :count študenti|[0,*] :count študentov' => '{1} :count zamestnanec|[2,4] :count zamestnanci|[0,*] :count zamestnancov',
        '{1} + :count administrátor školy|[2,*] + :count administrátori školy' => '{1} + :count administrátor firmy|[2,*] + :count administrátori firmy',
        'Kurzy, ktoré ti priradil učiteľ.' => 'Kurzy, ktoré ti priradil školiteľ.',
        'Keď ti učiteľ priradí kurz, nájdeš ho tu.' => 'Keď ti školiteľ priradí kurz, nájdeš ho tu.',
        'Náhľad kurzu tak, ako ho vidia študenti. Skryté kapitoly a nepublikované testy sú zobrazené iba vám.' => 'Náhľad kurzu tak, ako ho vidia zamestnanci. Skryté kapitoly a nepublikované testy sú zobrazené iba vám.',
        'Oznámenia učiteľa' => 'Oznámenia školiteľa',
        'Náhľad PDF pre učiteľa.' => 'Náhľad PDF pre školiteľa.',
        'učiteľ / autor kurzu' => 'školiteľ / autor kurzu',
        'Nový kurz, nový test, blížiaci sa termín, certifikát a oznámenia učiteľa. V aplikácii ich uvidíte vždy.' => 'Nový kurz, nový test, blížiaci sa termín, certifikát a oznámenia školiteľa. V aplikácii ich uvidíte vždy.',
        'Stiahnite si všetky údaje, ktoré o vás platforma uchováva (účet, triedy, progres, výsledky testov, certifikáty, notifikácie). Ak chcete účet vymazať, obráťte sa na administrátora školy.' => 'Stiahnite si všetky údaje, ktoré o vás platforma uchováva (účet, oddelenia, progres, výsledky testov, certifikáty, notifikácie). Ak chcete účet vymazať, obráťte sa na administrátora firmy.',
        'Študenti zatiaľ nezačali žiadny kurz' => 'Zamestnanci zatiaľ nezačali žiadny kurz',
        'Jednotliví študenti (pseudonymizovane)' => 'Jednotliví zamestnanci (pseudonymizovane)',
        'Krátko opíšte, čo sa študenti v kurze naučia.' => 'Krátko opíšte, čo sa zamestnanci v kurze naučia.',
        'Skupiny (triedy)' => 'Skupiny (oddelenia)',
        'Súbor neobsahuje žiadnych študentov.' => 'Súbor neobsahuje žiadnych zamestnancov.',
        'Zatiaľ vás školský administrátor nepriradil do žiadnej triedy.' => 'Zatiaľ vás administrátor firmy nepriradil do žiadneho oddelenia.',
        'Kurz zatiaľ nie je publikovaný. Študenti ho uvidia až po publikovaní.' => 'Kurz zatiaľ nie je publikovaný. Zamestnanci ho uvidia až po publikovaní.',
        ':name bol(a) odobratý(á) z triedy.' => ':name bol(a) odobratý(á) z oddelenia.',
        ':name bol(a) pridaný(á) do triedy.' => ':name bol(a) pridaný(á) do oddelenia.',
        'Moje triedy' => 'Moje oddelenia',
        'Súbor môže obsahovať najviac :max študentov.' => 'Súbor môže obsahovať najviac :max zamestnancov.',
        'Kurz bol publikovaný. Študenti, ktorým je priradený, ho už vidia.' => 'Kurz bol publikovaný. Zamestnanci, ktorým je priradený, ho už vidia.',
        'Hľadať študenta (min. 2 znaky)' => 'Hľadať zamestnanca (min. 2 znaky)',
        'Priradiť študentovi' => 'Priradiť zamestnancovi',
        'Priradiť triede' => 'Priradiť oddeleniu',
        'Kedy študent uvidí výsledok' => 'Kedy zamestnanec uvidí výsledok',
        'Porovnanie prvého pokusu vo vstupnom a výstupnom teste. Študenti sú uvedení iba pod pseudonymným kódom.' => 'Porovnanie prvého pokusu vo vstupnom a výstupnom teste. Zamestnanci sú uvedení iba pod pseudonymným kódom.',
        'trieda' => 'oddelenie',
        '— vyberte triedu —' => '— vyberte oddelenie —',
        'Oznámenie bolo uložené. Študenti ho uvidia po publikovaní kurzu.' => 'Oznámenie bolo uložené. Zamestnanci ho uvidia po publikovaní kurzu.',
        'Oznámenie bolo odoslané študentom kurzu.' => 'Oznámenie bolo odoslané zamestnancom kurzu.',
        'Návrhy sa uložia ako koncepty. Študenti ich neuvidia.' => 'Návrhy sa uložia ako koncepty. Zamestnanci ich neuvidia.',
        'Spárovaní študenti' => 'Spárovaní zamestnanci',
        'Text materiálu sa odošle AI službe. Neposielajú sa žiadne údaje o študentoch.' => 'Text materiálu sa odošle AI službe. Neposielajú sa žiadne údaje o zamestnancoch.',
        'Výsledky študentov vo vašich testoch.' => 'Výsledky zamestnancov vo vašich testoch.',
        'Prehľad vašich kurzov, tried a výsledkov.' => 'Prehľad vašich kurzov, oddelení a výsledkov.',
        'Študenti, ktorí potrebujú pomoc' => 'Zamestnanci, ktorí potrebujú pomoc',
        'Prehľad učiteľa' => 'Prehľad školiteľa',
        'Kurz zatiaľ nie je priradený žiadnej triede ani študentovi.' => 'Kurz zatiaľ nie je priradený žiadnemu oddeleniu ani zamestnancovi.',
        'Pokusy študentov' => 'Pokusy zamestnancov',
        'Študenti v mojich triedach' => 'Zamestnanci v mojich oddeleniach',
        'Skontrolujte správnosť, formulácie aj správne odpovede. Študenti uvidia iba otázky, ktoré schválite a pridáte do testu.' => 'Skontrolujte správnosť, formulácie aj správne odpovede. Zamestnanci uvidia iba otázky, ktoré schválite a pridáte do testu.',
        'Kapitola je viditeľná pre študentov' => 'Kapitola je viditeľná pre zamestnancov',
        'Študent otvorí ďalšiu kapitolu až po dokončení predchádzajúcej.' => 'Zamestnanec otvorí ďalšiu kapitolu až po dokončení predchádzajúcej.',
        'Kurz zmizne študentom aj učiteľom. Ak ho chcete len skryť a zachovať výsledky, radšej ho archivujte.' => 'Kurz zmizne zamestnancom aj školiteľom. Ak ho chcete len skryť a zachovať výsledky, radšej ho archivujte.',
        'Témy slúžia na vyhodnotenie, v ktorých oblastiach majú študenti problémy.' => 'Témy slúžia na vyhodnotenie, v ktorých oblastiach majú zamestnanci problémy.',
        'Certifikát sa študentovi vydá automaticky hneď po splnení podmienok.' => 'Certifikát sa zamestnancovi vydá automaticky hneď po splnení podmienok.',
        'Pokyny pre študentov' => 'Pokyny pre zamestnancov',
        'Oznámenie dostanú všetci študenti, ktorým je kurz priradený, ako notifikáciu.' => 'Oznámenie dostanú všetci zamestnanci, ktorým je kurz priradený, ako notifikáciu.',
        'Študent dostane pomernú časť bodov za čiastočne správnu odpoveď.' => 'Zamestnanec dostane pomernú časť bodov za čiastočne správnu odpoveď.',
        '{0} bez študentov|{1} :count študent|[2,4] :count študenti|[5,*] :count študentov' => '{0} bez zamestnancov|{1} :count zamestnanec|[2,4] :count zamestnanci|[5,*] :count zamestnancov',
        'Trieda' => 'Oddelenie',
        'Triedy' => 'Oddelenia',
        'Trieda :name' => 'Oddelenie :name',
        'Nová trieda' => 'Nové oddelenie',
        'Názov triedy' => 'Názov oddelenia',
        'Napríklad 4.A alebo I.B' => 'Napríklad IT, Obchod alebo Účtovníctvo',
        'Vytvoriť triedu' => 'Vytvoriť oddelenie',
        'Upraviť triedu' => 'Upraviť oddelenie',
        'Upraviť triedu :name' => 'Upraviť oddelenie :name',
        'Odstránenie triedy' => 'Odstránenie oddelenia',
        'Odstrániť triedu' => 'Odstrániť oddelenie',
        'Naozaj odstrániť túto triedu?' => 'Naozaj odstrániť toto oddelenie?',
        'Účty študentov a učiteľov zostanú zachované, zruší sa iba trieda.' => 'Účty zamestnancov a školiteľov zostanú zachované, zruší sa iba oddelenie.',
        'Triedy školy a ich učitelia a študenti.' => 'Oddelenia firmy a ich školitelia a zamestnanci.',
        'Hľadať triedu' => 'Hľadať oddelenie',
        'Žiadne triedy' => 'Žiadne oddelenia',
        'Zatiaľ žiadne triedy.' => 'Zatiaľ žiadne oddelenia.',
        'Vytvorte prvú triedu, napríklad 4.A.' => 'Vytvorte prvé oddelenie, napríklad IT.',
        'Pridať do triedy' => 'Pridať do oddelenia',
        'Odobrať :name z triedy?' => 'Odobrať :name z oddelenia?',
        'Trieda zatiaľ nemá priradeného učiteľa.' => 'Oddelenie zatiaľ nemá priradeného školiteľa.',
        'Trieda zatiaľ nemá študentov.' => 'Oddelenie zatiaľ nemá zamestnancov.',
        'Trieda bola vytvorená. Teraz do nej pridajte učiteľov a študentov.' => 'Oddelenie bolo vytvorené. Teraz doň pridajte školiteľov a zamestnancov.',
        'Trieda bola uložená.' => 'Oddelenie bolo uložené.',
        'Trieda bola odstránená.' => 'Oddelenie bolo odstránené.',
        'Trieda s týmto názvom v danom školskom roku už existuje.' => 'Oddelenie s týmto názvom už existuje.',
        'názov triedy' => 'názov oddelenia',
        'Import študentov' => 'Import zamestnancov',
        'Import študentov z CSV' => 'Import zamestnancov z CSV',
        'Import študentov (CSV)' => 'Import zamestnancov (CSV)',
        'Najviac 1 MB a 1000 študentov.' => 'Najviac 1 MB a 1000 zamestnancov.',
        'Zaradiť do triedy' => 'Zaradiť do oddelenia',
        'Poslať študentom pozvánku e-mailom' => 'Poslať zamestnancom pozvánku e-mailom',
        'Pozvánka obsahuje odkaz na nastavenie hesla, platný 7 dní. Bez nej si študent heslo nastaví cez „Zabudnuté heslo“.' => 'Pozvánka obsahuje odkaz na nastavenie hesla, platný 7 dní. Bez nej si zamestnanec heslo nastaví cez „Zabudnuté heslo“.',
        'Zapnúť body (XP), levely, sériu aktivity a odznaky pre študentov' => 'Zapnúť body (XP), levely, sériu aktivity a odznaky pre zamestnancov',
        'Motivačné prvky vidí iba samotný študent – nezobrazujú sa rebríčky ani porovnania s ostatnými študentmi.' => 'Motivačné prvky vidí iba samotný zamestnanec – nezobrazujú sa rebríčky ani porovnania s ostatnými zamestnancami.',
    ];

    /**
     * @param  array<string, mixed>  $replace
     */
    public static function of(string $schoolPhrase, array $replace = [], ?School $school = null): string
    {
        return __(self::phrase($schoolPhrase, $school), $replace);
    }

    /**
     * @param  array<string, mixed>  $replace
     */
    public static function choice(string $schoolPhrase, int $count, array $replace = [], ?School $school = null): string
    {
        return trans_choice(self::phrase($schoolPhrase, $school), $count, $replace);
    }

    public static function hasCompanyPhrase(string $schoolPhrase): bool
    {
        return isset(self::COMPANY[$schoolPhrase]);
    }

    public static function companyPhrase(string $schoolPhrase): string
    {
        return self::COMPANY[$schoolPhrase] ?? $schoolPhrase;
    }

    public static function isCompany(?School $school = null): bool
    {
        $school ??= auth()->user()?->school;

        return $school?->type?->isCompany() ?? false;
    }

    private static function phrase(string $schoolPhrase, ?School $school): string
    {
        return self::isCompany($school) ? (self::COMPANY[$schoolPhrase] ?? $schoolPhrase) : $schoolPhrase;
    }
}
