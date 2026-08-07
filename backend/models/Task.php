<?php

class Task
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

        return $storageDir . '/tasks.json';
    }

    private static function readStorage(): array
    {
        $path = self::getStoragePath();
        if (!file_exists($path)) {
            file_put_contents($path, json_encode(['tasks' => [], 'nextId' => 1], JSON_PRETTY_PRINT));
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (!is_array($data)) {
            $data = ['tasks' => [], 'nextId' => 1];
        }

        $data['tasks'] = $data['tasks'] ?? [];
        $data['nextId'] = max(1, (int) ($data['nextId'] ?? 1));

        return $data;
    }

    private static function writeStorage(array $data): void
    {
        file_put_contents(self::getStoragePath(), json_encode($data, JSON_PRETTY_PRINT));
    }

    private static function findTaskById(array $tasks, int $id): ?array
    {
        foreach ($tasks as $task) {
            if ((int) ($task['id'] ?? 0) === $id) {
                return $task;
            }
        }

        return null;
    }

    private static function findTaskIndexById(array $tasks, int $id): ?int
    {
        foreach ($tasks as $index => $task) {
            if ((int) ($task['id'] ?? 0) === $id) {
                return $index;
            }
        }

        return null;
    }

    public static function listTasks(?string $status = null, int $page = 1, int $limit = 10): array
    {
        $pdo = self::getConnection();
        if ($pdo === null) {
            $storage = self::readStorage();
            $tasks = $storage['tasks'];

            if ($status !== null && $status !== '') {
                $tasks = array_values(array_filter($tasks, static fn ($task) => ($task['status'] ?? 'todo') === $status));
            }

            $total = count($tasks);
            $offset = ($page - 1) * $limit;
            $pagedTasks = array_slice($tasks, $offset, $limit);

            return [
                'data' => $pagedTasks,
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
            ];
        }

        $offset = ($page - 1) * $limit;
        $where = [];
        $params = [];

        if ($status !== null && $status !== '') {
            $where[] = 'status = :status';
            $params[':status'] = $status;
        }

        $sql = 'SELECT * FROM tasks';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY created_at DESC LIMIT :limit OFFSET :offset';

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $countSql = 'SELECT COUNT(*) AS total FROM tasks';
        if ($where) {
            $countSql .= ' WHERE ' . implode(' AND ', $where);
        }

        $countStmt = $pdo->prepare($countSql);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();

        return [
            'data' => $stmt->fetchAll(),
            'page' => $page,
            'limit' => $limit,
            'total' => (int) $countStmt->fetchColumn(),
        ];
    }

    public static function createTask(array $data): array
    {
        $pdo = self::getConnection();
        if ($pdo === null) {
            $storage = self::readStorage();
            $task = [
                'id' => $storage['nextId'],
                'title' => trim((string) ($data['title'] ?? '')),
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? 'todo',
                'priority' => $data['priority'] ?? 'medium',
                'due_date' => $data['due_date'] ?? null,
                'user_id' => isset($data['user_id']) ? (int) $data['user_id'] : 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $storage['tasks'][] = $task;
            $storage['nextId']++;
            self::writeStorage($storage);
            return $task;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO tasks (title, description, status, priority, due_date, user_id) VALUES (?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $data['title'] ?? '',
            $data['description'] ?? null,
            $data['status'] ?? 'todo',
            $data['priority'] ?? 'medium',
            $data['due_date'] ?? null,
            isset($data['user_id']) ? (int) $data['user_id'] : 1,
        ]);

        $taskId = (int) $pdo->lastInsertId();
        return self::findById($taskId);
    }

    public static function updateTask(int $id, array $data): array
    {
        $pdo = self::getConnection();
        if ($pdo === null) {
            $storage = self::readStorage();
            $index = self::findTaskIndexById($storage['tasks'], $id);
            if ($index === null) {
                throw new RuntimeException('Task not found', 404);
            }

            foreach (['title', 'description', 'status', 'priority', 'due_date'] as $field) {
                if (array_key_exists($field, $data)) {
                    $storage['tasks'][$index][$field] = $data[$field];
                }
            }

            $storage['tasks'][$index]['updated_at'] = date('Y-m-d H:i:s');
            self::writeStorage($storage);
            return $storage['tasks'][$index];
        }

        $fields = [];
        $values = [];

        foreach (['title', 'description', 'status', 'priority', 'due_date'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                $values[] = $data[$field];
            }
        }

        if ($fields === []) {
            return self::findById($id);
        }

        $values[] = $id;
        $stmt = $pdo->prepare('UPDATE tasks SET ' . implode(', ', $fields) . ' WHERE id = ?');
        $stmt->execute($values);

        return self::findById($id);
    }

    public static function deleteTask(int $id): bool
    {
        $pdo = self::getConnection();
        if ($pdo === null) {
            $storage = self::readStorage();
            $index = self::findTaskIndexById($storage['tasks'], $id);
            if ($index === null) {
                return false;
            }

            array_splice($storage['tasks'], $index, 1);
            self::writeStorage($storage);
            return true;
        }

        $stmt = $pdo->prepare('DELETE FROM tasks WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->rowCount() > 0;
    }

    public static function findById(int $id): array
    {
        $pdo = self::getConnection();
        if ($pdo === null) {
            $storage = self::readStorage();
            $task = self::findTaskById($storage['tasks'], $id);
            if ($task === null) {
                throw new RuntimeException('Task not found', 404);
            }
            return $task;
        }

        $stmt = $pdo->prepare('SELECT * FROM tasks WHERE id = ?');
        $stmt->execute([$id]);
        $task = $stmt->fetch();

        if (!$task) {
            throw new RuntimeException('Task not found', 404);
        }

        return $task;
    }
}
