<x-layouts::app :title="__('Administrácia systému')">
    <x-page-header :title="__('Administrácia systému')" :description="__('Vitaj, :name.', ['name' => auth()->user()->first_name])" />

    <x-empty-state icon="building" :title="__('Prehľad sa pripravuje')" :description="__('Správa škôl, používateľov a štatistiky systému pribudnú v ďalšej fáze.')" />
</x-layouts::app>
