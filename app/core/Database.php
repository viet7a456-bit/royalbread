<?php

declare(strict_types=1);

class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $config = require ROOT_PATH . '/app/config/database.php';
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['dbname'],
            $config['charset']
        );

        try {
            self::$connection = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            // Log to project-level file for easy debugging
            $logDir = ROOT_PATH . '/tmp/logs';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            $logFile = $logDir . '/db_errors.log';
            $logEntry = sprintf(
                "[%s] DB Connection Failed: %s | DSN: %s\n",
                date('Y-m-d H:i:s'),
                $e->getMessage(),
                preg_replace('/password=\S+/', 'password=***', $dsn)
            );
            @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

            // Also log via PHP's built-in error_log (if configured)
            error_log('RoyalBread DB Connection Failed: ' . $e->getMessage());

            // Show the user-friendly maintenance page
            http_response_code(503);
            $errorViewPath = ROOT_PATH . '/app/views/errors/db_error.php';
            if (file_exists($errorViewPath)) {
                require $errorViewPath;
            } else {
                echo '<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><title>Bảo trì</title></head>';
                echo '<body style="display:flex;align-items:center;justify-content:center;min-height:100vh;font-family:sans-serif;background:#1a1412;color:#f5efe9;">';
                echo '<div style="text-align:center"><h1>Hệ thống đang bảo trì</h1><p>Vui lòng thử lại sau hoặc gọi Hotline: 0879 866 636</p></div>';
                echo '</body></html>';
            }
            exit;
        }

        return self::$connection;
    }
}
