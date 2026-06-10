<?php
$pageTitle = 'Cài đặt website';

$renderSelectOptions = static function (array $groups, string $selectedValue = ''): string {
    ob_start();
    ?>
    <option value="">-- Chọn món --</option>
    <?php foreach ($groups as $groupName => $groupItems): ?>
        <optgroup label="<?= e($groupName) ?>">
            <?php foreach ($groupItems as $candidate): ?>
                <option value="<?= e((string) $candidate['id']) ?>" <?= $selectedValue !== '' && $selectedValue === (string) $candidate['id'] ? 'selected' : '' ?>>
                    <?= e($candidate['name']) ?> - <?= e(format_price($candidate['price'])) ?>
                </option>
            <?php endforeach; ?>
        </optgroup>
    <?php endforeach; ?>
    <?php

    return trim((string) ob_get_clean());
};

$spotlightGroups = [];
foreach ($spotlightCandidates as $candidate) {
    $groupName = (string) ($candidate['category_name'] ?? 'Khác');
    $spotlightGroups[$groupName][] = $candidate;
}

$drinkCategorySlugs = ['tra-nhiet-doi', 'do-uong-truyen-thong', 'cafe'];
$drinkCandidateGroups = [];
foreach ($spotlightCandidates as $candidate) {
    $groupName = (string) ($candidate['category_name'] ?? 'Khác');
    $groupSlug = (string) ($candidate['category_slug'] ?? '');
    if (!in_array($groupSlug, $drinkCategorySlugs, true)) {
        continue;
    }

    $drinkCandidateGroups['Đồ uống'][] = $candidate;
}

$allItemOptionsMarkup = $renderSelectOptions($spotlightGroups);
$drinkOptionsMarkup = $renderSelectOptions($drinkCandidateGroups);
$defaultBannerMedia = $defaultHomeMedia ?? [];
$bankTransferDefaults = bank_transfer_details($settings);
?>

