<?php

declare(strict_types=1);

final class Environment
{
    private static bool $loaded = false;

    private static array $loadedFiles = [];

    public static function bootstrap(string $rootPath): void
    {
        if (self::$loaded) {
            return;
        }

        $rootPath = rtrim(str_replace('\\', '/', $rootPath), '/');

        foreach (self::candidateFiles($rootPath) as $filePath) {
            self::loadFile($filePath);
        }

        self::$loaded = true;
    }

    public static function isLocalRequest(): bool
    {
        $host = self::currentHost();

        if ($host === '' || $host === 'localhost' || $host === '127.0.0.1' || $host === '::1') {
            return true;
        }

        if (str_ends_with($host, '.local') || str_ends_with($host, '.test')) {
            return true;
        }

        if (preg_match('/^127\.\d+\.\d+\.\d+$/', $host) === 1) {
            return true;
        }

        $serverAddr = trim((string) ($_SERVER['SERVER_ADDR'] ?? ''));
        if ($serverAddr !== '' && ($serverAddr === '127.0.0.1' || $serverAddr === '::1')) {
            return true;
        }

        return false;
    }

    public static function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }

        if (trim((string) ($_SERVER['REQUEST_SCHEME'] ?? '')) === 'https') {
            return true;
        }

        if (trim((string) ($_SERVER['SERVER_PORT'] ?? '')) === '443') {
            return true;
        }

        if (strtolower(trim((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))) === 'https') {
            return true;
        }

        $cloudflareVisitor = trim((string) ($_SERVER['HTTP_CF_VISITOR'] ?? ''));
        if ($cloudflareVisitor !== '' && str_contains(strtolower($cloudflareVisitor), 'https')) {
            return true;
        }

        return false;
    }

    public static function currentHost(): string
    {
        $host = trim((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
        if ($host === '') {
            return '';
        }

        $host = strtolower($host);
        $colonPosition = strpos($host, ':');
        if ($colonPosition !== false) {
            $host = substr($host, 0, $colonPosition);
        }

        return $host;
    }

    public static function get(string $key, string $default = ''): string
    {
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

        return $default;
    }

    public static function loadedFiles(): array
    {
        return self::$loadedFiles;
    }

    private static function candidateFiles(string $rootPath): array
    {
        $baseFile = $rootPath . '/.env';
        $specificFile = self::isLocalRequest()
            ? $rootPath . '/.env.local'
            : $rootPath . '/.env.hosting';

        return [$baseFile, $specificFile];
    }

    private static function loadFile(string $filePath): void
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            return;
        }

        $content = file_get_contents($filePath);
        if ($content === false || $content === '') {
            return;
        }

        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $content = str_replace('\n', "\n", $content);

        foreach (explode("\n", $content) as $envLine) {
            $envLine = trim($envLine);
            if ($envLine === '' || str_starts_with($envLine, '#')) {
                continue;
            }

            $eqPos = strpos($envLine, '=');
            if ($eqPos === false) {
                continue;
            }

            $key = trim(substr($envLine, 0, $eqPos));
            $value = trim(substr($envLine, $eqPos + 1));

            if (
                strlen($value) >= 2
                && (($value[0] === '"' && $value[-1] === '"') || ($value[0] === "'" && $value[-1] === "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            if ($key === '') {
                continue;
            }

            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        self::$loadedFiles[] = $filePath;
    }
}
