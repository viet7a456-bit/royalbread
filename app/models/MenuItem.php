<?php

declare(strict_types=1);

class MenuItem
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function featured(int $limit = 6): array
    {
        $stmt = $this->db->prepare(
            'SELECT m.*, c.name AS category_name
             FROM menu_items m
             INNER JOIN categories c ON c.id = m.category_id
             WHERE m.is_featured = 1 AND m.is_available = 1
             ORDER BY m.sort_order ASC, m.id ASC
             LIMIT :item_limit'
        );
        $stmt->bindValue(':item_limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function findAvailableByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0)));
        if ($ids === []) {
            return [];
        }

        $placeholders = [];
        $orderCases = [];
        foreach ($ids as $index => $id) {
            $placeholder = ':item_' . $index;
            $placeholders[] = $placeholder;
            $orderCases[] = sprintf('WHEN %d THEN %d', $id, $index + 1);
        }

        $sql = sprintf(
            'SELECT m.*, c.name AS category_name
             FROM menu_items m
             INNER JOIN categories c ON c.id = m.category_id
             WHERE m.is_available = 1
               AND m.id IN (%s)
             ORDER BY CASE m.id %s ELSE 999 END',
            implode(', ', $placeholders),
            implode(' ', $orderCases)
        );

        $stmt = $this->db->prepare($sql);
        foreach ($ids as $index => $id) {
            $stmt->bindValue(':item_' . $index, $id, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function availableByCategoryNames(array $categoryNames, array $excludedIds = [], ?int $limit = 6): array
    {
        $categoryNames = array_values(array_filter(array_map('trim', $categoryNames)));
        if ($categoryNames === []) {
            return [];
        }

        $orderClauses = [];
        foreach ($categoryNames as $index => $categoryName) {
            $orderClauses[] = sprintf(
                "WHEN %s THEN %d",
                $this->db->quote($categoryName),
                $index + 1
            );
        }

        $categoryPlaceholders = [];
        $params = [];
        foreach ($categoryNames as $index => $categoryName) {
            $placeholder = ':category_' . $index;
            $categoryPlaceholders[] = $placeholder;
            $params[$placeholder] = $categoryName;
        }

        $excludedSql = '';
        if ($excludedIds !== []) {
            $excludedPlaceholders = [];
            foreach (array_values($excludedIds) as $index => $excludedId) {
                $placeholder = ':excluded_' . $index;
                $excludedPlaceholders[] = $placeholder;
                $params[$placeholder] = (int) $excludedId;
            }
            $excludedSql = ' AND m.id NOT IN (' . implode(', ', $excludedPlaceholders) . ')';
        }

        $limitSql = '';
        if ($limit !== null && $limit > 0) {
            $limitSql = ' LIMIT :item_limit';
        }

        $sql = sprintf(
            'SELECT m.*, c.name AS category_name
             FROM menu_items m
             INNER JOIN categories c ON c.id = m.category_id
             WHERE m.is_available = 1
               AND c.name IN (%s)%s
             ORDER BY CASE c.name %s ELSE 999 END, m.is_featured DESC, m.sort_order ASC, m.id ASC%s',
            implode(', ', $categoryPlaceholders),
            $excludedSql,
            implode(' ', $orderClauses),
            $limitSql
        );

        $stmt = $this->db->prepare($sql);
        foreach ($params as $placeholder => $value) {
            $stmt->bindValue($placeholder, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        if ($limit !== null && $limit > 0) {
            $stmt->bindValue(':item_limit', $limit, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function availableByCategorySlugs(array $categorySlugs, array $excludedIds = [], ?int $limit = 6): array
    {
        $categorySlugs = array_values(array_filter(array_map('trim', $categorySlugs)));
        if ($categorySlugs === []) {
            return [];
        }

        $orderClauses = [];
        foreach ($categorySlugs as $index => $categorySlug) {
            $orderClauses[] = sprintf(
                "WHEN %s THEN %d",
                $this->db->quote($categorySlug),
                $index + 1
            );
        }

        $categoryPlaceholders = [];
        $params = [];
        foreach ($categorySlugs as $index => $categorySlug) {
            $placeholder = ':category_slug_' . $index;
            $categoryPlaceholders[] = $placeholder;
            $params[$placeholder] = $categorySlug;
        }

        $excludedSql = '';
        if ($excludedIds !== []) {
            $excludedPlaceholders = [];
            foreach (array_values($excludedIds) as $index => $excludedId) {
                $placeholder = ':excluded_slug_' . $index;
                $excludedPlaceholders[] = $placeholder;
                $params[$placeholder] = (int) $excludedId;
            }
            $excludedSql = ' AND m.id NOT IN (' . implode(', ', $excludedPlaceholders) . ')';
        }

        $limitSql = '';
        if ($limit !== null && $limit > 0) {
            $limitSql = ' LIMIT :item_limit';
        }

        $sql = sprintf(
            'SELECT m.*, c.name AS category_name, c.slug AS category_slug
             FROM menu_items m
             INNER JOIN categories c ON c.id = m.category_id
             WHERE m.is_available = 1
               AND c.slug IN (%s)%s
             ORDER BY CASE c.slug %s ELSE 999 END, m.is_featured DESC, m.sort_order ASC, m.id ASC%s',
            implode(', ', $categoryPlaceholders),
            $excludedSql,
            implode(' ', $orderClauses),
            $limitSql
        );

        $stmt = $this->db->prepare($sql);
        foreach ($params as $placeholder => $value) {
            $stmt->bindValue($placeholder, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        if ($limit !== null && $limit > 0) {
            $stmt->bindValue(':item_limit', $limit, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function grouped(): array
    {
        $stmt = $this->db->query(
            'SELECT c.name AS category_name, c.slug AS category_slug, c.sort_order AS category_sort, m.*
             FROM menu_items m
             INNER JOIN categories c ON c.id = m.category_id
             WHERE m.is_available = 1
             ORDER BY c.sort_order ASC, m.sort_order ASC, m.id ASC'
        );

        $grouped = [];
        foreach ($stmt->fetchAll() as $item) {
            $grouped[$item['category_name']][] = $item;
        }

        return $grouped;
    }

    public function allForAdmin(): array
    {
        $stmt = $this->db->query(
            'SELECT m.*, c.name AS category_name, c.slug AS category_slug
             FROM menu_items m
             INNER JOIN categories c ON c.id = m.category_id
             ORDER BY c.sort_order ASC, m.sort_order ASC, m.id ASC'
        );

        return $stmt->fetchAll();
    }

    public function allForAdminPaginated(int $offset, int $limit): array
    {
        $stmt = $this->db->prepare(
            'SELECT m.*, c.name AS category_name, c.slug AS category_slug
             FROM menu_items m
             INNER JOIN categories c ON c.id = m.category_id
             ORDER BY c.sort_order ASC, m.sort_order ASC, m.id ASC
             LIMIT :item_limit OFFSET :item_offset'
        );
        $stmt->bindValue(':item_limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':item_offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function bestSelling(int $limit = 8): array
    {
        $stmt = $this->db->prepare(
            'SELECT
                m.*,
                c.name AS category_name,
                c.slug AS category_slug,
                SUM(oi.quantity) AS sold_quantity,
                COUNT(DISTINCT oi.order_id) AS orders_count,
                SUM(oi.quantity * oi.price) AS gross_revenue
             FROM order_items oi
             INNER JOIN orders o ON o.id = oi.order_id
             INNER JOIN menu_items m ON m.id = oi.menu_item_id
             INNER JOIN categories c ON c.id = m.category_id
             WHERE o.status = :status
             GROUP BY m.id, c.name, c.slug
             ORDER BY sold_quantity DESC, orders_count DESC, gross_revenue DESC, m.sort_order ASC
             LIMIT :item_limit'
        );
        $stmt->bindValue(':status', 'completed', PDO::PARAM_STR);
        $stmt->bindValue(':item_limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function countAll(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM menu_items')->fetchColumn();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT m.*, c.name AS category_name
             FROM menu_items m
             INNER JOIN categories c ON c.id = m.category_id
             WHERE m.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $item = $stmt->fetch();
        return $item ?: null;
    }

    public function findAvailableById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT m.*, c.name AS category_name
             FROM menu_items m
             INNER JOIN categories c ON c.id = m.category_id
             WHERE m.id = :id AND m.is_available = 1
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $item = $stmt->fetch();

        return $item ?: null;
    }

    public function all(): array
    {
        $stmt = $this->db->query(
            'SELECT m.*, c.name AS category_name, c.slug AS category_slug
             FROM menu_items m
             INNER JOIN categories c ON c.id = m.category_id
             ORDER BY c.sort_order ASC, m.sort_order ASC, m.id ASC'
        );
        return $stmt->fetchAll();
    }

    public function create(array $data): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO menu_items (
                category_id, name, description, price, image_url, is_featured, is_available, sort_order
            ) VALUES (
                :category_id, :name, :description, :price, :image_url, :is_featured, :is_available, :sort_order
            )'
        );

        $stmt->execute([
            'category_id' => (int) $data['category_id'],
            'name' => trim($data['name']),
            'description' => trim($data['description']),
            'price' => (int) $data['price'],
            'image_url' => trim($data['image_url']),
            'is_featured' => !empty($data['is_featured']) ? 1 : 0,
            'is_available' => !empty($data['is_available']) ? 1 : 0,
            'sort_order' => (int) $data['sort_order'],
        ]);
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE menu_items
             SET category_id = :category_id,
                 name = :name,
                 description = :description,
                 price = :price,
                 image_url = :image_url,
                 is_featured = :is_featured,
                 is_available = :is_available,
                 sort_order = :sort_order,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );

        $stmt->execute([
            'id' => $id,
            'category_id' => (int) $data['category_id'],
            'name' => trim($data['name']),
            'description' => trim($data['description']),
            'price' => (int) $data['price'],
            'image_url' => trim($data['image_url']),
            'is_featured' => !empty($data['is_featured']) ? 1 : 0,
            'is_available' => !empty($data['is_available']) ? 1 : 0,
            'sort_order' => (int) $data['sort_order'],
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM menu_items WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
