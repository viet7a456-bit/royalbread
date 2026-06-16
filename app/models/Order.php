<?php

declare(strict_types=1);

class Order
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO orders (
                customer_id, customer_name, customer_email, phone, address, note,
                total_amount, payment_method, discount_amount, payment_status, payment_reference
            ) VALUES (
                :customer_id, :customer_name, :customer_email, :phone, :address, :note,
                :total_amount, :payment_method, :discount_amount, :payment_status, :payment_reference
            )'
        );

        $stmt->execute([
            'customer_id' => $data['customer_id'] ?? null,
            'customer_name' => trim($data['customer_name']),
            'customer_email' => trim((string) ($data['customer_email'] ?? '')) !== '' ? trim((string) $data['customer_email']) : null,
            'phone' => trim($data['phone']),
            'address' => trim($data['address']),
            'note' => trim($data['note'] ?? ''),
            'total_amount' => (int) $data['total_amount'],
            'payment_method' => trim($data['payment_method'] ?? 'cod'),
            'discount_amount' => (int) ($data['discount_amount'] ?? 0),
            'payment_status' => trim((string) ($data['payment_status'] ?? 'unpaid')),
            'payment_reference' => trim((string) ($data['payment_reference'] ?? '')) !== ''
                ? trim((string) $data['payment_reference'])
                : null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM orders ORDER BY created_at DESC');
        return $stmt->fetchAll();
    }

    public function forCustomer(int $customerId, int $limit = 0): array
    {
        if ($customerId <= 0) {
            return [];
        }

        $sql = '
            SELECT *
            FROM orders
            WHERE customer_id = :customer_id
            ORDER BY created_at DESC
        ';

        if ($limit > 0) {
            $sql .= ' LIMIT :item_limit';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':customer_id', $customerId, PDO::PARAM_INT);

        if ($limit > 0) {
            $stmt->bindValue(':item_limit', $limit, PDO::PARAM_INT);
        }

        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM orders WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $order = $stmt->fetch();
        return $order ?: null;
    }

    public function getItems(int $orderId): array
    {
        $stmt = $this->db->prepare('
            SELECT
                oi.*,
                COALESCE(m.name, oi.menu_item_name, CONCAT(\'Món #\', oi.menu_item_id)) AS menu_item_name,
                COALESCE(m.image_url, oi.menu_item_image_url, \'\') AS image_url
            FROM order_items oi
            LEFT JOIN menu_items m ON oi.menu_item_id = m.id
            WHERE oi.order_id = :order_id
            ORDER BY oi.id ASC
        ');
        $stmt->execute(['order_id' => $orderId]);
        return $stmt->fetchAll();
    }

    public function itemsForOrders(array $orderIds): array
    {
        $normalizedIds = array_values(array_unique(array_filter(
            array_map(static fn(mixed $id): int => (int) $id, $orderIds),
            static fn(int $id): bool => $id > 0
        )));

        if ($normalizedIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($normalizedIds), '?'));
        $stmt = $this->db->prepare(
            '
            SELECT
                oi.*,
                COALESCE(m.name, oi.menu_item_name, CONCAT(\'Mon #\', oi.menu_item_id)) AS menu_item_name,
                COALESCE(m.image_url, oi.menu_item_image_url, \'\') AS image_url
            FROM order_items oi
            LEFT JOIN menu_items m ON oi.menu_item_id = m.id
            WHERE oi.order_id IN (' . $placeholders . ')
            ORDER BY oi.order_id DESC, oi.id ASC
            '
        );
        $stmt->execute($normalizedIds);

        $itemsByOrderId = [];
        foreach ($stmt->fetchAll() as $item) {
            $itemsByOrderId[(int) $item['order_id']][] = $item;
        }

        return $itemsByOrderId;
    }

    public function updateStatus(int $id, string $status): void
    {
        $stmt = $this->db->prepare('UPDATE orders SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public function updatePaymentStatus(int $id, string $paymentStatus): void
    {
        $fields = ['payment_status = :payment_status'];
        $params = [
            'id' => $id,
            'payment_status' => trim($paymentStatus),
        ];

        if (trim($paymentStatus) === 'paid') {
            $fields[] = 'payment_confirmed_at = CURRENT_TIMESTAMP';
        }

        $stmt = $this->db->prepare(
            'UPDATE orders SET ' . implode(', ', $fields) . ' WHERE id = :id'
        );
        $stmt->execute($params);
    }

    public function updatePaymentReference(int $id, string $paymentReference): void
    {
        $stmt = $this->db->prepare(
            'UPDATE orders
             SET payment_reference = :payment_reference
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'payment_reference' => trim($paymentReference),
        ]);
    }

    public function countAll(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM orders')->fetchColumn();
    }
}
