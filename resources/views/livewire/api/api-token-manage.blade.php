<div>
    <x-form wire:submit="createApiToken">
        {{ $this->form }}
    </x-form>

    <div id="api-token-manage-table" class="mx-4 mt-4 sm:mx-0">
        {{ $this->table }}
    </div>

    <x-filament::modal id="modal-token-display" :close-by-escaping="false" :close-by-clicking-away="false" :close-button="false" width="2xl">
        <x-slot name="heading">
            {{ __('API Token') }}
        </x-slot>

        <x-slot name="description">
            {{ __('Please copy your new API token. For your security, it won\'t be shown again.') }}
        </x-slot>

        {{ $this->getTokenDisplayForm() }}
    </x-filament::modal>
    <x-filament-actions::modals />
</div>
