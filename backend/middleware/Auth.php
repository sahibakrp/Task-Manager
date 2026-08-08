<?php

$config = require __DIR__ . '/../config/jwt.php';

if (!function_exists('base64url_encode')) {
    function base64url_encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}

if (!function_exists('base64url_decode')) {
    function base64url_decode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}

if (!function_exists('jwt_encode')) {
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
}

if (!function_exists('jwt_decode')) {
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
}

if (!function_exists('get_bearer_token')) {
    function get_bearer_token(): ?string
    {
        $auth = null;

        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $auth = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $auth = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        } elseif (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $keys = array_change_key_case($headers, CASE_UPPER);
            if (isset($keys['AUTHORIZATION'])) {
                $auth = $keys['AUTHORIZATION'];
            } elseif (isset($keys['HTTP_AUTHORIZATION'])) {
                $auth = $keys['HTTP_AUTHORIZATION'];
            }
        }

        if ($auth === null) {
            return null;
        }

        if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
            return $matches[1];
        }

        return null;
    }
}

if (!function_exists('getAuthenticatedUser')) {
    function getAuthenticatedUser(): ?array
    {
        $token = get_bearer_token();
        if ($token === null) {
            return null;
        }

        $payload = jwt_decode($token);
        if ($payload === null) {
            return null;
        }

        return $payload;
    }
}
