<?php
$pageTitle = 'Quản lý doanh thu';
$xlsxQuery = http_build_query(array_merge($_GET, ['format' => 'xlsx']));
$pdfQuery = http_build_query(array_merge($_GET, ['format' => 'pdf']));
$csvQuery = http_build_query(array_merge($_GET, ['format' => 'csv']));
?>

<section class="admin-panel-card">
    <div class="admin-section-head">
        <div>
            <p class="admin-kicker">Báo cáo tài chính</p>
            <h2>Doanh thu theo ngày và tháng</h2>
            <p>Doanh thu chỉ tính trên các đơn có trạng thái hoàn thành.</p>
        </div>
    </div>

    <form method="get" action="<?= e(url('admin/revenue')) ?>" class="admin-search-form admin-search-form--block">
        <input type="date" name="date" value="<?= e($filterDate) ?>">
        <input type="month" name="month" value="<?= e($filterMonth) ?>">
        <button type="submit" class="admin-btn">Lọc doanh thu</button>
        <a href="<?= e(url('admin/revenue')) ?>" class="admin-btn admin-btn--ghost">Tháng này</a>
    </form>

    <div class="admin-export-row no-print">
        <details class="admin-export-menu">
            <summary class="admin-btn admin-export-menu__summary">Xuất file</summary>
            <div class="admin-export-menu__panel">
                <a href="<?= e(url('admin/revenue/export?' . $xlsxQuery)) ?>" class="admin-export-menu__link">Xuất Excel</a>
                <a href="<?= e(url('admin/revenue/export?' . $pdfQuery)) ?>" class="admin-export-menu__link">Xuất PDF</a>
                <a href="<?= e(url('admin/revenue/export?' . $csvQuery)) ?>" class="admin-export-menu__link">Xuất CSV</a>
            </div>
        </details>
    </div>

    <div class="admin-stat-grid admin-stat-grid--compact">
        <article class="admin-stat-card">
            <span>Tổng doanh thu lọc được</span>
            <strong><?= e(format_price((int) $totalRevenue)) ?></strong>
            <p>
                <?php if ($filterDate !== ''): ?>
                    Ngày <?= e(date('d/m/Y', strtotime($filterDate))) ?>
                <?php elseif ($filterMonth !== ''): ?>
                    Tháng <?= e(date('m/Y', strtotime($filterMonth . '-01'))) ?>
                <?php else: ?>
                    Tất cả thời gian
                <?php endif; ?>
            </p>
        </article>
        <article class="admin-stat-card">
            <span>Số đơn hoàn thành</span>
            <strong><?= e((string) count($orders)) ?> đơn</strong>
            <p>Số đơn tạo nên doanh thu hiện tại</p>
        </article>
    </div>

    <?php if (!empty($orders)): ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Mã đơn</th>
                        <th>Khách hàng</th>
                        <th>Thời gian</th>
                        <th>Phương thức</th>
                        <th>Trạng thái tiền</th>
                        <th>Giảm giá</th>
                        <th>Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><strong>#<?= e((string) $order['id']) ?></strong></td>
                            <td>
                                <strong><?= e((string) ($order['customer_name'] ?? 'Khách hàng')) ?></strong><br>
                                <small><?= e((string) ($order['phone'] ?? '')) ?></small>
                            </td>
                            <td><?= e(date('H:i d/m/Y', strtotime((string) $order['created_at']))) ?></td>
                            <td><?= e(payment_method_label((string) ($order['payment_method'] ?? 'cod'))) ?></td>
                            <td>
                                <span class="admin-status-pill <?= e(payment_status_class((string) ($order['payment_status'] ?? 'unpaid'))) ?>">
                                    <?= e(payment_status_label((string) ($order['payment_status'] ?? 'unpaid'))) ?>
                                </span>
                            </td>
                            <td><?= e(format_price((int) ($order['discount_amount'] ?? 0))) ?></td>
                            <td><strong><?= e(format_price((int) ($order['total_amount'] ?? 0))) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="admin-empty-state">
            <strong>Không có doanh thu</strong>
            <p>Chưa có đơn hoàn thành trong khoảng thời gian đang lọc.</p>
        </div>
    <?php endif; ?>
</section>
