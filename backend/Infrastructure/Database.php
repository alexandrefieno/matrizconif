<?php
declare(strict_types=1);

namespace MatrizConif\Infrastructure;

use PDO;
use RuntimeException;

final class Database
{
    public static function fromEnvironment(array $env): PDO
    {
        foreach (['DB_HOST','DB_PORT','DB_DATABASE','DB_USERNAME','DB_PASSWORD'] as $key) {
            if (!array_key_exists($key, $env)) {
                throw new RuntimeException("Variável obrigatória ausente: {$key}");
            }
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $env['DB_HOST'],
            $env['DB_PORT'],
            $env['DB_DATABASE']
        );

        return new PDO($dsn, $env['DB_USERNAME'], $env['DB_PASSWORD'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}
