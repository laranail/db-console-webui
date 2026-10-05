<div>
    <flux:heading size="lg">{{ \Simtabi\Laranail\DBConsoleWebUI\Support\Translations::get('ui.databases') }}</flux:heading>

    @if ($flash)<flux:callout variant="success">{{ $flash }}</flux:callout>@endif
    @if ($error)<flux:callout variant="danger">{{ $error }}</flux:callout>@endif

    <form wire:submit="create">
        <flux:input wire:model="name" :label="\Simtabi\Laranail\DBConsoleWebUI\Support\Translations::get('ui.database_name')" />
        @error('name')<flux:text variant="danger">{{ $message }}</flux:text>@enderror
        <flux:button type="submit" variant="primary">{{ \Simtabi\Laranail\DBConsoleWebUI\Support\Translations::get('ui.create') }}</flux:button>
    </form>

    <flux:separator />

    <ul>
        @foreach ($databases as $database)
            <li>{{ $database }}</li>
        @endforeach
    </ul>

    <form wire:submit="drop">
        <flux:input wire:model="confirmName" :label="\Simtabi\Laranail\DBConsoleWebUI\Support\Translations::get('ui.confirm_drop')" />
        @error('confirmName')<flux:text variant="danger">{{ $message }}</flux:text>@enderror
        <flux:button type="submit" variant="danger">{{ \Simtabi\Laranail\DBConsoleWebUI\Support\Translations::get('ui.drop') }}</flux:button>
    </form>
</div>
