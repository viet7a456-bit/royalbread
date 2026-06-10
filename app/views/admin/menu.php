<?php
$pageTitle = 'Quản lý thực đơn';
?>

<section class="admin-panel-card">
    <div class="admin-section-head">
        <div>
            <p class="admin-kicker">Thêm món mới</p>
            <h2>Cập nhật thực đơn RoyalBread</h2>
        </div>
    </div>

    <form class="admin-form" method="post" action="<?= e(url('admin/menu/store')) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
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

<section class="admin-menu-sections">
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
                <?php if ($categoryItems === []): ?>
                    <div class="admin-empty-state">
                        <strong>Chưa có món trong danh mục này</strong>
                        <p>Bạn có thể thêm món mới ở biểu mẫu phía trên.</p>
                    </div>
                <?php else: ?>
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
                                    <button class="admin-text-btn admin-text-btn--danger" type="submit">Xóa món</button>
                                </form>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </details>
    <?php endforeach; ?>
</section>
