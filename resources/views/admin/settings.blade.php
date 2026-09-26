<x-layouts::app :title="__('Systémové nastavenia')">
    <x-page-header :title="__('Systémové nastavenia')" />

    <form method="POST" action="{{ route('admin.settings.update') }}" class="flex max-w-2xl flex-col gap-6">
        @csrf
        @method('PUT')

        <x-card :title="__('Oznam pre všetkých používateľov')" class="flex flex-col gap-4">
            <x-form.checkbox name="banner_enabled" :checked="$settings['banner_enabled']" :label="__('Zobraziť oznam v hornej časti aplikácie')" />
            <x-form.select name="banner_type" :label="__('Typ')" :options="['info' => __('Informácia'), 'warning' => __('Upozornenie')]" :selected="$settings['banner_type'] ?? 'info'" />
            <x-form.textarea name="banner_message" :label="__('Text oznamu')" :value="$settings['banner_message']" rows="2" maxlength="300"
                :hint="__('Napríklad: V sobotu od 8:00 do 10:00 bude platforma nedostupná z dôvodu údržby.')" />
        </x-card>

        <x-card :title="__('Nové školy')">
            <x-form.checkbox name="default_gamification" :checked="$settings['default_gamification'] ?? true" :label="__('Gamifikácia je pre nové školy predvolene zapnutá')" />
        </x-card>

        <div><x-button>{{ __('Uložiť') }}</x-button></div>
    </form>
</x-layouts::app>
