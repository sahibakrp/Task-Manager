<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../middleware/Auth.php';

function handleUserRoutes(string $method, array $segments): array
{
    $currentUser = getAuthenticatedUser();
    $isAdmin = isset($currentUser['role_id']) && (int) $currentUser['role_id'] === 1;

    if ($method === 'GET' && count($segments) === 1 && $segments[0] === 'users') {
        if ($currentUser === null) {
            return ['status' => 401, 'body' => ['message' => 'Authentication required']];
        }

        if (!$isAdmin) {
            return ['status' => 403, 'body' => ['message' => 'Forbidden']];
        }

        return ['status' => 200, 'body' => User::listUsers()];
    }

    if ($method === 'GET' && count($segments) === 2 && $segments[0] === 'users') {
        if ($currentUser === null) {
            return ['status' => 401, 'body' => ['message' => 'Authentication required']];
        }

        if (!$isAdmin) {
            return ['status' => 403, 'body' => ['message' => 'Forbidden']];
        }

        $id = (int) $segments[1];
        $user = User::findById($id);

        if ($user === null) {
            return ['status' => 404, 'body' => ['message' => 'User not found']];
        }

        return ['status' => 200, 'body' => $user];
    }

    return ['status' => 404, 'body' => ['message' => 'Route not found']];
}
