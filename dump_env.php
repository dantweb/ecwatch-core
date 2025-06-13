<?php

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->loadEnv(__DIR__ . '/.env.local');

foreach ($_SERVER as $key => $value) {
    if (str_starts_with($key, 'APP_')) {
        echo $key.'='.$value.PHP_EOL;
    }
}