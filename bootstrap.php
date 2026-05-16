<?php

declare(strict_types=1);

// Basic PSR-4-ish autoloader for App\ namespace
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Initialize logger to ./logs
if (file_exists(__DIR__ . '/logs')) {
    App\Utilities\Logger::init(__DIR__ . '/logs');
}

// Start session safely
App\Utilities\Session::start();
