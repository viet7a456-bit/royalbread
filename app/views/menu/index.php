<?php
$siteName = setting($settings, 'site_name', 'Bánh mì chảo Hoàng Gia - RoyalBread');
$tagline = setting($settings, 'tagline', 'Bánh mì chảo nóng giòn, topping đầy đặn, lên món nhanh cho khách.');
$pageTitle = 'Thực đơn | ' . $siteName;
$pageDescription = 'Khám phá thực đơn RoyalBread gồm bánh mì chảo, bánh mì kẹp và đồ uống, có thể đặt món trực tiếp trên website.';
$pageKeywords = 'thực đơn RoyalBread, bánh mì chảo, bánh mì kẹp, đồ uống, đặt món';
$brandLogo = asset('assets/images/royalbread-logo.png');
$isCustomerLoggedIn = !empty($_SESSION['customer_id']);

$displayMenuSections = $menuSections ?? [];
$menuTabs = $menuTabs ?? [];
$selectedCategory = (string) ($selectedCategory ?? 'all');
$selectedCategoryName = (string) ($selectedCategoryName ?? 'Tất cả thực đơn');
$currentPage = (int) ($currentPage ?? 1);
$totalPages = (int) ($totalPages ?? 1);
$totalItems = (int) ($totalItems ?? 0);
$perPage = (int) ($perPage ?? 10);
$visibleFrom = (int) ($visibleFrom ?? 0);
$visibleTo = (int) ($visibleTo ?? 0);

$buildMenuPath = static function (string $category = 'all', int $page = 1, string $hash = 'menu-sections'): string {
    $query = [];

    if ($category !== 'all') {
        $query['category'] = $category;
    }

    if ($page > 1) {
        $query['page'] = $page;
    }

    $href = 'menu';

    if ($query !== []) {
        $href .= '?' . http_build_query($query);
    }

    if ($hash !== '') {
        $href .= '#' . ltrim($hash, '#');
    }

    return $href;
};

$buildMenuLink = static function (string $category = 'all', int $page = 1, string $hash = 'menu-sections') use ($buildMenuPath): string {
    return url($buildMenuPath($category, $page, $hash));
};

$getMenuCategoryDescription = static function (string $slug, string $name): string {
    $descriptions = [
        'all' => 'Tổng hợp toàn bộ món nổi bật của RoyalBread để bạn xem nhanh và chọn món dễ hơn.',
        'banh-mi-chao' => 'Bánh mì chảo nóng hổi, đầy đủ topping và phù hợp cho bữa ăn no bụng.',
        'banh-mi-kep' => 'Bánh mì kẹp giòn rụm, nhân đậm đà và tiện cho bữa ăn nhanh.',
        'do-uong' => 'Gộp toàn bộ trà, nước và cafe để bạn chọn đồ uống trong cùng một khu vực.',
        'combo' => 'Các phần ăn kết hợp sẵn giúp tiết kiệm thời gian chọn món và chi phí.',
        'topping' => 'Danh sách topping gọi thêm để tùy chỉnh món ăn theo khẩu vị của bạn.',
        'an-vat' => 'Những món ăn vặt dễ gọi thêm, phù hợp dùng kèm với món chính.',
    ];

    if (isset($descriptions[$slug])) {
        return $descriptions[$slug];
    }

    $nameLower = mb_strtolower($name);
    if (str_contains($nameLower, 'chảo')) {
        return 'Bánh mì chảo nóng hổi, đầy đặn topping và lên món nhanh.';
    }

    if (str_contains($nameLower, 'bánh mì')) {
        return 'Các món bánh mì giòn thơm, tiện lợi và dễ chọn cho mọi khung giờ.';
    }

    if (str_contains($nameLower, 'uống') || str_contains($nameLower, 'trà') || str_contains($nameLower, 'cafe')) {
        return 'Thức uống mát lạnh, dễ kết hợp cùng món ăn chính.';
    }

    return 'Danh mục được cập nhật theo dữ liệu thực tế của RoyalBread.';
};

