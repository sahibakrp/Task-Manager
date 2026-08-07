<?php

$config = require __DIR__ . '/../config/jwt.php';

function base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode(string $data): string
{
    $remainder = strlen($data) % 4;
    if ($remainder) {
        $data .= str_repeat('=', 4 - $remainder);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}

function jwt_encode(array $payload): string
{
    global $config;
    $header = ['alg' => $config['algo'], 'typ' => 'JWT'];
    $payload['iat'] = time();
    if (!isset($payload['exp'])) {
        $payload['exp'] = time() + $config['expires_in'];
    }

    $segments = [];
    $segments[] = base64url_encode(json_encode($header));
    $segments[] = base64url_encode(json_encode($payload));

    $signing_input = implode('.', $segments);
    $sig = hash_hmac('sha256', $signing_input, $config['secret'], true);
    $segments[] = base64url_encode($sig);

    return implode('.', $segments);
}

function jwt_decode(string $token): ?array
{
    global $config;
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return null;
    }

    [$headerB64, $payloadB64, $sigB64] = $parts;
    $header = json_decode(base64url_decode($headerB64), true);
    $payload = json_decode(base64url_decode($payloadB64), true);
    $sig = base64url_decode($sigB64);

    if (!is_array($header) || !is_array($payload) || $sig === false) {
        return null;
    }

    $signing_input = $headerB64 . '.' . $payloadB64;
    $expected = hash_hmac('sha256', $signing_input, $config['secret'], true);
    if (!hash_equals($expected, $sig)) {
        return null;
    }

    if (isset($payload['exp']) && time() > (int) $payload['exp']) {
        return null;
    }

    return $payload;
}

function get_bearer_token(): ?string
{
    $headers = $_SERVER;
    $auth = $headers['HTTP_AUTHORIZATION'] ?? $headers['REDIRECT_HTTP_AUTHORIZATION'] ?? null;
    if ($auth === null) {
        return null;
    }

    if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
        return $matches[1];
    }

    return null;
}
