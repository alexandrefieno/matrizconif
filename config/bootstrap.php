<?php
declare(strict_types=1);

use Dotenv\Dotenv;
use MatrizConif\Infrastructure\Database;

$root = dirname(__DIR__);

require $root . '/vendor/autoload.php';

Dotenv::createImmutable($root)->safeLoad();

date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'America/Sao_Paulo');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => filter_var($_ENV['SESSION_SECURE'] ?? true, FILTER_VALIDATE_BOOL),
        'samesite' => 'Lax',
    ]);
    session_start();
}

return Database::fromEnvironment($_ENV);
