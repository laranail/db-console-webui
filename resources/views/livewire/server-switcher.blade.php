<div>
    <flux:heading size="sm">{{ __('db-console-webui::ui.active_server') }}</flux:heading>

    @if (count($servers) === 0)
        <flux:text variant="subtle">{{ __('db-console-webui::ui.no_servers') }}</flux:text>
    @else
        <flux:select wire:model.live="active" @change="$wire.select($event.target.value)">
            @foreach ($servers as $server)
                <flux:select.option value="{{ $server['name'] }}">
                    {{ $server['name'] }} ({{ $server['engine'] }})
                </flux:select.option>
            @endforeach
        </flux:select>
    @endif
</div>
