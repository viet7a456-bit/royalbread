<?php
$siteName = setting($settings, 'site_name', 'Bánh mì chảo Hoàng Gia - RoyalBread');
$address = setting($settings, 'address');
$hotline = setting($settings, 'hotline');
$openingHours = setting($settings, 'opening_hours');
$shopeefoodUrl = setting($settings, 'shopeefood_url', '#');
$brandLogo = asset('assets/images/royalbread-logo.png');
$pageTitle = ($customer !== null ? 'Tài khoản khách hàng' : 'Đăng nhập khách hàng') . ' | ' . $siteName;

$heroMainImage = media_url(setting($settings, 'home_signature_image', $featuredItems[0]['image_url'] ?? $brandLogo));
$heroBackdrop = media_url(setting($settings, 'home_banner_slide_1', $featuredItems[1]['image_url'] ?? $heroMainImage));
$sidebarFeatureImage = media_url($favoriteItems[0]['image_url'] ?? $suggestedItems[0]['image_url'] ?? $heroMainImage);

$customerName = $profile['name'] ?? ($_SESSION['customer_name'] ?? 'Khách hàng RoyalBread');
$customerPhone = trim((string) ($profile['phone'] ?? '')) !== '' ? (string) $profile['phone'] : 'Chưa cập nhật số điện thoại';
$customerEmail = trim((string) ($profile['email'] ?? '')) !== '' ? (string) $profile['email'] : 'Chưa cập nhật email';
$customerJoinedOn = $profile['joined_on'] ?? 'Chưa cập nhật';

$extractInitials = static function (string $fullName): string {
    $parts = preg_split('/\s+/u', trim($fullName)) ?: [];
    $letters = [];

    foreach ($parts as $part) {
        if ($part === '') {
            continue;
        }

        $letters[] = function_exists('mb_substr')
            ? mb_strtoupper(mb_substr($part, 0, 1, 'UTF-8'), 'UTF-8')
            : strtoupper(substr($part, 0, 1));

        if (count($letters) >= 2) {
            break;
        }
    }

    return $letters !== [] ? implode('', $letters) : 'RB';
};

$customerInitials = $extractInitials($customerName);
?>

<?php if ($customer === null): ?>
    <section class="customer-gate section-shell section-shell--tight">
        <div class="container customer-gate__grid">
            <article class="customer-gate__hero" style="--customer-hero-bg: url('<?= e($heroBackdrop) ?>');">
                <div class="customer-gate__copy">
                    <p class="section-kicker">Đăng nhập khách hàng</p>
                    <h1>Khu tài khoản khách RoyalBread</h1>
                    <p>Đăng nhập để xem đơn gần đây, lưu món yêu thích, đánh giá món đã mua và chat trực tiếp với cửa hàng ngay trong website.</p>

                    <div class="customer-gate__meta">
                        <span><?= e($address) ?></span>
                        <span>Hotline <?= e($hotline) ?></span>
                        <span><?= e($openingHours) ?></span>
                    </div>
                </div>

                <div class="customer-gate__visual">
                    <div class="customer-gate__visual-card">
                        <img src="<?= e($heroMainImage) ?>" alt="<?= e($siteName) ?>">
                    </div>
                    <div class="customer-gate__plaque">
                        <img src="<?= e($brandLogo) ?>" alt="<?= e($siteName) ?>">
                        <div>
                            <strong>RoyalBread</strong>
                            <span><?= e($openingHours) ?></span>
                        </div>
                    </div>
                </div>
            </article>

            <div class="customer-gate__actions">
                <article class="customer-gate__card">
                    <p class="section-kicker">Khách hàng</p>
                    <h2>Vào trang người dùng</h2>
                    <p>Đăng nhập để lưu món yêu thích, xem lịch sử mua hàng và nhắn hỗ trợ trực tiếp.</p>
                    <a class="btn btn-primary btn-block" href="<?= e(url('customer/login')) ?>">Đăng nhập khách hàng</a>
                </article>

                <?php if ($suggestedItems !== []): ?>
                    <article class="customer-gate__card customer-gate__card--menu">
                        <p class="section-kicker">Bán chạy</p>
                        <h2>Một vài món khách hay chọn</h2>
                        <div class="customer-gate__menu-list">
                            <?php foreach (array_slice($suggestedItems, 0, 3) as $item): ?>
                                <div>
                                    <strong><?= e($item['name']) ?></strong>
                                    <span><?= e(format_price($item['price'])) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <a class="text-link" href="<?= e(url('menu')) ?>">Xem toàn bộ thực đơn</a>
                    </article>
                <?php endif; ?>
            </div>
        </div>
    </section>
