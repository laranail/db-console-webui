<div>
    <flux:heading size="lg">{{ __('db-console-webui::ui.accounts') }}</flux:heading>

    @if ($flash)<flux:callout variant="success">{{ $flash }}</flux:callout>@endif
    @if ($error)<flux:callout variant="danger">{{ $error }}</flux:callout>@endif
    @if ($generatedPassword)
        <flux:callout variant="warning" icon="key">
            <flux:callout.heading>{{ __('db-console-webui::ui.password_once') }}</flux:callout.heading>
            <flux:callout.text><code>{{ $generatedPassword }}</code></flux:callout.text>
        </flux:callout>
    @endif

    <form wire:submit="create">
        <flux:input wire:model="username" :label="__('db-console-webui::ui.username')" />
        @error('username')<flux:text variant="danger">{{ $message }}</flux:text>@enderror
        <flux:input wire:model="host" :label="__('db-console-webui::ui.host')" />
        @error('host')<flux:text variant="danger">{{ $message }}</flux:text>@enderror
        <flux:button type="submit" variant="primary">{{ __('db-console-webui::ui.create') }}</flux:button>
    </form>

    <flux:separator />
    <ul>
        @foreach ($accounts as $account)
            <li>{{ $account }}</li>
        @endforeach
    </ul>
</div>
