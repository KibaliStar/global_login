<?php
require_once __DIR__ . '/../config/config.php';

echo "=== DEBUG ===\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'NOT SET') . "\n";
echo "REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'NOT SET') . "\n";
echo "Routes defined: \n";

$request = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($request, '?') !== false) {
    $request = substr($request, 0, strpos($request, '?'));
}
$method = strtoupper($_SERVER['REQUEST_METHOD']);

echo "Cleaned request: '$request'\n";

$routes = [
    'GET' => ['/', '/health', '/test-db', '/debug.php'],
    'POST' => ['/register', '/login']
];

if (isset($routes[$method]) && in_array($request, $routes[$method])) {
    echo "Route FOUND\n";
} else {
    echo "Route NOT FOUND - Available: " . implode(', ', $routes[$method] ?? []) . "\n";
}