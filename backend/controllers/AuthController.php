<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../middleware/Auth.php';

class AuthController
{
    public static function register(array $payload): array
    {
        $name = trim((string) ($payload['name'] ?? ''));
        $email = trim((string) ($payload['email'] ?? ''));
        $password = $payload['password'] ?? '';

        if ($name === '' || $email === '' || $password === '') {
            throw new InvalidArgumentException('Name, email and password are required.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email address.');
        }

        if (User::findByEmail($email) !== null) {
            throw new InvalidArgumentException('Email already registered.');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $hash,
            'role_id' => $payload['role_id'] ?? 2,
        ]);

        unset($user['password']);
        return $user;
    }

    public static function login(array $payload): array
    {
        $email = trim((string) ($payload['email'] ?? ''));
        $password = $payload['password'] ?? '';

        if ($email === '' || $password === '') {
            throw new InvalidArgumentException('Email and password are required.');
        }

        $user = User::findByEmail($email);
        if ($user === null) {
            throw new InvalidArgumentException('Invalid credentials.');
        }

        $storedHash = $user['password'] ?? ($user['pass'] ?? null);
        if ($storedHash === null || !password_verify($password, $storedHash)) {
            throw new InvalidArgumentException('Invalid credentials.');
        }

        // create token payload
        $payload = [
            'sub' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role_id' => $user['role_id'] ?? 2,
        ];

        $token = jwt_encode($payload);

        unset($user['password']);
        return ['token' => $token, 'user' => $user];
    }

    public static function logout(): array
    {
        // Stateless JWT: client should discard token. Optionally implement blacklist.
        return ['message' => 'Logged out'];
    }
}