<?php else: ?>
    <section class="customer-hero section-shell--tight">
        <div class="container">
            <div class="customer-hero__banner" style="--customer-hero-bg: url('<?= e($heroBackdrop) ?>');">
                <div class="customer-hero__copy">
                    <p class="section-kicker">Trang người dùng</p>
                    <h1>Xin chào, <?= e($customerName) ?></h1>
                    <div class="customer-hero__meta">
                        <span><?= e($address) ?></span>
                        <span>Hotline <?= e($hotline) ?></span>
                        <span><?= e($openingHours) ?></span>
                    </div>
                </div>

                <div class="customer-hero__visual">
                    <div class="customer-hero__plaque">
                        <img src="<?= e($brandLogo) ?>" alt="<?= e($siteName) ?>">
                        <div>
                            <strong>RoyalBread</strong>
                            <span><?= e($openingHours) ?></span>
                        </div>
                    </div>

                    <div class="customer-hero__plate customer-hero__plate--main">
                        <img src="<?= e($heroMainImage) ?>" alt="<?= e($siteName) ?>">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section-shell">
        <div class="container customer-dashboard">
            <aside class="customer-sidebar">
                <nav class="customer-sidebar__nav">
                    <a href="#tong-quan" class="is-active">Tổng quan</a>
                    <a href="#don-hang">Đơn hàng của tôi</a>
                    <a href="#mon-yeu-thich">Món yêu thích</a>
                    <a href="#dia-chi">Địa chỉ giao hàng</a>
                    <a href="#danh-gia">Đánh giá món</a>
                    <a href="#goi-y">Gợi ý thêm</a>
                    <a href="#thong-bao">Thông báo</a>
                    <a href="#ho-tro">Hỗ trợ</a>
                </nav>

                <article class="customer-sidebar__promo">
                    <img src="<?= e($sidebarFeatureImage) ?>" alt="<?= e($siteName) ?>">
                    <div>
                        <strong>Khách thân thiết RoyalBread</strong>
                        <p>Đăng nhập một lần để lưu món yêu thích, gửi đánh giá và chat trực tiếp với cửa hàng mà không ảnh hưởng khu admin.</p>
                    </div>
                    <a class="btn btn-primary btn-block" href="<?= e($shopeefoodUrl) ?>" target="_blank" rel="noopener noreferrer">Đặt qua ShopeeFood</a>
                </article>

                <article class="customer-sidebar__info">
                    <strong>Thông tin nhanh</strong>
                    <p><?= e($address) ?></p>
                    <p><?= e($hotline) ?></p>
                    <p><?= e($openingHours) ?></p>
                </article>
            </aside>

            <div class="customer-main">
                <div class="customer-main__top customer-tab-content is-visible" data-tab="tong-quan">
                    <article class="customer-card customer-profile-card customer-section" id="tong-quan">
                        <div class="customer-profile-card__avatar"><?= e($customerInitials) ?></div>

                        <div class="customer-profile-card__body">
                            <div class="customer-profile-card__heading">
                                <div>
                                    <h2><?= e($customerName) ?></h2>
                                    <span class="customer-pill"><?= e($membership['tier']) ?></span>
                                </div>
                                <p>Tên đăng nhập: <?= e($profile['username'] ?? 'Khách hàng') ?></p>
                            </div>

                            <div class="customer-profile-card__meta">
                                <span><?= e($customerPhone) ?></span>
                                <span><?= e($customerEmail) ?></span>
                                <span>Tham gia: <?= e($customerJoinedOn) ?></span>
                            </div>
                        </div>
                    </article>

                    <article class="customer-card customer-points-card">
                        <div class="customer-points-card__heading">
                            <p class="section-kicker">Điểm thành viên</p>
                            <h2><?= e(number_format((int) $membership['points'], 0, ',', '.')) ?> điểm</h2>
                        </div>

                        <p>Bạn còn <?= e(number_format((int) $membership['remaining_points'], 0, ',', '.')) ?> điểm nữa để chạm <?= e($membership['next_label']) ?>.</p>

                        <div class="customer-progress">
                            <span style="width: <?= e((string) $membership['progress']) ?>%;"></span>
                        </div>

                        <div class="customer-points-card__meta">
                            <span><?= e(number_format((int) $membership['total_spent'], 0, ',', '.')) ?>đ đã chi</span>
                            <span><?= e((string) $membership['order_count']) ?> đơn đã ghi nhận</span>
                        </div>
                    </article>
                </div>

                <div class="customer-actions customer-tab-content is-visible" data-tab="tong-quan">
                    <a class="btn btn-primary" href="<?= e(url('menu')) ?>">Đặt món ngay</a>
                    <a class="btn btn-outline" href="<?= e(url('cart')) ?>">Xem giỏ hàng</a>
                    <a class="btn btn-outline" href="<?= e(url('contact')) ?>">Liên hệ quán</a>

                    <form method="POST" action="<?= e(url('customer/logout')) ?>">
                        <?= csrf_field() ?>
                        <button class="btn btn-outline customer-actions__logout" type="submit">Đăng xuất</button>
                    </form>
                </div>

                <div class="customer-grid">
                    <section class="customer-card customer-section customer-tab-content" id="don-hang" data-tab="don-hang">
                        <div class="customer-section__heading">
                            <div>
                                <p class="section-kicker">Lịch sử mua hàng</p>
                                <h2>Đơn hàng của tôi</h2>
                            </div>
                            <span class="customer-section__meta"><?= e((string) $membership['order_count']) ?> đơn</span>
                        </div>

                        <?php if ($recentOrders !== []): ?>
                            <div class="customer-order-list">
                                <?php foreach ($recentOrders as $order): ?>
                                    <article class="customer-order-item">
                                        <div class="customer-order-item__head">
                                            <div>
                                                <strong>#RB<?= e(str_pad((string) $order['id'], 5, '0', STR_PAD_LEFT)) ?></strong>
                                                <span><?= e($order['created_label']) ?></span>
                                            </div>
                                            <span class="customer-status <?= e($order['status_class']) ?>"><?= e($order['status_label']) ?></span>
                                        </div>

                                        <div class="customer-order-item__body">
                                            <?php foreach ($order['items'] as $item): ?>
                                                <div>
                                                    <strong><?= e($item['menu_item_name']) ?></strong>
                                                    <span>x<?= e((string) $item['quantity']) ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>

                                        <div class="customer-order-item__foot">
                                            <span><?= e($order['payment_label']) ?> • <?= e($order['payment_status_label']) ?></span>
                                            <strong><?= e(format_price($order['total_amount'])) ?></strong>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="customer-empty">
                                <strong>Bạn chưa có đơn hàng nào trên website.</strong>
                                <p>Hãy mở thực đơn để bắt đầu đặt món và lưu lịch sử đơn cho tài khoản này.</p>
                            </div>
                        <?php endif; ?>
                    </section>

                    <section class="customer-card customer-section customer-tab-content" id="mon-yeu-thich" data-tab="mon-yeu-thich">
                        <div class="customer-section__heading">
                            <div>
                                <p class="section-kicker">Lưu thật sự</p>
                                <h2>Món yêu thích của bạn</h2>
                            </div>
                            <span class="customer-section__meta"><?= e((string) count($favoriteItems)) ?> món</span>
                        </div>

                        <?php if ($favoriteItems !== []): ?>
                            <div class="customer-item-list">
                                <?php foreach ($favoriteItems as $item): ?>
                                    <article class="customer-item-card">
                                        <img src="<?= e(media_url($item['image_url'])) ?>" alt="<?= e($item['name']) ?>">
                                        <div>
                                            <strong><?= e($item['name']) ?></strong>
                                            <span><?= e($item['category_name'] ?? 'RoyalBread') ?></span>
                                            <em><?= e(format_price($item['price'])) ?></em>
                                        </div>
                                        <form method="post" action="<?= e(url('customer/favorites/toggle')) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="menu_item_id" value="<?= e((string) $item['id']) ?>">
                                            <input type="hidden" name="redirect_to" value="account#mon-yeu-thich">
                                            <button type="submit" class="btn btn-outline">Bỏ lưu</button>
                                        </form>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="customer-empty">
                                <strong>Bạn chưa lưu món yêu thích nào.</strong>
                                <p>Vào trang thực đơn và bấm biểu tượng tim để lưu món muốn gọi lại nhanh hơn.</p>
                            </div>
                        <?php endif; ?>
                    </section>

                    <section class="customer-card customer-section customer-tab-content is-visible" id="uu-dai" data-tab="tong-quan">
                        <div class="customer-section__heading">
                            <div>
                                <p class="section-kicker">Điểm nhấn theo menu</p>
                                <h2>Giá tốt từ thực đơn</h2>
                            </div>
                        </div>

                        <div class="customer-compact-list">
                            <?php foreach ($priceHighlights as $highlight): ?>
                                <article>
                                    <strong><?= e($highlight['title']) ?></strong>
                                    <span><?= e($highlight['description']) ?></span>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <section class="customer-card customer-section customer-tab-content" id="dia-chi" data-tab="dia-chi">
                        <div class="customer-section__heading">
                            <div>
                                <p class="section-kicker">Liên hệ và nhận hàng</p>
                                <h2>Địa chỉ giao hàng</h2>
                            </div>
                        </div>

                        <div class="customer-address-list">
                            <?php foreach ($addressCards as $card): ?>
                                <article>
                                    <span class="customer-tag"><?= e($card['tag']) ?></span>
                                    <strong><?= e($card['title']) ?></strong>
                                    <p><?= e($card['address']) ?></p>
                                    <?php if (trim((string) $card['contact']) !== ''): ?>
                                        <small><?= e($card['contact']) ?></small>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <section class="customer-card customer-section customer-tab-content" id="danh-gia" data-tab="danh-gia">
                        <div class="customer-section__heading">
                            <div>
                                <p class="section-kicker">Đánh giá sản phẩm</p>
                                <h2>Viết review cho món đã mua</h2>
                            </div>
                            <span class="customer-section__meta"><?= e((string) count($customerReviews)) ?> review</span>
                        </div>

                        <div class="customer-review-layout">
                            <div class="customer-review-list">
                                <?php if ($reviewableItems !== []): ?>
                                    <?php foreach ($reviewableItems as $item): ?>
                                        <?php $reviewItemName = (string) ($item['name'] ?? $item['menu_item_name'] ?? 'Món đã mua'); ?>
                                        <article class="customer-review-card">
                                            <div class="customer-review-card__head">
                                                <img src="<?= e(media_url($item['image_url'])) ?>" alt="<?= e($reviewItemName) ?>">
                                                <div>
                                                    <strong><?= e($reviewItemName) ?></strong>
                                                    <span>Đơn #<?= e((string) $item['order_id']) ?></span>
                                                </div>
                                            </div>

                                            <form method="post" action="<?= e(url('customer/reviews/store')) ?>" class="customer-review-form">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="menu_item_id" value="<?= e((string) $item['menu_item_id']) ?>">
                                                <input type="hidden" name="order_id" value="<?= e((string) $item['order_id']) ?>">
                                                <label>
                                                    Điểm đánh giá
                                                    <select name="rating" required>
                                                        <option value="5" <?= (int) ($item['existing_rating'] ?? 5) === 5 ? 'selected' : '' ?>>5 sao</option>
                                                        <option value="4" <?= (int) ($item['existing_rating'] ?? 0) === 4 ? 'selected' : '' ?>>4 sao</option>
                                                        <option value="3" <?= (int) ($item['existing_rating'] ?? 0) === 3 ? 'selected' : '' ?>>3 sao</option>
                                                        <option value="2" <?= (int) ($item['existing_rating'] ?? 0) === 2 ? 'selected' : '' ?>>2 sao</option>
                                                        <option value="1" <?= (int) ($item['existing_rating'] ?? 0) === 1 ? 'selected' : '' ?>>1 sao</option>
                                                    </select>
                                                </label>
                                                <label>
                                                    Tiêu đề ngắn
                                                    <input type="text" name="review_title" value="<?= e($item['existing_title'] ?? '') ?>" placeholder="Ví dụ: Món ăn rất đầy đặn">
                                                </label>
                                                <label>
                                                    Nhận xét chi tiết
                                                    <textarea name="review_comment" rows="3" placeholder="Chia sẻ cảm nhận thật của bạn..." required><?= e($item['existing_comment'] ?? '') ?></textarea>
                                                </label>
                                                <button type="submit" class="btn btn-primary">Gửi đánh giá</button>
                                            </form>
                                        </article>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="customer-empty">
                                        <strong>Chưa có món nào sẵn sàng để đánh giá.</strong>
                                        <p>Khi bạn mua món trên website, RoyalBread sẽ mở form review tại đây để bạn nhận xét.</p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="customer-note-list">
                                <?php foreach (array_slice($customerReviews, 0, 5) as $review): ?>
                                    <article>
                                        <strong><?= e($review['menu_item_name'] ?? 'Món đã mua') ?></strong>
                                        <p><?= e(str_repeat('★', max(1, (int) ($review['rating'] ?? 0)))) ?> • <?= e($review['status']) ?></p>
                                        <p><?= e(trim((string) ($review['review_title'] ?? '')) !== '' ? $review['review_title'] : $review['review_comment']) ?></p>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </section>

                    <section class="customer-card customer-section customer-tab-content is-visible" id="thanh-vien" data-tab="tong-quan">
                        <div class="customer-section__heading">
                            <div>
                                <p class="section-kicker">Hành trình thành viên</p>
                                <h2>Tiến trình tích lũy</h2>
                            </div>
                        </div>

                        <div class="customer-membership">
                            <div class="customer-membership__milestone">
                                <span>0 điểm</span>
                                <strong>Thành viên mới</strong>
                            </div>
                            <div class="customer-membership__line">
                                <span style="width: <?= e((string) $membership['progress']) ?>%;"></span>
                            </div>
                            <div class="customer-membership__milestone is-active">
                                <span><?= e(number_format((int) $membership['points'], 0, ',', '.')) ?> điểm</span>
                                <strong><?= e($membership['tier']) ?></strong>
                            </div>
                            <div class="customer-membership__milestone">
                                <span><?= e(number_format((int) $membership['next_target'], 0, ',', '.')) ?> điểm</span>
                                <strong><?= e($membership['next_label']) ?></strong>
                            </div>
                        </div>
                    </section>

                    <section class="customer-card customer-section customer-tab-content" id="goi-y" data-tab="goi-y">
                        <div class="customer-section__heading">
                            <div>
                                <p class="section-kicker">Gợi ý thêm</p>
                                <h2>Món bạn có thể muốn gọi kèm</h2>
                            </div>
                        </div>

                        <div class="customer-suggestion-list">
                            <?php foreach (array_slice($suggestedItems, 0, 4) as $item): ?>
                                <article class="customer-suggestion-card">
                                    <img src="<?= e(media_url($item['image_url'])) ?>" alt="<?= e($item['name']) ?>">
                                    <div>
                                        <strong><?= e($item['name']) ?></strong>
                                        <span><?= e($item['category_name'] ?? '') ?></span>
                                        <em><?= e(format_price($item['price'])) ?></em>
                                    </div>

                                    <form method="POST" action="<?= e(url('cart/add')) ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= e((string) $item['id']) ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <input type="hidden" name="redirect_to" value="account#goi-y">
                                        <button type="submit" class="btn btn-outline">Thêm</button>
                                    </form>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <section class="customer-card customer-section customer-tab-content" id="thong-bao" data-tab="thong-bao">
                        <div class="customer-section__heading">
                            <div>
                                <p class="section-kicker">Cập nhật</p>
                                <h2>Thông báo mới</h2>
                            </div>
                        </div>

                        <div class="customer-note-list">
                            <?php foreach ($notifications as $item): ?>
                                <article>
                                    <strong><?= e($item['title']) ?></strong>
                                    <p><?= e($item['body']) ?></p>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <section class="customer-card customer-section customer-tab-content" id="ho-tro" data-tab="ho-tro">
                        <div class="customer-section__heading">
                            <div>
                                <p class="section-kicker">Hỗ trợ khách hàng</p>
                                <h2>Chat trực tiếp với cửa hàng</h2>
                            </div>
                            <?php if (!empty($chatThread)): ?>
                                <span class="customer-section__meta"><?= e(($chatThread['status'] ?? 'open') === 'closed' ? 'Đã đóng' : 'Đang mở') ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="customer-live-chat">
                            <div class="customer-message-list customer-live-chat__messages">
                                <?php foreach ($chatMessages as $message): ?>
                                    <article class="customer-live-chat__bubble <?= ($message['sender_type'] ?? '') === 'customer' ? 'is-customer' : 'is-admin' ?>">
                                        <div>
                                            <strong><?= e($message['sender_name'] ?? (($message['sender_type'] ?? '') === 'admin' ? 'RoyalBread' : $customerName)) ?></strong>
                                            <span><?= e($message['created_at']) ?></span>
                                        </div>
                                        <p><?= nl2br(e($message['message'])) ?></p>
                                    </article>
                                <?php endforeach; ?>

                                <?php foreach ($supportMessages as $message): ?>
                                    <article>
                                        <div>
                                            <strong><?= e($message['author']) ?></strong>
                                            <span><?= e($message['time']) ?></span>
                                        </div>
                                        <p><?= e($message['content']) ?></p>
                                    </article>
                                <?php endforeach; ?>
                            </div>

                            <form method="post" action="<?= e(url('customer/support/send')) ?>" class="customer-live-chat__form">
                                <?= csrf_field() ?>
                                <label>
                                    Nhắn với RoyalBread
                                    <textarea name="message" rows="4" placeholder="Ví dụ: Mình muốn đổi đồ uống trong đơn gần nhất..." required></textarea>
                                </label>
                                <button type="submit" class="btn btn-primary">Gửi tin nhắn</button>
                            </form>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php if ($customer !== null): ?>
