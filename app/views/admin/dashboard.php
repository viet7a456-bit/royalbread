<?php
$pageTitle = 'Tổng quan quản trị';
?>

<section class="admin-hero-card">
    <div>
        <p class="admin-kicker">Tổng quan hôm nay</p>
        <h2>Đơn hàng, khách hàng, khuyến mãi và chăm sóc sau bán</h2>
        <p>Theo dõi doanh thu, đơn chờ xử lý, khách hàng tiềm năng, đánh giá và chat hỗ trợ trên cùng một màn hình.</p>
    </div>
    <div class="admin-hero-card__actions">
        <a class="admin-btn" href="<?= e(url('admin/orders')) ?>">Quản lý đơn hàng</a>
        <a class="admin-btn admin-btn--ghost" href="<?= e(url('admin/settings')) ?>">Quản lý khuyến mãi</a>
    </div>
</section>

<section class="admin-stat-grid">
    <article class="admin-stat-card">
        <span>Doanh thu hoàn thành</span>
        <strong><?= e(format_price((int) $totalRevenue)) ?></strong>
        <p>Tổng tiền từ các đơn giao thành công</p>
    </article>
    <article class="admin-stat-card">
        <span>Đơn đang chờ</span>
        <strong><?= e((string) $newOrdersCount) ?></strong>
        <p>Đơn mới cần xác nhận</p>
    </article>
    <article class="admin-stat-card">
        <span>Khuyến mãi đang bật</span>
        <strong><?= e((string) $activePromotionCount) ?></strong>
        <p>Số chiến dịch đang hiển thị cho khách</p>
    </article>
    <article class="admin-stat-card">
        <span>Chat chưa đọc</span>
        <strong><?= e((string) $unreadLiveChatCount) ?></strong>
        <p>Tin nhắn trực tiếp từ khách hàng</p>
    </article>
</section>

<section class="admin-grid admin-grid--dashboard">
    <article class="admin-panel-card admin-panel-card--wide">
        <div class="admin-section-head">
            <div>
                <p class="admin-kicker">Đơn hàng mới</p>
                <h2>Đơn hàng gần đây</h2>
            </div>
            <a href="<?= e(url('admin/orders')) ?>" class="admin-btn admin-btn--ghost">Xem tất cả</a>
        </div>

        <?php if (!empty($latestOrders)): ?>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Ngày đặt</th>
                            <th>Trạng thái đơn</th>
                            <th>Trạng thái tiền</th>
                            <th>Tổng tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($latestOrders as $order): ?>
                            <?php $status = (string) ($order['status'] ?? 'pending'); ?>
                            <tr>
                                <td>#<?= e((string) $order['id']) ?></td>
                                <td><?= e((string) ($order['customer_name'] ?? 'Khách hàng')) ?><br><small><?= e((string) ($order['phone'] ?? '')) ?></small></td>
                                <td><?= e(date('d/m/Y H:i', strtotime((string) $order['created_at']))) ?></td>
                                <td>
                                    <span class="admin-status-pill <?= e(match ($status) {
                                        'processing' => 'is-processing',
                                        'completed' => 'is-completed',
                                        'cancelled' => 'is-cancelled',
                                        default => 'is-pending',
                                    }) ?>">
                                        <?= e(match ($status) {
                                            'processing' => 'Đang xử lý',
                                            'completed' => 'Hoàn thành',
                                            'cancelled' => 'Đã hủy',
                                            default => 'Đang chờ',
                                        }) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="admin-status-pill <?= e(payment_status_class((string) ($order['payment_status'] ?? 'unpaid'))) ?>">
                                        <?= e(payment_status_label((string) ($order['payment_status'] ?? 'unpaid'))) ?>
                                    </span>
                                </td>
                                <td><strong><?= e(format_price((int) ($order['total_amount'] ?? 0))) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="admin-empty-state">
                <strong>Chưa có đơn hàng nào</strong>
                <p>Khi khách đặt món thành công, đơn hàng sẽ xuất hiện ở đây.</p>
            </div>
        <?php endif; ?>
    </article>

    <article class="admin-panel-card">
        <div class="admin-section-head">
            <div>
                <p class="admin-kicker">Khách hàng nổi bật</p>
                <h2>Top khách tiềm năng</h2>
            </div>
            <a href="<?= e(url('admin/customers')) ?>" class="admin-btn admin-btn--ghost">Xem khách hàng</a>
        </div>

        <?php if (!empty($topPotentialCustomers)): ?>
            <div class="admin-message-list">
                <?php foreach ($topPotentialCustomers as $customer): ?>
                    <article>
                        <strong><?= e((string) ($customer['full_name'] ?? 'Khách hàng')) ?></strong>
                        <span><?= e((string) ($customer['membership_tier_label'] ?? 'Thành viên mới')) ?></span>
                        <p><?= e((string) ($customer['potential_segment'] ?? 'Tiềm năng')) ?> • <?= e((string) ($customer['customer_score'] ?? 0)) ?> điểm • <?= e(format_price((int) ($customer['total_spent'] ?? 0))) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="admin-empty-state">
                <strong>Chưa có dữ liệu khách nổi bật</strong>
                <p>Khách sẽ được xếp hạng sau khi bắt đầu phát sinh đơn hàng.</p>
            </div>
        <?php endif; ?>
    </article>

    <article class="admin-panel-card">
        <div class="admin-section-head">
            <div>
                <p class="admin-kicker">Bán chạy thật</p>
                <h2>Sản phẩm bán chạy</h2>
            </div>
            <a href="<?= e(url('admin/menu')) ?>" class="admin-btn admin-btn--ghost">Sửa sản phẩm</a>
        </div>

        <?php if (!empty($bestSellingItems)): ?>
            <div class="admin-featured-list">
                <?php foreach (array_slice($bestSellingItems, 0, 5) as $item): ?>
                    <article>
                        <img src="<?= e(media_url((string) ($item['image_url'] ?? ''))) ?>" alt="<?= e((string) ($item['name'] ?? 'Sản phẩm')) ?>">
                        <div>
                            <strong><?= e((string) ($item['name'] ?? 'Sản phẩm')) ?></strong>
                            <span><?= e((string) ($item['sold_quantity'] ?? 0)) ?> phần • <?= e((string) ($item['orders_count'] ?? 0)) ?> đơn</span>
                        </div>
                        <em><?= e(format_price((int) ($item['gross_revenue'] ?? 0))) ?></em>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="admin-empty-state">
                <strong>Chưa có dữ liệu bán chạy</strong>
                <p>Khi đơn hoàn thành phát sinh order_items, hệ thống sẽ thống kê tại đây.</p>
            </div>
        <?php endif; ?>
    </article>

    <article class="admin-panel-card">
        <div class="admin-section-head">
            <div>
                <p class="admin-kicker">Hệ thống</p>
                <h2>Tình trạng website</h2>
            </div>
            <a href="<?= e(url('admin/reviews')) ?>" class="admin-btn admin-btn--ghost">Duyệt đánh giá</a>
        </div>

        <div class="admin-info-list">
            <article>
                <span>Tên quán</span>
                <strong><?= e(setting($settings, 'site_name')) ?></strong>
            </article>
            <article>
                <span>Hotline</span>
                <strong><?= e(setting($settings, 'hotline')) ?></strong>
            </article>
            <article>
                <span>Đánh giá chờ duyệt</span>
                <strong><?= e((string) $pendingReviewCount) ?></strong>
            </article>
            <article>
                <span>Tin nhắn form liên hệ</span>
                <strong><?= e((string) $newMessageCount) ?></strong>
            </article>
        </div>
    </article>
</section>