$tabIcons = [
    'all' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>',
    'bread' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="10" rx="9" ry="6"/><path d="M3 10v4c0 3.3 4 6 9 6s9-2.7 9-6v-4"/></svg>',
    'pan' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="10" cy="12" r="7"/><path d="M17 12h5"/><path d="M22 10v4"/></svg>',
    'drink' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 2h8l-1 10H9L8 2z"/><path d="M9 12v6c0 2 1 3 3 3s3-1 3-3v-6"/><path d="M7 22h10"/></svg>',
    'extra' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/></svg>',
];

$getTabIconKey = static function (string $slug, string $name): string {
    if ($slug === 'all') {
        return 'all';
    }

    $nameLower = mb_strtolower($name);

    if (str_contains($slug, 'chao') || str_contains($nameLower, 'chảo')) {
        return 'pan';
    }

    if (str_contains($slug, 'banh-mi') || str_contains($nameLower, 'bánh mì')) {
        return 'bread';
    }

    if ($slug === 'do-uong' || str_contains($nameLower, 'uống') || str_contains($nameLower, 'trà') || str_contains($nameLower, 'cafe')) {
        return 'drink';
    }

    return 'extra';
};

$heroItems = [];
foreach ($displayMenuSections as $section) {
    foreach (array_slice($section['items'], 0, 3) as $item) {
        $heroItems[] = $item;
    }
}
$heroItems = array_slice($heroItems, 0, 3);
$heroMain = $heroItems[0] ?? null;

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

