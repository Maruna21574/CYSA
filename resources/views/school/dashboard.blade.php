<x-layouts::app :title="__('Prehľad školy')">
    <x-page-header :title="__('Prehľad školy')" :description="__('Vitaj, :name.', ['name' => auth()->user()->first_name])" />

    <x-empty-state icon="building" :title="__('Prehľad sa pripravuje')" :description="__('Správa učiteľov, študentov a tried pribudne v ďalšej fáze.')" />
</x-layouts::app>
