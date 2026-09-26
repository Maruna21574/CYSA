# Nasadenie CYSA na Websupport

Návod predpokladá webhosting Websupport s PHP 8.4+, MySQL/MariaDB, SSH prístupom a cronom.
Aplikácia nepotrebuje žiadny trvalo bežiaci proces (Node, queue worker, Redis) – všetko obsluhuje
jeden cron.

## 1. Príprava na hostingu

1. **PHP** – v administrácii nastavte pre doménu PHP 8.4 alebo novšie. Potrebné rozšírenia:
   `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `gd`, `zip`, `iconv`, `dom`, `intl` (odporúčané).
2. **Limity uploadu** – `upload_max_filesize` a `post_max_size` aspoň 64M (materiály majú limit
   50 MB, nastaviteľný cez `MATERIALS_MAX_UPLOAD_KB`).
3. **Databáza** – vytvorte MySQL/MariaDB databázu a používateľa (znaková sada `utf8mb4`).
4. **E-mail** – vytvorte schránku, napr. `noreply@vasadomena.sk` (SMTP pre pozvánky a notifikácie).

## 2. Stiahnutie aplikácie

```bash
ssh uzivatel@server
cd ~/                                   # mimo verejného adresára
git clone https://github.com/Maruna21574/CYSA.git cysa
cd cysa
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

## 3. Koreňový adresár webu

**Odporúčané:** v administrácii Websupportu nastavte koreňový adresár (document root) domény na
`cysa/public`. Verejne dostupný je potom iba priečinok `public`.

Ak to nie je možné a doména smeruje na koreň projektu, súbor `.htaccess` v koreni projektu presmeruje
všetky požiadavky do `public/` a zablokuje skryté súbory (`.env`). Aj tak je to len záložné riešenie.

## 4. Konfigurácia `.env`

```dotenv
APP_NAME=CYSA
APP_ENV=production
APP_DEBUG=false
APP_URL=https://www.vasadomena.sk
APP_LOCALE=sk
APP_TIMEZONE=Europe/Bratislava
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=…            # z administrácie Websupportu
DB_DATABASE=…
DB_USERNAME=…
DB_PASSWORD=…

SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=database
CACHE_STORE=database

MAIL_MAILER=smtp
MAIL_HOST=smtp.m1.websupport.sk
MAIL_PORT=465
MAIL_SCHEME=smtps
MAIL_USERNAME=noreply@vasadomena.sk
MAIL_PASSWORD=…
MAIL_FROM_ADDRESS=noreply@vasadomena.sk
MAIL_FROM_NAME=CYSA
```

`APP_ENV=production` automaticky zapne HTTPS odkazy, HSTS a Content Security Policy.
Hodnoty SMTP overte v administrácii e-mailov Websupportu.

## 5. Frontend (CSS/JS)

Na hostingu nie je potrebný Node.js. Assety sa zostavia lokálne a nahrajú:

```bash
# lokálne
npm ci
npm run build
scp -r public/build uzivatel@server:~/cysa/public/
```

(Alternatívne cez SFTP – nahrajte celý priečinok `public/build`.)
Po každej zmene v `resources/css` alebo `resources/js` build zopakujte.

## 6. Databáza a prvý administrátor

```bash
php artisan migrate --force
php artisan app:create-super-admin
```

Migrácie vytvoria aj predvolené kategórie, témy kybernetickej bezpečnosti a odznaky.
**Demo dáta (`db:seed`) sa na produkcii spustiť nedajú** – seeder to zablokuje.

Potom sa prihláste ako super administrátor, vytvorte školu a jej administrátora. Ten založí
učiteľov, triedy a importuje študentov (CSV).

## 7. Cron (povinné)

V administrácii Websupportu pridajte cron úlohu spúšťanú **každú minútu**:

```
* * * * * cd /home/…/cysa && php artisan schedule:run >> /dev/null 2>&1
```

Scheduler zabezpečí:

| Úloha | Frekvencia |
|---|---|
| spracovanie fronty (e-maily, notifikácie) – `queue:work --stop-when-empty` | každú minútu |
| automatické odovzdanie testov po uplynutí času – `quiz:expire-attempts` | každú minútu |
| pripomienky blížiacich sa termínov – `notifications:deadlines` | každú hodinu |

## 8. Optimalizácia

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

## 9. Ďalšie nasadenia

```bash
cd ~/cysa
bash deploy.sh          # git pull, composer, migrácie, cache
# + nahrať nový public/build, ak sa menil frontend
```

## 10. Kontrolný zoznam bezpečnosti

- [ ] `APP_DEBUG=false`, `APP_ENV=production`
- [ ] HTTPS certifikát aktívny, `APP_URL` začína `https://`
- [ ] document root = `public/`
- [ ] `https://vasadomena.sk/.env` vracia 403/404
- [ ] cron beží (v audit logu / na dashboarde pribúdajú odoslané notifikácie, testy sa odovzdávajú)
- [ ] zálohovanie databázy a priečinka `storage/app/private` (študijné materiály)
- [ ] `storage/` a `bootstrap/cache/` sú zapisovateľné pre PHP

## 11. Zálohy a obnova

- **Databáza** – denná záloha cez administráciu Websupportu alebo `mysqldump`.
- **Súbory** – `storage/app/private/materials` (nahraté materiály, obrázky otázok a kurzov).
- `.env` uložte bezpečne mimo repozitára – obsahuje `APP_KEY`, bez ktorého nejde dešifrovať session.

## 12. Presun materiálov na S3 (voliteľné)

```bash
composer require league/flysystem-aws-s3-v3 "^3.0"
```

V `.env` nastavte `MATERIALS_DISK=s3` a údaje `AWS_*`. Súbory sa potom servírujú cez dočasné
podpísané odkazy, autorizácia zostáva v aplikácii.