<section class="admin-panel-card">
    <div class="admin-section-head">
        <div>
            <p class="admin-kicker">Thông tin website</p>
            <h2>Cập nhật nhận diện và liên hệ</h2>
        </div>
    </div>

    <form class="admin-form" method="post" action="<?= e(url('admin/settings')) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="admin-form-grid">
            <label>
                Tên quán
                <input type="text" name="site_name" value="<?= e(setting($settings, 'site_name')) ?>">
            </label>
            <label>
                Hotline
                <input type="text" name="hotline" value="<?= e(setting($settings, 'hotline')) ?>">
            </label>
            <label class="admin-form-grid__full">
                Khẩu hiệu
                <input type="text" name="tagline" value="<?= e(setting($settings, 'tagline')) ?>">
            </label>
            <label class="admin-form-grid__full">
                Địa chỉ
                <input type="text" name="address" value="<?= e(setting($settings, 'address')) ?>">
            </label>
            <label class="admin-form-grid__full">
                Địa chỉ bản đồ (Google Maps)
                <input type="text" name="map_query" value="<?= e(setting($settings, 'map_query', '28 Dã Tượng, P. Lê Thanh Nghị, TP Hải Dương, Hải Dương, Việt Nam')) ?>">
            </label>
            <label class="admin-form-grid__full">
                Giờ mở cửa
                <input type="text" name="opening_hours" value="<?= e(setting($settings, 'opening_hours')) ?>">
            </label>
            <label class="admin-form-grid__full">
                Link ShopeeFood
                <input type="text" name="shopeefood_url" value="<?= e(setting($settings, 'shopeefood_url')) ?>">
            </label>
            <label class="admin-form-grid__full">
                Giới thiệu quán
                <textarea name="about_text" rows="6"><?= e(setting($settings, 'about_text')) ?></textarea>
            </label>
            <label>
                Ngân hàng chuyển khoản
                <input type="text" name="bank_name" value="<?= e($bankTransferDefaults['bank_name']) ?>">
            </label>
            <label>
                Mã ngân hàng VietQR
                <input type="text" name="bank_bin" value="<?= e($bankTransferDefaults['bank_bin']) ?>" placeholder="VD: mbbank, vietcombank, acb">
            </label>
            <label>
                Số tài khoản
                <input type="text" name="bank_account_number" value="<?= e($bankTransferDefaults['account_number']) ?>">
            </label>
            <label class="admin-form-grid__full">
                Chủ tài khoản
                <input type="text" name="bank_account_holder" value="<?= e($bankTransferDefaults['account_holder']) ?>">
            </label>
            <label class="admin-form-grid__full">
                Nội dung chuyển khoản gợi ý
                <input type="text" name="bank_transfer_note" value="<?= e($bankTransferDefaults['transfer_note']) ?>">
            </label>
            <label class="admin-form-grid__full">
                Từ khóa SEO mặc định
                <input type="text" name="seo_default_keywords" value="<?= e(setting($settings, 'seo_default_keywords', 'RoyalBread, bánh mì chảo, bánh mì Hải Dương, đồ uống')) ?>">
            </label>
        </div>

        <section class="admin-form-group">
            <div class="admin-repeat-header">
                <div>
                    <p class="admin-kicker">Món nổi bật trang chủ</p>
                    <h3>Chọn món hiển thị ở khối như ảnh 2</h3>
                    <p>Bạn có thể thêm hoặc bớt số lượng món tùy ý. Các món này sẽ hiện ở cụm món nổi bật ngoài trang chủ.</p>
                </div>
                <button
                    type="button"
                    class="admin-btn admin-btn--ghost admin-repeat-add"
                    data-add-row-button
                    data-target-list="home-spotlight-list"
                    data-template-id="template-home-spotlight-row"
                >
                    + Thêm món
                </button>
            </div>

            <div class="admin-dynamic-list" id="home-spotlight-list" data-dynamic-list data-label-base="Món hiển thị">
                <?php foreach ($spotlightSettingKeys as $position => $key): ?>
                    <?php
                    $displayNumber = $position + 1;
                    $selectedValue = setting($settings, $key, (string) ($defaultSpotlightMap[$key] ?? ''));
                    $storageIndex = (int) preg_replace('/\D+/', '', $key);
                    ?>
                    <div class="admin-dynamic-row" data-dynamic-row data-storage-index="<?= e((string) $storageIndex) ?>">
                        <label>
                            <span data-row-label><?= e('Món hiển thị ' . $displayNumber) ?></span>
                            <select name="<?= e($key) ?>">
                                <?= $renderSelectOptions($spotlightGroups, (string) $selectedValue) ?>
                            </select>
                        </label>
                        <button type="button" class="admin-text-btn admin-text-btn--danger" data-remove-row>Xóa</button>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="admin-form-group">
            <div class="admin-repeat-header">
                <div>
                    <p class="admin-kicker">Món đề xuất trang giỏ hàng</p>
                    <h3>Chọn món hiển thị ở khối Món ngon đề xuất</h3>
                    <p>Bạn có thể thêm hoặc bớt số lượng món gợi ý ở cuối trang giỏ hàng để khách bấm thêm nhanh.</p>
                </div>
                <button
                    type="button"
                    class="admin-btn admin-btn--ghost admin-repeat-add"
                    data-add-row-button
                    data-target-list="cart-recommend-list"
                    data-template-id="template-cart-recommend-row"
                >
                    + Thêm món
                </button>
            </div>

            <div class="admin-dynamic-list" id="cart-recommend-list" data-dynamic-list data-label-base="Món đề xuất">
                <?php foreach ($cartRecommendationKeys as $position => $key): ?>
                    <?php
                    $displayNumber = $position + 1;
                    $selectedValue = setting($settings, $key, (string) ($defaultCartRecommendationMap[$key] ?? ''));
                    $storageIndex = (int) preg_replace('/\D+/', '', $key);
                    ?>
                    <div class="admin-dynamic-row" data-dynamic-row data-storage-index="<?= e((string) $storageIndex) ?>">
                        <label>
                            <span data-row-label><?= e('Món đề xuất ' . $displayNumber) ?></span>
                            <select name="<?= e($key) ?>">
                                <?= $renderSelectOptions($spotlightGroups, (string) $selectedValue) ?>
                            </select>
                        </label>
                        <button type="button" class="admin-text-btn admin-text-btn--danger" data-remove-row>Xóa</button>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="admin-form-group">
            <div class="admin-repeat-header">
                <div>
                    <p class="admin-kicker">Đồ uống được yêu thích</p>
                    <h3>Chọn đồ uống hiển thị ngoài trang chủ</h3>
                    <p>Bạn có thể thêm bớt số lượng thẻ đồ uống. Mỗi thẻ sẽ bấm được để đi tới thực đơn đồ uống.</p>
                </div>
                <button
                    type="button"
                    class="admin-btn admin-btn--ghost admin-repeat-add"
                    data-add-row-button
                    data-target-list="home-drink-list"
                    data-template-id="template-home-drink-row"
                >
                    + Thêm món
                </button>
            </div>

            <div class="admin-dynamic-list" id="home-drink-list" data-dynamic-list data-label-base="Đồ uống">
                <?php foreach ($homeDrinkKeys as $position => $key): ?>
                    <?php
                    $displayNumber = $position + 1;
                    $selectedValue = setting($settings, $key, (string) ($defaultHomeDrinkMap[$key] ?? ''));
                    $storageIndex = (int) preg_replace('/\D+/', '', $key);
                    ?>
                    <div class="admin-dynamic-row" data-dynamic-row data-storage-index="<?= e((string) $storageIndex) ?>">
                        <label>
                            <span data-row-label><?= e('Đồ uống ' . $displayNumber) ?></span>
                            <select name="<?= e($key) ?>">
                                <?= $renderSelectOptions($drinkCandidateGroups, (string) $selectedValue) ?>
                            </select>
                        </label>
                        <button type="button" class="admin-text-btn admin-text-btn--danger" data-remove-row>Xóa</button>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="admin-form-group">
            <div class="admin-repeat-header">
                <div>
                    <p class="admin-kicker">Banner trang chủ</p>
                    <h3>Thêm hoặc thay đổi ảnh banner</h3>
                    <p>Bạn có thể thêm nhiều ảnh banner hơn trước. Nếu upload GIF thì ngoài website sẽ hiển thị như ảnh động.</p>
                </div>
                <button
                    type="button"
                    class="admin-btn admin-btn--ghost admin-repeat-add"
                    data-add-row-button
                    data-target-list="home-banner-list"
                    data-template-id="template-home-banner-row"
                >
                    + Thêm ảnh
                </button>
            </div>

            <div class="admin-image-grid admin-dynamic-list" id="home-banner-list" data-dynamic-list data-label-base="Banner slide">
                <?php foreach ($bannerSlideKeys as $position => $key): ?>
                    <?php
                    $displayNumber = $position + 1;
                    $storageIndex = (int) preg_replace('/\D+/', '', $key);
                    $imageValue = setting($settings, $key, $defaultBannerMedia[$key] ?? '');
                    ?>
                    <div class="admin-image-field admin-dynamic-row" data-dynamic-row data-storage-index="<?= e((string) $storageIndex) ?>">
                        <div class="admin-dynamic-row__head">
                            <strong data-row-label><?= e('Banner slide ' . $displayNumber) ?></strong>
                            <button type="button" class="admin-text-btn admin-text-btn--danger" data-remove-row>Xóa</button>
                        </div>
                        <small class="admin-form-hint">Ảnh nền cho banner trang chủ. Có thể dùng GIF để tạo ảnh động.</small>
                        <label>
                            Tải ảnh mới:
                            <input type="file" name="<?= e($key) ?>_file" accept="image/*" data-image-upload-input>
                        </label>
                        <label>
                            Hoặc Link (URL):
                            <input type="text" name="<?= e($key) ?>" value="<?= e($imageValue) ?>" data-image-url-input>
                        </label>
                        <span class="admin-image-preview<?= $imageValue === '' ? ' is-empty' : '' ?>" data-image-preview>
                            <img src="<?= $imageValue !== '' ? e(media_url($imageValue)) : '' ?>" alt="<?= e('Banner slide ' . $displayNumber) ?>" <?= $imageValue === '' ? 'hidden' : '' ?> data-image-preview-img>
                            <span <?= $imageValue !== '' ? 'hidden' : '' ?> data-image-preview-placeholder>Chưa có ảnh xem trước. Lưu lại để áp dụng hoặc nhập link ảnh.</span>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="admin-form-group">
            <div>
                <p class="admin-kicker">Ảnh hiển thị cố định</p>
                <h3>Thay đổi các ảnh còn lại từ admin</h3>
                <p>Bạn có thể đổi ảnh món nổi bật giữa trang hoặc ảnh nền trang liên hệ ở đây.</p>
            </div>

            <div class="admin-image-grid">
                <?php foreach (['home_signature_image' => 'Ảnh món nổi bật giữa trang', 'contact_hero_image' => 'Ảnh nền trang Liên hệ'] as $key => $label): ?>
                    <?php $imageValue = setting($settings, $key, $defaultBannerMedia[$key] ?? ''); ?>
                    <div class="admin-image-field">
                        <strong><?= e($label) ?></strong>
                        <small class="admin-form-hint"><?= e($key === 'home_signature_image' ? 'Ảnh chính ở khối món nổi bật trên trang chủ.' : 'Ảnh nền phía sau phần mở đầu trang Liên hệ.') ?></small>
                        <label>
                            Tải ảnh mới:
                            <input type="file" name="<?= e($key) ?>_file" accept="image/*" data-image-upload-input>
                        </label>
                        <label>
                            Hoặc Link (URL):
                            <input type="text" name="<?= e($key) ?>" value="<?= e($imageValue) ?>" data-image-url-input>
                        </label>
                        <span class="admin-image-preview<?= $imageValue === '' ? ' is-empty' : '' ?>" data-image-preview>
                            <img src="<?= $imageValue !== '' ? e(media_url($imageValue)) : '' ?>" alt="<?= e($label) ?>" <?= $imageValue === '' ? 'hidden' : '' ?> data-image-preview-img>
                            <span <?= $imageValue !== '' ? 'hidden' : '' ?> data-image-preview-placeholder>Chưa có ảnh xem trước. Lưu lại để áp dụng hoặc nhập link ảnh.</span>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <button class="admin-btn" type="submit">Lưu cài đặt</button>
        <section class="admin-form-group">
            <div>
                <p class="admin-kicker">Ảnh 3 thẻ danh mục</p>
                <h3>Đổi ảnh cho Bánh mì, Bánh mì chảo và Đồ uống</h3>
                <p>Các ảnh này hiển thị ở cụm 3 thẻ ngay dưới ô tìm kiếm ngoài trang chủ.</p>
            </div>

            <div class="admin-image-grid">
                <?php
                $homeCategoryCardFields = [
                    'home_category_card_bread_image' => 'Ảnh thẻ Bánh mì',
                    'home_category_card_pan_image' => 'Ảnh thẻ Bánh mì chảo',
                    'home_category_card_drink_image' => 'Ảnh thẻ Đồ uống',
                ];
                ?>
                <?php foreach ($homeCategoryCardFields as $key => $label): ?>
                    <?php $imageValue = setting($settings, $key, ''); ?>
                    <div class="admin-image-field">
                        <strong><?= e($label) ?></strong>
                        <small class="admin-form-hint">Nếu để trống thì website sẽ tự lấy ảnh từ món đầu tiên trong nhóm tương ứng.</small>
                        <label>
                            Tải ảnh mới:
                            <input type="file" name="<?= e($key) ?>_file" accept="image/*" data-image-upload-input>
                        </label>
                        <label>
                            Hoặc Link (URL):
                            <input type="text" name="<?= e($key) ?>" value="<?= e($imageValue) ?>" data-image-url-input>
                        </label>
                        <span class="admin-image-preview<?= $imageValue === '' ? ' is-empty' : '' ?>" data-image-preview>
                            <img src="<?= $imageValue !== '' ? e(media_url($imageValue)) : '' ?>" alt="<?= e($label) ?>" <?= $imageValue === '' ? 'hidden' : '' ?> data-image-preview-img>
                            <span <?= $imageValue !== '' ? 'hidden' : '' ?> data-image-preview-placeholder>Chưa có ảnh xem trước. Lưu lại để áp dụng hoặc nhập link ảnh.</span>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <button class="admin-btn" type="submit">Lưu cài đặt</button>
    </form>
