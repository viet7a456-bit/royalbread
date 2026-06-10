<?php

declare(strict_types=1);

class Setting
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function all(): array
    {
        $stmt = $this->db->query('SELECT setting_key, setting_value FROM settings');
        $settings = [];

        foreach ($stmt->fetchAll() as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        return $settings;
    }

    public function updateMany(array $settings): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO settings (setting_key, setting_value)
             VALUES (:setting_key, :setting_value)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );

        foreach ($settings as $key => $value) {
            $stmt->execute([
                'setting_key' => $key,
                'setting_value' => trim((string) $value),
            ]);
        }
    }
}
