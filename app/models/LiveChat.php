<?php

declare(strict_types=1);

class LiveChat
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function getOrCreateOpenThreadForCustomer(int $customerId): array
    {
        $thread = $this->findOpenThreadForCustomer($customerId);
        if ($thread !== null) {
            return $thread;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO live_chat_threads (customer_id, status, last_message_at)
             VALUES (:customer_id, :status, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'customer_id' => $customerId,
            'status' => 'open',
        ]);

        return $this->findThreadById((int) $this->db->lastInsertId()) ?? [
            'id' => 0,
            'customer_id' => $customerId,
            'status' => 'open',
        ];
    }

    public function findOpenThreadForCustomer(int $customerId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT *
             FROM live_chat_threads
             WHERE customer_id = :customer_id
               AND status = :status
             ORDER BY updated_at DESC
             LIMIT 1'
        );
        $stmt->execute([
            'customer_id' => $customerId,
            'status' => 'open',
        ]);
        $thread = $stmt->fetch();

        return $thread ?: null;
    }

    public function findThreadById(int $threadId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT
                t.*,
                c.full_name AS customer_name,
                c.username AS customer_username,
                c.email AS customer_email,
                c.phone AS customer_phone
             FROM live_chat_threads t
             INNER JOIN customers c ON c.id = t.customer_id
             WHERE t.id = :thread_id
             LIMIT 1'
        );
        $stmt->execute(['thread_id' => $threadId]);
        $thread = $stmt->fetch();

        return $thread ?: null;
    }

    public function threadsForAdmin(): array
    {
        $stmt = $this->db->query(
            'SELECT
                t.*,
                c.full_name AS customer_name,
                c.username AS customer_username,
                c.email AS customer_email,
                c.phone AS customer_phone,
                (
                    SELECT message
                    FROM live_chat_messages lm
                    WHERE lm.thread_id = t.id
                    ORDER BY lm.created_at DESC, lm.id DESC
                    LIMIT 1
                ) AS last_message,
                (
                    SELECT COUNT(*)
                    FROM live_chat_messages lm
                    WHERE lm.thread_id = t.id
                      AND lm.sender_type = \'customer\'
                      AND lm.is_read = 0
                ) AS unread_customer_messages
             FROM live_chat_threads t
             INNER JOIN customers c ON c.id = t.customer_id
             ORDER BY t.last_message_at DESC, t.updated_at DESC'
        );

        return $stmt->fetchAll();
    }

    public function messagesForThread(int $threadId): array
    {
        $stmt = $this->db->prepare(
            'SELECT *
             FROM live_chat_messages
             WHERE thread_id = :thread_id
             ORDER BY created_at ASC, id ASC'
        );
        $stmt->execute(['thread_id' => $threadId]);
        return $stmt->fetchAll();
    }

    public function addMessage(
        int $threadId,
        string $senderType,
        ?int $senderId,
        string $senderName,
        string $message
    ): void {
        $stmt = $this->db->prepare(
            'INSERT INTO live_chat_messages (
                thread_id, sender_type, sender_id, sender_name, message, is_read
             ) VALUES (
                :thread_id, :sender_type, :sender_id, :sender_name, :message, :is_read
             )'
        );
        $stmt->execute([
            'thread_id' => $threadId,
            'sender_type' => $senderType,
            'sender_id' => $senderId,
            'sender_name' => trim($senderName),
            'message' => trim($message),
            'is_read' => 0,
        ]);

        $this->touchThread($threadId);
    }

    public function markReadForViewer(int $threadId, string $viewerType): void
    {
        $messageSenderToMark = $viewerType === 'admin' ? 'customer' : 'admin';

        $stmt = $this->db->prepare(
            'UPDATE live_chat_messages
             SET is_read = 1
             WHERE thread_id = :thread_id
               AND sender_type = :sender_type
               AND is_read = 0'
        );
        $stmt->execute([
            'thread_id' => $threadId,
            'sender_type' => $messageSenderToMark,
        ]);
    }

    public function closeThread(int $threadId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE live_chat_threads
             SET status = :status, updated_at = CURRENT_TIMESTAMP
             WHERE id = :thread_id'
        );
        $stmt->execute([
            'thread_id' => $threadId,
            'status' => 'closed',
        ]);
    }

    public function reopenThread(int $threadId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE live_chat_threads
             SET status = :status, updated_at = CURRENT_TIMESTAMP
             WHERE id = :thread_id'
        );
        $stmt->execute([
            'thread_id' => $threadId,
            'status' => 'open',
        ]);
    }

    public function countUnreadForAdmin(): int
    {
        $stmt = $this->db->query(
            'SELECT COUNT(*)
             FROM live_chat_messages
             WHERE sender_type = \'customer\'
               AND is_read = 0'
        );

        return (int) $stmt->fetchColumn();
    }

    private function touchThread(int $threadId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE live_chat_threads
             SET last_message_at = CURRENT_TIMESTAMP,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :thread_id'
        );
        $stmt->execute(['thread_id' => $threadId]);
    }
}
