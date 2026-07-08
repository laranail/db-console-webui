<div>
    <flux:heading size="lg">{{ __('db-console-webui::ui.roles') }}</flux:heading>
    @foreach ($roles as $role)
        <flux:card>
            <flux:heading size="sm">{{ $role['label'] }} ({{ $role['name'] }})</flux:heading>
            <flux:text variant="subtle">{{ implode(', ', $role['permissions']) }}</flux:text>
        </flux:card>
    @endforeach
</div>
