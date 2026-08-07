<?php

class User
{
    private static function getConnection(): ?PDO
    {
        static $pdo = null;

        if ($pdo instanceof PDO) {
            return $pdo;
        }

        $config = require __DIR__ . '/../config/db.php';
        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $pdo = new PDO($dsn, $config['user'], $config['pass'], $options);
        } catch (PDOException $e) {
            return null;
        }

        return $pdo;
    }

    private static function getStoragePath(): string
    {
        $storageDir = __DIR__ . '/../storage';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0777, true);
        }

        return $storageDir . '/users.json';
    }

    private static function readStorage(): array
    {
        $path = self::getStoragePath();
        if (!file_exists($path)) {
            file_put_contents($path, json_encode(['users' => [], 'nextId' => 1], JSON_PRETTY_PRINT));
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (!is_array($data)) {
            $data = ['users' => [], 'nextId' => 1];
        }

        $data['users'] = $data['users'] ?? [];
        $data['nextId'] = max(1, (int) ($data['nextId'] ?? 1));

        return $data;
    }

    private static function writeStorage(array $data): void
    {
        file_put_contents(self::getStoragePath(), json_encode($data, JSON_PRETTY_PRINT));
    }

    public static function create(array $data): array
    {
        $pdo = self::getConnection();
        if ($pdo === null) {
            $storage = self::readStorage();
            $user = [
                'id' => $storage['nextId'],
                'name' => $data['name'] ?? '',
                'email' => $data['email'] ?? '',
                'password' => $data['password'] ?? '',
                'role_id' => $data['role_id'] ?? 2,
                'created_at' => date('Y-m-d H:i:s'),
            ];
            $storage['users'][] = $user;
            $storage['nextId']++;
            self::writeStorage($storage);
            return $user;
        }

        $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role_id) VALUES (?, ?, ?, ?)');
        $stmt->execute([
            $data['name'] ?? '',
            $data['email'] ?? '',
            $data['password'] ?? '',
            $data['role_id'] ?? 2,
        ]);

        $id = (int) $pdo->lastInsertId();
        $stmt = $pdo->prepare('SELECT id, name, email, role_id, created_at FROM users WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function findByEmail(string $email): ?array
    {
        $pdo = self::getConnection();
        if ($pdo === null) {
            $storage = self::readStorage();
            foreach ($storage['users'] as $user) {
                if (strtolower($user['email']) === strtolower($email)) {
                    return $user;
                }
            }
            return null;
        }

        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function findById(int $id): ?array
    {
        $pdo = self::getConnection();
        if ($pdo === null) {
            $storage = self::readStorage();
            foreach ($storage['users'] as $user) {
                if ((int) ($user['id'] ?? 0) === $id) {
                    return $user;
                }
            }
            return null;
        }

        $stmt = $pdo->prepare('SELECT id, name, email, role_id, created_at FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }
}
