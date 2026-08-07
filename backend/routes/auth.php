<?php

require_once __DIR__ . '/../controllers/AuthController.php';

function handleAuthRoutes(string $method, array $segments): array
{
    // POST /register
    if ($method === 'POST' && count($segments) === 1 && $segments[0] === 'register') {
        $rawBody = trim((string) file_get_contents('php://input'));
        if ($rawBody === '' && !empty($_POST)) {
            $rawBody = json_encode($_POST);
        }

        $payload = [];
        if ($rawBody !== '') {
            $decoded = json_decode($rawBody, true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        return ['status' => 201, 'body' => AuthController::register($payload)];
    }

    // POST /login
    if ($method === 'POST' && count($segments) === 1 && $segments[0] === 'login') {
        $rawBody = trim((string) file_get_contents('php://input'));
        if ($rawBody === '' && !empty($_POST)) {
            $rawBody = json_encode($_POST);
        }

        $payload = [];
        if ($rawBody !== '') {
            $decoded = json_decode($rawBody, true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        return ['status' => 200, 'body' => AuthController::login($payload)];
    }

    // POST /logout
    if ($method === 'POST' && count($segments) === 1 && $segments[0] === 'logout') {
        return ['status' => 200, 'body' => AuthController::logout()];
    }

    return ['status' => 404, 'body' => ['message' => 'Route not found']];
}
