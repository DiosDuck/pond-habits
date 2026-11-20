# Laravel for Symfony developers

A map from Symfony (and Laminas) ideas to Laravel 13 in this repo (ADR 0002). Each row links to real code here.
Official docs: https://laravel.com/docs

## The big difference

Symfony gives explicit configuration and Data Mapper (Doctrine). Laravel gives conventions,
Active Record (Eloquent) and facades. Both have a service container with autowiring.
In this repo, app code uses constructor injection where it can, as in Symfony.

## Map

| Symfony | Laravel in this repo | Example |
|---|---|---|
| `Kernel`, bundles | `bootstrap/app.php` and service providers | [bootstrap/app.php](../../backend/bootstrap/app.php) |
| `services.yaml`, autowiring | Container with autowiring. Bindings in a provider's `register()` | [AppServiceProvider.php](../../backend/app/Providers/AppServiceProvider.php) |
| Interface alias in `services.yaml` | `$this->app->singleton(Interface::class, fn () => ...)` | [AppServiceProvider.php](../../backend/app/Providers/AppServiceProvider.php) |
| `#[Route]` attribute on a controller | Route files: `routes/api.php` (prefix `/api`), `routes/web.php` | [routes/api.php](../../backend/routes/api.php) |
| Invokable controller | The same: a class with `__invoke` | [HealthController.php](../../backend/app/Http/Controllers/HealthController.php) |
| Firewall with no session | A route outside the `web` and `api` groups (`then:`) | [bootstrap/app.php](../../backend/bootstrap/app.php) |
| `kernel.request` subscriber | Middleware | (Phase 3) |
| Doctrine entity + repository | Eloquent model (Active Record): the model saves itself | [User.php](../../backend/app/Models/User.php) |
| Doctrine Migrations (`diff`) | Hand-written migrations with `up()` | [migrations](../../backend/database/migrations) |
| Doctrine UUID type | `HasUuids` trait: UUIDv7 keys | [User.php](../../backend/app/Models/User.php) |
| Fixtures | Factories and seeders | [UserFactory.php](../../backend/database/factories/UserFactory.php) |
| Security voter | Gate or policy | [AppServiceProvider.php](../../backend/app/Providers/AppServiceProvider.php) (`viewApiDocs`) |
| Security authenticator, JWT bundle | Sanctum, SPA cookie mode (ADR 0005) | (Phase 3) |
| Validator constraints, form types | Form Request classes or `$request->validate([...])` | (Phase 3) |
| Messenger + worker | Queues and jobs (database driver), `worker` container | [compose.yaml](../../compose.yaml) |
| Scheduler component, cron | `Schedule` in `routes/console.php`, `scheduler` container | [routes/console.php](../../backend/routes/console.php) |
| Monolog channels in `monolog.yaml` | Channels in `config/logging.php` | [config/logging.php](../../backend/config/logging.php) (`domain`) |
| `bin/console` command | `php artisan` command | (Phase 3: `app:create-admin`) |
| `.env` + `%env()%` | `.env` + `config/*.php`. Call `env()` only in config files. | [config/](../../backend/config) |
| Twig | Blade (only for mail views here) | (Phase 3) |
| `WebTestCase`, PHPUnit | Pest + `getJson()`, `RefreshDatabase` | [HealthTest.php](../../backend/tests/Feature/HealthTest.php) |
| API Platform docs | Scramble: OpenAPI from the code (ADR 0021) | [docs/generated](../generated/openapi.json) |

## Rules that catch Symfony developers

1. **`env()` only in `config/`.** With a config cache (production), `env()` in other code returns `null`.
   Read values with `config('app.name')` in app code.
2. **Facades are static proxies to the container.** `Log::channel('domain')` resolves the logger.
   They are easy, but hide the dependency. In this repo, app classes take dependencies in the constructor.
3. **Mass assignment.** `User::create($request->all())` is safe only for fields in `#[Fillable]`.
   Validate first, then pass only the validated data.
4. **Lazy loading and N+1.** Eloquent loads relations when you read them. Use `with('relation')`.
   Phase 3 turns on `Model::preventLazyLoading()` outside production.
5. **A gate closure with no `$user` parameter denies all guests.** Use `?User $user = null`.
   We had this bug (PR 5): [ApiDocsAccessTest.php](../../backend/tests/Feature/ApiDocsAccessTest.php).
6. **Route groups add middleware.** A route in `web` gets session, cookies and CSRF. `/health` has none on purpose.

## Read the code in this order

1. [bootstrap/app.php](../../backend/bootstrap/app.php): routes, middleware, exceptions.
2. [AppServiceProvider.php](../../backend/app/Providers/AppServiceProvider.php): bindings and gates.
3. [HealthController.php](../../backend/app/Http/Controllers/HealthController.php): one endpoint.
4. [DomainEventLog.php](../../backend/app/Support/DomainEvents/DomainEventLog.php): the domain event log (ADR 0008).
5. [HealthTest.php](../../backend/tests/Feature/HealthTest.php): feature tests with Pest.

## Links

- Service container: https://laravel.com/docs/container
- Eloquent: https://laravel.com/docs/eloquent
- Pest: https://pestphp.com/docs
