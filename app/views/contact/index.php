<?php
$siteName = setting($settings, 'site_name', 'Bánh mì chảo Hoàng Gia - RoyalBread');
$address = setting($settings, 'address', '28 Dã Tượng - TP Hải Dương (Sau nhà thi đấu Hải Dương)');
$hotline = setting($settings, 'hotline', '0879866636');
$openingHours = setting($settings, 'opening_hours', '07:00 - 22:00 mỗi ngày');
$shopeefoodUrl = setting($settings, 'shopeefood_url', '#');
$grabfoodUrl = 'https://food.grab.com/vn/vi/restaurant/b%C3%A1nh-m%C3%AC-s%E1%BA%A1ch-ho%C3%A0ng-gia-royalbread-delivery/5-C4E2JLBTLA6DCE?';
$facebookUrl = 'https://www.facebook.com/profile.php?id=61582626099340';
$pageTitle = 'Liên hệ | ' . $siteName;
$pageDescription = 'Liên hệ RoyalBread để đặt món, góp ý chất lượng hoặc nhận hỗ trợ giao hàng tại Hải Dương.';
$pageKeywords = 'liên hệ RoyalBread, hotline RoyalBread, địa chỉ RoyalBread, góp ý cửa hàng';

$defaultMapQuery = '28 Dã Tượng, P. Lê Thanh Nghị, TP Hải Dương, Hải Dương, Việt Nam';
$mapQuery = trim((string) setting($settings, 'map_query', $defaultMapQuery));
if ($mapQuery === '') {
    $mapQuery = $defaultMapQuery;
}

$estimator = new DeliveryEstimator($settings);
$shopLocation = $estimator->shopLocation();

if ($shopLocation !== null) {
    $shopLat = (float) $shopLocation['lat'];
    $shopLon = (float) $shopLocation['lon'];
    $mapLabel = $address;
    $mapEmbedUrl = 'https://maps.google.com/maps?hl=vi&q=' . rawurlencode($shopLat . ',' . $shopLon . ' (' . $siteName . ')') . '&t=&z=18&ie=UTF8&iwloc=B&output=embed';
    $mapOpenUrl = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($shopLat . ',' . $shopLon);
} else {
    $shopLat = null;
    $shopLon = null;
    $mapLabel = $address;
    $encodedMapQuery = rawurlencode($mapQuery);
    $mapEmbedUrl = 'https://maps.google.com/maps?hl=vi&q=' . $encodedMapQuery . '&t=&z=17&ie=UTF8&iwloc=B&output=embed';
    $mapOpenUrl = 'https://www.google.com/maps/search/?api=1&query=' . $encodedMapQuery;
}

$contactBg = media_url(setting($settings, 'contact_hero_image', setting($settings, 'home_banner_slide_1', '')));
$bgStyle = $contactBg !== '' ? 'background: url(\'' . e($contactBg) . '\') center/contain no-repeat #2a1810;' : '';
?>

