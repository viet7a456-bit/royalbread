<?php

declare(strict_types=1);

$env = static function (string $key): string {
    $value = getenv($key);
    if ($value !== false && $value !== '') {
        return (string) $value;
    }

    if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
        return (string) $_ENV[$key];
    }

    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
        return (string) $_SERVER[$key];
    }

    return '';
};

$readEnvFile = static function (string $key): string {
    $envPath = dirname(__DIR__, 2) . '/.env';
    if (!is_file($envPath) || !is_readable($envPath)) {
        return '';
    }

    $content = file_get_contents($envPath);
    if ($content === false || $content === '') {
        return '';
    }

    $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
    $content = str_replace(["\r\n", "\r"], "\n", $content);
    $content = str_replace('\n', "\n", $content);

    foreach (explode("\n", $content) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $eqPos = strpos($line, '=');
        if ($eqPos === false) {
            continue;
        }

        $envKey = trim(substr($line, 0, $eqPos));
        $envValue = trim(substr($line, $eqPos + 1));

        if (strlen($envValue) >= 2 && (($envValue[0] === '"' && $envValue[-1] === '"') || ($envValue[0] === "'" && $envValue[-1] === "'"))) {
            $envValue = substr($envValue, 1, -1);
        }

        if ($envKey === $key) {
            return $envValue;
        }
    }

    return '';
};

$envHost = $env('ROYALBREAD_DB_HOST');
$envName = $env('ROYALBREAD_DB_NAME');
$envUser = $env('ROYALBREAD_DB_USER');
$envPassword = $env('ROYALBREAD_DB_PASSWORD');
$envCharset = $env('ROYALBREAD_DB_CHARSET');

if ($envHost === '') {
    $envHost = $readEnvFile('ROYALBREAD_DB_HOST');
}
if ($envName === '') {
    $envName = $readEnvFile('ROYALBREAD_DB_NAME');
}
if ($envUser === '') {
    $envUser = $readEnvFile('ROYALBREAD_DB_USER');
}
if ($envPassword === '') {
    $envPassword = $readEnvFile('ROYALBREAD_DB_PASSWORD');
}
if ($envCharset === '') {
    $envCharset = $readEnvFile('ROYALBREAD_DB_CHARSET');
}

// 1. If environment variables are set (from .env or server config), use them
if ($envHost !== '') {
    return [
        'host' => $envHost,
        'dbname' => $envName,
        'username' => $envUser,
        'password' => $envPassword,
        'charset' => $envCharset !== '' ? $envCharset : 'utf8mb4',
    ];
}

// 2. Check for local override config file (not tracked in git)
$localConfigPath = __DIR__ . '/database.local.php';
if (file_exists($localConfigPath)) {
    return require $localConfigPath;
}

// 3. Default fallback for local XAMPP development
return [
    'host' => '127.0.0.1',
    'dbname' => 'royalbread_db',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
];
