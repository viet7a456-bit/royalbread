<?php
$pageTitle = 'Quản lý sản phẩm';
$currentPage = max(1, (int) ($currentPage ?? 1));
$totalPages = max(1, (int) ($totalPages ?? 1));
$totalItems = max(0, (int) ($totalItems ?? 0));
$perPage = max(1, (int) ($perPage ?? 10));
$visibleFrom = max(0, (int) ($visibleFrom ?? 0));
$visibleTo = max(0, (int) ($visibleTo ?? 0));
$buildMenuPageLink = static fn (int $page): string => url('admin/menu?page=' . max(1, $page));
$paginationStart = max(1, $currentPage - 2);
$paginationEnd = min($totalPages, $currentPage + 2);
if (($paginationEnd - $paginationStart) < 4) {
    if ($paginationStart === 1) {
        $paginationEnd = min($totalPages, 5);
    } elseif ($paginationEnd === $totalPages) {
        $paginationStart = max(1, $totalPages - 4);
    }
}
?>

<section class="admin-panel-card">
    <div class="admin-section-head">
        <div>
            <p class="admin-kicker">Thêm món mới</p>
            <h2>Cập nhật sản phẩm RoyalBread</h2>
        </div>
    </div>

    <form class="admin-form" method="post" action="<?= e(url('admin/menu/store')) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="page" value="<?= e((string) $currentPage) ?>">

        <div class="admin-form-grid">
            <label>
                Tên món
                <input type="text" name="name" required>
            </label>
            <label>
                Nhóm món
                <select name="category_id" required>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= e((string) $category['id']) ?>"><?= e($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Giá bán
                <input type="number" name="price" min="0" required>
            </label>
            <label>
                Thứ tự
                <input type="number" name="sort_order" min="0" value="99">
            </label>
            <label class="admin-form-grid__full">
                Mô tả
                <textarea name="description" rows="3"></textarea>
            </label>
            <label>
                Tải ảnh mới (từ máy tính)
                <input type="file" name="image_file" accept="image/*">
            </label>
            <label>
                Hoặc nhập link ảnh (URL)
                <input type="text" name="image_url" placeholder="https://...">
            </label>
        </div>

        <div class="admin-checkbox-row">
            <label><input type="checkbox" name="is_featured" value="1"> Món nổi bật</label>
            <label><input type="checkbox" name="is_available" value="1" checked> Đang phục vụ</label>
        </div>

        <button class="admin-btn" type="submit">Thêm món</button>
    </form>
</section>

<?php if ($totalItems > 0): ?>
    <section class="admin-panel-card admin-menu-pagination-card">
        <div class="admin-menu-pagination">
            <div class="admin-menu-pagination__summary">
                <p class="admin-kicker">Phân trang sản phẩm</p>
                <h2>Đang hiển thị <?= e((string) $visibleFrom) ?>-<?= e((string) $visibleTo) ?> / <?= e((string) $totalItems) ?> món</h2>
                <span><?= e((string) $perPage) ?> món mỗi trang</span>
            </div>

            <nav class="admin-menu-pagination__nav" aria-label="Phân trang sản phẩm">
                <?php if ($currentPage > 1): ?>
                    <a class="admin-menu-pagination__link admin-menu-pagination__link--nav" href="<?= e($buildMenuPageLink(1)) ?>" aria-label="Trang đầu">&laquo;</a>
                    <a class="admin-menu-pagination__link admin-menu-pagination__link--nav" href="<?= e($buildMenuPageLink($currentPage - 1)) ?>" aria-label="Trang trước">&lsaquo;</a>
                <?php endif; ?>

                <?php if ($paginationStart > 1): ?>
                    <a class="admin-menu-pagination__link" href="<?= e($buildMenuPageLink(1)) ?>">1</a>
                    <?php if ($paginationStart > 2): ?>
                        <span class="admin-menu-pagination__ellipsis">…</span>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($page = $paginationStart; $page <= $paginationEnd; $page++): ?>
                    <a class="admin-menu-pagination__link <?= $page === $currentPage ? 'is-active' : '' ?>" href="<?= e($buildMenuPageLink($page)) ?>">
                        <?= e((string) $page) ?>
                    </a>
                <?php endfor; ?>

                <?php if ($paginationEnd < $totalPages): ?>
                    <?php if ($paginationEnd < ($totalPages - 1)): ?>
                        <span class="admin-menu-pagination__ellipsis">…</span>
                    <?php endif; ?>
                    <a class="admin-menu-pagination__link" href="<?= e($buildMenuPageLink($totalPages)) ?>"><?= e((string) $totalPages) ?></a>
                <?php endif; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a class="admin-menu-pagination__link admin-menu-pagination__link--nav" href="<?= e($buildMenuPageLink($currentPage + 1)) ?>" aria-label="Trang sau">&rsaquo;</a>
                    <a class="admin-menu-pagination__link admin-menu-pagination__link--nav" href="<?= e($buildMenuPageLink($totalPages)) ?>" aria-label="Trang cuối">&raquo;</a>
                <?php endif; ?>
            </nav>
        </div>
    </section>
