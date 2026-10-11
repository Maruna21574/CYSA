@php use App\Support\Terms; @endphp
<x-layouts::app :title="Terms::of('Nastavenia školy', school: $school)">
    <x-page-header :title="Terms::of('Nastavenia školy', school: $school)" :description="$school->name" />

    <form method="POST" action="{{ route('school.settings.update') }}" class="max-w-2xl">
        @csrf
        @method('PUT')
        <x-card :title="__('Gamifikácia')" class="flex flex-col gap-4">
            <x-form.checkbox name="gamification" :checked="$school->gamificationEnabled()"
                :label="Terms::of('Zapnúť body (XP), levely, sériu aktivity a odznaky pre študentov', school: $school)"
                :hint="Terms::of('Motivačné prvky vidí iba samotný študent – nezobrazujú sa rebríčky ani porovnania s ostatnými študentmi.', school: $school)" />
            <div><x-button>{{ __('Uložiť') }}</x-button></div>
        </x-card>
    </form>
</x-layouts::app>
