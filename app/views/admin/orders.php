<?php
$pageTitle = 'Quản lý đơn hàng';
$finalStatuses = $finalStatuses ?? ['completed', 'cancelled'];
$statusLabels = [
    'pending' => 'Đang chờ',
    'processing' => 'Đang xử lý',
    'completed' => 'Hoàn thành',
    'cancelled' => 'Đã hủy',
];
$statusClasses = [
    'pending' => 'is-pending',
    'processing' => 'is-processing',
    'completed' => 'is-completed',
    'cancelled' => 'is-cancelled',
];
$xlsxQuery = http_build_query(array_merge($_GET, ['format' => 'xlsx']));
$pdfQuery = http_build_query(array_merge($_GET, ['format' => 'pdf']));
$csvQuery = http_build_query(array_merge($_GET, ['format' => 'csv']));
?>

<section class="admin-panel-card">
    <div class="admin-section-head">
        <div>
            <p class="admin-kicker">Đơn đặt hàng</p>
            <h2>Quản lý đơn hàng từ khách</h2>
            <p>Theo dõi trạng thái xử lý, thanh toán online và thông tin đối chiếu của từng đơn.</p>
        </div>

        <form method="get" action="<?= e(url('admin/orders')) ?>" class="admin-search-form">
            <input type="text" name="search" value="<?= e($searchQuery) ?>" placeholder="Mã đơn, tên khách, email, số điện thoại...">
            <input type="date" name="date" value="<?= e($searchDate) ?>">
            <button type="submit" class="admin-btn">Tìm kiếm</button>
            <?php if ($searchQuery !== '' || $searchDate !== ''): ?>
                <a href="<?= e(url('admin/orders')) ?>" class="admin-btn admin-btn--ghost">Xóa lọc</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="admin-export-row no-print">
        <a href="<?= e(url('admin/orders/export?' . $xlsxQuery)) ?>" class="admin-btn admin-btn--excel">Xuất Excel</a>
        <a href="<?= e(url('admin/orders/export?' . $pdfQuery)) ?>" class="admin-btn admin-btn--pdf">Xuất PDF</a>
        <a href="<?= e(url('admin/orders/export?' . $csvQuery)) ?>" class="admin-btn admin-btn--ghost">Xuất CSV</a>
    </div>

    <?php if ($orders !== []): ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Mã đơn</th>
                        <th>Khách hàng</th>
                        <th>Giao hàng</th>
                        <th>Thời gian</th>
                        <th>Thanh toán</th>
                        <th>Trạng thái đơn</th>
                        <th>Trạng thái tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <?php
                        $status = (string) ($order['status'] ?? 'pending');
                        $paymentStatus = (string) ($order['payment_status'] ?? 'unpaid');
                        $isLocked = in_array($status, $finalStatuses, true);
                        ?>
                        <tr>
                            <td>
                                <strong>#<?= e((string) $order['id']) ?></strong>
                                <?php if (!empty($order['payment_reference'])): ?>
                                    <br><small>Mã CK: <?= e((string) $order['payment_reference']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= e((string) ($order['customer_name'] ?? 'Khách lẻ')) ?></strong><br>
                                <a href="tel:<?= e((string) ($order['phone'] ?? '')) ?>"><?= e((string) ($order['phone'] ?? '')) ?></a>
                                <?php if (!empty($order['customer_email'])): ?>
                                    <br><small><?= e((string) $order['customer_email']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small><?= e((string) ($order['address'] ?? '')) ?></small>
                                <?php if (!empty($order['note'])): ?>
                                    <br><small class="admin-note-text"><?= e((string) $order['note']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small><?= e(date('d/m/Y H:i', strtotime((string) $order['created_at']))) ?></small>
                            </td>
                            <td>
                                <strong><?= e(format_price((int) ($order['total_amount'] ?? 0))) ?></strong><br>
                                <small><?= e(payment_method_label((string) ($order['payment_method'] ?? 'cod'))) ?></small>
                                <?php if ((int) ($order['discount_amount'] ?? 0) > 0): ?>
                                    <br><small>Giảm: <?= e(format_price((int) $order['discount_amount'])) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="admin-status-stack">
                                    <span class="admin-status-pill <?= e($statusClasses[$status] ?? 'is-pending') ?>">
                                        <?= e($statusLabels[$status] ?? $status) ?>
                                    </span>
                                    <form method="post" action="<?= e(url('admin/orders/update-status')) ?>" class="admin-order-status-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= e((string) $order['id']) ?>">
                                        <select name="status" <?= $isLocked ? 'disabled' : '' ?> onchange="if(!this.disabled){this.form.submit();}">
                                            <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Đang chờ</option>
                                            <option value="processing" <?= $status === 'processing' ? 'selected' : '' ?>>Đang xử lý</option>
                                            <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Hoàn thành</option>
                                            <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                                        </select>
                                        <?php if ($isLocked): ?>
                                            <small class="admin-lock-note">Đã khóa chỉnh sửa</small>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </td>
                            <td>
                                <div class="admin-status-stack">
                                    <span class="admin-status-pill <?= e(payment_status_class($paymentStatus)) ?>">
                                        <?= e(payment_status_label($paymentStatus)) ?>
                                    </span>
                                    <form method="post" action="<?= e(url('admin/orders/update-payment-status')) ?>" class="admin-order-status-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= e((string) $order['id']) ?>">
                                        <select name="payment_status" onchange="this.form.submit();">
                                            <option value="unpaid" <?= $paymentStatus === 'unpaid' ? 'selected' : '' ?>>Chưa thanh toán</option>
                                            <option value="pending_confirmation" <?= $paymentStatus === 'pending_confirmation' ? 'selected' : '' ?>>Chờ xác nhận</option>
                                            <option value="paid" <?= $paymentStatus === 'paid' ? 'selected' : '' ?>>Đã thanh toán</option>
                                            <option value="refunded" <?= $paymentStatus === 'refunded' ? 'selected' : '' ?>>Đã hoàn tiền</option>
                                        </select>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="admin-empty-state">
            <strong>Chưa có đơn hàng phù hợp</strong>
            <p>Không tìm thấy đơn nào khớp với điều kiện lọc hiện tại.</p>
        </div>
    <?php endif; ?>
</section>
