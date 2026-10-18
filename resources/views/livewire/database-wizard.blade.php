<div>
    <flux:heading size="lg">{{ \Simtabi\Laranail\DBConsoleWebUI\Support\Translations::get('ui.databases') }}</flux:heading>

    @if ($flash)<flux:callout variant="success">{{ $flash }}</flux:callout>@endif
    @if ($backupFailed)
        <flux:callout variant="danger" icon="shield-exclamation">
            <flux:callout.heading>{{ \Simtabi\Laranail\DBConsoleWebUI\Support\Translations::get('ui.backup_failed') }}</flux:callout.heading>
            <flux:callout.text>{{ $error }}</flux:callout.text>
        </flux:callout>
    @elseif ($error)
        <flux:callout variant="danger">{{ $error }}</flux:callout>
    @endif
    @if ($confirmUrl)
        <flux:button :href="$confirmUrl" variant="filled" icon="shield-check">{{ \Simtabi\Laranail\DBConsoleWebUI\Support\Translations::get('ui.confirm_identity') }}</flux:button>
    @endif
    @if (in_array('adopt', $adoptOptions, true))
        <flux:callout variant="warning">
            <flux:callout.text>{{ \Simtabi\Laranail\DBConsoleWebUI\Support\Translations::get('ui.adopt_risk') }}</flux:callout.text>
            <flux:button wire:click="adopt('adopt')">{{ \Simtabi\Laranail\DBConsoleWebUI\Support\Translations::get('ui.adopt_database') }}</flux:button>
        </flux:callout>
    @endif

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

    @if ($providerBackups)
        <flux:callout variant="secondary" icon="information-circle">
            <flux:callout.text>{{ \Simtabi\Laranail\DBConsoleWebUI\Support\Translations::get('ui.provider_backups') }}</flux:callout.text>
        </flux:callout>
    @endif

    <form wire:submit="drop">
        <flux:input wire:model="confirmName" :label="\Simtabi\Laranail\DBConsoleWebUI\Support\Translations::get('ui.confirm_drop')" />
        @error('confirmName')<flux:text variant="danger">{{ $message }}</flux:text>@enderror
        <flux:button type="submit" variant="danger">{{ \Simtabi\Laranail\DBConsoleWebUI\Support\Translations::get('ui.drop') }}</flux:button>
    </form>
</div>