<?php endif; ?>

<section class="admin-menu-sections">
    <?php if ($menuSections === []): ?>
        <section class="admin-panel-card">
            <div class="admin-empty-state">
                <strong>Chưa có món nào trong trang này</strong>
                <p>Hãy thêm món mới ở phía trên hoặc chuyển sang trang khác.</p>
            </div>
        </section>
    <?php endif; ?>

    <?php foreach ($menuSections as $index => $section): ?>
        <?php
        $category = $section['category'];
        $categoryItems = $section['items'];
        ?>
        <details class="admin-panel-card admin-menu-group" <?= $index === 0 ? 'open' : '' ?>>
            <summary class="admin-menu-group__summary">
                <div>
                    <p class="admin-kicker"><?= e($category['slug']) ?></p>
                    <h2><?= e($category['name']) ?></h2>
                </div>
                <span class="admin-menu-group__count"><?= e((string) count($categoryItems)) ?> món</span>
            </summary>

            <div class="admin-menu-group__content">
                <div class="admin-editor-stack">
                    <?php foreach ($categoryItems as $item): ?>
                        <article class="admin-panel-card item-editor-card">
                            <div class="item-editor-card__media">
                                <img src="<?= e(media_url($item['image_url'])) ?>" alt="<?= e($item['name']) ?>">
                                <div>
                                    <strong><?= e($item['name']) ?></strong>
                                    <span><?= e($item['category_name']) ?></span>
                                </div>
                            </div>

                            <form class="admin-form" method="post" action="<?= e(url('admin/menu/update')) ?>" enctype="multipart/form-data">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= e((string) $item['id']) ?>">
                                <input type="hidden" name="page" value="<?= e((string) $currentPage) ?>">

                                <div class="admin-form-grid">
                                    <label>
                                        Tên món
                                        <input type="text" name="name" value="<?= e($item['name']) ?>" required>
                                    </label>
                                    <label>
                                        Nhóm món
                                        <select name="category_id" required>
                                            <?php foreach ($categories as $categoryOption): ?>
                                                <option value="<?= e((string) $categoryOption['id']) ?>" <?= (int) $item['category_id'] === (int) $categoryOption['id'] ? 'selected' : '' ?>>
                                                    <?= e($categoryOption['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <label>
                                        Giá bán
                                        <input type="number" name="price" value="<?= e((string) $item['price']) ?>" min="0" required>
                                    </label>
                                    <label>
                                        Thứ tự
                                        <input type="number" name="sort_order" value="<?= e((string) $item['sort_order']) ?>" min="0">
                                    </label>
                                    <label class="admin-form-grid__full">
                                        Mô tả
                                        <textarea name="description" rows="3"><?= e($item['description']) ?></textarea>
                                    </label>
                                    <label>
                                        Tải ảnh mới (từ máy tính)
                                        <input type="file" name="image_file" accept="image/*">
                                    </label>
                                    <label>
                                        Hoặc nhập link ảnh (URL hiện tại)
                                        <input type="text" name="image_url" value="<?= e($item['image_url']) ?>">
                                    </label>
                                </div>

                                <div class="item-editor-card__footer">
                                    <div class="admin-checkbox-row">
                                        <label><input type="checkbox" name="is_featured" value="1" <?= !empty($item['is_featured']) ? 'checked' : '' ?>> Nổi bật</label>
                                        <label><input type="checkbox" name="is_available" value="1" <?= !empty($item['is_available']) ? 'checked' : '' ?>> Đang phục vụ</label>
                                    </div>

                                    <div class="item-editor-card__actions">
                                        <button class="admin-btn admin-btn--ghost" type="submit">Lưu thay đổi</button>
                                    </div>
                                </div>
                            </form>

                            <form method="post" action="<?= e(url('admin/menu/delete')) ?>" onsubmit="return confirm('Xóa món này khỏi thực đơn?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= e((string) $item['id']) ?>">
                                <input type="hidden" name="page" value="<?= e((string) $currentPage) ?>">
                                <button class="admin-text-btn admin-text-btn--danger" type="submit">Xóa món</button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </details>
    <?php endforeach; ?>
</section>

<?php if ($totalItems > 0): ?>
    <section class="admin-panel-card admin-menu-pagination-card">
        <div class="admin-menu-pagination">
            <div class="admin-menu-pagination__summary">
                <p class="admin-kicker">Phân trang sản phẩm</p>
                <h2>Trang <?= e((string) $currentPage) ?> / <?= e((string) $totalPages) ?></h2>
                <span>Dùng để chuyển nhanh giữa các nhóm món trong khu quản trị</span>
            </div>

            <nav class="admin-menu-pagination__nav" aria-label="Phân trang sản phẩm">
                <?php if ($currentPage > 1): ?>
                    <a class="admin-menu-pagination__link admin-menu-pagination__link--nav" href="<?= e($buildMenuPageLink(1)) ?>" aria-label="Trang đầu">&laquo;</a>
                    <a class="admin-menu-pagination__link admin-menu-pagination__link--nav" href="<?= e($buildMenuPageLink($currentPage - 1)) ?>" aria-label="Trang trước">&lsaquo;</a>
                <?php endif; ?>

                <?php if ($paginationStart > 1): ?>
                    <a class="admin-menu-pagination__link" href="<?= e($buildMenuPageLink(1)) ?>">1</a>
                    <?php if ($paginationStart > 2): ?>
                        <span class="admin-menu-pagination__ellipsis">…</span>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($page = $paginationStart; $page <= $paginationEnd; $page++): ?>
                    <a class="admin-menu-pagination__link <?= $page === $currentPage ? 'is-active' : '' ?>" href="<?= e($buildMenuPageLink($page)) ?>">
                        <?= e((string) $page) ?>
                    </a>
                <?php endfor; ?>

                <?php if ($paginationEnd < $totalPages): ?>
                    <?php if ($paginationEnd < ($totalPages - 1)): ?>
                        <span class="admin-menu-pagination__ellipsis">…</span>
                    <?php endif; ?>
                    <a class="admin-menu-pagination__link" href="<?= e($buildMenuPageLink($totalPages)) ?>"><?= e((string) $totalPages) ?></a>
                <?php endif; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a class="admin-menu-pagination__link admin-menu-pagination__link--nav" href="<?= e($buildMenuPageLink($currentPage + 1)) ?>" aria-label="Trang sau">&rsaquo;</a>
                    <a class="admin-menu-pagination__link admin-menu-pagination__link--nav" href="<?= e($buildMenuPageLink($totalPages)) ?>" aria-label="Trang cuối">&raquo;</a>
                <?php endif; ?>
            </nav>
        </div>
    </section>
<?php endif; ?>
