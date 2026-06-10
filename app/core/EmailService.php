<?php

declare(strict_types=1);

class EmailService
{
    public static function sendPromotionAnnouncement(array $recipient, array $promotion, array $settings): bool
    {
        $email = trim((string) ($recipient['email'] ?? ''));
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        $siteName = setting($settings, 'site_name', 'RoyalBread');
        $hotline = setting($settings, 'hotline', '0879866636');
        $subject = ascii_text($siteName . ' - khuyen mai moi danh cho ban');

        $discountLine = '';
        if ((int) ($promotion['discount_percent'] ?? 0) > 0) {
            $discountLine = 'Giam ' . (int) $promotion['discount_percent'] . '%';
        } elseif ((int) ($promotion['discount_amount'] ?? 0) > 0) {
            $discountLine = 'Giam ' . format_price((int) $promotion['discount_amount']);
        }

        $couponCode = trim((string) ($promotion['coupon_code'] ?? ''));
        $expiresAt = trim((string) ($promotion['expires_at'] ?? ''));

        $body = '
            <div style="font-family:Arial,sans-serif;max-width:680px;margin:0 auto;background:#fff9f1;border:1px solid #ead9c2;border-radius:18px;overflow:hidden;">
                <div style="background:#4b2c18;color:#fff7e8;padding:20px 24px;">
                    <h1 style="margin:0;font-size:24px;">' . e($siteName) . '</h1>
                    <p style="margin:8px 0 0;">Thong bao khuyen mai moi</p>
                </div>
                <div style="padding:24px;">
                    <p>Chao <strong>' . e((string) ($recipient['full_name'] ?? 'Khach hang')) . '</strong>,</p>
                    <p>RoyalBread vua cap nhat uu dai moi danh cho ' . e(promotion_target_label((string) ($promotion['target_tier'] ?? 'all'))) . '.</p>
                    <h2 style="margin:16px 0 10px;color:#7f4a17;">' . e((string) ($promotion['title'] ?? 'Khuyen mai RoyalBread')) . '</h2>
                    <p style="line-height:1.7;color:#5a4637;">' . nl2br(e((string) ($promotion['content'] ?? ''))) . '</p>
                    ' . ($discountLine !== '' ? '<p><strong>Uu dai:</strong> ' . e($discountLine) . '</p>' : '') . '
                    ' . ($couponCode !== '' ? '<p><strong>Ma uu dai:</strong> ' . e($couponCode) . '</p>' : '') . '
                    ' . ($expiresAt !== '' ? '<p><strong>Han su dung:</strong> ' . e(date('d/m/Y H:i', strtotime($expiresAt))) . '</p>' : '') . '
                    <p><a href="' . e(full_url('menu')) . '" style="display:inline-block;padding:12px 20px;border-radius:999px;background:#b97b2c;color:#fff7ea;text-decoration:none;font-weight:700;">Dat mon ngay</a></p>
                    <hr style="border:none;border-top:1px solid #ead9c2;margin:22px 0;">
                    <p><strong>Hotline:</strong> ' . e($hotline) . '</p>
                </div>
            </div>
        ';

        return self::sendHtmlMail($email, $subject, $body);
    }

    private static function sendHtmlMail(string $to, string $subject, string $body): bool
    {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: RoyalBread <no-reply@royalbread.local>',
        ];

        $result = @mail($to, $subject, $body, implode("\r\n", $headers));
        self::logMailResult($result ? 'sent' : 'failed', $to, $subject);

        return $result;
    }

    private static function logMailResult(string $status, string $email, string $subject): void
    {
        $logDir = ROOT_PATH . '/tmp/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $logEntry = sprintf(
            "[%s] %s | to=%s | subject=%s\n",
            date('Y-m-d H:i:s'),
            strtoupper($status),
            $email,
            $subject
        );

        @file_put_contents($logDir . '/mail.log', $logEntry, FILE_APPEND | LOCK_EX);
    }
}
