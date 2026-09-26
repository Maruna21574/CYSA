# CYSA – vzdelávacia platforma kybernetickej bezpečnosti

Webová platforma pre základné a stredné školy: kurzy, študijné materiály, testy s automatickým
vyhodnotením, analytika pre učiteľov, certifikáty a výskumné porovnanie vedomostí pred a po
vzdelávaní. Súčasť diplomovej práce zameranej na kybernetickú bezpečnosť žiakov.

**Stack:** PHP 8.4, Laravel 13, Livewire 4, Alpine.js, Tailwind CSS 4, MySQL/MariaDB.
Navrhnuté pre bežný webhosting (bez Node servera a démonov) – viď [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md).

## Funkcie

| Oblasť | Obsah |
|---|---|
| Role | super admin, administrátor školy, učiteľ, študent; oprávnenia cez Policies |
| Organizácia | školy, triedy, CSV import študentov, pozvánky e-mailom |
| Kurzy | moduly a kapitoly (drag-and-drop), rich-text obsah, materiály (PDF, Office, obrázky, video, YouTube, odkazy), priradenie triedam/študentom |
| Testy | banka otázok, 6 typov otázok, pokusy, časový limit, termíny, náhodné poradie, snapshot otázok |
| Výsledky | automatické vyhodnotenie, ručná úprava bodov s auditom, história pokusov |
| Analytika | úspešnosť otázok a tém, najčastejšie chyby, študenti s problémami, CSV exporty |
| Výskum | vstupný/výstupný test, párový t-test, Cohenovo d, pseudonymizovaný export |
| Certifikáty | automatické vydanie, PDF s QR kódom, verejné overenie `/verify-certificate/{code}` |
| Notifikácie | in-app + voliteľne e-mail, pripomienky termínov, oznámenia učiteľa |
| AI | návrhy otázok z materiálov (PDF, DOCX, PPTX, ODT, TXT) a z textu kapitol, zhrnutie, kľúčové pojmy, vysvetlenia; vždy iba koncepty na schválenie učiteľom |
| Gamifikácia | XP, levely, séria aktivity, odznaky (voliteľné pre školu) |
| Bezpečnosť & GDPR | audit log, rate limiting, CSP, bezpečný upload, export a anonymizácia osobných údajov |

## Lokálny vývoj (Laragon / Windows alebo Linux/macOS)

```bash
composer install
npm install
cp .env.example .env          # nastavte DB_* (MySQL)
php artisan key:generate
php artisan migrate --seed    # demo škola, kurz, testy a výsledky
npm run build                 # alebo npm run dev
php artisan serve             # alebo Laragon: http://cysa.test
```

Fronta a scheduler lokálne: `php artisan queue:work` a `php artisan schedule:work`.

### Demo účty (heslo `password`)

| Rola | E-mail |
|---|---|
| Super admin | admin@cysa.test |
| Administrátor školy | skola@cysa.test |
| Učiteľ | ucitel@cysa.test |
| Študenti (trieda 4.A) | student1@cysa.test … student6@cysa.test |

## Testy

```bash
php artisan test
vendor/bin/pint --test
```

## Štruktúra

```
app/Enums            role, stavy, typy otázok, audit udalosti
app/Policies         autorizácia každého modelu
app/Services         Quiz (pokusy, vyhodnotenie), Progress, Certificates, Analytics,
                     Files (súkromné úložisko), Import (CSV), Notifications, Search, Audit, Users
app/Gamification     voliteľný modul napojený na doménové udalosti
app/Livewire         interaktívne časti (prehrávač testu, editory, tabuľky)
resources/views      Blade šablóny, komponenty v resources/views/components
tests/               Feature a Unit testy
```
