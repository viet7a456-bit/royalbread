<?php $pageTitle = 'Đánh giá sản phẩm'; ?>

<section class="admin-panel-card">
    <div class="admin-section-head">
        <div>
            <p class="admin-kicker">Đánh giá khách hàng</p>
            <h2>Duyệt review và bình luận sản phẩm</h2>
        </div>

        <div class="admin-hero-card__actions">
            <a class="admin-btn <?= $currentStatus === '' ? '' : 'admin-btn--ghost' ?>" href="<?= e(url('admin/reviews')) ?>">Tất cả</a>
            <a class="admin-btn <?= $currentStatus === 'pending' ? '' : 'admin-btn--ghost' ?>" href="<?= e(url('admin/reviews?status=pending')) ?>">Chờ duyệt</a>
            <a class="admin-btn <?= $currentStatus === 'approved' ? '' : 'admin-btn--ghost' ?>" href="<?= e(url('admin/reviews?status=approved')) ?>">Đã duyệt</a>
            <a class="admin-btn <?= $currentStatus === 'rejected' ? '' : 'admin-btn--ghost' ?>" href="<?= e(url('admin/reviews?status=rejected')) ?>">Từ chối</a>
        </div>
    </div>

    <?php if ($reviews !== []): ?>
        <div class="admin-message-list admin-review-list">
            <?php foreach ($reviews as $review): ?>
                <article class="admin-review-card">
                    <div class="admin-review-card__head">
                        <div>
                            <strong><?= e($review['full_name'] ?? 'Khách hàng') ?></strong>
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
