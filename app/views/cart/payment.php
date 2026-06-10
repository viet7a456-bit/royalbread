<?php
$siteName = setting($settings, 'site_name', 'RoyalBread');
$pageTitle = 'Thanh toan online | ' . $siteName;
$pageDescription = 'Hoan tat thanh toan online cho don hang RoyalBread bang ma QR ngan hang.';
$pageImage = media_url(setting($settings, 'home_signature_image', 'assets/images/royalbread-logo.png'));
$bank = bank_transfer_details($settings);
?>

<section class="section-shell section-shell--tight">
    <div class="container payment-page">
        <div class="payment-page__grid">
            <article class="payment-card">
                <p class="section-kicker">Thanh toan online</p>
                <h1>Don hang #<?= e((string) ($order['id'] ?? 0)) ?></h1>
                <p>Quet ma QR ben canh hoac chuyen khoan dung noi dung de RoyalBread doi chieu nhanh.</p>

                <div class="payment-order-meta">
                    <span><?= e($paymentMethodLabel) ?></span>
                    <span><?= e($paymentStatusLabel) ?></span>
                    <span><?= e(format_price((int) ($order['total_amount'] ?? 0))) ?></span>
                </div>

                <div class="payment-bank-list">
                    <div>
                        <strong>Ngan hang</strong>
                        <span><?= e($bank['bank_name']) ?></span>
                    </div>
                    <div>
                        <strong>So tai khoan</strong>
                        <span><?= e($bank['account_number']) ?></span>
                    </div>
                    <div>
                        <strong>Chu tai khoan</strong>
                        <span><?= e($bank['account_holder']) ?></span>
                    </div>
                    <div>
                        <strong>Noi dung</strong>
                        <span><?= e($paymentReference) ?></span>
                    </div>
                </div>

                <div class="payment-order-lines">
                    <?php foreach ($orderItems as $item): ?>
                        <article>
                            <span><?= e((string) ($item['menu_item_name'] ?? 'Mon an')) ?></span>
                            <strong>x<?= e((string) ($item['quantity'] ?? 1)) ?></strong>
                        </article>
                    <?php endforeach; ?>
                </div>

                <form method="post" action="<?= e(url('cart/payment/confirm')) ?>" class="payment-confirm-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="order_id" value="<?= e((string) ($order['id'] ?? 0)) ?>">
                    <button type="submit" class="btn btn-primary btn-block">Toi da thanh toan</button>
                </form>
            </article>

            <aside class="payment-card payment-card--qr">
                <p class="section-kicker">Ma QR VietQR</p>
                <?php if ($qrImageUrl !== ''): ?>
                    <img class="payment-qr-image" src="<?= e($qrImageUrl) ?>" alt="Ma QR thanh toan RoyalBread">
                <?php else: ?>
                    <div class="payment-qr-placeholder">
                        <strong>Chua tao duoc QR</strong>
                        <p>Kiem tra lai thong tin ngan hang trong trang cai dat admin.</p>
                    </div>
                <?php endif; ?>

                <div class="payment-summary-box">
                    <span>Tong can thanh toan</span>
                    <strong><?= e(format_price((int) ($order['total_amount'] ?? 0))) ?></strong>
                </div>

                <a class="btn btn-outline btn-block" href="<?= e(url()) ?>">Ve trang chu</a>
            </aside>
        </div>
    </div>
</section>
