<?php

declare(strict_types=1);

/**
 * Manual smoke test against https://client.badssl.com/
 *
 * composer run setup:badssl   # downloads PEM; creates .env from .env.example if missing
 * php try.php                 # expect HTTP 200 and HTML mentioning client.badssl.com
 */

require __DIR__ . '/vendor/autoload.php';

use Mihod\PaymentGateway\Exception\InvalidConfigurationException;
use Mihod\PaymentGateway\SignedMtlsClient;

$envFile = __DIR__ . '/.env';

if (! is_readable($envFile)) {
    fwrite(STDERR, "Missing .env — run: composer run setup:badssl\n");
    fwrite(STDERR, "(creates .env from .env.example and downloads BadSSL certs if .env is absent)\n");
    exit(1);
}

try {
    $client = SignedMtlsClient::fromEnvFile($envFile);
} catch (InvalidConfigurationException $e) {
    fwrite(STDERR, "Configuration error: {$e->getMessage()}\n\n");
    fwrite(STDERR, "Fix your .env:\n");
    fwrite(STDERR, "  - Run: composer run setup:badssl\n");
    fwrite(STDERR, "  - Put the printed paths into .env (BadSSL uses the SAME path for CERT and KEY).\n");
    fwrite(STDERR, "  - Passphrase for that demo key: badssl.com\n");
    exit(1);
}

$response = $client->sendSignedGet('https://client.badssl.com/', [
    'transaction_id' => '12345',
    'amount' => '99.99',
    'currency' => 'USD',
]);

echo $response->statusCode() . "\n";
echo $response->body();
