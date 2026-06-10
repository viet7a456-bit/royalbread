<?php

declare(strict_types=1);

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

define('ROOT_PATH', __DIR__);

// Load .env file if present.
$envFilePath = ROOT_PATH . '/.env';
if (is_file($envFilePath) && is_readable($envFilePath)) {
    $envContent = file_get_contents($envFilePath);
    if ($envContent !== false) {
        // Remove UTF-8 BOM if the host file manager saved the file with BOM.
        $envContent = preg_replace('/^\xEF\xBB\xBF/', '', $envContent) ?? $envContent;

        // Some web file managers paste "\n" as plain text. Normalize it here.
        $envContent = str_replace(["\r\n", "\r"], "\n", $envContent);
        $envContent = str_replace('\n', "\n", $envContent);

        foreach (explode("\n", $envContent) as $envLine) {
            $envLine = trim($envLine);
            // Skip comments and lines without =
            if ($envLine === '' || str_starts_with($envLine, '#')) {
                continue;
            }
            $eqPos = strpos($envLine, '=');
            if ($eqPos === false) {
                continue;
            }
            $key = trim(substr($envLine, 0, $eqPos));
            $value = trim(substr($envLine, $eqPos + 1));
            // Remove surrounding quotes if present
            if (strlen($value) >= 2 && (($value[0] === '"' && $value[-1] === '"') || ($value[0] === "'" && $value[-1] === "'"))) {
                $value = substr($value, 1, -1);
            }
            if ($key !== '' && getenv($key) === false) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

$appConfig = require ROOT_PATH . '/app/config/app.php';
date_default_timezone_set($appConfig['timezone']);

spl_autoload_register(function (string $class): void {
    $directories = [
        ROOT_PATH . '/app/core/',
        ROOT_PATH . '/app/controllers/',
        ROOT_PATH . '/app/models/',
    ];

    foreach ($directories as $directory) {
        $path = $directory . $class . '.php';
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

require_once ROOT_PATH . '/app/helpers.php';
Schema::ensure();

$app = new App();
$app->run();
