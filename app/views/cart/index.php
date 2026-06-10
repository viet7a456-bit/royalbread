<?php
$siteName = setting($settings, 'site_name', 'Bánh mì chảo Hoàng Gia - RoyalBread');
$tagline = setting($settings, 'tagline', 'Bánh mì chảo nóng giòn, topping đầy đặn, lên món nhanh cho khách tại Hải Dương.');
$address = setting($settings, 'address', '28 Dã Tượng - TP Hải Dương (Sau nhà thi đấu Hải Dương)');
$hotline = setting($settings, 'hotline', '0879866636');
$openingHours = setting($settings, 'opening_hours', '07:00 - 22:00 mỗi ngày');
$pageTitle = 'Giỏ hàng | ' . $siteName;
$cartMode = $cartMode ?? 'cart';
$isBuyNowMode = !empty($isBuyNowMode);

$cartItemCount = 0;
foreach ($cartItems as $item) {
    $cartItemCount += (int) ($item['quantity'] ?? 0);
}

$heroFoodItem = $cartItems[0] ?? $featuredItems[0] ?? null;
$heroFoodImage = media_url($heroFoodItem['image_url'] ?? asset('assets/images/home-hero-banner-3.png'));
$distanceKm = normalize_distance_km($distanceKm ?? 0);
$shippingRatePerKm = (int) ($shippingRatePerKm ?? 5000);
$distanceKmText = $distanceKm > 0 ? format_distance_km($distanceKm) : '';
$distanceFieldValue = old('distance_km', $distanceKm > 0 ? (string) $distanceKm : '', 'checkout');

$bankTransferInfo = bank_transfer_details($settings);

$suggestedToppingCards = $suggestedToppings ?? [];
$suggestedDrinkCards = $suggestedDrinks ?? [];
$hasAddonTabs = $suggestedToppingCards !== [] && $suggestedDrinkCards !== [];

$trustPoints = [
    [
        'icon' => '🚚',
        'title' => 'Giao hàng nhanh chóng',
        'text' => 'Toàn thành phố trong vòng 30 phút',
    ],
    [
        'icon' => '🛡',
        'title' => 'Nguyên liệu tươi ngon',
        'text' => 'Chọn lọc kỹ lưỡng, chế biến mỗi ngày',
    ],
    [
        'icon' => '👨‍🍳',
        'title' => 'Hương vị đặc trưng',
        'text' => 'Công thức riêng biệt của RoyalBread',
    ],
    [
        'icon' => '🕒',
        'title' => 'Hỗ trợ tận tâm',
        'text' => $openingHours,
    ],
];
?>

<section class="page-hero page-hero--compact cart-hero">
    <div class="container page-hero__grid cart-hero__grid">
        <div class="page-hero__copy cart-hero__copy">
            <p class="section-kicker"><?= $isBuyNowMode ? 'Đặt hàng ngay' : 'Đặt món nhanh chóng' ?></p>
            <h1><?= $isBuyNowMode ? 'Chốt nhanh món bạn vừa chọn' : 'Giỏ hàng của bạn' ?></h1>
            <p><?= $isBuyNowMode ? 'RoyalBread đang giữ riêng món bạn chọn để bạn điền thông tin và đặt ngay.' : 'Kiểm tra lại các món đã chọn trước khi tiến hành thanh toán nhé.' ?></p>

            <div class="cart-hero__meta">
                <span><?= e((string) $cartItemCount) ?> món đang chờ xác nhận</span>
                <span><?= e($address) ?></span>
                <span>Hotline <?= e($hotline) ?></span>
            </div>

            <div class="cart-hero__actions">
                <a href="<?= e(url('menu')) ?>" class="btn btn-outline"><?= $isBuyNowMode ? 'Chọn thêm món khác' : 'Tiếp tục chọn món' ?></a>
                <?php if ($isBuyNowMode): ?>
                    <a href="<?= e(url('cart')) ?>" class="btn btn-outline">Xem giỏ hàng chung</a>
                <?php endif; ?>
                <a href="#cartCheckoutForm" class="btn btn-primary"><?= $isBuyNowMode ? 'Đặt hàng ngay' : 'Tiến hành đặt hàng' ?></a>
            </div>
        </div>

        <div class="cart-hero__visual">
            <img src="<?= e($heroFoodImage) ?>" alt="<?= e($heroFoodItem['name'] ?? $siteName) ?>">
        </div>
    </div>
</section>

