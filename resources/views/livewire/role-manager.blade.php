<div>
    <flux:heading size="lg">{{ \Simtabi\Laranail\DBConsoleWebUI\Support\Translations::get('ui.roles') }}</flux:heading>
    @foreach ($roles as $role)
        <flux:card>
            <flux:heading size="sm">{{ $role['label'] }} ({{ $role['name'] }})</flux:heading>
            <flux:text variant="subtle">{{ implode(', ', $role['permissions']) }}</flux:text>
        </flux:card>
    @endforeach
</div>
