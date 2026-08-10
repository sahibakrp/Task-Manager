<?php

declare(strict_types=1);

use App\Config\Database;
use Dotenv\Dotenv;
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/db.php';

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();



$appEnv = $_ENV['APP_ENV'] ?? 'development';

if ($appEnv === 'production') {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');

$allowedOrigins = array_filter(
    array_map(
        'trim',
        explode(
            ',',
            $_ENV['ALLOWED_ORIGINS'] ?? 'http://localhost:5173'
        )
    )
);

$requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (
    $requestOrigin !== '' &&
    in_array($requestOrigin, $allowedOrigins, true)
) {
    header("Access-Control-Allow-Origin: {$requestOrigin}");
    header('Vary: Origin');
}

header(
    'Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS'
);

header(
    'Access-Control-Allow-Headers: Content-Type, Authorization, X-Request-ID'
);

header('Access-Control-Max-Age: 86400');

if (
    ($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS'
) {
    http_response_code(204);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$requestId = $_SERVER['HTTP_X_REQUEST_ID'] ?? '';

if ($requestId === '') {
    $requestId = bin2hex(random_bytes(16));
}

header("X-Request-ID: {$requestId}");

$method = strtoupper(
    $_SERVER['REQUEST_METHOD'] ?? 'GET'
);

$uri = parse_url(
    $_SERVER['REQUEST_URI'] ?? '/',
    PHP_URL_PATH
);

if (!is_string($uri) || $uri === '') {
    $uri = '/';
}

$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$basePath = dirname($scriptName);

$basePath = '/' . trim(
    str_replace('\\', '/', $basePath),
    '/'
);

if (
    $basePath !== '/' &&
    $basePath !== '' &&
    str_starts_with($uri, $basePath)
) {
    $uri = substr(
        $uri,
        strlen($basePath)
    );
}

$uri = '/' . ltrim($uri, '/');

$uri = preg_replace(
    '#/+#',
    '/',
    $uri
) ?: '/';

if (strlen($uri) > 2048) {
    http_response_code(414);

    echo json_encode(
        [
            'success' => false,
            'message' => 'Request URI is too long',
            'request_id' => $requestId
        ],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );

    exit;
}

$trimmedUri = trim($uri, '/');

$segments = $trimmedUri === ''
    ? []
    : array_values(
        array_filter(
            explode('/', $trimmedUri),
            static fn ($segment) => $segment !== ''
        )
    );

function sendJsonResponse(
    int $status,
    array $body
): never {
    http_response_code($status);

    echo json_encode(
        $body,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );

    exit;
}

try {
    if (
        $method === 'GET' &&
        count($segments) === 0
    ) {
        sendJsonResponse(
            200,
            [
                'success' => true,
                'message' => 'Task Manager API',
                'version' => '1.0.0',
                'status' => 'ok',
                'environment' => $appEnv,
                'request_id' => $requestId,
                'routes' => [
                    'GET /',
                    'GET /db/health',
                    'GET /tasks',
                    'GET /tasks/:id',
                    'POST /tasks',
                    'PUT /tasks/:id',
                    'DELETE /tasks/:id',
                    'GET /users',
                    'GET /users/:id',
                    'POST /register',
                    'POST /login',
                    'POST /logout'
                ]
            ]
        );
    }

    if (
        $method === 'GET' &&
        count($segments) === 1 &&
        $segments[0] === 'health'
    ) {
        $db = Database::getConnection();

        $statement = $db->query('SELECT 1');
        $databaseCheck = $statement->fetchColumn();

        if ((int) $databaseCheck !== 1) {
            sendJsonResponse(
                503,
                [
                    'success' => false,
                    'status' => 'unhealthy',
                    'service' => 'task-manager-api',
                    'database' => 'disconnected',
                    'request_id' => $requestId
                ]
            );
        }

        sendJsonResponse(
            200,
            [
                'success' => true,
                'status' => 'ok',
                'service' => 'task-manager-api',
                'version' => '1.0.0',
                'environment' => $appEnv,
                'database' => 'connected',
                'schema' => 'verified',
                'timestamp' => gmdate('c'),
                'request_id' => $requestId
            ]
        );
    }

    if (
        $method === 'GET' &&
        count($segments) === 2 &&
        $segments[0] === 'health' &&
        $segments[1] === 'db'
    ) {
        $db = Database::getConnection();

        $statement = $db->query('SELECT 1');
        $databaseCheck = $statement->fetchColumn();

        if ((int) $databaseCheck !== 1) {
            sendJsonResponse(
                503,
                [
                    'success' => false,
                    'status' => 'unhealthy',
                    'database' => 'disconnected',
                    'request_id' => $requestId
                ]
            );
        }

        sendJsonResponse(
            200,
            [
                'success' => true,
                'status' => 'ok',
                'database' => 'connected',
                'schema' => 'verified',
                'timestamp' => gmdate('c'),
                'request_id' => $requestId
            ]
        );
    }

    require_once __DIR__ . '/routes/auth.php';

    $result = handleAuthRoutes(
        $method,
        $segments
    );

    if (
        !is_array($result) ||
        ($result['status'] ?? 404) === 404
    ) {
        require_once __DIR__ . '/routes/users.php';

        $result = handleUserRoutes(
            $method,
            $segments
        );
    }

    if (
        !is_array($result) ||
        ($result['status'] ?? 404) === 404
    ) {
        require_once __DIR__ . '/routes/tasks.php';

        $result = handleTaskRoutes(
            $method,
            $segments
        );
    }

    if (
        !is_array($result) ||
        !isset($result['status']) ||
        !isset($result['body'])
    ) {
        sendJsonResponse(
            500,
            [
                'success' => false,
                'message' => 'Invalid route handler response',
                'request_id' => $requestId
            ]
        );
    }

    $status = (int) $result['status'];

    $body = is_array($result['body'])
        ? $result['body']
        : [
            'success' => false,
            'message' => 'Invalid API response'
        ];

    if (!isset($body['request_id'])) {
        $body['request_id'] = $requestId;
    }

    sendJsonResponse(
        $status,
        $body
    );

} catch (InvalidArgumentException $e) {
    error_log(
        "[{$requestId}] Validation error: " .
        $e->getMessage()
    );

    sendJsonResponse(
        400,
        [
            'success' => false,
            'message' => $e->getMessage(),
            'request_id' => $requestId
        ]
    );

} catch (RuntimeException $e) {
    $status = (int) $e->getCode();

    if ($status < 400 || $status > 599) {
        $status = 500;
    }

    error_log(
        "[{$requestId}] Runtime error: " .
        $e->getMessage()
    );

    $message =
        $status === 500 && $appEnv === 'production'
            ? 'Internal server error'
            : $e->getMessage();

    sendJsonResponse(
        $status,
        [
            'success' => false,
            'message' => $message,
            'request_id' => $requestId
        ]
    );

} catch (Throwable $e) {
    error_log(
        "[{$requestId}] Unhandled error: " .
        $e->getMessage() .
        ' in ' .
        $e->getFile() .
        ':' .
        $e->getLine()
    );

    $message =
        $appEnv === 'production'
            ? 'Internal server error'
            : $e->getMessage();

    sendJsonResponse(
        500,
        [
            'success' => false,
            'message' => $message,
            'request_id' => $requestId
        ]
    );
}