<section class="section-shell section-shell--tight">
    <div class="container cart-layout">
        <?php if (empty($cartItems)): ?>
            <div class="cart-empty cart-panel">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                <h2><?= $isBuyNowMode ? 'Không còn món để đặt ngay' : 'Giỏ hàng đang trống' ?></h2>
                <p><?= $isBuyNowMode ? 'Món bạn chọn trước đó không còn khả dụng. Hãy quay lại thực đơn để chọn món khác.' : 'Hãy thêm một vài món ngon vào giỏ hàng để bắt đầu đơn mới của bạn.' ?></p>
                <a href="<?= e(url('menu')) ?>" class="btn btn-primary">Xem thực đơn ngay</a>
            </div>
        <?php else: ?>
            <div class="cart-main">
                <section class="cart-panel cart-order-card">
                    <div class="cart-panel__head">
                        <div>
                            <h2><?= $isBuyNowMode ? 'Món đang đặt ngay' : 'Sản phẩm đã chọn' ?></h2>
                            <p><?= $isBuyNowMode ? 'Bạn có thể điều chỉnh số lượng trước khi chốt đơn ngay cho món này.' : 'Điều chỉnh số lượng hoặc xóa món ngay tại đây trước khi chốt đơn.' ?></p>
                        </div>
                    </div>

                    <div class="cart-line-head">
                        <span>Sản phẩm</span>
                        <span>Đơn giá</span>
                        <span>Số lượng</span>
                        <span>Thành tiền</span>
                        <span></span>
                    </div>

                    <div class="cart-line-list">
                        <?php foreach ($cartItems as $item): ?>
                            <?php $lineDescription = trim((string) ($item['description'] ?? '')) !== '' ? $item['description'] : 'Món được chuẩn bị theo dữ liệu thực đơn hiện tại.'; ?>
                            <article class="cart-line">
                                <div class="cart-line__product">
                                    <img src="<?= e(media_url($item['image_url'])) ?>" alt="<?= e($item['name']) ?>">
                                    <div>
                                        <strong><?= e($item['name']) ?></strong>
                                        <p><?= e($lineDescription) ?></p>
                                    </div>
                                </div>

                                <div class="cart-line__cell cart-line__cell--price">
                                    <span class="cart-line__label">Đơn giá</span>
                                    <strong><?= e(format_price($item['price'])) ?></strong>
                                </div>

                                <div class="cart-line__cell cart-line__cell--qty">
                                    <span class="cart-line__label">Số lượng</span>
                                    <form method="post" action="<?= e(url('cart/update')) ?>" class="cart-qty-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= e((string) $item['id']) ?>">
                                        <input type="hidden" name="mode" value="<?= e($cartMode) ?>">
                                        <button type="button" onclick="this.parentNode.querySelector('input[type=number]').stepDown(); this.parentNode.submit();">-</button>
                                        <input type="number" name="quantity" value="<?= e((string) $item['quantity']) ?>" min="1" max="99" onchange="this.form.submit()">
                                        <button type="button" onclick="this.parentNode.querySelector('input[type=number]').stepUp(); this.parentNode.submit();">+</button>
                                    </form>
                                </div>

                                <div class="cart-line__cell cart-line__cell--subtotal">
                                    <span class="cart-line__label">Thành tiền</span>
                                    <strong><?= e(format_price($item['subtotal'])) ?></strong>
                                </div>

                                <div class="cart-line__cell cart-line__cell--remove">
                                    <form method="post" action="<?= e(url('cart/remove')) ?>" style="display:inline;" onsubmit="return confirm('Bỏ món này khỏi danh sách đặt?')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= e((string) $item['id']) ?>">
                                        <input type="hidden" name="mode" value="<?= e($cartMode) ?>">
                                        <button type="submit" class="cart-remove" aria-label="<?= e('Xóa ' . $item['name']) ?>" style="background:none;border:none;padding:0;cursor:pointer;color:inherit;">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                        </button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <div class="cart-actions">
                        <a href="<?= e(url('menu')) ?>" class="btn btn-outline"><?= $isBuyNowMode ? '+ Chọn thêm món khác' : '+ Tiếp tục chọn món' ?></a>
                    </div>
                </section>

                <?php if ($suggestedToppingCards !== [] || $suggestedDrinkCards !== []): ?>
                    <section class="cart-panel cart-addons">
                        <div class="cart-panel__head">
                            <div>
                                <h2>Gợi ý thêm trước khi đặt hàng</h2>
                                <p>Thêm topping hoặc đồ uống để đơn hàng trọn vị hơn.</p>
                            </div>
                        </div>

                        <?php if ($hasAddonTabs): ?>
                            <div class="cart-addon-tabs" data-cart-addon-tabs>
                                <button type="button" class="cart-addon-tab is-active" data-cart-addon-tab="topping">Topping</button>
                                <button type="button" class="cart-addon-tab" data-cart-addon-tab="drink">Đồ uống</button>
                            </div>
                        <?php endif; ?>

                        <?php if ($suggestedToppingCards !== []): ?>
                            <div class="cart-addon-pane<?= $hasAddonTabs ? ' is-active' : '' ?>" data-cart-addon-pane="topping">
                                <div class="cart-addon-grid">
                                    <?php foreach ($suggestedToppingCards as $item): ?>
                                        <article class="cart-addon-card">
                                            <img src="<?= e(media_url($item['image_url'])) ?>" alt="<?= e($item['name']) ?>">
                                            <div class="cart-addon-card__body">
                                                <span><?= e($item['category_name']) ?></span>
                                                <strong><?= e($item['name']) ?></strong>
                                                <small><?= e(format_price($item['price'])) ?></small>
                                            </div>
                                            <form method="post" action="<?= e(url('cart/add')) ?>" class="cart-addon-card__form">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= e((string) $item['id']) ?>">
                                                <input type="hidden" name="quantity" value="1">
                                                <input type="hidden" name="redirect_to" value="cart<?= $isBuyNowMode ? '?mode=buy-now' : '' ?>">
                                                <button type="submit" class="cart-addon-card__btn">Thêm vào giỏ</button>
                                            </form>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($suggestedDrinkCards !== []): ?>
                            <div class="cart-addon-pane<?= !$hasAddonTabs ? ' is-active' : '' ?>" data-cart-addon-pane="drink">
                                <div class="cart-addon-grid">
                                    <?php foreach ($suggestedDrinkCards as $item): ?>
                                        <article class="cart-addon-card">
                                            <img src="<?= e(media_url($item['image_url'])) ?>" alt="<?= e($item['name']) ?>">
                                            <div class="cart-addon-card__body">
                                                <span><?= e($item['category_name']) ?></span>
                                                <strong><?= e($item['name']) ?></strong>
                                                <small><?= e(format_price($item['price'])) ?></small>
                                            </div>
                                            <form method="post" action="<?= e(url('cart/add')) ?>" class="cart-addon-card__form">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= e((string) $item['id']) ?>">
                                                <input type="hidden" name="quantity" value="1">
                                                <input type="hidden" name="redirect_to" value="cart<?= $isBuyNowMode ? '?mode=buy-now' : '' ?>">
                                                <button type="submit" class="cart-addon-card__btn">Thêm vào giỏ</button>
                                            </form>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>
            </div>

            <aside class="cart-sidebar">
                <section
                    class="cart-panel cart-summary"
                    data-cart-summary
                    data-subtotal="<?= e((string) $subtotal) ?>"
                    data-shipping-rate="<?= e((string) $shippingRatePerKm) ?>"
                >
                    <div class="cart-card-title">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 3h2l.4 2M7 13h10l4-8H5.4"/><circle cx="9" cy="19" r="1"/><circle cx="18" cy="19" r="1"/></svg>
                        <h3>Tóm tắt đơn hàng</h3>
                    </div>

                    <div class="cart-summary__row">
                        <span>Tạm tính</span>
                        <strong data-cart-subtotal-value><?= e(format_price($subtotal)) ?></strong>
                    </div>
                    <div class="cart-summary__row">
                        <span>Phí giao hàng</span>
                        <strong data-cart-shipping-value><?= e(format_price($shippingFee)) ?></strong>
                    </div>
                    <?php if (!empty($promotionPreview)): ?>
                        <div class="cart-summary__row cart-summary__row--discount">
                            <span>Ưu đãi thành viên</span>
                            <strong>-<?= e(format_price((int) $discountPreview)) ?></strong>
                        </div>
                        <p class="cart-summary__meta">
                            <?= e((string) ($promotionPreview['promotion']['title'] ?? 'Khuyến mãi')) ?>
                            <?php if (!empty($promotionPreview['promotion']['coupon_code'])): ?>
                                • Mã: <strong><?= e((string) $promotionPreview['promotion']['coupon_code']) ?></strong>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                    <div class="cart-summary__total">
                        <span><?= !empty($promotionPreview) ? 'Cần thanh toán' : 'Tổng cộng' ?></span>
                        <strong data-cart-total-value><?= e(format_price(!empty($promotionPreview) ? (int) $totalAfterDiscount : (int) $total)) ?></strong>
                    </div>

                    <button type="submit" form="cartCheckoutForm" class="btn btn-primary btn-block cart-summary__submit"><?= $isBuyNowMode ? 'Đặt hàng ngay' : 'Tiến hành đặt hàng' ?></button>
                </section>

                <form method="post" action="<?= e(url('cart/checkout')) ?>" class="cart-panel checkout-form cart-checkout-card" id="cartCheckoutForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="checkout_mode" value="<?= e($cartMode) ?>">
                    <input type="hidden" name="delivery_lat" value="<?= e(old('delivery_lat', '', 'checkout')) ?>" data-delivery-lat-input>
                    <input type="hidden" name="delivery_lon" value="<?= e(old('delivery_lon', '', 'checkout')) ?>" data-delivery-lon-input>
                    <input type="hidden" name="resolved_address" value="<?= e(old('resolved_address', '', 'checkout')) ?>" data-resolved-address-input>

                    <div class="cart-card-title">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M16 16v5H8v-5"/><path d="M12 3L4 9v12h16V9l-8-6z"/><path d="M9 14h6"/></svg>
                        <h3>Thông tin giao hàng</h3>
                    </div>

                    <p class="checkout-form__hint"><?= $isBuyNowMode ? 'RoyalBread sẽ giữ riêng món bạn đang đặt ngay. Bạn vẫn có thể thêm topping hoặc đồ uống nếu muốn.' : 'Trước khi bấm đặt hàng, bạn có thể chọn thêm topping hoặc đồ uống ngay ở phần gợi ý bên trái.' ?></p>

                    <label>
                        Họ và tên *
                        <input type="text" name="customer_name" value="<?= e(old('customer_name', $customer['full_name'] ?? '', 'checkout')) ?>" placeholder="Nhập họ và tên" required>
                    </label>
                    <label>
                        Email nhận xác nhận đơn
                        <input type="email" name="customer_email" value="<?= e(old('customer_email', $customer['email'] ?? '', 'checkout')) ?>" placeholder="Nhập email để nhận xác nhận đơn hàng">
                    </label>
                    <label>
                        Số điện thoại *
                        <input type="text" name="phone" value="<?= e(old('phone', $customer['phone'] ?? '', 'checkout')) ?>" placeholder="Nhập số điện thoại" required>
                    </label>
                    <label>
                        Địa chỉ nhận hàng * <small style="color:rgba(212,175,125,0.7);font-weight:400;">(chọn từ gợi ý hoặc dùng vị trí hiện tại)</small>
                        <textarea name="address" rows="2" placeholder="Gõ tên đường hoặc khu vực rồi chọn từ gợi ý..." data-delivery-address-input required><?= e(old('address', '', 'checkout')) ?></textarea>
                    </label>
                    <div class="checkout-location-tools">
                        <div class="checkout-location-tools__actions">
                            <button type="button" class="checkout-location-tools__btn" data-current-location-btn>Dùng vị trí hiện tại</button>
                            <span class="checkout-location-tools__note">Gõ địa chỉ rồi chọn từ gợi ý để RoyalBread nhận diện đúng vị trí giao hàng hơn.</span>
                        </div>
                        <div class="checkout-location-suggestions" data-address-suggestions hidden></div>
                        <p class="checkout-location-status" data-location-status hidden></p>
                    </div>
                    <label>
                        Số nhà / ngõ / chi tiết <small style="color:rgba(212,175,125,0.7);font-weight:400;">(không bắt buộc)</small>
                        <input type="text" name="address_detail" value="<?= e(old('address_detail', '', 'checkout')) ?>" placeholder="VD: Ngõ 12, số 45, tầng 3..." data-address-detail-input>
                    </label>
                    <label>
                        Khoảng cách giao hàng (km) <small style="color:rgba(212,175,125,0.7);font-weight:400;">(tự động tính)</small>
                        <input
                            type="text"
                            name="distance_km"
                            value="<?= e($distanceFieldValue) ?>"
                            placeholder="Chọn địa chỉ gợi ý hoặc vị trí hiện tại"
                            data-distance-km-input
                            readonly
                            style="cursor:not-allowed;opacity:0.75;"
                        >
                    </label>
                    <label>
                        Ghi chú thêm (không bắt buộc)
                        <textarea name="note" rows="2" placeholder="VD: Lấy nhiều tương ớt, không hành..."><?= e(old('note', '', 'checkout')) ?></textarea>
                    </label>
                    <label>
                        Phương thức thanh toán
                        <select name="payment_method" data-payment-method-select>
                            <option value="cod" <?= old('payment_method', 'cod', 'checkout') === 'cod' ? 'selected' : '' ?>>Thanh toán khi nhận hàng (COD)</option>
                            <option value="bank_transfer" <?= old('payment_method', '', 'checkout') === 'bank_transfer' ? 'selected' : '' ?>>Chuyển khoản trước (MB Bank)</option>
                            <option value="online_qr" <?= old('payment_method', '', 'checkout') === 'online_qr' ? 'selected' : '' ?>>Thanh toán online bằng QR ngân hàng</option>
                        </select>
                    </label>

                    <div class="checkout-bank-card" data-bank-transfer-info hidden>
                        <span class="checkout-bank-card__badge">Chuyển khoản trước</span>
                        <h4>Thông tin tài khoản nhận tiền</h4>
                        <div class="checkout-bank-card__rows">
                            <article>
                                <span>Ngân hàng</span>
                                <strong><?= e($bankTransferInfo['bank_name']) ?></strong>
                            </article>
                            <article>
                                <span>Số tài khoản</span>
                                <strong class="checkout-bank-card__value checkout-bank-card__value--accent"><?= e($bankTransferInfo['account_number']) ?></strong>
                            </article>
                            <article>
                                <span>Chủ tài khoản</span>
                                <strong><?= e($bankTransferInfo['account_holder']) ?></strong>
                            </article>
                            <article>
                                <span>Nội dung CK</span>
                                <strong class="checkout-bank-card__value"><?= e($bankTransferInfo['transfer_note']) ?></strong>
                            </article>
                        </div>
                        <p>Vui lòng ghi đúng số điện thoại khi chuyển khoản để quán đối chiếu đơn nhanh hơn.</p>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block cart-checkout-btn"><?= $isBuyNowMode ? 'Đặt hàng ngay' : 'Tiến hành đặt hàng' ?></button>
                </form>
            </aside>
        <?php endif; ?>
    </div>
