<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/core/Environment.php';
Environment::bootstrap(dirname(__DIR__, 2));

$envHost = Environment::get('ROYALBREAD_DB_HOST');
$envName = Environment::get('ROYALBREAD_DB_NAME');
$envUser = Environment::get('ROYALBREAD_DB_USER');
$envPassword = Environment::get('ROYALBREAD_DB_PASSWORD');
$envCharset = Environment::get('ROYALBREAD_DB_CHARSET');

$hasAnyEnvConfig = $envHost !== '' || $envName !== '' || $envUser !== '' || $envPassword !== '' || $envCharset !== '';
$hasRequiredEnvConfig = $envHost !== '' && $envName !== '' && $envUser !== '';

if ($hasRequiredEnvConfig) {
    return [
        'host' => $envHost,
        'dbname' => $envName,
        'username' => $envUser,
        'password' => $envPassword,
        'charset' => $envCharset !== '' ? $envCharset : 'utf8mb4',
    ];
}

$localConfigPath = __DIR__ . '/database.local.php';
if (Environment::isLocalRequest() && file_exists($localConfigPath)) {
    return require $localConfigPath;
}

if (Environment::isLocalRequest()) {
    return [
        'host' => '127.0.0.1',
        'dbname' => 'royalbread_db',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ];
}

if ($hasAnyEnvConfig) {
    throw new RuntimeException(
        'Cau hinh database tren hosting chua day du. Vui long kiem tra lai ROYALBREAD_DB_HOST, ROYALBREAD_DB_NAME, ROYALBREAD_DB_USER va ROYALBREAD_DB_PASSWORD trong file .env hoac .env.hosting.'
    );
}

throw new RuntimeException(
    'Chua tim thay cau hinh database cho hosting. Hay tao file .env hoac .env.hosting trong thu muc goc du an.'
);
