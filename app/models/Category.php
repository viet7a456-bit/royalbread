<?php

declare(strict_types=1);

class Category
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM categories ORDER BY sort_order ASC, name ASC');
        return $stmt->fetchAll();
    }

    public function countAll(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM categories')->fetchColumn();
    }
}
