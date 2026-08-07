<?php

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestedPath = __DIR__ . '/' . ltrim($uri, '/');

if ($uri !== '/' && is_file($requestedPath)) {
    return false;
}

require __DIR__ . '/index.php';
