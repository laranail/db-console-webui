<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Simtabi\Laranail\DBConsole\Authorization\DeprecatedAbilities;
use Simtabi\Laranail\DBConsoleWebUI\Http\Middleware\EnsureCanManage;

/*
 * The middleware is the UI's only entry gate, so the ability names it checks
 * must be the ones db-console registers. A wrong name denies everyone, or —
 * with a permissive Gate::before — nobody notices until an audit.
 */
function passThroughEnsureCanManage(?User $user): int
{
    $request = Request::create('/db-console');
    $request->setUserResolver(fn (): ?User => $user);

    try {
        return app(EnsureCanManage::class)
            ->handle($request, fn (): Response => new Response('ok'))
            ->getStatusCode();
    } catch (HttpException $e) {
        return $e->getStatusCode();
    }
}

beforeEach(function (): void {
    $this->migrateCatalog();
});

it('checks abilities db-console actually defines', function (string $ability): void {
    expect(Gate::has($ability))->toBeTrue("db-console registers no [{$ability}] ability");
})->with(['laranail-db-console.database.view', 'laranail-db-console.server.view']);

it('admits a user holding a scoped db-console ability', function (string $ability): void {
    Gate::before(fn (User $user, string $asked): ?bool => $asked === $ability ? true : null);

    expect(passThroughEnsureCanManage(new User))->toBe(200);
})->with(['laranail-db-console.database.view', 'laranail-db-console.server.view']);

it('refuses a signed-in user with no db-console ability', function (): void {
    expect(passThroughEnsureCanManage(new User))->toBe(403);
});

it('checks the scoped names, not the deprecated bare aliases', function (): void {
    DeprecatedAbilities::forgetWarnings();
    $notices = [];
    set_error_handler(function (int $level, string $message) use (&$notices): bool {
        $notices[] = $message;

        return true;
    }, E_USER_DEPRECATED);

    try {
        passThroughEnsureCanManage(new User);
    } finally {
        restore_error_handler();
    }

    expect($notices)->toBe([]);
});

it('refuses a guest', function (): void {
    expect(passThroughEnsureCanManage(null))->toBe(403);
});
