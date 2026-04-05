# Payment gateway mTLS + HMAC client

Framework-agnostic PHP library for **mutual TLS** (client certificate) HTTP **GET** requests with **HMAC** integrity signatures on the query string. Suited for payment-style APIs that require transport security and request signing.

## Requirements

- PHP 8.1+
- Extensions: `json`, `openssl`
- Composer

## Install

```bash
composer require mishagodovanuk/payment_gateway
```

PHP namespace remains `Mihod\PaymentGateway\` (Composer package name is `mishagodovanuk/payment_gateway`).

**Before the package is on [Packagist](https://packagist.org)** (or if you prefer Git directly), add the VCS repository in your project `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/mishagodovanuk/payment_gateway"
        }
    ],
    "require": {
        "mishagodovanuk/payment_gateway": "dev-main"
    }
}
```

After you [submit the GitHub repo to Packagist](https://packagist.org/packages/submit), others can run `composer require mishagodovanuk/payment_gateway` without a custom `repositories` block.

## Configuration

Copy `.env.example` to `.env` and set certificate paths, key passphrase, and `HMAC_SECRET`. For BadSSL demos, use [badssl.com/download](https://badssl.com/download/) (private key passphrase: `badssl.com`).

Environment variables override values from the `.env` file when both are present.

## Design (interfaces & DTOs)

- **`Mihod\PaymentGateway\Signature\SignerInterface`** — canonical query string + MAC signature (`HmacSigner` is the default).
- **`Mihod\PaymentGateway\Http\MtlsTransportInterface`** — GET over mTLS (`GuzzleMtlsTransport` via `GuzzleClientFactory`).
- **`Mihod\PaymentGateway\Dto\SignedHttpResponse`** — immutable result DTO for successful calls (status, body, headers).

`SignedMtlsClient` depends on these abstractions so you can swap implementations in tests or wire custom signers/transports in DI (Laravel, Symfony, Yii2, PHP-DI, etc.).

## Usage

### Manual / direct usage (no framework)

Use this when you run plain PHP (CLI script, cron, small tool) and you do not use Laravel, Symfony, or Yii.

**1. Install the package**

```bash
composer require mishagodovanuk/payment_gateway
```

**2. Configure environment**

Copy `.env.example` to `.env` next to your script (or anywhere you prefer). Set absolute paths to your PEM files, `HMAC_SECRET`, and optional `SIGNATURE_HEADER_NAME` / `SIGNATURE_HASH_ALGO`.

**3. Call the client from a PHP file**

Bootstrap Composer autoload, then either load config from that file or build it in code.

**Option A — read settings from a `.env` file path** (library parses the file; process `$_ENV` is merged so exported variables override the file):

```php
<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Mihod\PaymentGateway\SignedMtlsClient;

$client = SignedMtlsClient::fromEnvFile(__DIR__ . '/.env');

$response = $client->sendSignedGet('https://client.badssl.com/', [
    'transaction_id' => '12345',
    'amount' => '99.99',
    'currency' => 'USD',
]);

echo $response->statusCode() . PHP_EOL;
echo $response->body() . PHP_EOL;
```

Run: `php your-script.php`

**Option B — pass configuration only in code** (no `.env` file; good for one-off scripts if you accept hardcoded paths):

```php
<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Mihod\PaymentGateway\Config\ClientConfiguration;
use Mihod\PaymentGateway\SignedMtlsClient;

$config = ClientConfiguration::fromArray([
    'MTLS_CLIENT_CERT' => '/absolute/path/client.pem',
    'MTLS_CLIENT_KEY' => '/absolute/path/key.pem',
    'MTLS_CLIENT_KEY_PASSPHRASE' => 'optional-or-empty',
    'HMAC_SECRET' => 'your-shared-secret',
    'MTLS_VERIFY_SSL' => 'true',
    'SIGNATURE_HEADER_NAME' => 'X-Signature',
]);

$client = new SignedMtlsClient($config);

