@AGENTS.md

# CYSA – projektové pravidlá

- Diplomová práca + produkčná vzdelávacia platforma kybernetickej bezpečnosti pre ZŠ/SŠ.
- UI iba po slovensky; texty cez `__('...')` so slovenským kľúčom, preklady Laravelu v `lang/sk`.
- Nasadenie na Websupport (zdieľaný hosting): žiadne démony, fronty cez `database` + cron `schedule:run`. `composer.json` má `config.platform.php = 8.4.0` – nemeniť bez overenia verzie PHP na hostingu.
- Role: enum `App\Enums\UserRole` v `users.role`; middleware `role:` len na úrovni sekcií, prístup k záznamom vždy cez Policies. `school_admin` má aj schopnosti učiteľa. Super admin prechádza cez `Gate::before`.
- Livewire komponenty sú class-based (`app/Livewire`, view v `resources/views/livewire`), layouty `layouts::app` / `layouts::guest`.
- Bezpečnostné udalosti zapisuj cez `App\Services\Audit\AuditLogger` s hodnotou z `App\Enums\AuditAction`; `audit_logs` je append-only.
- AI generovanie otázok je posledná fáza projektu.
