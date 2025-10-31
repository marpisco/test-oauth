<?php
/**
 * Router for PHP Built-in Server
 * This file is only needed when using PHP's built-in web server
 * When using Apache with Laragon, .htaccess handles routing automatically
 */

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Serve static files directly
$file = __DIR__ . $requestUri;
if (file_exists($file) && is_file($file)) {
    return false; // Serve the requested file as-is
}

// Route all other requests through index.php
require __DIR__ . '/index.php';
