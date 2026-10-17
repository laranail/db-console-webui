<?php

declare(strict_types=1);

namespace Simtabi\Laranail\DBConsoleWebUI\Http\Livewire\Concerns;

use Illuminate\Support\Facades\Route;
use Simtabi\Laranail\DBConsole\Exceptions\AssuranceRequired;
use Simtabi\Laranail\DBConsole\Exceptions\DBConsoleException;
use Simtabi\Laranail\DBConsole\Exceptions\ResourceAlreadyExists;
use Simtabi\Laranail\DBConsole\Services\Results\OperationResult;

/**
 * Turns the core's refusals into choices the operator can act on. The core
 * decides; this only shows what it said:
 *
 * - a resource that already exists comes with the adopt policies this
 *   operator may resubmit with (empty unless they hold resource.adopt);
 * - an assurance refusal links to the host's own confirmation page, since
 *   the host authenticates and db-console only says what is missing.
 */
trait RespondsToRefusals
{
    /** @var list<string> ExistingResourcePolicy values offered by the last refusal */
    public array $adoptOptions = [];

    public ?string $confirmUrl = null;

    protected function respondToRefusal(DBConsoleException $e): void
    {
        $this->error = $e->userMessage();

        $this->adoptOptions = $e instanceof ResourceAlreadyExists
            ? array_values(array_filter($e->options(), static fn (string $option): bool => $option !== 'refuse'))
            : [];

        $this->confirmUrl = $e instanceof AssuranceRequired && Route::has('password.confirm')
            ? route('password.confirm')
            : null;
    }

    protected function clearRefusal(): void
    {
        $this->adoptOptions = [];
        $this->confirmUrl = null;
    }

    /**
     * Whether an adopt policy is one the last refusal offered. Anything else
     * never reaches the core.
     */
    protected function offered(string $policy): bool
    {
        return in_array($policy, $this->adoptOptions, true);
    }

    protected function adopted(OperationResult $result): bool
    {
        return $result->alreadyExisted || ($result->data['adopted'] ?? false) === true;
    }
}
