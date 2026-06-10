<?php
$siteName = setting($settings, 'site_name', 'Bánh mì chảo Hoàng Gia - RoyalBread');
$tagline = setting($settings, 'tagline', 'Bánh mì chảo nóng giòn, topping đầy đặn, lên món nhanh cho khách tại Hải Dương.');
$address = setting($settings, 'address', '28 Dã Tượng - TP Hải Dương (Sau nhà thi đấu Hải Dương)');
$hotline = setting($settings, 'hotline', '0879866636');
$openingHours = setting($settings, 'opening_hours', '07:00 - 22:00 mỗi ngày');
$shopeefoodUrl = setting($settings, 'shopeefood_url', '#');
$pageTitle = $siteName . ' | Trang chủ';
$pageDescription = 'RoyalBread phục vụ bánh mì chảo, bánh mì kẹp và đồ uống với giao hàng nhanh tại Hải Dương.';
$pageKeywords = 'RoyalBread, bánh mì chảo Hải Dương, bánh mì kẹp, đồ uống, đặt món online';
$pageImage = media_url(setting($settings, 'home_banner_slide_1', 'assets/images/home-hero-banner-3.png'));
$brandLogo = asset('assets/images/royalbread-logo.png');

$getSettingValuesByPrefix = static function (array $settings, string $prefix): array {
    $matchedValues = [];

    foreach ($settings as $key => $value) {
        if (preg_match('/^' . preg_quote($prefix, '/') . '(\d+)$/', (string) $key, $matches) !== 1) {
            continue;
        }

        $matchedValues[(int) $matches[1]] = trim((string) $value);
    }

    ksort($matchedValues);

    return array_values(array_filter($matchedValues, static fn (string $value): bool => $value !== ''));
};

$resolveGroup = static function (array $groups, array $exactMatches, array $needleMatches = []): array {
    foreach ($exactMatches as $match) {
        if (isset($groups[$match])) {
            return [$match, $groups[$match]];
        }
    }

    foreach ($groups as $name => $items) {
        foreach ($needleMatches as $needle) {
            if (str_contains($name, $needle)) {
                return [$name, $items];
            }
        }
    }

    $firstName = array_key_first($groups);

    return [$firstName ?? '', $firstName !== null ? $groups[$firstName] : []];
};

$mergeGroups = static function (array $groups, array $preferredNames): array {
    $merged = [];

    foreach ($preferredNames as $name) {
        if (!isset($groups[$name])) {
            continue;
        }

        foreach ($groups[$name] as $item) {
            $merged[] = $item;
        }
    }

    return $merged;
};

$pickItemsByIds = static function (array $itemsById, array $ids): array {
    $picked = [];
    $seen = [];

    foreach ($ids as $rawId) {
        $id = (int) $rawId;
        if ($id <= 0 || isset($seen[$id]) || !isset($itemsById[$id])) {
            continue;
        }

        $seen[$id] = true;
        $picked[] = $itemsById[$id];
    }

    return $picked;
};

[$breadGroupName, $breadItems] = $resolveGroup($menuGroups, ['Bánh Mì Kẹp'], ['Bánh Mì Kẹp', 'Bánh Mì']);
[$panGroupName, $panItems] = $resolveGroup($menuGroups, ['Bánh Mì Chảo'], ['Chảo']);

$menuGroupsBySlug = [];
foreach ($menuGroups as $groupItems) {
    $groupSlug = (string) ($groupItems[0]['category_slug'] ?? '');
    if ($groupSlug !== '') {
        $menuGroupsBySlug[$groupSlug] = $groupItems;
    }
}

$breadItems = $menuGroupsBySlug['banh-mi-kep'] ?? $breadItems;
$breadGroupName = $breadItems !== [] ? 'Bánh Mì Kẹp' : $breadGroupName;
$panItems = $menuGroupsBySlug['banh-mi-chao'] ?? $panItems;
$panGroupName = $panItems !== [] ? 'Bánh Mì Chảo' : $panGroupName;

$drinkItems = [];
foreach (['tra-nhiet-doi', 'do-uong-truyen-thong', 'cafe'] as $drinkSlug) {
    foreach ($menuGroupsBySlug[$drinkSlug] ?? [] as $item) {
        $drinkItems[] = $item;
    }
}