</section>

<section class="admin-panel-card">
    <div class="admin-section-head">
        <div>
            <p class="admin-kicker">Khuyến mãi và thông báo</p>
            <h2>Tạo chiến dịch giảm giá</h2>
            <p>Khi tạo khuyến mãi mới, website sẽ hiển thị trong tài khoản khách. Có thể gửi email thông báo ngay cho nhóm khách phù hợp.</p>
        </div>
    </div>

    <form class="admin-form" method="post" action="<?= e(url('admin/promotions/store')) ?>">
        <?= csrf_field() ?>
        <div class="admin-form-grid">
            <label class="admin-form-grid__full">
                Tiêu đề khuyến mãi
                <input type="text" name="title" placeholder="VD: Giảm 15% cho thành viên Vàng" required>
            </label>
            <label class="admin-form-grid__full">
                Nội dung
                <textarea name="content" rows="4" placeholder="Mô tả ưu đãi, điều kiện áp dụng..." required></textarea>
            </label>
            <label>
                Nhóm khách áp dụng
                <select name="target_tier">
                    <option value="all">Tất cả khách hàng</option>
                    <option value="new">Thành viên mới</option>
                    <option value="silver">Thành viên Bạc</option>
                    <option value="gold">Thành viên Vàng</option>
                </select>
            </label>
            <label>
                Giảm theo %
                <input type="number" name="discount_percent" min="0" max="100" value="0">
            </label>
            <label>
                Hoặc giảm tiền
                <input type="number" name="discount_amount" min="0" step="1000" value="0">
            </label>
            <label>
                Mã coupon
                <input type="text" name="coupon_code" placeholder="ROYAL15">
            </label>
            <label>
                Hạn dùng
                <input type="datetime-local" name="expires_at">
            </label>
            <label class="admin-checkbox-row">
                <input type="checkbox" name="is_active" value="1" checked>
                <span>Kích hoạt ngay trên website</span>
            </label>
            <label class="admin-checkbox-row">
                <input type="checkbox" name="send_email" value="1">
                <span>Gửi email thông báo cho khách phù hợp</span>
            </label>
        </div>
        <button class="admin-btn" type="submit">Tạo khuyến mãi</button>
    </form>

    <div class="admin-section-head" style="margin-top: 28px;">
        <div>
            <p class="admin-kicker">Chiến dịch hiện có</p>
            <h2>Danh sách khuyến mãi</h2>
        </div>
    </div>

    <?php if (!empty($promotions)): ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Tiêu đề</th>
                        <th>Nhóm khách</th>
                        <th>Ưu đãi</th>
                        <th>Coupon</th>
                        <th>Hạn dùng</th>
                        <th>Trạng thái</th>
                        <th>Xóa</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($promotions as $promotion): ?>
                        <?php
                        $discountParts = [];
                        if ((int) ($promotion['discount_percent'] ?? 0) > 0) {
                            $discountParts[] = (int) $promotion['discount_percent'] . '%';
                        }
                        if ((int) ($promotion['discount_amount'] ?? 0) > 0) {
                            $discountParts[] = format_price((int) $promotion['discount_amount']);
                        }
                        $expiresAt = trim((string) ($promotion['expires_at'] ?? ''));
                        $isActive = (int) ($promotion['is_active'] ?? 0) === 1 && ($expiresAt === '' || strtotime($expiresAt) >= time());
                        ?>
                        <tr>
                            <td>
                                <strong><?= e((string) ($promotion['title'] ?? 'Khuyến mãi')) ?></strong><br>
                                <small><?= e((string) ($promotion['content'] ?? '')) ?></small>
                            </td>
                            <td><?= e(promotion_target_label((string) ($promotion['target_tier'] ?? 'all'))) ?></td>
                            <td><?= e($discountParts !== [] ? implode(' + ', $discountParts) : 'Không có') ?></td>
                            <td><?= e((string) ($promotion['coupon_code'] ?? 'Không có')) ?></td>
                            <td><?= e($expiresAt !== '' ? date('d/m/Y H:i', strtotime($expiresAt)) : 'Không giới hạn') ?></td>
                            <td>
                                <span class="admin-status-pill <?= $isActive ? 'is-completed' : 'is-cancelled' ?>">
                                    <?= e($isActive ? 'Đang hoạt động' : 'Đã tắt/hết hạn') ?>
                                </span>
                            </td>
                            <td>
                                <form method="post" action="<?= e(url('admin/promotions/delete')) ?>" onsubmit="return confirm('Xóa khuyến mãi này?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="promotion_id" value="<?= e((string) ($promotion['id'] ?? 0)) ?>">
                                    <button type="submit" class="admin-text-btn admin-text-btn--danger">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="admin-empty-state">
            <strong>Chưa có khuyến mãi nào</strong>
            <p>Tạo chiến dịch mới để hiển thị thông báo và tự động áp dụng ưu đãi cho khách phù hợp.</p>
        </div>
    <?php endif; ?>
