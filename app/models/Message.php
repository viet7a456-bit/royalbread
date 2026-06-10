<?php

declare(strict_types=1);

class Message
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function create(array $data): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO messages (customer_name, phone, contact_time, subject, message, status)
             VALUES (:customer_name, :phone, :contact_time, :subject, :message, :status)'
        );

        $stmt->execute([
            'customer_name' => trim($data['customer_name']),
            'phone' => trim($data['phone']),
            'contact_time' => trim($data['contact_time']),
            'subject' => trim((string) ($data['subject'] ?? '')),
            'message' => trim($data['message']),
            'status' => 'new',
        ]);
    }

    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM messages ORDER BY created_at DESC');
        return $stmt->fetchAll();
    }

    public function latest(int $limit = 6): array
    {
        $stmt = $this->db->prepare('SELECT * FROM messages ORDER BY created_at DESC LIMIT :item_limit');
        $stmt->bindValue(':item_limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function countAll(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM messages')->fetchColumn();
    }

    public function countNew(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM messages WHERE status = 'new'");
        return (int) $stmt->fetchColumn();
    }
}
