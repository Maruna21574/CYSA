<x-layouts::app :title="__('Môj prehľad')">
    <x-page-header :title="__('Môj prehľad')" :description="__('Vitaj, :name.', ['name' => auth()->user()->first_name])" />

    <x-empty-state icon="book" :title="__('Zatiaľ nemáš priradené žiadne kurzy')" :description="__('Keď ti učiteľ priradí kurz, nájdeš ho tu.')" />
</x-layouts::app>