$response = $client->sendSignedGet('https://api.example.com/check', ['id' => '1']);
```

**Option C — use environment variables already set by the shell or systemd** (no file path; merge into `ClientConfiguration::fromArray($_ENV)` after ensuring your process has the same variable names as in `.env.example`).

In all cases the flow is: **build `ClientConfiguration` → `new SignedMtlsClient($config)` → `sendSignedGet($url, $query)` → `SignedHttpResponse` or an exception**.

---

### Laravel

Laravel already loads `.env` into `$_ENV` / `config()`. Map those values into `ClientConfiguration` once, register `SignedMtlsClient` as a singleton, then type-hint it in controllers, jobs, or commands.

**1. Add keys to `.env`** (same names as `.env.example` in this package, or your own — then map them).

**2. Create `config/payment_gateway.php`:**

```php
<?php

declare(strict_types=1);

return [
    'mtls_client_cert' => env('MTLS_CLIENT_CERT'),
    'mtls_client_key' => env('MTLS_CLIENT_KEY'),
    'mtls_client_key_passphrase' => env('MTLS_CLIENT_KEY_PASSPHRASE', ''),
    'hmac_secret' => env('HMAC_SECRET'),
    'mtls_verify_ssl' => env('MTLS_VERIFY_SSL', 'true'),
    'mtls_ca_bundle' => env('MTLS_CA_BUNDLE'),
    'signature_header_name' => env('SIGNATURE_HEADER_NAME', 'X-Signature'),
    'signature_hash_algo' => env('SIGNATURE_HASH_ALGO', 'sha256'),
];
```

**3. Register the client in `App\Providers\AppServiceProvider::register()`:**

```php
use Mihod\PaymentGateway\Config\ClientConfiguration;
use Mihod\PaymentGateway\SignedMtlsClient;

$this->app->singleton(SignedMtlsClient::class, function ($app) {
    $c = $app['config']->get('payment_gateway');

    $config = ClientConfiguration::fromArray([
        'MTLS_CLIENT_CERT' => $c['mtls_client_cert'],
        'MTLS_CLIENT_KEY' => $c['mtls_client_key'],
        'MTLS_CLIENT_KEY_PASSPHRASE' => $c['mtls_client_key_passphrase'],
        'HMAC_SECRET' => $c['hmac_secret'],
        'MTLS_VERIFY_SSL' => $c['mtls_verify_ssl'],
        'MTLS_CA_BUNDLE' => $c['mtls_ca_bundle'],
        'SIGNATURE_HEADER_NAME' => $c['signature_header_name'],
        'SIGNATURE_HASH_ALGO' => $c['signature_hash_algo'],
    ]);

    return new SignedMtlsClient($config);
});
```

**4. Inject where needed:**

```php
use Mihod\PaymentGateway\SignedMtlsClient;

public function __construct(private readonly SignedMtlsClient $paymentGateway) {}

public function check(): void
{
    $response = $this->paymentGateway->sendSignedGet(config('app.gateway_url'), ['transaction_id' => '1']);
}
```

Use `php artisan config:cache` in production so secrets are not read from `.env` on every request in the way `env()` does outside config files.

---

### Symfony

Symfony injects parameters from `.env` via `%env(...)%`. The clean approach is a small factory service that builds `ClientConfiguration` and returns `SignedMtlsClient`.

**1. Put variables in `.env` / `.env.local`** with the same names as this package’s `.env.example`.

**2. Define services** (YAML or PHP). Example in `config/services.yaml`:

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true

    Mihod\PaymentGateway\Config\ClientConfiguration:
        factory: ['App\PaymentGateway\PaymentGatewayFactory', 'createConfiguration']

    Mihod\PaymentGateway\SignedMtlsClient:
        factory: ['App\PaymentGateway\PaymentGatewayFactory', 'createClient']
        arguments:
            $configuration: '@Mihod\PaymentGateway\Config\ClientConfiguration'
```

**3. Implement `App\PaymentGateway\PaymentGatewayFactory`:**

