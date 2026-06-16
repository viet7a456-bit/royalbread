<?php
$pageTitle = 'Đánh giá sản phẩm';
$currentStatus = trim((string) ($currentStatus ?? ''));
$currentPage = max(1, (int) ($currentPage ?? 1));
$totalPages = max(1, (int) ($totalPages ?? 1));
$totalReviews = max(0, (int) ($totalReviews ?? 0));
$visibleFrom = max(0, (int) ($visibleFrom ?? 0));
$visibleTo = max(0, (int) ($visibleTo ?? 0));

$paginationStart = max(1, $currentPage - 2);
$paginationEnd = min($totalPages, $currentPage + 2);
if (($paginationEnd - $paginationStart) < 4) {
    if ($paginationStart === 1) {
        $paginationEnd = min($totalPages, 5);
    } elseif ($paginationEnd === $totalPages) {
        $paginationStart = max(1, $totalPages - 4);
    }
}

$buildReviewPageLink = static function (int $page) use ($currentStatus): string {
    $query = ['page' => max(1, $page)];
    if ($currentStatus !== '') {
        $query['status'] = $currentStatus;
    }

    return url('admin/reviews?' . http_build_query($query));
};
?>

<section class="admin-panel-card">
    <div class="admin-section-head">
        <div>
            <p class="admin-kicker">Đánh giá khách hàng</p>
            <h2>Duyệt review và bình luận sản phẩm</h2>
        </div>

        <div class="admin-hero-card__actions">
            <a class="admin-btn <?= $currentStatus === '' ? '' : 'admin-btn--ghost' ?>" href="<?= e(url('admin/reviews?page=1')) ?>">Tất cả</a>
            <a class="admin-btn <?= $currentStatus === 'pending' ? '' : 'admin-btn--ghost' ?>" href="<?= e(url('admin/reviews?page=1&status=pending')) ?>">Chờ duyệt</a>
            <a class="admin-btn <?= $currentStatus === 'approved' ? '' : 'admin-btn--ghost' ?>" href="<?= e(url('admin/reviews?page=1&status=approved')) ?>">Đã duyệt</a>
            <a class="admin-btn <?= $currentStatus === 'rejected' ? '' : 'admin-btn--ghost' ?>" href="<?= e(url('admin/reviews?page=1&status=rejected')) ?>">Từ chối</a>
        </div>
    </div>

    <?php if ($reviews !== []): ?>
        <div class="admin-message-list admin-review-list">
            <?php foreach ($reviews as $review): ?>
                <article class="admin-review-card">
                    <div class="admin-review-card__head">
                        <div>
                            <strong><?= e($review['customer_name'] ?? $review['customer_username'] ?? 'Khách hàng') ?></strong>
                            <span><?= e($review['menu_item_name'] ?? 'Sản phẩm') ?> • Đơn #<?= e((string) ($review['order_id'] ?? 0)) ?></span>
                        </div>
                        <span class="admin-status-pill is-<?= e($review['status']) ?>"><?= e(mb_strtoupper((string) $review['status'])) ?></span>
                    </div>

                    <div class="admin-review-card__meta">
                        <span><?= str_repeat('★', max(1, (int) ($review['rating'] ?? 0))) ?></span>
                        <small><?= e($review['created_at']) ?></small>
                    </div>

                    <?php if (trim((string) ($review['review_title'] ?? '')) !== ''): ?>
                        <h3><?= e($review['review_title']) ?></h3>
                    <?php endif; ?>
                    <p><?= nl2br(e($review['review_comment'] ?? '')) ?></p>

                    <form method="post" action="<?= e(url('admin/reviews/update-status')) ?>" class="admin-review-card__actions">
                        <?= csrf_field() ?>
                        <input type="hidden" name="review_id" value="<?= e((string) $review['id']) ?>">
                        <input type="hidden" name="page" value="<?= e((string) $currentPage) ?>">
                        <input type="hidden" name="current_status" value="<?= e($currentStatus) ?>">
                        <button class="admin-btn admin-btn--ghost" type="submit" name="status" value="pending">Để chờ</button>
                        <button class="admin-btn" type="submit" name="status" value="approved">Duyệt</button>
                        <button class="admin-btn admin-btn--ghost" type="submit" name="status" value="rejected">Từ chối</button>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="admin-empty-state">
            <strong>Chưa có đánh giá nào phù hợp bộ lọc này</strong>
            <p>Khi khách mua hàng và gửi review, admin sẽ duyệt tại đây.</p>
        </div>
    <?php endif; ?>
</section>

<?php if ($totalReviews > 0): ?>
    <section class="admin-panel-card admin-menu-pagination-card">
        <div class="admin-menu-pagination">
            <div class="admin-menu-pagination__summary">
                <p class="admin-kicker">Phân trang đánh giá</p>
                <h2>Đang hiển thị <?= e((string) $visibleFrom) ?>-<?= e((string) $visibleTo) ?> / <?= e((string) $totalReviews) ?> đánh giá</h2>
                <span>Trang <?= e((string) $currentPage) ?> / <?= e((string) $totalPages) ?></span>
            </div>

            <nav class="admin-menu-pagination__nav" aria-label="Phân trang đánh giá">
                <?php if ($currentPage > 1): ?>
                    <a class="admin-menu-pagination__link admin-menu-pagination__link--nav" href="<?= e($buildReviewPageLink(1)) ?>" aria-label="Trang đầu">&laquo;</a>
                    <a class="admin-menu-pagination__link admin-menu-pagination__link--nav" href="<?= e($buildReviewPageLink($currentPage - 1)) ?>" aria-label="Trang trước">&lsaquo;</a>
                <?php endif; ?>

                <?php if ($paginationStart > 1): ?>
                    <a class="admin-menu-pagination__link" href="<?= e($buildReviewPageLink(1)) ?>">1</a>
                    <?php if ($paginationStart > 2): ?>
                        <span class="admin-menu-pagination__ellipsis">…</span>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($page = $paginationStart; $page <= $paginationEnd; $page++): ?>
                    <a class="admin-menu-pagination__link <?= $page === $currentPage ? 'is-active' : '' ?>" href="<?= e($buildReviewPageLink($page)) ?>">
                        <?= e((string) $page) ?>
                    </a>
                <?php endfor; ?>

                <?php if ($paginationEnd < $totalPages): ?>
                    <?php if ($paginationEnd < ($totalPages - 1)): ?>
                        <span class="admin-menu-pagination__ellipsis">…</span>
                    <?php endif; ?>
                    <a class="admin-menu-pagination__link" href="<?= e($buildReviewPageLink($totalPages)) ?>"><?= e((string) $totalPages) ?></a>
                <?php endif; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a class="admin-menu-pagination__link admin-menu-pagination__link--nav" href="<?= e($buildReviewPageLink($currentPage + 1)) ?>" aria-label="Trang sau">&rsaquo;</a>
                    <a class="admin-menu-pagination__link admin-menu-pagination__link--nav" href="<?= e($buildReviewPageLink($totalPages)) ?>" aria-label="Trang cuối">&raquo;</a>
                <?php endif; ?>
            </nav>
        </div>
    </section>
<?php endif; ?>