<section class="ct-hero" style="<?= $bgStyle ?>">
    <div class="ct-hero__overlay"></div>
    <div class="container ct-hero__content">
        <div class="ct-hero__text">
            <p class="ct-hero__kicker">Liên hệ với</p>
            <h1 style="text-transform: uppercase;"><?= e($siteName) ?></h1>
            <p class="ct-hero__desc">Chúng tôi luôn sẵn sàng lắng nghe và phục vụ bạn.<br>Hãy liên hệ để đặt món, góp ý hoặc giải đáp thắc mắc nhé!</p>

            <div class="ct-hero__features">
                <div class="ct-feature">
                    <div class="ct-feature__icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div>
                        <strong>GIỜ MỞ CỬA</strong>
                        <span><?= e($openingHours) ?></span>
                    </div>
                </div>
                <div class="ct-feature">
                    <div class="ct-feature__icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 18H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h3.19M15 6h2a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-3.19M23 13v-2M11 6l-4 6h6l-4 6"/></svg>
                    </div>
                    <div>
                        <strong>GIAO HÀNG NHANH</strong>
                        <span>30 - 45 phút</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="ct-main">
    <div class="container ct-main__grid">
        <div class="ct-card ct-form-card">
            <div class="ct-card__head">
                <span class="ct-decor"></span>
                <h2>GỬI TIN NHẮN CHO CHÚNG TÔI</h2>
                <span class="ct-decor"></span>
            </div>

            <form method="post" action="<?= e(url('contact')) ?>" class="ct-form">
                <?= csrf_field() ?>
                <div class="ct-form__row">
                    <label>
                        Họ và tên *
                        <input type="text" name="customer_name" value="<?= e(old('customer_name', '', 'contact')) ?>" placeholder="Nhập họ và tên của bạn" required>
                    </label>
                    <label>
                        Email / SĐT *
                        <input type="text" name="phone" value="<?= e(old('phone', '', 'contact')) ?>" placeholder="Nhập số điện thoại/email" required>
                    </label>
                </div>
                <div class="ct-form__row">
                    <label>
                        Thời gian liên hệ
                        <input type="text" name="contact_time" value="<?= e(old('contact_time', '', 'contact')) ?>" placeholder="Nhập thời gian">
                    </label>
                    <label>
                        Chủ đề *
                        <select name="subject" required>
                            <option value="">Chọn chủ đề</option>
                            <option value="order" <?= old('subject', '', 'contact') === 'order' ? 'selected' : '' ?>>Đặt hàng số lượng lớn</option>
                            <option value="feedback" <?= old('subject', '', 'contact') === 'feedback' ? 'selected' : '' ?>>Góp ý chất lượng</option>
                            <option value="other" <?= old('subject', '', 'contact') === 'other' ? 'selected' : '' ?>>Khác</option>
                        </select>
                    </label>
                </div>
                <label>
                    Nội dung tin nhắn *
                    <textarea name="message" rows="4" placeholder="Nhập nội dung tin nhắn của bạn..." required><?= e(old('message', '', 'contact')) ?></textarea>
                </label>

                <div class="ct-form__action">
                    <button type="submit" class="ct-btn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                        GỬI TIN NHẮN
                    </button>
                    <small>Thông tin của bạn được bảo mật tuyệt đối.</small>
                </div>
            </form>
        </div>

        <div class="ct-card ct-info-card">
            <div class="ct-card__head">
                <span class="ct-decor"></span>
                <h2>THÔNG TIN LIÊN HỆ</h2>
                <span class="ct-decor"></span>
            </div>

            <div class="ct-info-list">
                <article>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <div>
                        <strong>ĐỊA CHỈ</strong>
                        <p><?= e($address) ?></p>
                    </div>
                </article>
                <article>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    <div>
                        <strong>SỐ ĐIỆN THOẠI</strong>
                        <p><a href="tel:<?= e($hotline) ?>"><?= e($hotline) ?></a></p>
                    </div>
                </article>
                <article>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                    <div>
                        <strong>FACEBOOK</strong>
                        <p><a href="<?= e($facebookUrl) ?>" target="_blank" rel="noopener">Hoàng Gia RoyalBread</a></p>
                    </div>
                </article>
                <article>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                    <div>
                        <strong>ĐẶT QUA APP</strong>
                        <p>
                            <a href="<?= e($grabfoodUrl) ?>" target="_blank" rel="noopener">GrabFood</a> |
                            <a href="<?= e($shopeefoodUrl) ?>" target="_blank" rel="noopener">ShopeeFood</a>
                        </p>
                    </div>
                </article>
            </div>
        </div>
    </div>
</section>

<section class="ct-map">
    <div class="container">
        <div class="ct-card__head">
            <span class="ct-decor"></span>
            <h2>TÌM ĐƯỜNG ĐẾN QUÁN</h2>
            <span class="ct-decor"></span>
        </div>

        <div class="ct-map__wrap">
            <iframe
                src="<?= e($mapEmbedUrl) ?>"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                title="Bản đồ RoyalBread"></iframe>
        </div>

        <div class="ct-map__meta">
            <strong>Vị trí quán dễ nhận biết</strong>
            <p><?= e($mapLabel) ?></p>
            <small>Mốc gần quán: sau nhà thi đấu Hải Dương. Nếu bản đồ trên máy bạn lệch nhẹ, hãy dùng nút chỉ đường bên dưới để mở đúng vị trí ghim của quán.</small>
        </div>

        <div class="ct-map__actions">
            <a class="ct-btn" href="<?= e($mapOpenUrl) ?>" target="_blank" rel="noopener">
                Mở đúng vị trí quán trên Google Maps
            </a>
            <button
                type="button"
                class="ct-btn ct-btn--outline"
                data-contact-directions
                data-map-open-url="<?= e($mapOpenUrl) ?>"
                <?= $shopLat !== null && $shopLon !== null ? 'data-shop-lat="' . e((string) $shopLat) . '" data-shop-lon="' . e((string) $shopLon) . '"' : '' ?>
            >
                Dẫn đường từ vị trí hiện tại của tôi
            </button>
        </div>

        <p class="ct-map__status" data-contact-location-status></p>
    </div>
</section>
