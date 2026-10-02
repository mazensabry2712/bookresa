<?php

// PHPUnit's <env> entries are reliable for the test runner, but the hosting
// environment can still export production variables through the process.
// Set both getenv()/$_ENV values before the Laravel application is created.
foreach ([
    'APP_ENV' => 'testing',
    'APP_CONFIG_CACHE' => 'bootstrap/cache/config-testing.php',
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => ':memory:',
    'DB_URL' => '',
    'CACHE_STORE' => 'array',
    'SESSION_DRIVER' => 'array',
    'QUEUE_CONNECTION' => 'sync',
    'MAIL_MAILER' => 'array',
] as $key => $value) {
    putenv($key.'='.$value);
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

require dirname(__DIR__).'/vendor/autoload.php';
