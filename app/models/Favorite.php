<?php

declare(strict_types=1);

class Favorite
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function toggle(int $customerId, int $menuItemId): bool
    {
        if ($this->exists($customerId, $menuItemId)) {
            $stmt = $this->db->prepare(
                'DELETE FROM customer_favorites
                 WHERE customer_id = :customer_id AND menu_item_id = :menu_item_id'
            );
            $stmt->execute([
                'customer_id' => $customerId,
                'menu_item_id' => $menuItemId,
            ]);

            return false;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO customer_favorites (customer_id, menu_item_id)
             VALUES (:customer_id, :menu_item_id)'
        );
        $stmt->execute([
            'customer_id' => $customerId,
            'menu_item_id' => $menuItemId,
        ]);

        return true;
    }

    public function exists(int $customerId, int $menuItemId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*)
             FROM customer_favorites
             WHERE customer_id = :customer_id AND menu_item_id = :menu_item_id'
        );
        $stmt->execute([
            'customer_id' => $customerId,
            'menu_item_id' => $menuItemId,
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function itemIdsForCustomer(int $customerId): array
    {
        $stmt = $this->db->prepare(
            'SELECT menu_item_id
             FROM customer_favorites
             WHERE customer_id = :customer_id
             ORDER BY created_at DESC'
        );
        $stmt->execute(['customer_id' => $customerId]);

        return array_map('intval', array_column($stmt->fetchAll(), 'menu_item_id'));
    }

    public function itemsForCustomer(int $customerId, int $limit = 12): array
    {
        $stmt = $this->db->prepare(
            'SELECT
                m.*,
                c.name AS category_name,
                c.slug AS category_slug,
                f.created_at AS favorited_at
             FROM customer_favorites f
             INNER JOIN menu_items m ON m.id = f.menu_item_id
             INNER JOIN categories c ON c.id = m.category_id
             WHERE f.customer_id = :customer_id
             ORDER BY f.created_at DESC
             LIMIT :item_limit'
        );
        $stmt->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
        $stmt->bindValue(':item_limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