$drinkGroupName = $drinkItems !== [] ? 'Đồ uống' : '';
$featuredFromDrinks = $drinkItems !== [] ? $drinkItems : $featuredItems;
$heroBreadItem = $breadItems[0] ?? $featuredItems[0] ?? null;
$homeDrinkMenuLink = url('menu') . '#group-' . md5('Đồ uống');

$menuItemsById = [];
foreach ($menuGroups as $groupItems) {
    foreach ($groupItems as $item) {
        $menuItemsById[(int) $item['id']] = $item;
    }
}

$spotlightList = $pickItemsByIds($menuItemsById, $getSettingValuesByPrefix($settings, 'home_spotlight_item_'));
if ($spotlightList === []) {
    $spotlightList = array_slice($panItems !== [] ? $panItems : $featuredItems, 0, 4);
}

$spotlightItem = $spotlightList[0] ?? $panItems[0] ?? $featuredItems[0] ?? null;
$homeSignatureFallbackImage = media_url(setting($settings, 'home_signature_image', ''));
$homeSignatureImage = media_url($spotlightItem['image_url'] ?? '');
if ($homeSignatureImage === '') {
    $homeSignatureImage = $homeSignatureFallbackImage;
}

$drinkHighlights = $pickItemsByIds($menuItemsById, $getSettingValuesByPrefix($settings, 'home_drink_item_'));
if ($drinkHighlights === []) {
    $drinkHighlights = array_slice($featuredFromDrinks, 0, 5);
}

$configuredSlideValues = $getSettingValuesByPrefix($settings, 'home_banner_slide_');
$heroSlides = [];
foreach ($configuredSlideValues as $slideValue) {
    $heroSlides[] = [
        'background' => media_url($slideValue),
        'main' => '',
        'main_alt' => $siteName,
        'accent' => '',
        'accent_alt' => $siteName,
        'duration' => 7000,
    ];
}

if ($heroSlides === []) {
    $heroSlides = [
        [
            'background' => asset('assets/images/storefront-bg.jpg'),
            'main' => '',
            'main_alt' => $siteName,
            'accent' => '',
            'accent_alt' => $siteName,
            'duration' => 7000,
        ],
        [
            'background' => asset('assets/images/home-hero-banner-2.png'),
            'main' => '',
            'main_alt' => $siteName,
            'accent' => '',
            'accent_alt' => $siteName,
            'duration' => 7000,
        ],
        [
            'background' => asset('assets/images/home-hero-banner-3.png'),
            'main' => '',
            'main_alt' => $siteName,
            'accent' => '',
            'accent_alt' => $siteName,
            'duration' => 7000,
        ],
    ];
}

