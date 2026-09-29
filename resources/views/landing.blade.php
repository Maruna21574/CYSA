@php
    $topics = [
        ['lock', 'Heslá a autentifikácia', 'Ako vytvoriť silné heslo, prečo používať správcu hesiel a dvojfaktorové overenie.'],
        ['alert', 'Phishing', 'Rozpoznanie podvodných e-mailov, SMS a falošných stránok, ktoré lákajú heslá a peniaze.'],
        ['users', 'Sociálne inžinierstvo', 'Triky útočníkov, ktorí zneužívajú dôveru, strach alebo ochotu pomôcť.'],
        ['squares', 'Sociálne siete', 'Súkromie profilu, bezpečné zdieľanie a čo nezverejňovať o sebe ani o druhých.'],
        ['document', 'Osobné údaje a GDPR', 'Čo sú osobné údaje, aké práva máme a ako s údajmi zaobchádzať.'],
        ['shield', 'Malvér a podvodné aplikácie', 'Vírusy, ransomvér a falošné aplikácie – ako sa im vyhnúť.'],
        ['adjustments', 'Bezpečnosť zariadení', 'Aktualizácie, zálohy, verejné Wi-Fi a zabezpečenie mobilu či počítača.'],
        ['bell', 'Kyberšikana', 'Ako ju rozpoznať, ako sa brániť a kde hľadať pomoc.'],
    ];

    $services = [
        ['building', 'Pre školy', 'Správa školy', ['Triedy a študenti na jednom mieste', 'Import študentov z CSV', 'Prehľad výsledkov celej školy', 'Bezpečné a v súlade s GDPR']],
        ['book', 'Pre učiteľov', 'Kurzy a testy', ['Kurzy, kapitoly a materiály', 'Testy so 6 typmi otázok', 'AI návrhy otázok z materiálov', 'Analytika a export výsledkov']],
        ['trophy', 'Pre študentov', 'Učenie hrou', ['Kurzy krok za krokom', 'Okamžité vyhodnotenie testov', 'Body, levely a odznaky', 'Certifikát s overením cez QR']],
        ['users', 'Pre firmy', 'Školenie zamestnancov', ['Povinné základy: phishing, heslá, GDPR', 'Hromadný import zamestnancov', 'Prehľad, kto má kurz splnený', 'Certifikát ako doklad o absolvovaní']],
    ];

    $features = [
        'Vstupný a výstupný test na meranie pokroku', 'Automatické vyhodnotenie všetkých typov otázok', 'Certifikáty v PDF s QR overením',
        'Náhodné poradie otázok a časový limit', 'Notifikácie o nových testoch a termínoch', 'Analytika úspešnosti podľa tém',
        'Pozvánky pre študentov aj zamestnancov e-mailom', 'Oznámenia pre celú triedu alebo tím', 'Audit a ochrana osobných údajov',
    ];

    $steps = [
        ['Organizácia sa zaregistruje', 'Administrátor školy alebo firmy dostane prístup a pridá učiteľov, školiteľov a skupiny.'],
        ['Pripraví sa kurz', 'Použijete hotový kurz alebo si vytvoríte vlastný – s materiálmi a testami.'],
        ['Účastníci sa učia', 'Študenti aj zamestnanci prechádzajú kapitoly, riešia testy a hneď vidia výsledok aj vysvetlenie.'],
        ['Vidíte pokrok', 'Vstupný a výstupný test ukáže, čo sa ľudia naozaj naučili, a prehľad, kto má povinný kurz splnený.'],
    ];
