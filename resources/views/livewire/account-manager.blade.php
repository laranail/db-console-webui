<div>
    <flux:heading size="lg">{{ \Simtabi\Laranail\DBConsoleWebUI\Support\Translations::get('ui.accounts') }}</flux:heading>

    @if ($flash)<flux:callout variant="success">{{ $flash }}</flux:callout>@endif
    @if ($error)<flux:callout variant="danger">{{ $error }}</flux:callout>@endif
    @if ($generatedPassword)
        <flux:callout variant="warning" icon="key">
            <flux:callout.heading>{{ \Simtabi\Laranail\DBConsoleWebUI\Support\Translations::get('ui.password_once') }}</flux:callout.heading>
            <flux:callout.text><code>{{ $generatedPassword }}</code></flux:callout.text>
        </flux:callout>
    @endif

    <form wire:submit="create">
        <flux:input wire:model="username" :label="\Simtabi\Laranail\DBConsoleWebUI\Support\Translations::get('ui.username')" />
        @error('username')<flux:text variant="danger">{{ $message }}</flux:text>@enderror
        <flux:input wire:model="host" :label="\Simtabi\Laranail\DBConsoleWebUI\Support\Translations::get('ui.host')" />
        @error('host')<flux:text variant="danger">{{ $message }}</flux:text>@enderror
        <flux:button type="submit" variant="primary">{{ \Simtabi\Laranail\DBConsoleWebUI\Support\Translations::get('ui.create') }}</flux:button>
    </form>

    <flux:separator />
    <ul>
        @foreach ($accounts as $account)
            <li>{{ $account }}</li>
        @endforeach
    </ul>
</div>