<section class="mn-hero">
    <div class="container mn-hero__grid">
        <div class="mn-hero__copy">
            <h1 class="mn-hero__title">THỰC ĐƠN</h1>
            <p class="mn-hero__script">Hương vị phố cổ</p>
            <div class="mn-hero__divider">
                <span></span>
                <em><?= e($selectedCategoryName) ?></em>
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
            <?php foreach ($menuTabs as $tab): ?>
                <?php
                $tabSlug = (string) ($tab['slug'] ?? 'all');
                $tabName = (string) ($tab['name'] ?? 'Tất cả');
                $isActive = $tabSlug === $selectedCategory;
                $tabHash = $tabSlug === 'all' ? 'menu-sections' : 'group-' . $tabSlug;
                $tabIconKey = $getTabIconKey($tabSlug, $tabName);
                ?>
                <a
                    class="mn-tab<?= $isActive ? ' active' : '' ?>"
                    href="<?= e($buildMenuLink($tabSlug, 1, $tabHash)) ?>"
                    data-filter="<?= e($tabSlug) ?>"
                    data-legacy-filter="<?= e((string) ($tab['legacy_hash'] ?? '')) ?>"
                >
                    <?= $tabIcons[$tabIconKey] ?>
                    <span><?= e(mb_strtoupper($tabName)) ?></span>
                    <small><?= e((string) ($tab['item_count'] ?? 0)) ?></small>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="mn-sections" id="menu-sections">
    <div class="container mn-sections__stack">
        <?php foreach ($displayMenuSections as $section): ?>
            <?php
            $sectionSlug = (string) ($section['slug'] ?? 'all');
            $sectionName = (string) ($section['name'] ?? 'Thực đơn');
            $sectionItems = $section['items'] ?? [];
            $sectionHash = $sectionSlug === 'all' ? 'group-all' : 'group-' . $sectionSlug;
            $sectionDesc = $getMenuCategoryDescription($sectionSlug, $sectionName);
            $sectionIconKey = $getTabIconKey($sectionSlug, $sectionName);
            ?>
            <div
                class="mn-group"
                data-group="<?= e($sectionSlug) ?>"
                data-legacy-group="<?= e((string) ($section['legacy_hash'] ?? '')) ?>"
                id="<?= e($sectionHash) ?>"
            >
                <div class="mn-group__header">
                    <div class="mn-group__title-area">
                        <h2 class="mn-group__name">
                            <?= $tabIcons[$sectionIconKey] ?>
                            <?= e($sectionName) ?>
                        </h2>
                        <p class="mn-group__desc"><?= e($sectionDesc) ?></p>
                    </div>

                    <div class="mn-group__summary">
                        <span class="mn-group__summary-badge"><?= e((string) count($sectionItems)) ?> món trong trang này</span>
                        <?php if ($selectedCategory !== 'all'): ?>
                            <a class="mn-group__see-all" href="<?= e($buildMenuLink('all', 1, 'menu-sections')) ?>">
                                Xem toàn bộ <span>&rarr;</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($sectionItems === []): ?>
                    <div class="mn-group__empty">
                        Danh mục này hiện chưa có món để hiển thị.
                    </div>
                <?php else: ?>
                    <div class="mn-card-grid">
                        <?php foreach ($sectionItems as $item): ?>
                            <?php
                            $reviewSummary = $reviewSummaries[(int) $item['id']] ?? null;
                            $recentReviews = $recentReviewsByItem[(int) $item['id']] ?? [];
                            $latestReview = $recentReviews[0] ?? null;
                            $reviewCount = (int) ($reviewSummary['review_count'] ?? 0);
                            $reviewAverage = (float) ($reviewSummary['rating_average'] ?? 0);
                            $isFavorite = in_array((int) $item['id'], $favoriteItemIds ?? [], true);
                            $itemRedirect = $buildMenuPath($selectedCategory, $currentPage, 'menu-item-' . (string) $item['id']);
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
                                                <input type="hidden" name="redirect_to" value="<?= e($itemRedirect) ?>">
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
                                                <input type="hidden" name="redirect_to" value="<?= e($itemRedirect) ?>">
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
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php if ($totalPages > 1): ?>
    <section class="mn-pagination-section">
        <div class="container">
            <div class="mn-pagination__summary">
                <div class="mn-pagination__summary-copy">
                    <strong>Hiển thị món <?= e((string) $visibleFrom) ?> - <?= e((string) $visibleTo) ?> / <?= e((string) $totalItems) ?></strong>
                    <span><?= e($selectedCategoryName) ?> • <?= e((string) $perPage) ?> món mỗi trang</span>
                </div>

                <?php if ($selectedCategory !== 'all'): ?>
                    <a class="mn-pagination__back" href="<?= e($buildMenuLink('all', 1, 'menu-sections')) ?>">Về tất cả danh mục</a>
                <?php endif; ?>
            </div>

            <nav class="mn-pagination" aria-label="Phân trang thực đơn">
                <div class="mn-pagination__links">
                    <?php if ($currentPage > 1): ?>
                        <a class="mn-pagination__link mn-pagination__link--nav" href="<?= e($buildMenuLink($selectedCategory, 1, 'menu-sections')) ?>" aria-label="Trang đầu">&laquo;</a>
                        <a class="mn-pagination__link mn-pagination__link--nav" href="<?= e($buildMenuLink($selectedCategory, $currentPage - 1, 'menu-sections')) ?>" aria-label="Trang trước">&lsaquo;</a>
                    <?php endif; ?>

                    <?php if ($paginationStart > 1): ?>
                        <a class="mn-pagination__link" href="<?= e($buildMenuLink($selectedCategory, 1, 'menu-sections')) ?>">1</a>
                        <?php if ($paginationStart > 2): ?>
                            <span class="mn-pagination__ellipsis">…</span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($page = $paginationStart; $page <= $paginationEnd; $page++): ?>
                        <a class="mn-pagination__link <?= $page === $currentPage ? 'is-active' : '' ?>" href="<?= e($buildMenuLink($selectedCategory, $page, 'menu-sections')) ?>">
                            <?= e((string) $page) ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($paginationEnd < $totalPages): ?>
                        <?php if ($paginationEnd < ($totalPages - 1)): ?>
                            <span class="mn-pagination__ellipsis">…</span>
                        <?php endif; ?>
                        <a class="mn-pagination__link" href="<?= e($buildMenuLink($selectedCategory, $totalPages, 'menu-sections')) ?>"><?= e((string) $totalPages) ?></a>
                    <?php endif; ?>

                    <?php if ($currentPage < $totalPages): ?>
                        <a class="mn-pagination__link mn-pagination__link--nav" href="<?= e($buildMenuLink($selectedCategory, $currentPage + 1, 'menu-sections')) ?>" aria-label="Trang sau">&rsaquo;</a>
                        <a class="mn-pagination__link mn-pagination__link--nav" href="<?= e($buildMenuLink($selectedCategory, $totalPages, 'menu-sections')) ?>" aria-label="Trang cuối">&raquo;</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </section>
<?php endif; ?>