<script>
(function () {
    'use strict';

    var dashboard = document.querySelector('.customer-dashboard');
    if (!dashboard) return;

    var sidebarLinks = dashboard.querySelectorAll('.customer-sidebar__nav a');
    var allTabContent = dashboard.querySelectorAll('.customer-tab-content');
    var tabMap = {
        'tong-quan': 'tong-quan',
        'don-hang': 'don-hang',
        'mon-yeu-thich': 'mon-yeu-thich',
        'dia-chi': 'dia-chi',
        'danh-gia': 'danh-gia',
        'goi-y': 'goi-y',
        'thong-bao': 'thong-bao',
        'ho-tro': 'ho-tro'
    };

    function switchTab(tabName) {
        sidebarLinks.forEach(function (link) {
            link.classList.remove('is-active');
            var linkHash = (link.getAttribute('href') || '').replace('#', '');
            if (linkHash === tabName) {
                link.classList.add('is-active');
            }
        });

        allTabContent.forEach(function (el) {
            el.classList.remove('is-visible', 'tab-animate');
        });

        var isOverview = (tabName === 'tong-quan');

        allTabContent.forEach(function (el) {
            var elTab = el.getAttribute('data-tab');
            if (elTab === tabName) {
                el.classList.add('is-visible');
                requestAnimationFrame(function () {
                    el.classList.add('tab-animate');
                });
            }
        });

        if (isOverview) {
            dashboard.classList.remove('customer-dashboard--single-tab');
        } else {
            dashboard.classList.add('customer-dashboard--single-tab');
        }

        if (window.innerWidth < 1180) {
            var mainArea = dashboard.querySelector('.customer-main');
            if (mainArea) {
                setTimeout(function () {
                    mainArea.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 80);
            }
        }
    }

    sidebarLinks.forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            var hash = (link.getAttribute('href') || '').replace('#', '');
            if (tabMap[hash] !== undefined) {
                window.location.hash = hash;
                switchTab(hash);
            }
        });
    });

    window.addEventListener('hashchange', function () {
        var hash = window.location.hash.replace('#', '');
        if (tabMap[hash] !== undefined) {
            switchTab(hash);
        }
    });

    var initialHash = window.location.hash.replace('#', '');
    if (tabMap[initialHash] !== undefined) {
        switchTab(initialHash);
    } else {
        switchTab('tong-quan');
    }
})();
</script>
<?php endif; ?>
