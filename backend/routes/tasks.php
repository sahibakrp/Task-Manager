<?php

require_once __DIR__ . '/../controllers/TaskController.php';

function parseJsonPayload(): array
{
    $rawBody = trim((string) file_get_contents('php://input'));
    if ($rawBody === '' && !empty($_POST)) {
        $rawBody = json_encode($_POST);
    }

    if ($rawBody === '' && isset($_SERVER['CONTENT_LENGTH']) && (int) $_SERVER['CONTENT_LENGTH'] > 0) {
        $rawBody = trim((string) file_get_contents('php://stdin'));
    }

    if ($rawBody === '') {
        return [];
    }

    $payload = json_decode($rawBody, true);
    if (is_array($payload)) {
        return $payload;
    }

    if (str_starts_with($rawBody, '{') && str_ends_with($rawBody, '}')) {
        $inner = substr($rawBody, 1, -1);
        $pairs = explode(',', $inner);
        $parsedBody = [];

        foreach ($pairs as $pair) {
            $pair = trim($pair);
            if ($pair === '') {
                continue;
            }

            $parts = explode(':', $pair, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $key = trim($parts[0]);
            $value = trim($parts[1]);
            $value = trim($value, "\"'");
            $parsedBody[$key] = $value;
        }

        if ($parsedBody !== []) {
            return $parsedBody;
        }
    }

    parse_str($rawBody, $parsedBody);
    return is_array($parsedBody) ? $parsedBody : [];
}

function handleTaskRoutes(string $method, array $segments): array
{
    if ($method === 'GET' && count($segments) === 1 && $segments[0] === 'tasks') {
        return ['status' => 200, 'body' => TaskController::list($_GET)];
    }

    if ($method === 'POST' && count($segments) === 1 && $segments[0] === 'tasks') {
        $payload = parseJsonPayload();
        return ['status' => 201, 'body' => TaskController::create($payload)];
    }

    if ($method === 'PUT' && count($segments) === 2 && $segments[0] === 'tasks') {
        $id = (int) $segments[1];
        $payload = parseJsonPayload();
        return ['status' => 200, 'body' => TaskController::update($id, $payload)];
    }

    if ($method === 'DELETE' && count($segments) === 2 && $segments[0] === 'tasks') {
        $id = (int) $segments[1];
        $deleted = TaskController::delete($id);
        return ['status' => ($deleted ? 200 : 404), 'body' => ['deleted' => $deleted]];
    }

    return ['status' => 404, 'body' => ['message' => 'Route not found']];
}
