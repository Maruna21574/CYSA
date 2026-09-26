@AGENTS.md

# CYSA – projektové pravidlá

- Diplomová práca + produkčná vzdelávacia platforma kybernetickej bezpečnosti pre ZŠ/SŠ.
- UI iba po slovensky; texty cez `__('...')` so slovenským kľúčom, preklady Laravelu v `lang/sk`.
- Nasadenie na Websupport (zdieľaný hosting): žiadne démony, fronty cez `database` + cron `schedule:run`. `composer.json` má `config.platform.php = 8.4.0` – nemeniť bez overenia verzie PHP na hostingu.
- Role: enum `App\Enums\UserRole` v `users.role`; middleware `role:` len na úrovni sekcií, prístup k záznamom vždy cez Policies. `school_admin` má aj schopnosti učiteľa. Super admin prechádza cez `Gate::before`.
- Livewire komponenty sú class-based (`app/Livewire`, view v `resources/views/livewire`), layouty `layouts::app` / `layouts::guest`.
- Bezpečnostné udalosti zapisuj cez `App\Services\Audit\AuditLogger` s hodnotou z `App\Enums\AuditAction`; `audit_logs` je append-only.
- AI: rozhranie `App\Services\AI\Contracts\AiClient` (implementácia `AnthropicClient` cez oficiálne PHP SDK, štruktúrovaný JSON výstup), logika v `AIQuizGenerationService`, validácia v `AiQuestionParser`. AI otázky sú vždy `status=draft` a do testu idú až po schválení. V testoch používaj `Tests\Support\FakeAiClient`, nikdy skutočné API.
- Súbory (materiály, obálky kurzov) iba cez `App\Services\Files\MaterialStorage` na disku `config('cysa.materials.disk')`; upload validuje `App\Rules\AllowedMaterialFile` (prípona + finfo obsah). Nikdy nie verejný disk.
- HTML obsah kapitol sa sanitizuje v mutátore `Chapter::content` (`App\Support\ContentSanitizer`); pri novom rich-text poli použi rovnaký sanitizér.
- Prístup ku kurzu: `CoursePolicy` (autor / school admin školy; študent iba publikovaný + priradený cez `Course::availableTo`).
