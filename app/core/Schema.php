<?php

declare(strict_types=1);

class Schema
{
    private const TARGET_VERSION = '2026-06-seo-payments-promotions';
    private static bool $bootstrapped = false;

    public static function ensure(): void
    {
        if (self::$bootstrapped) {
            return;
        }

        self::$bootstrapped = true;
        $db = Database::connection();

        self::ensureSettingsTable($db);
        $currentVersion = self::currentVersion($db);
        if ($currentVersion === self::TARGET_VERSION) {
            return;
        }

        self::ensureOrdersColumns($db);
        self::ensurePromotionsTable($db);
        self::ensurePromotionColumns($db);
        self::ensureDefaultSettings($db);
        self::setVersion($db, self::TARGET_VERSION);
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
        $check = $db->prepare('SHOW COLUMNS FROM `' . $table . '` LIKE :column_name');
        $check->execute(['column_name' => $column]);
        if ($check->fetch() !== false) {
            return;
        }

        $db->exec($statement);
    }
}