@endphp
<!DOCTYPE html>
<html lang="sk" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ __('CYSA – online vzdelávacia platforma kybernetickej bezpečnosti pre školy aj firmy. Kurzy, testy a certifikáty pre študentov aj zamestnancov – phishing, heslá, GDPR a ďalšie základy.') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} · {{ __('Kybernetická bezpečnosť pre školy a firmy') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-white font-sans text-slate-900 antialiased">
    <a href="#obsah" class="sr-only z-50 rounded bg-white px-4 py-2 focus:not-sr-only focus:fixed focus:top-2 focus:left-2">{{ __('Preskočiť na obsah') }}</a>

    {{-- Decorative background circles --}}
    <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[46rem] overflow-hidden bg-gradient-to-b from-slate-100 to-white" aria-hidden="true">
        <div class="absolute -top-40 left-1/2 size-[64rem] -translate-x-1/2 rounded-full border-[7rem] border-white/80"></div>
        <div class="absolute top-24 -right-40 size-[34rem] rounded-full bg-brand-100/60 blur-3xl"></div>
    </div>

    {{-- Top navigation --}}
    <header class="sticky top-3 z-40 px-4" x-data="{ open: false }">
        <nav class="mx-auto flex max-w-6xl items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white/95 px-4 py-3 shadow-sm backdrop-blur sm:px-6" aria-label="{{ __('Hlavná navigácia') }}">
            <a href="{{ route('home') }}" class="flex items-center gap-2 text-brand-700">
                <x-icon name="shield" class="size-8" />
                <span class="text-2xl font-bold tracking-tight">{{ config('app.name') }}</span>
            </a>

            <ul class="hidden items-center gap-6 text-sm font-semibold text-slate-700 lg:flex">
                <li><a href="#o-nas" class="hover:text-brand-700">{{ __('O nás') }}</a></li>
                <li><a href="#kurzy" class="hover:text-brand-700">{{ __('Kurzy') }}</a></li>
                <li><a href="#sluzby" class="hover:text-brand-700">{{ __('Služby') }}</a></li>
                <li><a href="#ako-to-funguje" class="hover:text-brand-700">{{ __('Ako to funguje') }}</a></li>
                <li><a href="#kontakt" class="hover:text-brand-700">{{ __('Kontakt') }}</a></li>
                <li><a href="{{ route('certificates.verify') }}" class="hover:text-brand-700">{{ __('Overiť certifikát') }}</a></li>
            </ul>

            <div class="flex items-center gap-2">
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-accent">
                        <x-icon name="home" class="size-4" />{{ __('Do aplikácie') }}
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-accent">
                        <x-icon name="lock" class="size-4" />{{ __('Prihlásenie') }}
                    </a>
                @endauth
                <button type="button" class="rounded-lg p-2 text-slate-700 hover:bg-slate-100 lg:hidden" @click="open = ! open" :aria-expanded="open" aria-controls="mobile-menu">
                    <span class="sr-only">{{ __('Menu') }}</span>
                    <x-icon name="menu" class="size-6" />
                </button>
            </div>
        </nav>

        <div id="mobile-menu" x-show="open" x-cloak x-transition class="mx-auto mt-2 max-w-6xl rounded-2xl border border-slate-200 bg-white p-4 shadow-lg lg:hidden" @click="open = false">
            <ul class="flex flex-col gap-1 text-base font-semibold text-slate-700">
                @foreach (['o-nas' => 'O nás', 'kurzy' => 'Kurzy', 'sluzby' => 'Služby', 'ako-to-funguje' => 'Ako to funguje', 'kontakt' => 'Kontakt'] as $anchor => $label)
                    <li><a href="#{{ $anchor }}" class="block rounded-lg px-3 py-2 hover:bg-slate-50">{{ __($label) }}</a></li>
                @endforeach
                <li><a href="{{ route('certificates.verify') }}" class="block rounded-lg px-3 py-2 hover:bg-slate-50">{{ __('Overiť certifikát') }}</a></li>
            </ul>
        </div>
    </header>

    <main id="obsah">
        {{-- Hero --}}
        <section class="mx-auto grid max-w-6xl items-center gap-12 px-4 pt-16 pb-10 sm:px-6 lg:grid-cols-2 lg:pt-24">
            <div>
                <p class="mb-4 inline-flex items-center gap-2 rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700">
                    <x-icon name="sparkles" class="size-4" />{{ __('Pre školy aj firmy') }}
                </p>
                <h1 class="text-4xl leading-tight font-extrabold tracking-tight text-slate-900 sm:text-5xl lg:text-6xl">
                    {{ __('Kybernetická') }}<br>
                    <span class="underline decoration-brand-600 decoration-4 underline-offset-8">{{ __('bezpečnosť') }}</span> {{ __('pre') }}
                    <span class="underline decoration-brand-600 decoration-4 underline-offset-8">{{ __('školy') }}</span> {{ __('a') }}
                    <span class="underline decoration-brand-600 decoration-4 underline-offset-8">{{ __('firmy') }}</span>
                </h1>
                <p class="mt-8 max-w-lg text-lg text-slate-700">
                    <strong class="font-semibold text-slate-900">{{ __('Pre študentov základných a stredných škôl aj zamestnancov firiem.') }}</strong>
                    {{ __('Online kurzy kybernetickej bezpečnosti, testy s okamžitým vyhodnotením a certifikáty o absolvovaní.') }}
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="#kontakt" class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-6 py-3 font-semibold text-white shadow-sm transition hover:bg-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600">
                        {{ __('Chcem ukážku') }}
                    </a>
                    <a href="#kurzy" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-6 py-3 font-semibold text-slate-800 hover:bg-slate-50">
                        {{ __('Pozrieť témy kurzov') }}<x-icon name="chevron-right" class="size-4" />
                    </a>
                </div>
            </div>

            {{-- Illustration built from app UI elements --}}
            <div class="relative mx-auto mb-12 w-full max-w-md lg:max-w-none" aria-hidden="true">
                <div class="absolute inset-0 -z-10 rotate-6 rounded-[2.5rem] bg-gradient-to-br from-brand-600 to-brand-400 opacity-90"></div>
                <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-2xl">
                    <div class="mb-4 flex items-center justify-between">
                        <span class="text-sm font-semibold text-slate-500">{{ __('Otázka 3 z 10') }}</span>
                        <span class="flex items-center gap-1 rounded-lg bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700"><x-icon name="clock" class="size-4" />08:42</span>
                    </div>
                    <p class="text-lg font-semibold text-slate-900">{{ __('Ktoré heslo je najbezpečnejšie?') }}</p>
                    <ul class="mt-4 flex flex-col gap-2 text-sm">
                        <li class="rounded-lg border border-slate-200 px-4 py-2.5">Janko2010</li>
                        <li class="rounded-lg border border-slate-200 px-4 py-2.5">qwerty123</li>
                        <li class="flex items-center justify-between rounded-lg border border-accent bg-accent/10 px-4 py-2.5 font-medium text-brand-700">ModryKocurSkaceCezPlot!7 <x-icon name="check-circle" class="size-5 text-accent" /></li>
                        <li class="rounded-lg border border-slate-200 px-4 py-2.5">Heslo1234</li>
                    </ul>
                </div>

                <div class="absolute -bottom-14 -left-4 flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-xl sm:-left-10">
                    <span class="rounded-full bg-amber-100 p-2 text-amber-600"><x-icon name="badge" class="size-7" /></span>
                    <span>
                        <span class="block text-xs text-slate-500">{{ __('Nový odznak') }}</span>
                        <span class="block text-sm font-bold text-slate-900">{{ __('Expert na phishing') }}</span>
                    </span>
                </div>
            </div>
        </section>

        {{-- Services --}}
        <section id="sluzby" class="mx-auto max-w-6xl scroll-mt-28 px-4 pt-16 sm:px-6" aria-labelledby="sluzby-nadpis">
            <h2 id="sluzby-nadpis" class="sr-only">{{ __('Služby') }}</h2>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($services as [$icon, $audience, $title, $items])
                    <article class="flex flex-col rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm transition hover:shadow-lg">
                        <span class="mx-auto mb-4 rounded-2xl bg-brand-50 p-4 text-brand-600"><x-icon :name="$icon" class="size-10" /></span>
                        <p class="text-sm font-semibold text-slate-600">{{ __($audience) }}</p>
                        <h3 class="mt-1 text-2xl font-extrabold text-accent">{{ __($title) }}</h3>
                        <ul class="mt-6 flex flex-col gap-2 text-left text-sm text-slate-700">
                            @foreach ($items as $item)
                                <li class="flex items-start gap-2"><x-icon name="check" class="mt-0.5 size-4 shrink-0 text-accent" />{{ __($item) }}</li>
                            @endforeach
                        </ul>
                        <a href="#kontakt" class="mt-8 inline-flex justify-center rounded-lg bg-brand-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-accent">{{ __('Mám záujem') }}</a>
                    </article>
                @endforeach
            </div>
        </section>

        {{-- About --}}
        <section id="o-nas" class="mx-auto grid max-w-6xl scroll-mt-28 items-center gap-12 px-4 py-24 sm:px-6 lg:grid-cols-2" aria-labelledby="o-nas-nadpis">
            <div>
                <h2 id="o-nas-nadpis" class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">{{ __('O nás') }}</h2>
                <p class="mt-6 text-lg text-slate-700">
                    {{ __('CYSA vznikla ako súčasť diplomovej práce zameranej na kybernetickú bezpečnosť študentov základných a stredných škôl. Obsah kurzov vychádza z prieskumu medzi študentmi a učiteľmi – zameriava sa na oblasti, v ktorých majú študenti najväčšie medzery.') }}
                </p>
                <p class="mt-4 text-slate-700">
                    {{ __('Naším cieľom je, aby sa bezpečné správanie na internete učilo prakticky, na príkladoch zo života študentov – a aby škola vedela zmerať, čo sa študenti naozaj naučili.') }}
                </p>
                <p class="mt-4 text-slate-700">
                    {{ __('Rovnaké základy – rozpoznať phishing, chrániť heslá a osobné údaje – dnes potrebuje každý zamestnanec. Preto CYSA ponúka kurzy aj firmám a organizáciám ako povinné bezpečnostné školenie s dokladom o absolvovaní.') }}
                </p>
            </div>
            <dl class="grid grid-cols-2 gap-4">
                @foreach ([['8', 'tém kybernetickej bezpečnosti'], ['6', 'typov otázok v testoch'], ['24/7', 'prístup z počítača aj mobilu'], ['GDPR', 'bezpečné spracovanie údajov']] as [$value, $label])
                    <div class="flex flex-col rounded-2xl border border-slate-200 bg-slate-50 p-6">
                        <dt class="text-sm text-slate-600">{{ __($label) }}</dt>
                        <dd class="order-first text-3xl font-extrabold text-brand-700">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        {{-- Course topics --}}
        <section id="kurzy" class="scroll-mt-28 bg-slate-50 py-24" aria-labelledby="kurzy-nadpis">
            <div class="mx-auto max-w-6xl px-4 sm:px-6">
                <h2 id="kurzy-nadpis" class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">{{ __('Aké kurzy u nás nájdete') }}</h2>
                <p class="mt-3 max-w-2xl text-slate-700">{{ __('Každá téma obsahuje výklad, študijné materiály a krátke testy s vysvetlením správnych odpovedí. Učitelia aj firemní školitelia si môžu vytvárať vlastné kurzy.') }}</p>

                <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($topics as [$icon, $title, $text])
                        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs transition hover:-translate-y-0.5 hover:shadow-md">
                            <span class="mb-4 inline-flex rounded-xl bg-brand-50 p-3 text-brand-600"><x-icon :name="$icon" class="size-6" /></span>
                            <h3 class="font-bold text-slate-900">{{ __($title) }}</h3>
                            <p class="mt-2 text-sm text-slate-600">{{ __($text) }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- How it works + features --}}
        <section id="ako-to-funguje" class="mx-auto max-w-6xl scroll-mt-28 px-4 py-24 sm:px-6" aria-labelledby="postup-nadpis">
            <h2 id="postup-nadpis" class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">{{ __('Ako to funguje') }}</h2>
            <ol class="mt-10 grid gap-6 md:grid-cols-4">
                @foreach ($steps as [$title, $text])
                    <li class="relative rounded-2xl border border-slate-200 p-6">
                        <span class="flex size-10 items-center justify-center rounded-full bg-accent text-lg font-bold text-white">{{ $loop->iteration }}</span>
                        <h3 class="mt-4 font-bold text-slate-900">{{ __($title) }}</h3>
                        <p class="mt-2 text-sm text-slate-600">{{ __($text) }}</p>
                    </li>
                @endforeach
            </ol>

            <h3 class="mt-20 text-2xl font-extrabold text-slate-900">{{ __('Všetko, čo škola alebo firma potrebuje, na jednom mieste') }}</h3>
            <ul class="mt-6 grid gap-x-8 gap-y-3 text-slate-700 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($features as $feature)
                    <li class="flex items-start gap-2"><x-icon name="check-circle" class="mt-0.5 size-5 shrink-0 text-brand-600" />{{ __($feature) }}</li>
                @endforeach
            </ul>
        </section>

        {{-- Contact --}}
        <section id="kontakt" class="scroll-mt-28 bg-slate-900 py-24 text-white" aria-labelledby="kontakt-nadpis">
            <div class="mx-auto grid max-w-6xl gap-12 px-4 sm:px-6 lg:grid-cols-5">
                <div class="lg:col-span-2">
                    <h2 id="kontakt-nadpis" class="text-3xl font-extrabold tracking-tight sm:text-4xl">{{ __('Kontakt') }}</h2>
                    <p class="mt-4 text-slate-300">{{ __('Chcete platformu vyskúšať vo vašej škole či firme alebo máte otázku ku kurzom? Napíšte nám – ozveme sa čo najskôr.') }}</p>
                    @if (config('cysa.contact_email'))
                        <p class="mt-8 flex items-center gap-3 text-slate-200">
                            <x-icon name="bell" class="size-5 text-accent" />
                            <a href="mailto:{{ config('cysa.contact_email') }}" class="font-semibold hover:underline">{{ config('cysa.contact_email') }}</a>
                        </p>
                    @endif
                    <p class="mt-3 flex items-center gap-3 text-slate-200">
                        <x-icon name="badge" class="size-5 text-accent" />
                        <a href="{{ route('certificates.verify') }}" class="font-semibold hover:underline">{{ __('Overenie certifikátu') }}</a>
                    </p>
                </div>

                <div class="rounded-2xl bg-white p-6 text-slate-900 shadow-xl sm:p-8 lg:col-span-3">
                    @if (session('contact_sent'))
                        <div class="flex flex-col items-center py-10 text-center" role="status">
                            <span class="rounded-full bg-accent/15 p-3 text-accent"><x-icon name="check" class="size-8" /></span>
                            <p class="mt-4 text-xl font-bold">{{ __('Ďakujeme, správa bola odoslaná.') }}</p>
                            <p class="mt-1 text-slate-600">{{ __('Ozveme sa vám čo najskôr.') }}</p>
                        </div>
                    @else
                        @if (session('error'))
                            <p class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-700" role="alert">{{ session('error') }}</p>
                        @endif
                        <form method="POST" action="{{ route('contact.store') }}" class="grid gap-4 sm:grid-cols-2">
                            @csrf
                            {{-- Honeypot for bots - hidden from people and screen readers --}}
                            <div class="hidden" aria-hidden="true">
                                <label for="website">Website</label>
                                <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
                            </div>

                            <x-form.input name="name" :label="__('Meno a priezvisko')" required maxlength="100" autocomplete="name" />
                            <x-form.input name="email" type="email" :label="__('E-mail')" required maxlength="255" autocomplete="email" />
                            <x-form.input name="school" :label="__('Škola / firma (nepovinné)')" maxlength="150" autocomplete="organization" />
                            <x-form.select name="subject" :label="__('Téma')" :options="collect($subjects)->map(fn ($label) => __($label))->all()" required />
                            <div class="sm:col-span-2">
                                <x-form.textarea name="message" :label="__('Správa')" rows="5" required maxlength="3000" />
                            </div>
                            <div class="sm:col-span-2">
                                <x-form.checkbox name="consent" :label="__('Súhlasím so spracovaním uvedených údajov na účel odpovede na moju správu.')" />
                            </div>
                            <div class="sm:col-span-2">
                                <button type="submit" class="w-full rounded-lg bg-brand-600 px-6 py-3 font-semibold text-white transition hover:bg-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 sm:w-auto">{{ __('Odoslať správu') }}</button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </section>
    </main>

    <footer class="relative overflow-hidden border-t-4 border-brand-600 bg-brand-50 text-sm text-slate-700">
        {{-- Decorative circles --}}
        <div class="pointer-events-none absolute inset-0" aria-hidden="true">
            <div class="absolute -top-24 -right-24 size-80 rounded-full bg-brand-100"></div>
            <div class="absolute -bottom-32 -left-20 size-72 rounded-full border-[2.5rem] border-white/70"></div>
        </div>

        <div class="relative mx-auto max-w-6xl px-4 pt-16 pb-8 sm:px-6">
            <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-12">
                <div class="lg:col-span-4">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-brand-600">
                        <x-icon name="shield" class="size-8" />
                        <span class="text-2xl font-bold tracking-tight">{{ config('app.name') }}</span>
                    </a>
                    <p class="mt-4 max-w-xs leading-6 text-slate-600">
                        {{ __('Online vzdelávacia platforma kybernetickej bezpečnosti pre školy aj firmy. Kurzy, testy a certifikáty pre študentov aj zamestnancov na jednom mieste.') }}
                    </p>
                    <a href="#kontakt" class="mt-6 inline-flex items-center gap-2 rounded-lg bg-brand-600 px-5 py-2.5 font-semibold text-white shadow-sm transition hover:bg-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600">
                        {{ __('Chcem ukážku') }}<x-icon name="chevron-right" class="size-4" />
                    </a>
                </div>

                <nav class="lg:col-span-2" aria-labelledby="footer-platforma">
                    <h2 id="footer-platforma" class="text-xs font-bold tracking-wider text-brand-600 uppercase">{{ __('Platforma') }}</h2>
                    <ul class="mt-4 flex flex-col gap-3">
                        @foreach (['o-nas' => 'O nás', 'sluzby' => 'Služby', 'ako-to-funguje' => 'Ako to funguje', 'kontakt' => 'Kontakt'] as $anchor => $label)
                            <li><a href="#{{ $anchor }}" class="hover:text-brand-600 hover:underline">{{ __($label) }}</a></li>
                        @endforeach
                    </ul>
                </nav>

                <nav class="lg:col-span-3" aria-labelledby="footer-kurzy">
                    <h2 id="footer-kurzy" class="text-xs font-bold tracking-wider text-brand-600 uppercase">{{ __('Témy kurzov') }}</h2>
                    <ul class="mt-4 flex flex-col gap-3">
                        @foreach (array_slice($topics, 0, 5) as [$icon, $title])
                            <li><a href="#kurzy" class="hover:text-brand-600 hover:underline">{{ __($title) }}</a></li>
                        @endforeach
                    </ul>
                </nav>

                <div class="lg:col-span-3">
                    <h2 class="text-xs font-bold tracking-wider text-brand-600 uppercase">{{ __('Kontakt a prístup') }}</h2>
                    <ul class="mt-4 flex flex-col gap-3">
                        @if (config('cysa.contact_email'))
                            <li>
                                <a href="mailto:{{ config('cysa.contact_email') }}" class="inline-flex items-center gap-2 hover:text-brand-600 hover:underline">
                                    <x-icon name="mail" class="size-5 text-brand-500" />{{ config('cysa.contact_email') }}
                                </a>
                            </li>
                        @endif
                        <li>
                            <a href="{{ route('certificates.verify') }}" class="inline-flex items-center gap-2 hover:text-brand-600 hover:underline">
                                <x-icon name="badge" class="size-5 text-brand-500" />{{ __('Overiť certifikát') }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('login') }}" class="inline-flex items-center gap-2 hover:text-brand-600 hover:underline">
                                <x-icon name="lock" class="size-5 text-brand-500" />{{ __('Prihlásenie') }}
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mt-14 flex flex-col items-center justify-between gap-4 border-t border-brand-200 pt-6 text-slate-600 sm:flex-row">
                <p>© {{ now()->year }} {{ config('app.name') }} · {{ __('Kybernetická bezpečnosť pre školy a firmy') }}</p>
                <a href="#obsah" class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 font-semibold text-brand-600 shadow-xs ring-1 ring-brand-200 hover:bg-brand-100">
                    <x-icon name="arrow-up" class="size-4" />{{ __('Späť hore') }}
                </a>
            </div>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