</section>

<?php if (!empty($featuredItems)): ?>
    <section class="section-shell bg-light cart-recommend-section">
        <div class="container">
            <div class="section-heading section-heading--single">
                <div>
                    <p class="section-kicker">Có thể bạn sẽ thích</p>
                    <h2>Món ngon đề xuất</h2>
                </div>
            </div>

            <div class="cart-suggest-grid">
                <?php foreach ($featuredItems as $item): ?>
                    <article class="cart-suggest-card">
                        <div class="cart-suggest-card__media">
                            <img src="<?= e(media_url($item['image_url'])) ?>" alt="<?= e($item['name']) ?>" loading="lazy">
                            <?php if (!empty($item['is_featured'])): ?>
                                <span class="cart-suggest-card__badge">Best seller</span>
                            <?php endif; ?>
                        </div>
                        <div class="cart-suggest-card__content">
                            <span class="cart-suggest-card__category"><?= e($item['category_name'] ?? 'RoyalBread') ?></span>
                            <h3><?= e($item['name']) ?></h3>
                            <p><?= e(trim((string) ($item['description'] ?? '')) !== '' ? $item['description'] : 'Thêm món này để đơn hàng phong phú hơn.') ?></p>
                            <div class="cart-suggest-card__footer">
                                <strong><?= e(format_price($item['price'])) ?></strong>
                                <form method="post" action="<?= e(url('cart/add')) ?>" class="cart-suggest-card__form">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= e((string) $item['id']) ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <input type="hidden" name="redirect_to" value="cart<?= $isBuyNowMode ? '?mode=buy-now' : '' ?>">
                                    <button type="submit" class="cart-suggest-card__btn" aria-label="<?= e('Thêm ' . $item['name'] . ' vào giỏ') ?>">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<section class="section-shell--tight">
    <div class="container">
        <div class="cart-trust-grid">
            <?php foreach ($trustPoints as $point): ?>
                <article class="cart-trust-card">
                    <span class="cart-trust-card__icon"><?= e($point['icon']) ?></span>
                    <div>
                        <strong><?= e($point['title']) ?></strong>
                        <p><?= e($point['text']) ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