```php
<?php

declare(strict_types=1);

namespace App\PaymentGateway;

use Mihod\PaymentGateway\Config\ClientConfiguration;
use Mihod\PaymentGateway\SignedMtlsClient;

final class PaymentGatewayFactory
{
    public static function createConfiguration(): ClientConfiguration
    {
        return ClientConfiguration::fromArray([
            'MTLS_CLIENT_CERT' => $_ENV['MTLS_CLIENT_CERT'],
            'MTLS_CLIENT_KEY' => $_ENV['MTLS_CLIENT_KEY'],
            'MTLS_CLIENT_KEY_PASSPHRASE' => $_ENV['MTLS_CLIENT_KEY_PASSPHRASE'] ?? '',
            'HMAC_SECRET' => $_ENV['HMAC_SECRET'],
            'MTLS_VERIFY_SSL' => $_ENV['MTLS_VERIFY_SSL'] ?? 'true',
            'MTLS_CA_BUNDLE' => $_ENV['MTLS_CA_BUNDLE'] ?? null,
            'SIGNATURE_HEADER_NAME' => $_ENV['SIGNATURE_HEADER_NAME'] ?? 'X-Signature',
            'SIGNATURE_HASH_ALGO' => $_ENV['SIGNATURE_HASH_ALGO'] ?? 'sha256',
        ]);
    }

    public static function createClient(ClientConfiguration $configuration): SignedMtlsClient
    {
        return new SignedMtlsClient($configuration);
    }
}
```

Symfony loads `.env` before the container runs, so `$_ENV` is populated. Alternatively, inject `%env(MTLS_CLIENT_CERT)%` as constructor arguments to the factory instead of reading `$_ENV` directly.

**4. Inject `SignedMtlsClient` into controllers/services by type-hint.**

---

### Yii 2

Yii 2 uses a global application container (`Yii::$container`) and/or the `components` section of the application config.

**1. Add env vars** (e.g. via `vlucas/phpdotenv` in `web/index.php` before the app boots, or export them in the web server / PHP-FPM pool).

**2. Register a singleton in `config/web.php` (and `config/console.php` if you use CLI):**

```php
<?php

use Mihod\PaymentGateway\Config\ClientConfiguration;
use Mihod\PaymentGateway\SignedMtlsClient;

$config = [
    // ...
    'container' => [
        'definitions' => [
            SignedMtlsClient::class => function () {
                $cfg = ClientConfiguration::fromArray([
                    'MTLS_CLIENT_CERT' => getenv('MTLS_CLIENT_CERT') ?: '',
                    'MTLS_CLIENT_KEY' => getenv('MTLS_CLIENT_KEY') ?: '',
                    'MTLS_CLIENT_KEY_PASSPHRASE' => getenv('MTLS_CLIENT_KEY_PASSPHRASE') ?: '',
                    'HMAC_SECRET' => getenv('HMAC_SECRET') ?: '',
                    'MTLS_VERIFY_SSL' => getenv('MTLS_VERIFY_SSL') ?: 'true',
                    'MTLS_CA_BUNDLE' => getenv('MTLS_CA_BUNDLE') ?: null,
                    'SIGNATURE_HEADER_NAME' => getenv('SIGNATURE_HEADER_NAME') ?: 'X-Signature',
                    'SIGNATURE_HASH_ALGO' => getenv('SIGNATURE_HASH_ALGO') ?: 'sha256',
                ]);

                return new SignedMtlsClient($cfg);
            },
        ],
    ],
];
```

**3. Resolve the client** where you need it:

```php
$client = \Yii::$container->get(\Mihod\PaymentGateway\SignedMtlsClient::class);
$response = $client->sendSignedGet($url, $query);
```

Or register a named component and use `$this->paymentGateway` in controllers if you wrap it in a thin service class.

---

### Any framework (summary)

1. Ensure certificate paths and `HMAC_SECRET` are available to PHP (env, vault, or config).
2. Build **`ClientConfiguration::fromArray([...])`** with keys exactly as in `.env.example` (or map your names to those keys).
3. Instantiate **`new SignedMtlsClient($config)`** once per request or as a **singleton** shared across the app.
4. Call **`sendSignedGet($url, $query)`**; handle **`SignedHttpResponse`**, **`HttpResponseException`**, and Guzzle exceptions.

