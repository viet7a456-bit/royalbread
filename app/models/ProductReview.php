<?php

declare(strict_types=1);

class ProductReview
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function createOrUpdate(array $data): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO product_reviews (
                customer_id, menu_item_id, order_id, rating, review_title, review_comment, status
            ) VALUES (
                :customer_id, :menu_item_id, :order_id, :rating, :review_title, :review_comment, :status
            )
            ON DUPLICATE KEY UPDATE
                order_id = VALUES(order_id),
                rating = VALUES(rating),
                review_title = VALUES(review_title),
                review_comment = VALUES(review_comment),
                status = VALUES(status),
                updated_at = CURRENT_TIMESTAMP'
        );

        $stmt->execute([
            'customer_id' => (int) $data['customer_id'],
            'menu_item_id' => (int) $data['menu_item_id'],
            'order_id' => !empty($data['order_id']) ? (int) $data['order_id'] : null,
            'rating' => max(1, min(5, (int) $data['rating'])),
            'review_title' => trim((string) ($data['review_title'] ?? '')),
            'review_comment' => trim((string) ($data['review_comment'] ?? '')),
            'status' => trim((string) ($data['status'] ?? 'pending')),
        ]);
    }

    public function summaryByItemIds(array $itemIds): array
    {
        $itemIds = array_values(array_unique(array_filter(array_map('intval', $itemIds), static fn (int $id): bool => $id > 0)));
        if ($itemIds === []) {
            return [];
        }

        $placeholders = [];
        foreach ($itemIds as $index => $itemId) {
            $placeholders[] = ':item_' . $index;
        }

        $sql = 'SELECT
                    menu_item_id,
                    COUNT(*) AS review_count,
                    ROUND(AVG(rating), 1) AS rating_average
                FROM product_reviews
                WHERE status = :status
                  AND menu_item_id IN (' . implode(', ', $placeholders) . ')
                GROUP BY menu_item_id';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':status', 'approved', PDO::PARAM_STR);
        foreach ($itemIds as $index => $itemId) {
            $stmt->bindValue(':item_' . $index, $itemId, PDO::PARAM_INT);
        }
        $stmt->execute();

        $summaries = [];
        foreach ($stmt->fetchAll() as $row) {
            $summaries[(int) $row['menu_item_id']] = [
                'review_count' => (int) $row['review_count'],
                'rating_average' => (float) $row['rating_average'],
            ];
        }

        return $summaries;
    }

    public function approvedRecentByItemIds(array $itemIds, int $limitPerItem = 2): array
    {
        $itemIds = array_values(array_unique(array_filter(array_map('intval', $itemIds), static fn (int $id): bool => $id > 0)));
        if ($itemIds === []) {
            return [];
        }

        $placeholders = [];
        foreach ($itemIds as $index => $itemId) {
            $placeholders[] = ':item_' . $index;
        }

        $sql = 'SELECT
                    pr.*,
                    c.full_name AS customer_name
                FROM product_reviews pr
                INNER JOIN customers c ON c.id = pr.customer_id
                WHERE pr.status = :status
                  AND pr.menu_item_id IN (' . implode(', ', $placeholders) . ')
                ORDER BY pr.created_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':status', 'approved', PDO::PARAM_STR);
        foreach ($itemIds as $index => $itemId) {
            $stmt->bindValue(':item_' . $index, $itemId, PDO::PARAM_INT);
        }
        $stmt->execute();

        $grouped = [];
        foreach ($stmt->fetchAll() as $row) {
            $menuItemId = (int) $row['menu_item_id'];
            if (!isset($grouped[$menuItemId])) {
                $grouped[$menuItemId] = [];
            }

            if (count($grouped[$menuItemId]) >= $limitPerItem) {
                continue;
            }

            $grouped[$menuItemId][] = $row;
        }

        return $grouped;
    }

    public function allForAdmin(string $status = ''): array
    {
        $sql = 'SELECT
                    pr.*,
                    c.full_name AS customer_name,
                    c.username AS customer_username,
                    m.name AS menu_item_name,
                    m.image_url AS menu_item_image_url
                FROM product_reviews pr
                INNER JOIN customers c ON c.id = pr.customer_id
                INNER JOIN menu_items m ON m.id = pr.menu_item_id';

        $params = [];
        if ($status !== '') {
            $sql .= ' WHERE pr.status = :status';
            $params['status'] = $status;
        }

        $sql .= ' ORDER BY pr.created_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function updateStatus(int $id, string $status): void
    {
        $stmt = $this->db->prepare(
            'UPDATE product_reviews
             SET status = :status, updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'status' => $status,
        ]);
    }

    public function forCustomer(int $customerId): array
    {
        $stmt = $this->db->prepare(
            'SELECT
                pr.*,
                m.name AS menu_item_name,
                m.image_url AS menu_item_image_url,
                m.category_id
             FROM product_reviews pr
             INNER JOIN menu_items m ON m.id = pr.menu_item_id
             WHERE pr.customer_id = :customer_id
             ORDER BY pr.created_at DESC'
        );
        $stmt->execute(['customer_id' => $customerId]);
        return $stmt->fetchAll();
    }

    public function reviewableItemsForCustomer(int $customerId, int $limit = 6): array
    {
        $stmt = $this->db->prepare(
            'SELECT
                oi.menu_item_id,
                MAX(oi.menu_item_name) AS menu_item_name,
                MAX(COALESCE(m.image_url, oi.menu_item_image_url, \'\')) AS image_url,
                MAX(o.id) AS order_id,
                MAX(pr.id) AS review_id,
                MAX(pr.rating) AS rating,
                MAX(pr.review_title) AS review_title,
                MAX(pr.review_comment) AS review_comment,
                MAX(pr.status) AS review_status
             FROM orders o
             INNER JOIN order_items oi ON oi.order_id = o.id
             LEFT JOIN menu_items m ON m.id = oi.menu_item_id
             LEFT JOIN product_reviews pr
                ON pr.customer_id = o.customer_id
               AND pr.menu_item_id = oi.menu_item_id
             WHERE o.customer_id = :customer_id
             GROUP BY oi.menu_item_id
             ORDER BY MAX(o.created_at) DESC
             LIMIT :item_limit'
        );
        $stmt->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
        $stmt->bindValue(':item_limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
