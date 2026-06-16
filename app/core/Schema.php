<?php

declare(strict_types=1);

class Schema
{
    private const TARGET_VERSION = '2026-06-account-features-columns';
    private static bool $bootstrapped = false;

    public static function ensure(): void
    {
        if (self::$bootstrapped) {
            return;
        }

        self::$bootstrapped = true;
        $db = Database::connection();

        self::runMigrationSafely('settings_table', fn() => self::ensureSettingsTable($db));
        self::runMigrationSafely('customer_favorites_table', fn() => self::ensureCustomerFavoritesTable($db));
        self::runMigrationSafely('customer_favorites_columns', fn() => self::ensureCustomerFavoritesColumns($db));
        self::runMigrationSafely('live_chat_threads_table', fn() => self::ensureLiveChatThreadsTable($db));
        self::runMigrationSafely('live_chat_threads_columns', fn() => self::ensureLiveChatThreadsColumns($db));
        self::runMigrationSafely('live_chat_messages_table', fn() => self::ensureLiveChatMessagesTable($db));
        self::runMigrationSafely('live_chat_messages_columns', fn() => self::ensureLiveChatMessagesColumns($db));
        self::runMigrationSafely('product_reviews_table', fn() => self::ensureProductReviewsTable($db));
        self::runMigrationSafely('product_reviews_columns', fn() => self::ensureProductReviewsColumns($db));

        $currentVersion = self::currentVersionSafely($db);
        if ($currentVersion === self::TARGET_VERSION) {
            return;
        }

        $legacyMigrationsSucceeded = true;
        $legacyMigrationsSucceeded = self::runMigrationSafely('orders_columns', fn() => self::ensureOrdersColumns($db))
            && $legacyMigrationsSucceeded;
        $legacyMigrationsSucceeded = self::runMigrationSafely('promotions_table', fn() => self::ensurePromotionsTable($db))
            && $legacyMigrationsSucceeded;
        $legacyMigrationsSucceeded = self::runMigrationSafely('promotion_columns', fn() => self::ensurePromotionColumns($db))
            && $legacyMigrationsSucceeded;
        $legacyMigrationsSucceeded = self::runMigrationSafely('default_settings', fn() => self::ensureDefaultSettings($db))
            && $legacyMigrationsSucceeded;

        if ($legacyMigrationsSucceeded) {
            self::runMigrationSafely('schema_version_write', fn() => self::setVersion($db, self::TARGET_VERSION));
        }
    }

    private static function currentVersionSafely(PDO $db): string
    {
        try {
            return self::currentVersion($db);
        } catch (Throwable $error) {
            self::logMigrationError('schema_version_read', $error);
            return '';
        }
    }

    private static function runMigrationSafely(string $step, callable $callback): bool
    {
        try {
            $callback();
            return true;
        } catch (Throwable $error) {
            self::logMigrationError($step, $error);
            return false;
        }
    }

