<?php

declare(strict_types=1);

$autoload = dirname(__DIR__) . '/vendor/autoload.php';

if (! is_file($autoload)) {
    throw new RuntimeException('Run composer install before running tests.');
}

require $autoload;
