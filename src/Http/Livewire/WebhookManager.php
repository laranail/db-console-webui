<?php

declare(strict_types=1);

namespace Simtabi\Laranail\DBConsoleWebUI\Http\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Simtabi\Laranail\DBConsole\Enums\WebhookEvent;
use Simtabi\Laranail\DBConsole\Exceptions\DBConsoleException;
use Simtabi\Laranail\DBConsole\Models\WebhookSubscription;
use Simtabi\Laranail\DBConsole\Validation\Requests\WebhookRequest;
use Simtabi\Laranail\DBConsole\Validation\RuleProvider;
use Simtabi\Laranail\DBConsole\Webhooks\WebhookManager as WebhookService;

/**
 * Manage webhook subscriptions. Subscribing calls the core WebhookManager,
 * which mints the signing secret and returns it once; the UI shows it once and
 * never persists it. Validation is the core's.
 */
final class WebhookManager extends Component
{
    public string $url = '';

    /** @var list<string> */
    public array $events = [];

    public ?string $signingSecret = null;

    public ?string $error = null;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'url' => RuleProvider::field(WebhookRequest::class, 'url'),
            'events' => RuleProvider::field(WebhookRequest::class, 'events'),
        ];
    }

    public function subscribe(): void
    {
        $this->reset('signingSecret', 'error');
        $this->validate();

        try {
            [, $secret] = app(WebhookService::class)->subscribe($this->url, $this->events);
            $this->signingSecret = $secret;   // shown once
            $this->reset('url', 'events');
        } catch (DBConsoleException $e) {
            $this->error = $e->userMessage();
        }
    }

    public function remove(string $id): void
    {
        try {
            app(WebhookService::class)->unsubscribe($id);
        } catch (DBConsoleException $e) {
            $this->error = $e->userMessage();
        }
    }

    public function render(): View
    {
        return \Illuminate\Support\Facades\View::make('db-console-webui::livewire.webhook-manager', [
            'subscriptions' => WebhookSubscription::query()->get(),
            'eventTypes' => array_map(static fn (WebhookEvent $e): string => $e->value, WebhookEvent::cases()),
        ]);
    }
}
