<?php

require_once __DIR__ . '/../models/Task.php';

class TaskController
{
    public static function list(array $query, ?array $currentUser = null): array
    {
        $status = isset($query['status']) ? trim((string) $query['status']) : null;
        $page = max(1, (int) ($query['page'] ?? 1));
        $limit = max(1, min(100, (int) ($query['limit'] ?? 10)));

        $userId = null;
        if (is_array($currentUser) && isset($currentUser['sub'])) {
            $userId = (int) $currentUser['sub'];
            // if admin, don't filter by user
            if (isset($currentUser['role_id']) && (int) $currentUser['role_id'] === 1) {
                $userId = null;
            }
        }

        return Task::listTasks($status, $page, $limit, $userId);
    }

    public static function create(array $payload): array
    {
        $errors = self::validatePayload($payload, true);
        if ($errors !== []) {
            throw new InvalidArgumentException($errors[0]);
        }

        return Task::createTask($payload);
    }

    public static function update(int $id, array $payload): array
    {
        $errors = self::validatePayload($payload, false);
        if ($errors !== []) {
            throw new InvalidArgumentException($errors[0]);
        }

        return Task::updateTask($id, $payload);
    }

    public static function delete(int $id): bool
    {
        return Task::deleteTask($id);
    }

    private static function validatePayload(array $payload, bool $isCreate): array
    {
        $errors = [];

        if ($isCreate && empty(trim((string) ($payload['title'] ?? '')))) {
            $errors[] = 'Title is required.';
        }

        if (isset($payload['status']) && !in_array($payload['status'], ['todo', 'in-progress', 'done'], true)) {
            $errors[] = 'Status must be one of: todo, in-progress, done.';
        }

        if (isset($payload['priority']) && !in_array($payload['priority'], ['low', 'medium', 'high'], true)) {
            $errors[] = 'Priority must be one of: low, medium, high.';
        }

        if (isset($payload['due_date']) && $payload['due_date'] !== null && $payload['due_date'] !== '') {
            $date = DateTimeImmutable::createFromFormat('Y-m-d', (string) $payload['due_date']);
            $errorsForDate = DateTimeImmutable::getLastErrors();
            if ($date === false || ($errorsForDate !== false && ($errorsForDate['warning_count'] > 0 || $errorsForDate['error_count'] > 0))) {
                $errors[] = 'Due date must be a valid date in YYYY-MM-DD format.';
            }
        }

        if (isset($payload['user_id']) && !ctype_digit((string) $payload['user_id'])) {
            $errors[] = 'User id must be an integer.';
        }

        return $errors;
    }
}