</section>

<template id="template-home-spotlight-row">
    <div class="admin-dynamic-row" data-dynamic-row data-storage-index="__INDEX__">
        <label>
            <span data-row-label>Món hiển thị __LABEL__</span>
            <select name="home_spotlight_item___INDEX__">
                <?= $allItemOptionsMarkup ?>
            </select>
        </label>
        <button type="button" class="admin-text-btn admin-text-btn--danger" data-remove-row>Xóa</button>
    </div>
</template>

<template id="template-cart-recommend-row">
    <div class="admin-dynamic-row" data-dynamic-row data-storage-index="__INDEX__">
        <label>
            <span data-row-label>Món đề xuất __LABEL__</span>
            <select name="cart_recommend_item___INDEX__">
                <?= $allItemOptionsMarkup ?>
            </select>
        </label>
        <button type="button" class="admin-text-btn admin-text-btn--danger" data-remove-row>Xóa</button>
    </div>
</template>

<template id="template-home-drink-row">
    <div class="admin-dynamic-row" data-dynamic-row data-storage-index="__INDEX__">
        <label>
            <span data-row-label>Đồ uống __LABEL__</span>
            <select name="home_drink_item___INDEX__">
                <?= $drinkOptionsMarkup ?>
            </select>
        </label>
        <button type="button" class="admin-text-btn admin-text-btn--danger" data-remove-row>Xóa</button>
    </div>