Signing uses a **canonical query string**: keys sorted, values cast to string, `http_build_query(..., PHP_QUERY_RFC3986)`. Set `SIGNATURE_HEADER_NAME` to `Authorization` if your API expects that header instead of `X-Signature`.

## Errors

- Non-2xx HTTP responses throw `Mihod\PaymentGateway\Exception\HttpResponseException` (includes status code and body).
- Invalid paths or secrets throw `Mihod\PaymentGateway\Exception\InvalidConfigurationException`.
- Transport failures propagate Guzzle exceptions.

## Tests

```bash
composer install
composer test:unit     # no certificates required
```

**Integration test / `php try.php` (real mTLS to BadSSL):**

BadSSL ships **one** PEM file (certificate + encrypted private key). Use the **same path** for `MTLS_CLIENT_CERT` and `MTLS_CLIENT_KEY`, passphrase `badssl.com`.

```bash
composer run setup:badssl   # downloads PEM + creates .env from .env.example if .env is missing
composer test:integration
php try.php
```

If you already have a `.env`, the script does not overwrite it — merge the printed `MTLS_*` lines manually, or remove `.env` and run `setup:badssl` again.

- **Unit** tests cover HMAC canonicalization and signing (no network).
- **Integration** test calls `https://client.badssl.com/` when `.env` is valid; otherwise skipped.

## Quality

**Single command (recommended for CI or before you push):**

```bash
composer quality       # PHPCS → PHPStan → PHPUnit → Deptrac → PHPMD (src + tests)
```

**Full suite including PhpMetrics** (slower; HTML report under `var/phpmetrics/`):

```bash
composer quality:all
```

**Individual checks:**

```bash
composer cs-check      # PHPCS PSR-12 (parallel, sniff codes)
composer analyse       # PHPStan level 8
composer deptrac       # architectural layers
composer phpmd         # PHPMD on src/
composer phpmd:tests   # PHPMD on tests/ (separate ruleset)
composer metrics       # PhpMetrics HTML → var/phpmetrics/
composer test:coverage # line + HTML coverage (needs Xdebug: xdebug.mode=coverage)
```

Tooling config lives next to `composer.json` (`phpcs.xml.dist`, `phpstan.neon`, `phpmd.xml`, `phpmd-tests.xml`, `deptrac.yaml`, `phpunit.xml`). Reports under `var/` and `coverage/` are gitignored.

Coverage report is written to `coverage/html/index.html`. Requires the **Xdebug** extension with coverage enabled (`php -d xdebug.mode=coverage` is set in the `test:coverage` script). **PCOV** is an alternative if you install `pcov` and use `php -d pcov.enabled=1` instead.

The test suite targets **100% line, method, and class coverage** for executable code under `src/` (interfaces have no executable lines). `EnvironmentLoader` keeps a defensive `file_get_contents === false` branch wrapped in `// @codeCoverageIgnoreStart/End` because PHP 8.2+ may return an empty string for a directory path instead of `false`; the `is_file()` check handles that case.

## Test data providers

External datasets live under `tests/DataProviders/` and are wired with `#[DataProviderExternal(ClassName::class, 'methodName')]`:

| Provider | Used by |
|----------|---------|
| `HmacSignerDataProvider` | `HmacSignerTest` — canonical query strings and HMAC digests |
| `ClientConfigurationDataProvider` | `ClientConfigurationTest::testFromArrayThrowsInvalidConfiguration` — invalid `fromArray` cases |
| `GuzzleClientFactoryDataProvider` | `GuzzleClientFactoryTest::testCreateClientAppliesSslOptions` — verify / CA / passphrase |

Tests that need **instance-only** temp files (`$this->certFile` in `setUp()`), **one-off** setup (`chmod` on a temp file), or a **single** assertion stay as plain test methods (YAGNI).

## Specification

The package implements mTLS client transport, HMAC-signed GET over a canonical query string, `.env`-style configuration, PSR-4 layout, and unit plus integration tests.
