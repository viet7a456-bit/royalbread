<?php

declare(strict_types=1);

class OrderMailer
{
    private array $settings;

    public function __construct(array $settings = [])
    {
        $this->settings = $settings !== [] ? $settings : (new Setting())->all();
    }

    public function sendOrderConfirmation(array $order, array $items): bool
    {
        $email = trim((string) ($order['customer_email'] ?? ''));
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        $subject = 'RoyalBread xac nhan don hang #' . (int) ($order['id'] ?? 0);
        $body = $this->buildHtml($order, $items);

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: RoyalBread <no-reply@royalbread.local>',
        ];

        $result = @mail($email, $subject, $body, implode("\r\n", $headers));
        if ($result) {
            $this->logMailResult('sent', $email, $subject);
            return true;
        }

        $this->logMailResult('failed', $email, $subject, $body);
        return false;
    }

    private function buildHtml(array $order, array $items): string
    {
        $siteName = setting($this->settings, 'site_name', 'RoyalBread');
        $hotline = setting($this->settings, 'hotline', '0879866636');
        $address = setting($this->settings, 'address', '28 Da Tuong - TP Hai Duong');

        $itemRows = '';
        foreach ($items as $item) {
            $itemRows .= sprintf(
                '<tr><td style="padding:8px 0;border-bottom:1px solid #f0e4d5;">%s</td><td style="padding:8px 0;border-bottom:1px solid #f0e4d5;text-align:center;">x%d</td><td style="padding:8px 0;border-bottom:1px solid #f0e4d5;text-align:right;">%s</td></tr>',
                e((string) ($item['menu_item_name'] ?? 'Mon an')),
                (int) ($item['quantity'] ?? 0),
                e(format_price((int) ($item['price'] ?? 0) * (int) ($item['quantity'] ?? 0)))
            );
        }

        return '
            <div style="font-family:Arial,sans-serif;max-width:680px;margin:0 auto;background:#fff9f1;border:1px solid #ead9c2;border-radius:18px;overflow:hidden;">
                <div style="background:#4b2c18;color:#fff7e8;padding:20px 24px;">
                    <h1 style="margin:0;font-size:24px;">' . e($siteName) . '</h1>
                    <p style="margin:8px 0 0;">Xac nhan don hang #' . (int) ($order['id'] ?? 0) . '</p>
                </div>
                <div style="padding:24px;">
                    <p>Chao ' . e((string) ($order['customer_name'] ?? 'ban')) . ',</p>
                    <p>RoyalBread da nhan don hang cua ban va se lien he som de xac nhan.</p>
                    <table style="width:100%;border-collapse:collapse;margin:18px 0;">
                        <thead>
                            <tr>
                                <th style="text-align:left;padding-bottom:10px;border-bottom:2px solid #d6b17e;">Mon</th>
                                <th style="text-align:center;padding-bottom:10px;border-bottom:2px solid #d6b17e;">SL</th>
                                <th style="text-align:right;padding-bottom:10px;border-bottom:2px solid #d6b17e;">Thanh tien</th>
                            </tr>
                        </thead>
                        <tbody>' . $itemRows . '</tbody>
                    </table>
                    <p><strong>Tong cong:</strong> ' . e(format_price((int) ($order['total_amount'] ?? 0))) . '</p>
                    <p><strong>Dia chi giao:</strong> ' . e((string) ($order['address'] ?? '')) . '</p>
                    <p><strong>Thanh toan:</strong> ' . e((string) ($order['payment_method_label'] ?? 'COD')) . '</p>
                    <hr style="border:none;border-top:1px solid #ead9c2;margin:22px 0;">
                    <p><strong>Hotline:</strong> ' . e($hotline) . '</p>
                    <p><strong>Dia chi quan:</strong> ' . e($address) . '</p>
                </div>
            </div>
        ';
    }

    private function logMailResult(string $status, string $email, string $subject, string $body = ''): void
    {
        $logDir = ROOT_PATH . '/tmp/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $logEntry = sprintf(
            "[%s] %s | to=%s | subject=%s%s\n",
            date('Y-m-d H:i:s'),
            strtoupper($status),
            $email,
            $subject,
            $body !== '' ? ' | body_saved=yes' : ''
        );

        @file_put_contents($logDir . '/mail.log', $logEntry, FILE_APPEND | LOCK_EX);
    }
}