    private static function logMigrationError(string $step, Throwable $error): void
    {
        $logDir = ROOT_PATH . '/tmp/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $entry = sprintf(
            "[%s] Schema step failed [%s]: %s\n%s\n",
            date('Y-m-d H:i:s'),
            $step,
            $error->getMessage(),
            $error->getTraceAsString()
        );
        @file_put_contents($logDir . '/schema_errors.log', $entry, FILE_APPEND | LOCK_EX);
        error_log('RoyalBread schema step failed [' . $step . ']: ' . $error->getMessage());
    }

    private static function ensureCustomerFavoritesTable(PDO $db): void
    {
        $db->exec(
            'CREATE TABLE IF NOT EXISTS customer_favorites (
                id int(10) unsigned NOT NULL AUTO_INCREMENT,
                customer_id int(10) unsigned NOT NULL,
                menu_item_id int(10) unsigned NOT NULL,
                created_at timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (id),
                UNIQUE KEY uniq_customer_favorite (customer_id, menu_item_id),
                KEY idx_customer_favorites_customer_created (customer_id, created_at),
                KEY fk_customer_favorites_item (menu_item_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    private static function ensureCustomerFavoritesColumns(PDO $db): void
    {
        self::ensureColumn(
            $db,
            'customer_favorites',
            'customer_id',
            'ALTER TABLE customer_favorites ADD COLUMN customer_id int(10) unsigned NOT NULL'
        );
        self::ensureColumn(
            $db,
            'customer_favorites',
            'menu_item_id',
            'ALTER TABLE customer_favorites ADD COLUMN menu_item_id int(10) unsigned NOT NULL'
        );
        self::ensureColumn(
            $db,
            'customer_favorites',
            'created_at',
            'ALTER TABLE customer_favorites ADD COLUMN created_at timestamp NOT NULL DEFAULT current_timestamp()'
        );
    }

    private static function ensureLiveChatMessagesTable(PDO $db): void
    {
        $db->exec(
            'CREATE TABLE IF NOT EXISTS live_chat_messages (
                id int(10) unsigned NOT NULL AUTO_INCREMENT,
                thread_id int(10) unsigned NOT NULL,
                sender_type varchar(20) NOT NULL,
                sender_id int(10) unsigned DEFAULT NULL,
                sender_name varchar(150) NOT NULL,
                message text NOT NULL,
                is_read tinyint(1) NOT NULL DEFAULT 0,
                created_at timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (id),
                KEY idx_live_chat_messages_thread_created (thread_id, created_at),
                KEY idx_live_chat_messages_thread_read (thread_id, is_read)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    private static function ensureLiveChatMessagesColumns(PDO $db): void
    {
        self::ensureColumn(
            $db,
            'live_chat_messages',
            'thread_id',
            'ALTER TABLE live_chat_messages ADD COLUMN thread_id int(10) unsigned NOT NULL'
        );
        self::ensureColumn(
            $db,
            'live_chat_messages',
            'sender_type',
            'ALTER TABLE live_chat_messages ADD COLUMN sender_type varchar(20) NOT NULL'
        );
        self::ensureColumn(
            $db,
            'live_chat_messages',
            'sender_id',
            'ALTER TABLE live_chat_messages ADD COLUMN sender_id int(10) unsigned DEFAULT NULL'
        );
        self::ensureColumn(
            $db,
            'live_chat_messages',
            'sender_name',
            'ALTER TABLE live_chat_messages ADD COLUMN sender_name varchar(150) NOT NULL'
        );
        self::ensureColumn(
            $db,
            'live_chat_messages',
            'message',
            'ALTER TABLE live_chat_messages ADD COLUMN message text NOT NULL'
        );
        self::ensureColumn(
            $db,
            'live_chat_messages',
            'is_read',
            'ALTER TABLE live_chat_messages ADD COLUMN is_read tinyint(1) NOT NULL DEFAULT 0'
        );
        self::ensureColumn(
            $db,
            'live_chat_messages',
            'created_at',
            'ALTER TABLE live_chat_messages ADD COLUMN created_at timestamp NOT NULL DEFAULT current_timestamp()'
        );
    }

    private static function ensureLiveChatThreadsTable(PDO $db): void
    {
        $db->exec(
            'CREATE TABLE IF NOT EXISTS live_chat_threads (
                id int(10) unsigned NOT NULL AUTO_INCREMENT,
                customer_id int(10) unsigned NOT NULL,
                status varchar(20) NOT NULL DEFAULT "open",
                last_message_at timestamp NOT NULL DEFAULT current_timestamp(),
                created_at timestamp NOT NULL DEFAULT current_timestamp(),
                updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (id),
                KEY idx_live_chat_threads_status_updated (status, updated_at),
                KEY fk_live_chat_threads_customer (customer_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    private static function ensureLiveChatThreadsColumns(PDO $db): void
    {
        self::ensureColumn(
            $db,
            'live_chat_threads',
            'customer_id',
            'ALTER TABLE live_chat_threads ADD COLUMN customer_id int(10) unsigned NOT NULL'
        );
        self::ensureColumn(
            $db,
            'live_chat_threads',
            'status',
            'ALTER TABLE live_chat_threads ADD COLUMN status varchar(20) NOT NULL DEFAULT "open"'
        );
        self::ensureColumn(
            $db,
            'live_chat_threads',
            'last_message_at',
            'ALTER TABLE live_chat_threads ADD COLUMN last_message_at timestamp NOT NULL DEFAULT current_timestamp()'
        );
        self::ensureColumn(
            $db,
            'live_chat_threads',
            'created_at',
            'ALTER TABLE live_chat_threads ADD COLUMN created_at timestamp NOT NULL DEFAULT current_timestamp()'
        );
        self::ensureColumn(
            $db,
            'live_chat_threads',
            'updated_at',
            'ALTER TABLE live_chat_threads ADD COLUMN updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()'
        );
    }

    private static function ensureProductReviewsTable(PDO $db): void
    {
        $db->exec(
            'CREATE TABLE IF NOT EXISTS product_reviews (
                id int(10) unsigned NOT NULL AUTO_INCREMENT,
                customer_id int(10) unsigned NOT NULL,
                menu_item_id int(10) unsigned NOT NULL,
                order_id int(10) unsigned DEFAULT NULL,
                rating tinyint(3) unsigned NOT NULL,
                review_title varchar(150) NOT NULL DEFAULT "",
                review_comment text NOT NULL,
                status varchar(20) NOT NULL DEFAULT "pending",
                created_at timestamp NOT NULL DEFAULT current_timestamp(),
                updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (id),
                UNIQUE KEY uniq_product_review_customer_item (customer_id, menu_item_id),
                KEY idx_product_reviews_item_status (menu_item_id, status),
                KEY fk_product_reviews_order (order_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    private static function ensureProductReviewsColumns(PDO $db): void
    {
        self::ensureColumn(
            $db,
            'product_reviews',
            'customer_id',
            'ALTER TABLE product_reviews ADD COLUMN customer_id int(10) unsigned NOT NULL'
        );
        self::ensureColumn(
            $db,
            'product_reviews',
            'menu_item_id',
            'ALTER TABLE product_reviews ADD COLUMN menu_item_id int(10) unsigned NOT NULL'
        );
        self::ensureColumn(
            $db,
            'product_reviews',
            'order_id',
            'ALTER TABLE product_reviews ADD COLUMN order_id int(10) unsigned DEFAULT NULL'
        );
        self::ensureColumn(
            $db,
            'product_reviews',
            'rating',
            'ALTER TABLE product_reviews ADD COLUMN rating tinyint(3) unsigned NOT NULL'
        );
        self::ensureColumn(
            $db,
            'product_reviews',
            'review_title',
            'ALTER TABLE product_reviews ADD COLUMN review_title varchar(150) NOT NULL DEFAULT ""'
        );
        self::ensureColumn(
            $db,
            'product_reviews',
            'review_comment',
            'ALTER TABLE product_reviews ADD COLUMN review_comment text NOT NULL'
        );
        self::ensureColumn(
            $db,
            'product_reviews',
            'status',
            'ALTER TABLE product_reviews ADD COLUMN status varchar(20) NOT NULL DEFAULT "pending"'
        );
        self::ensureColumn(
            $db,
            'product_reviews',
            'created_at',
            'ALTER TABLE product_reviews ADD COLUMN created_at timestamp NOT NULL DEFAULT current_timestamp()'
        );
        self::ensureColumn(
            $db,
            'product_reviews',
            'updated_at',
            'ALTER TABLE product_reviews ADD COLUMN updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()'
        );
    }

    private static function ensureSettingsTable(PDO $db): void
    {
        $db->exec(
            'CREATE TABLE IF NOT EXISTS settings (
                setting_key varchar(100) NOT NULL PRIMARY KEY,
                setting_value text NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    private static function currentVersion(PDO $db): string
    {
        $stmt = $db->prepare('SELECT setting_value FROM settings WHERE setting_key = :key LIMIT 1');
        $stmt->execute(['key' => 'schema_version']);
        $value = $stmt->fetchColumn();

        return is_string($value) ? $value : '';
    }

    private static function setVersion(PDO $db, string $version): void
    {
        $stmt = $db->prepare(
            'INSERT INTO settings (setting_key, setting_value)
             VALUES (:setting_key, :setting_value)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        $stmt->execute([
            'setting_key' => 'schema_version',
            'setting_value' => $version,
        ]);
    }

    private static function ensureOrdersColumns(PDO $db): void
    {
        self::ensureColumn(
            $db,
            'orders',
            'payment_status',
            "ALTER TABLE orders ADD COLUMN payment_status varchar(30) NOT NULL DEFAULT 'unpaid'"
        );
        self::ensureColumn(
            $db,
            'orders',
            'payment_reference',
            'ALTER TABLE orders ADD COLUMN payment_reference varchar(120) DEFAULT NULL'
        );
        self::ensureColumn(
            $db,
            'orders',
            'payment_confirmed_at',
            'ALTER TABLE orders ADD COLUMN payment_confirmed_at datetime DEFAULT NULL'
        );
        self::ensureColumn(
            $db,
            'orders',
            'discount_amount',
            'ALTER TABLE orders ADD COLUMN discount_amount int(11) NOT NULL DEFAULT 0'
        );
    }

    private static function ensurePromotionsTable(PDO $db): void
    {
        $db->exec(
            'CREATE TABLE IF NOT EXISTS promotions (
                id int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
                title varchar(255) NOT NULL,
                content text NOT NULL,
                target_tier varchar(50) NOT NULL DEFAULT "all",
                discount_percent int(11) NOT NULL DEFAULT 0,
                discount_amount int(11) NOT NULL DEFAULT 0,
                coupon_code varchar(50) DEFAULT NULL,
                expires_at datetime DEFAULT NULL,
                is_active tinyint(1) NOT NULL DEFAULT 1,
                created_at timestamp NOT NULL DEFAULT current_timestamp()
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    private static function ensurePromotionColumns(PDO $db): void
    {
        self::ensureColumn(
            $db,
            'promotions',
            'discount_percent',
            'ALTER TABLE promotions ADD COLUMN discount_percent int(11) NOT NULL DEFAULT 0'
        );
        self::ensureColumn(
            $db,
            'promotions',
            'discount_amount',
            'ALTER TABLE promotions ADD COLUMN discount_amount int(11) NOT NULL DEFAULT 0'
        );
        self::ensureColumn(
            $db,
            'promotions',
            'coupon_code',
            'ALTER TABLE promotions ADD COLUMN coupon_code varchar(50) DEFAULT NULL'
        );
        self::ensureColumn(
            $db,
            'promotions',
            'expires_at',
            'ALTER TABLE promotions ADD COLUMN expires_at datetime DEFAULT NULL'
        );
        self::ensureColumn(
            $db,
            'promotions',
            'is_active',
            'ALTER TABLE promotions ADD COLUMN is_active tinyint(1) NOT NULL DEFAULT 1'
        );
    }

    private static function ensureDefaultSettings(PDO $db): void
    {
        $defaults = [
            'bank_bin' => 'mbbank',
            'seo_default_keywords' => 'RoyalBread, banh mi chao, banh mi Hai Duong, do an sang, combo RoyalBread',
        ];

        $stmt = $db->prepare(
            'INSERT INTO settings (setting_key, setting_value)
             VALUES (:setting_key, :setting_value)
             ON DUPLICATE KEY UPDATE setting_value = setting_value'
        );

        foreach ($defaults as $key => $value) {
            $stmt->execute([
                'setting_key' => $key,
                'setting_value' => $value,
            ]);
        }
    }

    private static function ensureColumn(PDO $db, string $table, string $column, string $statement): void
    {
        // Check if the table exists first to avoid SQL errors when the table is missing.
        // We use query() because some MariaDB/MySQL versions do not support parameters in SHOW TABLES/COLUMNS.
        $tableCheck = $db->query("SHOW TABLES LIKE '" . $table . "'");
        if ($tableCheck->fetch() === false) {
            throw new Exception("Bảng '{$table}' không tồn tại trong cơ sở dữ liệu. Vui lòng import file database/royalbread.sql.");
        }

        $check = $db->query("SHOW COLUMNS FROM `{$table}` LIKE '" . $column . "'");
        if ($check->fetch() !== false) {
            return;
        }

        $db->exec($statement);
    }
}
