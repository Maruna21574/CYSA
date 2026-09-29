<x-layouts::app :title="__('Nastavenia školy')">
    <x-page-header :title="__('Nastavenia školy')" :description="$school->name" />

    <form method="POST" action="{{ route('school.settings.update') }}" class="max-w-2xl">
        @csrf
        @method('PUT')
        <x-card :title="__('Gamifikácia')" class="flex flex-col gap-4">
            <x-form.checkbox name="gamification" :checked="$school->gamificationEnabled()"
                :label="__('Zapnúť body (XP), levely, sériu aktivity a odznaky pre študentov')"
                :hint="__('Motivačné prvky vidí iba samotný študent – nezobrazujú sa rebríčky ani porovnania s ostatnými študentmi.')" />
            <div><x-button>{{ __('Uložiť') }}</x-button></div>
        </x-card>
    </form>
</x-layouts::app>
