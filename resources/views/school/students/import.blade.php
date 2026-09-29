<x-layouts::app :title="__('Import študentov')">
    <x-page-header :title="__('Import študentov z CSV')" :breadcrumbs="[
        ['label' => __('Používatelia'), 'url' => route('users.index')],
        ['label' => __('Import študentov')],
    ]" />

    <div class="grid gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ route('school.students.import.store') }}" enctype="multipart/form-data" class="lg:col-span-2">
            @csrf

            <x-card class="flex flex-col gap-4">
                <x-form.input name="file" type="file" :label="__('CSV súbor')" accept=".csv,text/csv" :hint="__('Najviac 1 MB a 1000 študentov.')" required
                    class="file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-brand-700" />

                <x-form.select name="classroom_id" :label="__('Zaradiť do triedy')" :options="$classrooms" :placeholder="__('— nezaradiť —')" />

                <x-form.checkbox name="send_invitation" :checked="true" :label="__('Poslať študentom pozvánku e-mailom')"
                    :hint="__('Pozvánka obsahuje odkaz na nastavenie hesla, platný 7 dní. Bez nej si študent heslo nastaví cez „Zabudnuté heslo“.')" />
            </x-card>

            @if (session('import_errors'))
                <x-card :title="__('Chyby v súbore')" class="mt-4 border-red-200">
                    <ul class="flex flex-col gap-2 text-sm">
                        @foreach (session('import_errors') as $line => $messages)
                            <li>
                                <span class="font-semibold text-slate-900">{{ __('Riadok :line:', ['line' => $line]) }}</span>
                                <span class="text-red-700">{{ implode(' ', $messages) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </x-card>
            @endif

            <div class="mt-4 flex gap-2">
                <x-button>{{ __('Importovať') }}</x-button>
                <x-link-button variant="secondary" :href="route('users.index')">{{ __('Zrušiť') }}</x-link-button>
            </div>
        </form>

        <x-card :title="__('Formát súboru')" class="h-fit text-sm text-slate-700">
            <p>{{ __('Prvý riadok musí obsahovať názvy stĺpcov. Poradie stĺpcov nie je dôležité.') }}</p>
            <pre class="mt-3 overflow-x-auto rounded-lg bg-slate-900 p-3 text-xs text-slate-100">meno;priezvisko;email
Jana;Kováčová;jana.k@example.com
Marek;Horváth;marek.h@example.com</pre>
            <ul class="mt-3 list-disc space-y-1 pl-5">
                <li>{{ __('V Exceli použite „Uložiť ako → CSV UTF-8“ alebo „CSV (oddelený bodkočiarkou)“.') }}</li>
                <li>{{ __('Ak je v súbore čo i len jedna chyba, neimportuje sa nič – stačí súbor opraviť a nahrať znova.') }}</li>
            </ul>
        </x-card>
    </div>
</x-layouts::app>
