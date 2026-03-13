<?php

// Force test environment variables BEFORE Laravel boots.
// Docker injects DB_CONNECTION=mysql via OS env vars which override phpunit.xml.
// putenv() overrides getenv() at the process level, so Docker can't win.
$testEnv = [
    'APP_ENV' => 'testing',
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => ':memory:',
    'CACHE_STORE' => 'array',
    'SESSION_DRIVER' => 'array',
    'QUEUE_CONNECTION' => 'sync',
    'MAIL_MAILER' => 'array',
    'BCRYPT_ROUNDS' => '4',
    'APP_LOCALE' => 'en',
];

foreach ($testEnv as $key => $value) {
    putenv("$key=$value");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

require __DIR__.'/../vendor/autoload.php';
