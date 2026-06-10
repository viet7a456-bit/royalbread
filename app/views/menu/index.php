<?php
$siteName = setting($settings, 'site_name', 'Bánh mì chảo Hoàng Gia - RoyalBread');
$tagline = setting($settings, 'tagline', 'Bánh mì chảo nóng giòn, topping đầy đặn, lên món nhanh cho khách.');
$pageTitle = 'Thực đơn | ' . $siteName;
$pageDescription = 'Khám phá thực đơn RoyalBread gồm bánh mì chảo, bánh mì kẹp và đồ uống, có thể đặt món trực tiếp trên website.';
$pageKeywords = 'thực đơn RoyalBread, bánh mì chảo, bánh mì kẹp, đồ uống, đặt món';
$brandLogo = asset('assets/images/royalbread-logo.png');
$isCustomerLoggedIn = !empty($_SESSION['customer_id']);

$displayMenuGroups = $menuGroups;

$heroItems = [];
foreach ($displayMenuGroups as $items) {
    foreach (array_slice($items, 0, 2) as $item) {
        $heroItems[] = $item;
    }
}
$heroItems = array_slice($heroItems, 0, 3);
$heroMain = $heroItems[0] ?? null;

$categoryDescriptions = [
    'Bánh Mì Chảo' => 'Nóng hổi thơm ngon - Đầy đủ dinh dưỡng',
    'Topping' => 'Gọi thêm topping - Linh hoạt theo khẩu vị',
    'Combo' => 'Ăn kèm đồ uống - Tiện lợi và tiết kiệm',
    'Đồ uống' => 'Gộp trà nhiệt đới, đồ uống truyền thống và cafe cho dễ chọn',
    'Bánh Mì Kẹp' => 'Giòn rụm bên ngoài - Đậm đà bên trong',
    'Ăn Vặt' => 'Món ăn vặt dễ gọi - Dùng kèm đều hợp',
];

function get_cat_desc_menu(string $name, array $descriptions): string
{
    if (isset($descriptions[$name])) {
        return $descriptions[$name];
    }

    $nameLower = mb_strtolower($name);

    if (str_contains($nameLower, 'chảo')) {
        return 'Nóng hổi thơm ngon - Đầy đủ dinh dưỡng';
    }
    if (str_contains($nameLower, 'bánh mì')) {
        return 'Giòn rụm bên ngoài - Đậm đà bên trong';
    }
    if (str_contains($nameLower, 'trà') || str_contains($nameLower, 'uống') || str_contains($nameLower, 'cafe')) {
        return 'Thức uống mát lạnh - Sảng khoái mỗi ngày';
    }

    return 'Món ăn được cập nhật đúng theo dữ liệu hiện tại';
}

$tabIcons = [
    'all' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>',
    'bread' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="10" rx="9" ry="6"/><path d="M3 10v4c0 3.3 4 6 9 6s9-2.7 9-6v-4"/></svg>',
    'pan' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="10" cy="12" r="7"/><path d="M17 12h5"/><path d="M22 10v4"/></svg>',
    'drink' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 2h8l-1 10H9L8 2z"/><path d="M9 12v6c0 2 1 3 3 3s3-1 3-3v-6"/><path d="M7 22h10"/></svg>',
    'extra' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/></svg>',
];

function get_tab_icon_menu(string $name): string
{
    $nameLower = mb_strtolower($name);

    if (str_contains($nameLower, 'chảo')) {
        return 'pan';
    }
    if (str_contains($nameLower, 'bánh mì')) {
        return 'bread';
    }
    if (str_contains($nameLower, 'trà') || str_contains($nameLower, 'uống') || str_contains($nameLower, 'cafe')) {
        return 'drink';
    }

    return 'extra';
}
?>