</template>

<template id="template-home-banner-row">
    <div class="admin-image-field admin-dynamic-row" data-dynamic-row data-storage-index="__INDEX__">
        <div class="admin-dynamic-row__head">
            <strong data-row-label>Banner slide __LABEL__</strong>
            <button type="button" class="admin-text-btn admin-text-btn--danger" data-remove-row>Xóa</button>
        </div>
        <small class="admin-form-hint">Ảnh nền cho banner trang chủ. Có thể dùng GIF để tạo ảnh động.</small>
        <label>
            Tải ảnh mới:
            <input type="file" name="home_banner_slide___INDEX___file" accept="image/*" data-image-upload-input>
        </label>
        <label>
            Hoặc Link (URL):
            <input type="text" name="home_banner_slide___INDEX__" value="" data-image-url-input>
        </label>
        <span class="admin-image-preview is-empty" data-image-preview>
            <img src="" alt="Banner slide __LABEL__" hidden data-image-preview-img>
            <span data-image-preview-placeholder>Chưa có ảnh xem trước. Lưu lại để áp dụng hoặc nhập link ảnh.</span>
        </span>
    </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const getRows = function (list) {
        return Array.from(list.querySelectorAll('[data-dynamic-row]'));
    };

    const refreshLabels = function (list) {
        const baseLabel = list.getAttribute('data-label-base') || 'Mục';
        getRows(list).forEach(function (row, index) {
            row.querySelectorAll('[data-row-label]').forEach(function (labelNode) {
                labelNode.textContent = baseLabel + ' ' + (index + 1);
            });

            const previewImage = row.querySelector('[data-image-preview-img]');
            if (previewImage) {
                previewImage.alt = baseLabel + ' ' + (index + 1);
            }
        });
    };

    const getNextStorageIndex = function (list) {
        return getRows(list).reduce(function (maxValue, row) {
            const currentValue = Number(row.getAttribute('data-storage-index') || '0');
            return currentValue > maxValue ? currentValue : maxValue;
        }, 0) + 1;
    };

    const syncPreviewState = function (row, imageUrl) {
        const preview = row.querySelector('[data-image-preview]');
        const previewImage = row.querySelector('[data-image-preview-img]');
        const placeholder = row.querySelector('[data-image-preview-placeholder]');

        if (!preview || !previewImage || !placeholder) {
            return;
        }

        const hasImage = String(imageUrl || '').trim() !== '';
        preview.classList.toggle('is-empty', !hasImage);
        previewImage.hidden = !hasImage;
        placeholder.hidden = hasImage;

        if (hasImage) {
            previewImage.src = imageUrl;
        } else {
            previewImage.removeAttribute('src');
        }
    };

    document.querySelectorAll('[data-dynamic-list]').forEach(refreshLabels);

    document.querySelectorAll('[data-add-row-button]').forEach(function (button) {
        button.addEventListener('click', function () {
            const list = document.getElementById(button.getAttribute('data-target-list') || '');
            const template = document.getElementById(button.getAttribute('data-template-id') || '');

            if (!list || !template) {
                return;
            }

            const nextStorageIndex = getNextStorageIndex(list);
            const nextDisplayIndex = getRows(list).length + 1;
            const html = template.innerHTML
                .replaceAll('__INDEX__', String(nextStorageIndex))
                .replaceAll('__LABEL__', String(nextDisplayIndex));

            list.insertAdjacentHTML('beforeend', html);
            refreshLabels(list);
        });
    });

    document.addEventListener('click', function (event) {
        const removeButton = event.target.closest('[data-remove-row]');
        if (!removeButton) {
            return;
        }

        const row = removeButton.closest('[data-dynamic-row]');
        const list = removeButton.closest('[data-dynamic-list]');
        if (!row || !list) {
            return;
        }

        row.remove();
        refreshLabels(list);
    });

    document.addEventListener('change', function (event) {
        const fileInput = event.target.closest('[data-image-upload-input]');
        if (!fileInput) {
            return;
        }

        const row = fileInput.closest('[data-dynamic-row], .admin-image-field');
        if (!row) {
            return;
        }

        const file = fileInput.files && fileInput.files[0];
        if (!file) {
            return;
        }

        const reader = new FileReader();
        reader.onload = function (loadEvent) {
            syncPreviewState(row, String(loadEvent.target && loadEvent.target.result ? loadEvent.target.result : ''));
        };
        reader.readAsDataURL(file);
    });

    document.addEventListener('input', function (event) {
        const urlInput = event.target.closest('[data-image-url-input]');
        if (!urlInput) {
            return;
        }

        const row = urlInput.closest('[data-dynamic-row], .admin-image-field');
        if (!row) {
            return;
        }

        syncPreviewState(row, urlInput.value);
    });
});
</script>
