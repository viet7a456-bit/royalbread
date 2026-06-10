<?php

declare(strict_types=1);

class Promotion
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function allForAdmin(): array
    {
        $stmt = $this->db->query(
            'SELECT *
             FROM promotions
             ORDER BY is_active DESC, created_at DESC, id DESC'
        );

        return $stmt->fetchAll();
    }

    public function activeForTier(string $tierSlug): array
    {
        $stmt = $this->db->prepare(
            'SELECT *
             FROM promotions
             WHERE is_active = 1
               AND (expires_at IS NULL OR expires_at >= NOW())
               AND target_tier IN ("all", :target_tier)
             ORDER BY created_at DESC, id DESC'
        );
        $stmt->execute(['target_tier' => $this->sanitizeTier($tierSlug)]);

        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO promotions (
                title, content, target_tier, discount_percent, discount_amount,
                coupon_code, expires_at, is_active
            ) VALUES (
                :title, :content, :target_tier, :discount_percent, :discount_amount,
                :coupon_code, :expires_at, :is_active
            )'
        );

        $expiresAt = str_replace('T', ' ', trim((string) ($data['expires_at'] ?? '')));
        $stmt->execute([
            'title' => trim((string) ($data['title'] ?? '')),
            'content' => trim((string) ($data['content'] ?? '')),
            'target_tier' => $this->sanitizeTier((string) ($data['target_tier'] ?? 'all')),
            'discount_percent' => max(0, min(100, (int) ($data['discount_percent'] ?? 0))),
            'discount_amount' => max(0, (int) ($data['discount_amount'] ?? 0)),
            'coupon_code' => trim((string) ($data['coupon_code'] ?? '')) !== ''
                ? strtoupper(trim((string) $data['coupon_code']))
                : null,
            'expires_at' => $expiresAt !== '' ? $expiresAt : null,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM promotions WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM promotions WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $promotion = $stmt->fetch();

        return $promotion ?: null;
    }

    public function bestForCustomerTier(string $tierSlug, int $subtotal): ?array
    {
        $promotions = $this->activeForTier($tierSlug);
        $bestPromotion = null;
        $bestDiscount = 0;

        foreach ($promotions as $promotion) {
            $discount = $this->discountAmountForSubtotal($promotion, $subtotal);
            if ($discount <= 0 || $discount <= $bestDiscount) {
                continue;
            }

            $bestPromotion = $promotion;
            $bestDiscount = $discount;
        }

        if ($bestPromotion === null) {
            return null;
        }

        $bestPromotion['computed_discount'] = $bestDiscount;
        return $bestPromotion;
    }

    public function discountAmountForSubtotal(array $promotion, int $subtotal): int
    {
        $percentDiscount = 0;
        $percent = max(0, min(100, (int) ($promotion['discount_percent'] ?? 0)));
        if ($percent > 0) {
            $percentDiscount = (int) floor($subtotal * ($percent / 100));
        }

        $fixedDiscount = max(0, (int) ($promotion['discount_amount'] ?? 0));
        $discount = max($percentDiscount, $fixedDiscount);

        return min($discount, max(0, $subtotal));
    }

    private function sanitizeTier(string $tierSlug): string
    {
        return match (trim($tierSlug)) {
            'new', 'silver', 'gold' => trim($tierSlug),
            default => 'all',
        };
    }
}
