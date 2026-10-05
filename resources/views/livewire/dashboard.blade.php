<div>
    <flux:heading size="lg">{{ \Simtabi\Laranail\DBConsoleWebUI\Support\Translations::get('ui.dashboard') }}</flux:heading>

    @if ($error)
        <flux:callout variant="danger" icon="exclamation-triangle">
            <flux:callout.heading>{{ \Simtabi\Laranail\DBConsoleWebUI\Support\Translations::get('ui.unreachable') }}</flux:callout.heading>
            <flux:callout.text>{{ $error }}</flux:callout.text>
            <x-slot name="actions">
                <flux:button wire:click="$refresh" size="sm">{{ \Simtabi\Laranail\DBConsoleWebUI\Support\Translations::get('ui.retry') }}</flux:button>
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
