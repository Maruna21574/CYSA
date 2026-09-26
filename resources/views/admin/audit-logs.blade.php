<x-layouts::app :title="__('Audit log')">
    <x-page-header :title="__('Audit log')" :description="__('Nemenný záznam bezpečnostných a administratívnych udalostí.')" />

    <livewire:admin.audit-log-table />
</x-layouts::app>
