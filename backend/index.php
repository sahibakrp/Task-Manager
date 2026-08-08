<?php

header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/routes/tasks.php';
require_once __DIR__ . '/routes/auth.php';
require_once __DIR__ . '/routes/users.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$basePath = dirname($scriptName);
$basePath = '/' . trim($basePath, '/');

if ($basePath !== '/' && $basePath !== '' && str_starts_with($uri, $basePath)) {
    $uri = substr($uri, strlen($basePath));
}

$uri = '/' . ltrim($uri, '/');
$segments = array_values(array_filter(explode('/', trim($uri, '/'))));

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && count($segments) === 0) {
        $result = [
            'status' => 200,
            'body' => [
                'message' => 'Task Manager API',
                'routes' => [
                    'GET /tasks',
                    'GET /tasks/:id',
                    'GET /users',
                    'GET /users/:id',
                    'POST /tasks',
                    'PUT /tasks/:id',
                    'DELETE /tasks/:id',
                    'POST /register',
                    'POST /login',
                    'POST /logout'
                ]
            ]
        ];
    } else {
        // try auth routes first
        $result = handleAuthRoutes($_SERVER['REQUEST_METHOD'], $segments);
        if ($result['status'] === 404) {
            $result = handleUserRoutes($_SERVER['REQUEST_METHOD'], $segments);
        }
        if ($result['status'] === 404) {
            $result = handleTaskRoutes($_SERVER['REQUEST_METHOD'], $segments);
        }
    }

    http_response_code($result['status']);
    echo json_encode($result['body']);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['message' => $e->getMessage()]);
} catch (RuntimeException $e) {
    $status = $e->getCode() >= 400 ? $e->getCode() : 500;
    http_response_code($status);
    echo json_encode(['message' => $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Server error: ' . $e->getMessage()]);
}