$heroNameMain = trim((string) preg_replace('/\s*-\s*RoyalBread$/u', '', $siteName));
$heroNameBrand = str_contains($siteName, 'RoyalBread') ? 'RoyalBread' : $siteName;
$initialHeroSlide = $heroSlides[0] ?? [
    'background' => media_url($heroBreadItem['image_url'] ?? ''),
    'main' => '',
    'main_alt' => $heroBreadItem['name'] ?? $siteName,
    'accent' => '',
    'accent_alt' => $spotlightItem['name'] ?? $siteName,
];
$heroSlidesJson = htmlspecialchars(
    json_encode($heroSlides, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ENT_QUOTES,
    'UTF-8'
);

$categoryShowcase = [
    [
        'title' => 'Bánh mì',
        'summary' => 'Giòn rụm bên ngoài',
        'detail' => 'Đậm đà bên trong',
        'group_name' => $breadGroupName,
        'items' => $breadItems,
        'image' => media_url(setting($settings, 'home_category_card_bread_image', $breadItems[0]['image_url'] ?? '')),
    ],
    [
        'title' => 'Bánh mì chảo',
        'summary' => 'Nóng hổi thơm ngon',
        'detail' => 'Đầy đặn topping mỗi phần',
        'group_name' => $panGroupName,
        'items' => $panItems,
        'image' => media_url(setting($settings, 'home_category_card_pan_image', $panItems[0]['image_url'] ?? '')),
    ],
    [
        'title' => 'Đồ uống',
        'summary' => 'Mát lạnh dễ uống',
        'detail' => 'Gộp tất cả trà, nước và cafe',
        'group_name' => $drinkGroupName,
        'items' => $drinkItems,
        'image' => media_url(setting($settings, 'home_category_card_drink_image', $drinkItems[0]['image_url'] ?? '')),
    ],
];
?>

<div class="home-page">
    <section class="home-hero section-shell--tight">
        <div class="container">
            <div
                class="home-hero__frame"
                data-home-hero
                data-home-hero-slides="<?= $heroSlidesJson ?>"
                style="--home-hero-bg: url('<?= e($initialHeroSlide['background'] ?? '') ?>');"
            >
                <div class="home-hero__bg-layer"></div>

                <?php if (count($heroSlides) > 1): ?>
                    <button class="home-hero__nav home-hero__nav--prev" type="button" data-home-hero-prev aria-label="Ảnh trước">&#8249;</button>
                    <button class="home-hero__nav home-hero__nav--next" type="button" data-home-hero-next aria-label="Ảnh tiếp theo">&#8250;</button>
                <?php endif; ?>

                <div class="home-hero__media" aria-hidden="true">
                    <div class="home-hero__video-shell" data-home-hero-video-shell hidden>
                        <video
                            class="home-hero__video"
                            data-home-hero-video
                            muted
                            loop
                            playsinline
                            preload="metadata"
                            disablepictureinpicture
                        ></video>
                    </div>
                    <div class="home-hero__image-shell" data-home-hero-image-shell>
                        <img
                            class="home-hero__fg-image"
                            data-home-hero-fg-image
                            src="<?= e($initialHeroSlide['background'] ?? '') ?>"
                            alt="Banner"
                        >
                    </div>
                </div>

                <div class="home-hero__content">
                    <p class="home-script">Hương vị</p>
                    <h1><?= e($heroNameMain) ?></h1>
                    <p class="home-hero__brandline"><?= e($heroNameBrand) ?> - Hải Dương</p>
                    <p class="home-hero__lead"><?= e($tagline) ?></p>

                    <div class="home-hero__meta">
                        <span><?= e($address) ?></span>
                        <span>Hotline <?= e($hotline) ?></span>
                        <span><?= e($openingHours) ?></span>
                    </div>

                    <div class="home-hero__actions">
                        <a class="btn btn-primary" href="<?= e(url('menu')) ?>">Khám phá ngay</a>
                        <a class="btn btn-outline home-btn-light" href="<?= e($shopeefoodUrl) ?>" target="_blank" rel="noopener noreferrer">Đặt qua ShopeeFood</a>
                    </div>
                </div>

                <div class="home-hero__plaque">
                    <img src="<?= e($brandLogo) ?>" alt="<?= e($siteName) ?>">
                    <div>
                        <strong><?= e($heroNameBrand) ?></strong>
                        <span><?= e($openingHours) ?></span>
                    </div>
                </div>

                <?php if (count($heroSlides) > 1): ?>
                    <div class="home-hero__dots" data-home-hero-dots>
                        <?php foreach ($heroSlides as $index => $slide): ?>
                            <button
                                type="button"
                                class="home-hero__dot<?= $index === 0 ? ' is-active' : '' ?>"
                                data-home-hero-dot="<?= e((string) $index) ?>"
                                aria-label="<?= e('Chuyển đến ảnh ' . ($index + 1)) ?>"
                            ></button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="home-section">
        <div class="container">
            <div class="home-heading">
                <span></span>
                <h2>Thực đơn của chúng tôi</h2>
                <span></span>
            </div>

            <div class="home-search-container">
                <div class="home-search-bar">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="text" id="liveSearchInput" placeholder="Tìm kiếm món ăn, ví dụ: bánh mì chảo..." autocomplete="off">
                </div>
                <div id="liveSearchResults" class="home-search-results" style="display: none;"></div>
            </div>

            <div class="home-category-grid">
                <?php foreach ($categoryShowcase as $card): ?>
                    <?php $leadItem = $card['items'][0] ?? null; ?>
                    <?php if ($leadItem === null) {
                        continue;
                    } ?>
                    <article class="home-category-card">
                        <img class="home-category-card__media" src="<?= e($card['image'] !== '' ? $card['image'] : media_url($leadItem['image_url'])) ?>" alt="<?= e($card['title']) ?>">
                        <div class="home-category-card__body">
                            <h3><?= e($card['title']) ?></h3>
                            <p><?= e($card['summary']) ?></p>
                            <small><?= e($card['detail']) ?> • <?= e((string) count($card['items'])) ?> món</small>
                        </div>
                        <a class="home-arrow-link" href="<?= e(url('menu') . '#group-' . md5($card['group_name'])) ?>" aria-label="<?= e('Xem ' . $card['title']) ?>">&#8594;</a>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <?php if ($spotlightItem !== null): ?>
        <section class="home-section home-section--spotlight">
            <div class="container">
                <div class="home-spotlight" data-home-spotlight>
                    <div class="home-spotlight__media">
                        <img
                            src="<?= e($homeSignatureImage !== '' ? $homeSignatureImage : media_url($spotlightItem['image_url'] ?? '')) ?>"
                            alt="<?= e($spotlightItem['name']) ?>"
                            data-home-spotlight-image
                        >
                    </div>

                    <div class="home-spotlight__copy">
                        <span class="home-badge" data-home-spotlight-badge><?= e($spotlightItem['category_name'] ?? 'Món ngon nổi bật') ?></span>
                        <h2 data-home-spotlight-title><?= e($spotlightItem['name']) ?></h2>
                        <p data-home-spotlight-description><?= e($spotlightItem['description'] !== '' ? $spotlightItem['description'] : $tagline) ?></p>
                        <strong data-home-spotlight-price><?= e(format_price($spotlightItem['price'])) ?></strong>

                        <a class="btn btn-primary" href="<?= e(url('menu')) ?>">Đặt món ngay</a>

                        <div class="home-spotlight__list">
                            <?php foreach ($spotlightList as $item): ?>
                                <?php
                                $itemDescription = $item['description'] !== '' ? $item['description'] : $tagline;
                                $itemImage = $item['image_url'] !== '' ? media_url($item['image_url']) : ($homeSignatureFallbackImage !== '' ? $homeSignatureFallbackImage : $homeSignatureImage);
                                $isActiveSpotlight = ((int) ($item['id'] ?? 0) === (int) ($spotlightItem['id'] ?? 0));
                                ?>
                                <button
                                    type="button"
                                    class="home-spotlight__choice<?= $isActiveSpotlight ? ' is-active' : '' ?>"
                                    data-home-spotlight-choice
                                    data-image="<?= e($itemImage) ?>"
                                    data-alt="<?= e($item['name']) ?>"
                                    data-badge="<?= e($item['category_name'] ?? 'Món ngon nổi bật') ?>"
                                    data-title="<?= e($item['name']) ?>"
                                    data-description="<?= e($itemDescription) ?>"
                                    data-price="<?= e(format_price($item['price'])) ?>"
                                    aria-pressed="<?= $isActiveSpotlight ? 'true' : 'false' ?>"
                                >
                                    <span><?= e($item['category_name']) ?></span>
                                    <div>
                                        <h3><?= e($item['name']) ?></h3>
                                        <p><?= e(format_price($item['price'])) ?></p>
                                    </div>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($drinkHighlights !== []): ?>
        <section class="home-section">
            <div class="container">
                <div class="home-heading">
                    <span></span>
                    <h2>Đồ uống được yêu thích</h2>
                    <span></span>
                </div>

                <div class="home-drink-grid">
                    <?php foreach ($drinkHighlights as $item): ?>
                        <a class="home-drink-card home-drink-card--link" href="<?= e($homeDrinkMenuLink) ?>" aria-label="<?= e('Xem ' . $item['name'] . ' trong thực đơn') ?>">
                            <div class="home-drink-card__media">
                                <img src="<?= e(media_url($item['image_url'])) ?>" alt="<?= e($item['name']) ?>">
                            </div>
                            <div class="home-drink-card__body">
                                <h3><?= e($item['name']) ?></h3>
                                <strong><?= e(format_price($item['price'])) ?></strong>
                                <span class="home-drink-card__cta">Xem trong thực đơn</span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
</div>
