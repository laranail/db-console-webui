<div>
    <flux:heading size="lg">{{ \Simtabi\Laranail\DBConsoleWebUI\Support\Translations::get('ui.accounts') }}</flux:heading>

    @if ($flash)<flux:callout variant="success">{{ $flash }}</flux:callout>@endif
    @if ($error)<flux:callout variant="danger">{{ $error }}</flux:callout>@endif
    @if ($confirmUrl)
        <flux:button :href="$confirmUrl" variant="filled" icon="shield-check">{{ \Simtabi\Laranail\DBConsoleWebUI\Support\Translations::get('ui.confirm_identity') }}</flux:button>
    @endif
    @if ($adoptOptions !== [])
        <flux:callout variant="warning">
            <flux:callout.text>{{ \Simtabi\Laranail\DBConsoleWebUI\Support\Translations::get('ui.adopt_risk') }}</flux:callout.text>
            @if (in_array('adopt', $adoptOptions, true))
                <flux:button wire:click="adopt('adopt')">{{ \Simtabi\Laranail\DBConsoleWebUI\Support\Translations::get('ui.adopt_account') }}</flux:button>
            @endif
            @if (in_array('adopt_and_apply', $adoptOptions, true))
                <flux:button wire:click="adopt('adopt_and_apply')" variant="danger">{{ \Simtabi\Laranail\DBConsoleWebUI\Support\Translations::get('ui.adopt_account_password') }}</flux:button>
                <flux:callout.text>{{ \Simtabi\Laranail\DBConsoleWebUI\Support\Translations::get('ui.adopt_password_risk') }}</flux:callout.text>
            @endif
        </flux:callout>
    @endif
    @if ($generatedPassword)
        <flux:callout variant="warning" icon="key">
            <flux:callout.heading>{{ \Simtabi\Laranail\DBConsoleWebUI\Support\Translations::get('ui.password_once') }}</flux:callout.heading>
            <flux:callout.text><code>{{ $generatedPassword }}</code></flux:callout.text>
        </flux:callout>
    @endif

    <form wire:submit="create">
        <flux:input wire:model="username" :label="\Simtabi\Laranail\DBConsoleWebUI\Support\Translations::get('ui.username')" />
        @error('username')<flux:text variant="danger">{{ $message }}</flux:text>@enderror
        @if ($scopesHosts)
            <flux:input wire:model="host" :label="\Simtabi\Laranail\DBConsoleWebUI\Support\Translations::get('ui.host')" />
            @error('host')<flux:text variant="danger">{{ $message }}</flux:text>@enderror
        @endif
        <flux:button type="submit" variant="primary">{{ \Simtabi\Laranail\DBConsoleWebUI\Support\Translations::get('ui.create') }}</flux:button>
    </form>

    <flux:separator />
    <ul>
        @foreach ($accounts as $account)
            <li>
                {{ $account }}
                <flux:button size="sm" variant="ghost" wire:click="chooseDrop(@js($account))">{{ \Simtabi\Laranail\DBConsoleWebUI\Support\Translations::get('ui.drop') }}</flux:button>
            </li>
        @endforeach
    </ul>

    @if ($dropUsername !== '')
        <form wire:submit="drop">
            <flux:text>{{ \Simtabi\Laranail\DBConsoleWebUI\Support\Translations::get('ui.drop_account', ['account' => $dropUsername . ($scopesHosts ? '@' . $dropHost : '')]) }}</flux:text>
            <flux:input wire:model="confirmDrop" :label="\Simtabi\Laranail\DBConsoleWebUI\Support\Translations::get('ui.confirm_drop_account')" />
            @error('confirmDrop')<flux:text variant="danger">{{ $message }}</flux:text>@enderror
            @error('dropUsername')<flux:text variant="danger">{{ $message }}</flux:text>@enderror
            @error('dropHost')<flux:text variant="danger">{{ $message }}</flux:text>@enderror
            <flux:button type="submit" variant="danger">{{ \Simtabi\Laranail\DBConsoleWebUI\Support\Translations::get('ui.drop') }}</flux:button>
        </form>
    @endif
</div>
