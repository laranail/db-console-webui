<div>
    <flux:heading size="lg">{{ __('db-console-webui::ui.dashboard') }}</flux:heading>

    @if ($error)
        <flux:callout variant="danger" icon="exclamation-triangle">
            <flux:callout.heading>{{ __('db-console-webui::ui.unreachable') }}</flux:callout.heading>
            <flux:callout.text>{{ $error }}</flux:callout.text>
            <x-slot name="actions">
                <flux:button wire:click="$refresh" size="sm">{{ __('db-console-webui::ui.retry') }}</flux:button>
            </x-slot>
        </flux:callout>
    @else
        <flux:text>{{ $server }}: {{ count($databases) }} database(s)</flux:text>
        <ul>
            @foreach ($databases as $database)
                <li>{{ $database }}</li>
            @endforeach
        </ul>
    @endif
</div>
