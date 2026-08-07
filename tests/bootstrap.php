<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

$_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'test';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
