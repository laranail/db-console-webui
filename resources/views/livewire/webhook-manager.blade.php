<div>
    <flux:heading size="lg">{{ __('db-console-webui::ui.webhooks') }}</flux:heading>

    @if ($error)<flux:callout variant="danger">{{ $error }}</flux:callout>@endif
    @if ($signingSecret)
        <flux:callout variant="warning" icon="key">
            <flux:callout.heading>Signing secret (shown once)</flux:callout.heading>
            <flux:callout.text><code>{{ $signingSecret }}</code></flux:callout.text>
        </flux:callout>
    @endif

    <form wire:submit="subscribe">
        <flux:input wire:model="url" label="URL" />
        @error('url')<flux:text variant="danger">{{ $message }}</flux:text>@enderror
        <flux:select wire:model="events" multiple>
            @foreach ($eventTypes as $type)
                <flux:select.option value="{{ $type }}">{{ $type }}</flux:select.option>
            @endforeach
        </flux:select>
        @error('events')<flux:text variant="danger">{{ $message }}</flux:text>@enderror
        <flux:button type="submit" variant="primary">Subscribe</flux:button>
    </form>

    <flux:separator />
    <ul>
        @foreach ($subscriptions as $sub)
            <li>
                {{ $sub->url }} [{{ $sub->active ? 'active' : 'disabled' }}]
                <flux:button wire:click="remove('{{ $sub->id }}')" size="xs" variant="danger">Remove</flux:button>
            </li>
        @endforeach
    </ul>
</div>