<section class="mn-hero">
    <div class="container mn-hero__grid">
        <div class="mn-hero__copy">
            <h1 class="mn-hero__title">THỰC ĐƠN</h1>
            <p class="mn-hero__script">Hương vị phố cổ</p>
            <div class="mn-hero__divider">
                <span></span>
                <em>Tinh hoa trong từng món ăn</em>
                <span></span>
            </div>
            <p class="mn-hero__desc"><?= e($tagline) ?></p>
        </div>

        <div class="mn-hero__visual">
            <?php if ($heroMain !== null): ?>
                <div class="mn-hero__plate">
                    <img src="<?= e(media_url($heroMain['image_url'])) ?>" alt="<?= e($heroMain['name']) ?>">
                </div>
            <?php endif; ?>
            <div class="mn-hero__logo-badge">
                <img src="<?= e($brandLogo) ?>" alt="<?= e($siteName) ?>">
                <div>
                    <strong><?= e($siteName) ?></strong>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="mn-tabs-wrap">
    <div class="container">
        <div class="mn-tabs" id="menuCategoryTabs">
            <a class="mn-tab active" href="#menu-sections" data-filter="all">
                <?= $tabIcons['all'] ?>
                <span>TẤT CẢ</span>
            </a>
            <?php foreach ($displayMenuGroups as $groupName => $items): ?>
                <?php $iconKey = get_tab_icon_menu($groupName); ?>
                <a class="mn-tab" href="#group-<?= e(md5($groupName)) ?>" data-filter="<?= e(md5($groupName)) ?>">
                    <?= $tabIcons[$iconKey] ?>
                    <span><?= e(mb_strtoupper($groupName)) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="mn-sections" id="menu-sections">
    <div class="container mn-sections__stack">
        <?php foreach ($displayMenuGroups as $groupName => $items): ?>
            <div class="mn-group" data-group="<?= e(md5($groupName)) ?>" id="group-<?= e(md5($groupName)) ?>">
                <div class="mn-group__header">
                    <div class="mn-group__title-area">
                        <h2 class="mn-group__name">
                            <?= $tabIcons[get_tab_icon_menu($groupName)] ?>
                            <?= e($groupName) ?>
                        </h2>
                        <p class="mn-group__desc"><?= e(get_cat_desc_menu($groupName, $categoryDescriptions)) ?></p>
                    </div>
                    <a class="mn-group__see-all" href="#group-<?= e(md5($groupName)) ?>">
                        Xem tất cả <span>&rarr;</span>
                    </a>
                </div>

                <div class="mn-card-grid">
                    <?php foreach ($items as $item): ?>
                        <?php
                        $reviewSummary = $reviewSummaries[(int) $item['id']] ?? null;
                        $recentReviews = $recentReviewsByItem[(int) $item['id']] ?? [];
                        $latestReview = $recentReviews[0] ?? null;
                        $reviewCount = (int) ($reviewSummary['review_count'] ?? 0);
                        $reviewAverage = (float) ($reviewSummary['rating_average'] ?? 0);
                        $isFavorite = in_array((int) $item['id'], $favoriteItemIds ?? [], true);
                        ?>
                        <article class="mn-card" id="menu-item-<?= e((string) $item['id']) ?>">
                            <div class="mn-card__media">
                                <img src="<?= e(media_url($item['image_url'])) ?>" alt="<?= e($item['name']) ?>">
                                <?php if (!empty($item['is_featured'])): ?>
                                    <span class="mn-badge mn-badge--best">BEST<br>SELLER</span>
                                <?php endif; ?>
                            </div>

                            <div class="mn-card__body">
                                <div class="mn-card__meta-row">
                                    <span class="mn-card__meta-pill"><?= e($item['category_name']) ?></span>
                                    <?php if ($isCustomerLoggedIn): ?>
                                        <form method="post" action="<?= e(url('customer/favorites/toggle')) ?>" class="mn-favorite-form">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="menu_item_id" value="<?= e((string) $item['id']) ?>">
                                            <input type="hidden" name="redirect_to" value="menu#menu-item-<?= e((string) $item['id']) ?>">
                                            <button type="submit" class="mn-card__favorite<?= $isFavorite ? ' is-active' : '' ?>" aria-label="<?= e($isFavorite ? 'Bỏ yêu thích' : 'Thêm yêu thích') ?>">
                                                <?= $isFavorite ? '♥' : '♡' ?>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <a class="mn-card__favorite" href="<?= e(url('customer/login')) ?>" aria-label="Đăng nhập để lưu yêu thích">♡</a>
                                    <?php endif; ?>
                                </div>

                                <h3 class="mn-card__name"><?= e($item['name']) ?></h3>
                                <p class="mn-card__desc"><?= e($item['description']) ?></p>

                                <div class="mn-card__rating-row">
                                    <?php if ($reviewCount > 0): ?>
                                        <span class="mn-card__rating">★ <?= e(number_format($reviewAverage, 1, ',', '.')) ?></span>
                                        <small><?= e((string) $reviewCount) ?> đánh giá</small>
                                    <?php else: ?>
                                        <small class="mn-card__rating mn-card__rating--empty">Chưa có đánh giá</small>
                                    <?php endif; ?>
                                </div>

                                <?php if ($latestReview !== null): ?>
                                    <div class="mn-card__review-snippet">
                                        <strong><?= e($latestReview['customer_name'] ?? 'Khách hàng RoyalBread') ?></strong>
                                        <p><?= e(trim((string) ($latestReview['review_title'] ?? '')) !== '' ? $latestReview['review_title'] : $latestReview['review_comment']) ?></p>
                                    </div>
                                <?php endif; ?>

                                <div class="mn-card__footer">
                                    <strong class="mn-card__price"><?= e(format_price($item['price'])) ?></strong>
                                    <div class="mn-card__actions">
                                        <form method="post" action="<?= e(url('cart/add')) ?>" class="mn-add-form">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= e((string) $item['id']) ?>">
                                            <input type="hidden" name="quantity" value="1">
                                            <input type="hidden" name="redirect_to" value="menu">
                                            <button type="submit" class="mn-card__action-btn mn-card__action-btn--ghost">Thêm vào giỏ</button>
                                        </form>
                                        <form method="post" action="<?= e(url('cart/buy-now')) ?>" class="mn-add-form">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= e((string) $item['id']) ?>">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="mn-card__action-btn mn-card__action-btn--buy">Đặt ngay</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
