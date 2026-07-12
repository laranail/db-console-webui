<div>
    <flux:heading size="lg">{{ __('db-console-webui::ui.databases') }}</flux:heading>

    @if ($flash)<flux:callout variant="success">{{ $flash }}</flux:callout>@endif
    @if ($error)<flux:callout variant="danger">{{ $error }}</flux:callout>@endif

    <form wire:submit="create">
        <flux:input wire:model="name" :label="__('db-console-webui::ui.database_name')" />
        @error('name')<flux:text variant="danger">{{ $message }}</flux:text>@enderror
        <flux:button type="submit" variant="primary">{{ __('db-console-webui::ui.create') }}</flux:button>
    </form>

    <flux:separator />

    <ul>
        @foreach ($databases as $database)
            <li>{{ $database }}</li>
        @endforeach
    </ul>

    <form wire:submit="drop">
        <flux:input wire:model="confirmName" :label="__('db-console-webui::ui.confirm_drop')" />
        @error('confirmName')<flux:text variant="danger">{{ $message }}</flux:text>@enderror
        <flux:button type="submit" variant="danger">{{ __('db-console-webui::ui.drop') }}</flux:button>
    </form>
</div>
