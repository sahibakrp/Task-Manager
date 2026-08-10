<?php

declare(strict_types=1);

namespace App\Config;

use PDO;
use PDOException;
use RuntimeException;
use Throwable;

final class Database
{
    private static ?PDO $connection = null;

    private const REQUIRED_TABLES = [
        'roles',
        'users',
        'tasks',
    ];

    public static function getConnection(): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1';
        $port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306';
        $database = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: '';
        $username = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: '';
        $password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '';
        $environment = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'development';

        if ($database === '') {
            throw new RuntimeException('DB_NAME is not configured');
        }

        if ($username === '') {
            throw new RuntimeException('DB_USER is not configured');
        }

        try {
            if ($environment !== 'production') {
                self::ensureDatabaseExists(
                    $host,
                    $port,
                    $database,
                    $username,
                    $password
                );
            }

            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $host,
                $port,
                $database
            );

            self::$connection = new PDO(
                $dsn,
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_STRINGIFY_FETCHES => false,
                    PDO::ATTR_TIMEOUT => 5,
                ]
            );

            self::ensureSchema(self::$connection);

            return self::$connection;
        } catch (PDOException $e) {
            error_log(
                'Database connection failed: ' . $e->getMessage()
            );

            throw new RuntimeException(
                'Database connection failed'
            );
        }
    }

    private static function ensureDatabaseExists(
        string $host,
        string $port,
        string $database,
        string $username,
        string $password
    ): void {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;charset=utf8mb4',
                $host,
                $port
            );

            $pdo = new PDO(
                $dsn,
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 5,
                ]
            );

            if (!preg_match('/^[a-zA-Z0-9_]+$/', $database)) {
                throw new RuntimeException(
                    'Invalid database name'
                );
            }

            $pdo->exec(
                "CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
            );
        } catch (PDOException $e) {
            error_log(
                'Database creation failed: ' . $e->getMessage()
            );

            throw new RuntimeException(
                'Unable to create database'
            );
        }
    }

    private static function ensureSchema(PDO $pdo): void
    {
        $missingTables = self::getMissingTables($pdo);

        if (empty($missingTables)) {
            self::ensureDefaultRoles($pdo);
            return;
        }

        $schemaPath =
            dirname(__DIR__) .
            DIRECTORY_SEPARATOR .
            'database' .
            DIRECTORY_SEPARATOR .
            'schema.sql';

        if (!is_file($schemaPath)) {
            throw new RuntimeException(
                'Database schema file not found: ' . $schemaPath
            );
        }

        if (!is_readable($schemaPath)) {
            throw new RuntimeException(
                'Database schema file is not readable'
            );
        }

        $schema = file_get_contents($schemaPath);

        if ($schema === false) {
            throw new RuntimeException(
                'Unable to read database schema'
            );
        }

        $schema = trim($schema);

        if ($schema === '') {
            throw new RuntimeException(
                'Database schema is empty'
            );
        }

        try {
            self::executeSchema($pdo, $schema);
            self::verifyRequiredTables($pdo);
            self::ensureDefaultRoles($pdo);
        } catch (Throwable $e) {
            error_log(
                'Database schema initialization failed: ' .
                $e->getMessage()
            );

            throw new RuntimeException(
                'Database schema initialization failed'
            );
        }
    }

    private static function executeSchema(
        PDO $pdo,
        string $schema
    ): void {
        $schema = preg_replace('/^\xEF\xBB\xBF/', '', $schema);

        if ($schema === null) {
            throw new RuntimeException(
                'Unable to process database schema'
            );
        }

        $statements = self::splitSqlStatements($schema);

        if (empty($statements)) {
            throw new RuntimeException(
                'No SQL statements found in schema'
            );
        }

        foreach ($statements as $statement) {
            $statement = trim($statement);

            if ($statement === '') {
                continue;
            }

            $pdo->exec($statement);
        }
    }

    private static function splitSqlStatements(
        string $sql
    ): array {
        $statements = [];
        $current = '';
        $length = strlen($sql);

        $inSingleQuote = false;
        $inDoubleQuote = false;
        $inBacktick = false;
        $inLineComment = false;
        $inBlockComment = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $next = $i + 1 < $length ? $sql[$i + 1] : '';

            if ($inLineComment) {
                $current .= $char;

                if ($char === "\n") {
                    $inLineComment = false;
                }

                continue;
            }

            if ($inBlockComment) {
                if ($char === '*' && $next === '/') {
                    $current .= '*/';
                    $i++;
                    $inBlockComment = false;
                } else {
                    $current .= $char;
                }

                continue;
            }

            if (
                !$inSingleQuote &&
                !$inDoubleQuote &&
                !$inBacktick
            ) {
                if ($char === '#' || ($char === '-' && $next === '-')) {
                    $inLineComment = true;

                    if ($char === '-' && $next === '-') {
                        $current .= '--';
                        $i++;
                    } else {
                        $current .= '#';
                    }

                    continue;
                }

                if ($char === '/' && $next === '*') {
                    $inBlockComment = true;
                    $current .= '/*';
                    $i++;
                    continue;
                }
            }

            if ($char === "'" && !$inDoubleQuote && !$inBacktick) {
                if ($inSingleQuote && $next === "'") {
                    $current .= "''";
                    $i++;
                    continue;
                }

                $inSingleQuote = !$inSingleQuote;
                $current .= $char;
                continue;
            }

            if ($char === '"' && !$inSingleQuote && !$inBacktick) {
                if ($inDoubleQuote && $next === '"') {
                    $current .= '""';
                    $i++;
                    continue;
                }

                $inDoubleQuote = !$inDoubleQuote;
                $current .= $char;
                continue;
            }

            if ($char === '`' && !$inSingleQuote && !$inDoubleQuote) {
                $inBacktick = !$inBacktick;
                $current .= $char;
                continue;
            }

            if (
                $char === ';' &&
                !$inSingleQuote &&
                !$inDoubleQuote &&
                !$inBacktick
            ) {
                $statement = trim($current);

                if ($statement !== '') {
                    $statements[] = $statement;
                }

                $current = '';
                continue;
            }

            $current .= $char;
        }

        $statement = trim($current);

        if ($statement !== '') {
            $statements[] = $statement;
        }

        return $statements;
    }

    private static function getMissingTables(PDO $pdo): array
    {
        $missingTables = [];

        $statement = $pdo->prepare(
            "
            SELECT COUNT(*)
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
            AND table_name = :table
            "
        );

        foreach (self::REQUIRED_TABLES as $table) {
            $statement->execute([
                'table' => $table,
            ]);

            $exists = (int) $statement->fetchColumn();

            if ($exists === 0) {
                $missingTables[] = $table;
            }
        }

        return $missingTables;
    }

    private static function verifyRequiredTables(PDO $pdo): void
    {
        $missingTables = self::getMissingTables($pdo);

        if (!empty($missingTables)) {
            throw new RuntimeException(
                'Required tables missing: ' .
                implode(', ', $missingTables)
            );
        }
    }

    private static function ensureDefaultRoles(PDO $pdo): void
    {
        $statement = $pdo->prepare(
            "
            INSERT INTO roles (id, name)
            VALUES
                (1, 'Admin'),
                (2, 'User')
            ON DUPLICATE KEY UPDATE
                name = VALUES(name)
            "
        );

        $statement->execute();
    }

    public static function ping(): bool
    {
        try {
            $pdo = self::getConnection();

            $statement = $pdo->query('SELECT 1');

            return (int) $statement->fetchColumn() === 1;
        } catch (Throwable $e) {
            error_log(
                'Database ping failed: ' .
                $e->getMessage()
            );

            return false;
        }
    }

    public static function getDatabaseStatus(): array
    {
        try {
            $pdo = self::getConnection();

            $tables = [];

            foreach (self::REQUIRED_TABLES as $table) {
                $statement = $pdo->prepare(
                    "
                    SELECT COUNT(*)
                    FROM information_schema.tables
                    WHERE table_schema = DATABASE()
                    AND table_name = :table
                    "
                );

                $statement->execute([
                    'table' => $table,
                ]);

                $tables[$table] =
                    (int) $statement->fetchColumn() > 0;
            }

            return [
                'connected' => true,
                'database' => $pdo->query('SELECT DATABASE()')->fetchColumn(),
                'tables' => $tables,
                'schema_ready' => !in_array(false, $tables, true),
            ];
        } catch (Throwable $e) {
            return [
                'connected' => false,
                'database' => null,
                'tables' => [],
                'schema_ready' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function __clone()
    {
    }
}