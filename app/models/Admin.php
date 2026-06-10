<?php

declare(strict_types=1);

class Admin
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM admins WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        $admin = $stmt->fetch();

        return $admin ?: null;
    }
}
