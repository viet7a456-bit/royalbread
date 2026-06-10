<?php

declare(strict_types=1);

class Customer
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM customers WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        $customer = $stmt->fetch();

        return $customer ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM customers WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $customer = $stmt->fetch();

        return $customer ?: null;
    }

    public function findWithStatsById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            '
            SELECT
                c.*,
                COUNT(DISTINCT o.id) AS orders_count,
                COALESCE(SUM(CASE WHEN o.status = \'completed\' THEN o.total_amount ELSE 0 END), 0) AS total_spent,
                MAX(o.created_at) AS last_order_at
            FROM customers c
            LEFT JOIN orders o ON o.customer_id = c.id
            WHERE c.id = :id
            GROUP BY c.id, c.username, c.password_hash, c.full_name, c.email, c.phone, c.created_at, c.points
            LIMIT 1
            '
        );
        $stmt->execute(['id' => $id]);
        $customer = $stmt->fetch();

        if (!$customer) {
            return null;
        }

        $customers = $this->decorateStats([$customer]);
        return $customers[0] ?? null;
    }

    public function create(array $data): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO customers (username, password_hash, full_name, email, phone)
             VALUES (:username, :password_hash, :full_name, :email, :phone)'
        );

        $stmt->execute([
            'username' => trim($data['username']),
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'full_name' => trim($data['full_name']),
            'email' => $data['email'] ? trim($data['email']) : null,
            'phone' => $data['phone'] ? trim($data['phone']) : null,
        ]);
    }

    public function countAll(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM customers')->fetchColumn();
    }

    public function allWithStats(string $searchQuery = ''): array
    {
        $searchQuery = trim($searchQuery);
        $params = [];
        $whereSql = '';

        if ($searchQuery !== '') {
            $whereSql = '
                WHERE c.username LIKE :search
                   OR c.full_name LIKE :search
                   OR COALESCE(c.email, \'\') LIKE :search
                   OR COALESCE(c.phone, \'\') LIKE :search
            ';
            $params['search'] = '%' . $searchQuery . '%';
        }

        $stmt = $this->db->prepare(
            '
             SELECT
                 c.*,
                 COUNT(DISTINCT o.id) AS orders_count,
                 COALESCE(SUM(CASE WHEN o.status = \'completed\' THEN o.total_amount ELSE 0 END), 0) AS total_spent,
                 MAX(o.created_at) AS last_order_at
             FROM customers c
             LEFT JOIN orders o ON o.customer_id = c.id
             ' . $whereSql . '
             GROUP BY c.id, c.username, c.password_hash, c.full_name, c.email, c.phone, c.created_at, c.points
             ORDER BY c.created_at DESC, c.id DESC
             '
        );

        $stmt->execute($params);
        return $this->decorateStats($stmt->fetchAll());
    }

    public function topPotential(int $limit = 5): array
    {
        $customers = $this->allWithStats();
        usort($customers, static function (array $left, array $right): int {
            $leftScore = (int) ($left['customer_score'] ?? 0);
            $rightScore = (int) ($right['customer_score'] ?? 0);

            if ($leftScore === $rightScore) {
                return (int) ($right['total_spent'] ?? 0) <=> (int) ($left['total_spent'] ?? 0);
            }

            return $rightScore <=> $leftScore;
        });

        return array_slice($customers, 0, $limit);
    }

    public function recipientsForPromotion(string $targetTier): array
    {
        $customers = $this->allWithStats();

        return array_values(array_filter(
            $customers,
            static function (array $customer) use ($targetTier): bool {
                $email = trim((string) ($customer['email'] ?? ''));
                if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                    return false;
                }

                if ($targetTier === 'all') {
                    return true;
                }

                return (string) ($customer['membership_tier_slug'] ?? 'new') === $targetTier;
            }
        ));
    }

    private function decorateStats(array $customers): array
    {
        foreach ($customers as &$customer) {
            $ordersCount = (int) ($customer['orders_count'] ?? 0);
            $totalSpent = (int) ($customer['total_spent'] ?? 0);
            $lastOrderAt = (string) ($customer['last_order_at'] ?? '');
            $daysSinceLastOrder = $this->daysSince($lastOrderAt);
            $averageOrderValue = $ordersCount > 0 ? (int) round($totalSpent / max(1, $ordersCount)) : 0;
            $membership = membership_tier_meta($ordersCount, $totalSpent);

            $score = min(45, $ordersCount * 7);
            $score += min(90, (int) floor($totalSpent / 50000) * 5);

            if ($daysSinceLastOrder === null) {
                $score += 8;
            } elseif ($daysSinceLastOrder <= 7) {
                $score += 35;
            } elseif ($daysSinceLastOrder <= 30) {
                $score += 24;
            } elseif ($daysSinceLastOrder <= 90) {
                $score += 12;
            }

            if (trim((string) ($customer['email'] ?? '')) !== '') {
                $score += 6;
            }

            if (trim((string) ($customer['phone'] ?? '')) !== '') {
                $score += 6;
            }

            $segment = 'Moi dang ky';
            if ($score >= 120 || ($ordersCount >= 6 && $totalSpent >= 800000)) {
                $segment = 'VIP';
            } elseif ($score >= 75 || ($ordersCount >= 3 && $totalSpent >= 300000)) {
                $segment = 'Tiem nang';
            } elseif ($score >= 35 || $ordersCount > 0) {
                $segment = 'Can cham soc';
            }

            $customer['average_order_value'] = $averageOrderValue;
            $customer['days_since_last_order'] = $daysSinceLastOrder;
            $customer['customer_score'] = $score;
            $customer['potential_segment'] = $segment;
            $customer['membership_tier_label'] = $membership['tier'];
            $customer['membership_tier_slug'] = $membership['tier_slug'];
            $customer['membership_points'] = $membership['points'];
        }
        unset($customer);

        return $customers;
    }

    private function daysSince(string $dateTime): ?int
    {
        if (trim($dateTime) === '') {
            return null;
        }

        $timestamp = strtotime($dateTime);
        if ($timestamp === false) {
            return null;
        }

        return max(0, (int) floor((time() - $timestamp) / 86400));
    }
}
