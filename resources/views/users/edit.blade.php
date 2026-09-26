<x-layouts::app :title="$user->name">
    <x-page-header :title="$user->name" :description="$user->role->label()" :breadcrumbs="[
        ['label' => __('Používatelia'), 'url' => route('users.index')],
        ['label' => $user->name],
    ]">
        <x-slot:actions>
            <form method="POST" action="{{ route('users.invitation', $user) }}">
                @csrf
                <x-button variant="secondary">{{ __('Poslať pozvánku / odkaz na heslo') }}</x-button>
            </form>
        </x-slot:actions>
    </x-page-header>

    <form method="POST" action="{{ route('users.update', $user) }}" class="max-w-2xl">
        @method('PUT')
        @include('users._form')

        <div class="mt-4 flex gap-2">
            <x-button>{{ __('Uložiť') }}</x-button>
            <x-link-button variant="secondary" :href="route('users.index')">{{ __('Zrušiť') }}</x-link-button>
        </div>
    </form>

    @unless ($user->is(auth()->user()))
        <x-card :title="__('Odstránenie účtu')" class="mt-8 max-w-2xl border-red-200">
            <p class="text-sm text-slate-600">{{ __('Používateľ sa už neprihlási. Ak chcete prístup zablokovať len dočasne, účet radšej deaktivujte v zozname používateľov.') }}</p>
            <form method="POST" action="{{ route('users.destroy', $user) }}" class="mt-3"
                  x-data @submit="if (! confirm(@js(__('Naozaj odstrániť tento účet?')))) $event.preventDefault()">
                @csrf
                @method('DELETE')
                <x-button variant="danger">{{ __('Odstrániť účet') }}</x-button>
            </form>
        </x-card>

        <x-card :title="__('Anonymizácia (GDPR)')" class="mt-4 max-w-2xl border-red-200">
            <p class="text-sm text-slate-600">{{ __('Nevratne nahradí meno a e-mail zástupnými údajmi, zruší účet, členstvo v triedach a certifikáty. Pseudonymizované výsledky testov zostanú pre štatistiky.') }}</p>
            <form method="POST" action="{{ route('users.anonymize', $user) }}" class="mt-3 flex flex-wrap items-end gap-2">
                @csrf
                <x-form.input name="confirmation" :label="__('Na potvrdenie napíšte ANONYMIZOVAŤ')" autocomplete="off" class="max-w-xs" />
                <x-button variant="danger">{{ __('Anonymizovať') }}</x-button>
            </form>
        </x-card>
    @endunless
</x-layouts::app>
