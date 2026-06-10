<?php
$pageTitle = 'Quản lý khách hàng';
$xlsxQuery = http_build_query(array_merge($_GET, ['format' => 'xlsx']));
$pdfQuery = http_build_query(array_merge($_GET, ['format' => 'pdf']));
$csvQuery = http_build_query(array_merge($_GET, ['format' => 'csv']));
?>

<section class="admin-panel-card">
    <div class="admin-section-head">
        <div>
            <p class="admin-kicker">Tài khoản khách hàng</p>
            <h2>Thống kê khách hàng tiềm năng</h2>
            <p>Hệ thống chấm điểm theo tần suất mua, tổng chi tiêu, lần mua gần nhất và mức độ đầy đủ thông tin liên hệ.</p>
        </div>

        <form method="get" action="<?= e(url('admin/customers')) ?>" class="admin-search-form">
            <input type="text" name="search" value="<?= e($searchQuery) ?>" placeholder="Tên, username, email, số điện thoại...">
            <button type="submit" class="admin-btn">Tìm kiếm</button>
            <?php if ($searchQuery !== ''): ?>
                <a href="<?= e(url('admin/customers')) ?>" class="admin-btn admin-btn--ghost">Xóa lọc</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (!empty($topPotentialCustomers)): ?>
        <div class="admin-stat-grid admin-stat-grid--compact">
            <?php foreach ($topPotentialCustomers as $customer): ?>
                <article class="admin-stat-card">
                    <span><?= e((string) ($customer['potential_segment'] ?? 'Tiềm năng')) ?></span>
                    <strong><?= e((string) ($customer['full_name'] ?? 'Khách hàng')) ?></strong>
                    <p><?= e((string) ($customer['customer_score'] ?? 0)) ?> điểm • <?= e((string) ($customer['membership_tier_label'] ?? 'Thành viên mới')) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="admin-export-row no-print">
        <a href="<?= e(url('admin/customers/export?' . $xlsxQuery)) ?>" class="admin-btn admin-btn--excel">Xuất Excel</a>
        <a href="<?= e(url('admin/customers/export?' . $pdfQuery)) ?>" class="admin-btn admin-btn--pdf">Xuất PDF</a>
        <a href="<?= e(url('admin/customers/export?' . $csvQuery)) ?>" class="admin-btn admin-btn--ghost">Xuất CSV</a>
    </div>

    <?php if ($customers !== []): ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Tài khoản</th>
                        <th>Liên hệ</th>
                        <th>Hành vi mua</th>
                        <th>Tiềm năng</th>
                        <th>Lần mua gần nhất</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $customer): ?>
                        <?php
                        $segmentClass = match ((string) ($customer['potential_segment'] ?? '')) {
                            'VIP' => 'is-completed',
                            'Tiem nang' => 'is-processing',
                            'Can cham soc' => 'is-pending',
                            default => 'is-pending',
                        };
                        $lastOrderAt = trim((string) ($customer['last_order_at'] ?? ''));
                        ?>
                        <tr>
                            <td>
                                <strong><?= e((string) ($customer['full_name'] ?? 'Khách hàng')) ?></strong><br>
                                <small>@<?= e((string) ($customer['username'] ?? 'guest')) ?></small><br>
                                <small><?= e((string) ($customer['membership_tier_label'] ?? 'Thành viên mới')) ?></small>
                            </td>
                            <td>
                                <?php if (!empty($customer['phone'])): ?>
                                    <a href="tel:<?= e((string) $customer['phone']) ?>"><?= e((string) $customer['phone']) ?></a><br>
                                <?php endif; ?>
                                <?php if (!empty($customer['email'])): ?>
                                    <a href="mailto:<?= e((string) $customer['email']) ?>"><?= e((string) $customer['email']) ?></a>
                                <?php else: ?>
                                    <small>Chưa có email</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= e((string) ($customer['orders_count'] ?? 0)) ?> đơn</strong><br>
                                <small>Đã chi: <?= e(format_price((int) ($customer['total_spent'] ?? 0))) ?></small><br>
                                <small>Đơn TB: <?= e(format_price((int) ($customer['average_order_value'] ?? 0))) ?></small>
                            </td>
                            <td>
                                <span class="admin-status-pill <?= e($segmentClass) ?>">
                                    <?= e((string) ($customer['potential_segment'] ?? 'Mới đăng ký')) ?>
                                </span>
                                <br><small><?= e((string) ($customer['customer_score'] ?? 0)) ?> điểm</small>
                                <?php if (($customer['days_since_last_order'] ?? null) !== null): ?>
                                    <br><small><?= e((string) $customer['days_since_last_order']) ?> ngày từ lần mua gần nhất</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small><?= $lastOrderAt !== '' ? e(date('d/m/Y H:i', strtotime($lastOrderAt))) : 'Chưa có đơn' ?></small><br>
                                <small>Đăng ký: <?= e(date('d/m/Y H:i', strtotime((string) $customer['created_at']))) ?></small>
                            </td>
                            <td>
                                <?php $orderSearch = trim((string) (($customer['phone'] ?? '') !== '' ? $customer['phone'] : ($customer['full_name'] ?? ''))); ?>
                                <a class="admin-text-btn" href="<?= e(url('admin/orders?search=' . urlencode($orderSearch))) ?>">Xem đơn hàng</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="admin-empty-state">
            <strong>Chưa có khách hàng phù hợp</strong>
            <p>Không tìm thấy khách nào khớp với điều kiện tìm kiếm hiện tại.</p>
        </div>
    <?php endif; ?>
</section>
