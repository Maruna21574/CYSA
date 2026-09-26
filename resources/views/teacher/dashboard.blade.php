<x-layouts::app :title="__('Prehľad učiteľa')">
    <x-page-header :title="__('Prehľad učiteľa')" :description="__('Vitaj, :name.', ['name' => auth()->user()->first_name])" />

    <x-empty-state icon="book" :title="__('Zatiaľ tu nič nie je')" :description="__('Kurzy, testy a výsledky študentov sa tu zobrazia po vytvorení prvého kurzu.')" />
</x-layouts::app>
