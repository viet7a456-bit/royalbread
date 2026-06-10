<?php
$settings = $settings ?? [];
$siteName = setting($settings, 'site_name', 'Bánh mì chảo Hoàng Gia - RoyalBread');
$tagline = setting($settings, 'tagline', 'Bánh mì chảo nóng giòn, topping đầy đặn, lên món nhanh cho khách tại Hải Dương.');
$hotline = setting($settings, 'hotline', '0879866636');
$address = setting($settings, 'address', '28 Dã Tượng - TP Hải Dương');
$openingHours = setting($settings, 'opening_hours', '06:00 - 23:59 mỗi ngày');
$shopeefoodUrl = setting($settings, 'shopeefood_url', '#');
$facebookUrl = 'https://www.facebook.com/profile.php?id=61582626099340';
$zaloUrl = 'https://zalo.me/pc';
$brandLogo = asset('assets/images/royalbread-logo.png');
$styleCssVersion = (string) @filemtime(ROOT_PATH . '/assets/css/style.css');
$appJsVersion = (string) @filemtime(ROOT_PATH . '/assets/js/app.js');
$uiSparkLottieSrc = 'https://lottie.host/0479f524-f797-42f9-b75f-9150a26f8b83/x3pkmfJgGK.lottie';
$isCustomerLoggedIn = !empty($_SESSION['customer_id']);
$isAdminLoggedIn = !empty($_SESSION['admin_id']);

$currentPath = current_path();
$baseKeywords = setting($settings, 'seo_default_keywords', 'RoyalBread, bánh mì chảo, bánh mì Hải Dương, đồ uống');
$defaultImage = media_url(setting($settings, 'home_signature_image', 'assets/images/royalbread-logo.png'));
$pageTitle = $pageTitle ?? $siteName;
$pageDescription = $pageDescription ?? $tagline;
$pageKeywords = $pageKeywords ?? $baseKeywords;
$pageCanonical = $pageCanonical ?? current_full_url(false);
$pageImage = isset($pageImage) && trim((string) $pageImage) !== '' ? media_url((string) $pageImage) : $defaultImage;

$noindexPrefixes = ['admin', 'cart', 'account', 'customer/login', 'customer/register', 'customer/logout'];
$pageRobots = $pageRobots ?? 'index,follow';
foreach ($noindexPrefixes as $prefix) {
    if ($currentPath === $prefix || str_starts_with($currentPath, $prefix . '/')) {
        $pageRobots = 'noindex,nofollow';
        break;
    }
}

$structuredData = [
    '@context' => 'https://schema.org',
    '@type' => 'Restaurant',
    'name' => $siteName,
    'description' => $pageDescription,
    'url' => full_url(),
    'image' => [$pageImage],
    'telephone' => $hotline,
    'address' => [
        '@type' => 'PostalAddress',
        'streetAddress' => $address,
        'addressCountry' => 'VN',
    ],
    'servesCuisine' => ['Bánh mì', 'Đồ uống'],
    'sameAs' => [$facebookUrl, $zaloUrl, $shopeefoodUrl],
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($pageDescription) ?>">
    <meta name="keywords" content="<?= e($pageKeywords) ?>">
    <meta name="robots" content="<?= e($pageRobots) ?>">
    <meta name="author" content="<?= e($siteName) ?>">
    <link rel="canonical" href="<?= e($pageCanonical) ?>">

    <meta property="og:locale" content="vi_VN">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($pageDescription) ?>">
    <meta property="og:url" content="<?= e($pageCanonical) ?>">
    <meta property="og:site_name" content="<?= e($siteName) ?>">
    <meta property="og:image" content="<?= e($pageImage) ?>">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($pageTitle) ?>">
    <meta name="twitter:description" content="<?= e($pageDescription) ?>">
    <meta name="twitter:image" content="<?= e($pageImage) ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Allura&family=Cormorant+Garamond:wght@500;600;700&family=Mulish:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.9.14/dist/dotlottie-wc.js" type="module" data-chatbot-lottie="true"></script>
    <link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>?v=<?= e($styleCssVersion !== '' ? $styleCssVersion : '1') ?>">
    <script type="application/ld+json"><?= json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
</head>
<body class="site-body">
    <div class="site-shell">
        <header class="site-header">
            <div class="container site-header__inner">
                <a class="site-brand" href="<?= e(url()) ?>">
                    <span class="site-brand__mark">
                        <img src="<?= e($brandLogo) ?>" alt="<?= e($siteName) ?>">
                    </span>
                    <span class="site-brand__copy">
                        <strong><?= e($siteName) ?></strong>
                        <small><?= e($tagline) ?></small>
                    </span>
                </a>

                <button class="nav-toggle" type="button" data-nav-toggle aria-label="Mở menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>

                <nav class="site-nav" data-nav-menu>
                    <div class="site-nav__indicator" aria-hidden="true"></div>
                    <a class="<?= is_current('') ? 'active' : '' ?>" href="<?= e(url()) ?>">Trang chủ</a>
                    <a class="<?= is_current('menu') ? 'active' : '' ?>" href="<?= e(url('menu')) ?>">Thực đơn</a>
                    <a class="<?= is_current('contact') ? 'active' : '' ?>" href="<?= e(url('contact')) ?>">Liên hệ</a>

                    <div class="site-nav__user-group">
                        <?php if ($isCustomerLoggedIn): ?>
                            <a class="site-nav__account <?= is_current('account') ? 'active' : '' ?>" href="<?= e(url('account')) ?>">
                                <span class="site-nav__account-label">Xin chào, <?= e((string) ($_SESSION['customer_name'] ?? 'Bạn')) ?></span>
                            </a>
                            <form method="post" action="<?= e(url('customer/logout')) ?>" style="display:inline;">
                                <?= csrf_field() ?>
                                <button type="submit" class="site-nav__logout-btn">Đăng xuất</button>
                            </form>
                        <?php elseif ($isAdminLoggedIn): ?>
                            <a class="site-nav__account <?= is_current('admin') || is_current('admin/dashboard') ? 'active' : '' ?>" href="<?= e(url('admin/dashboard')) ?>">
                                <span class="site-nav__account-label">Admin: <?= e((string) ($_SESSION['admin_name'] ?? 'Quản trị viên')) ?></span>
                            </a>
                            <form method="post" action="<?= e(url('admin/logout')) ?>" style="display:inline;">
                                <?= csrf_field() ?>
                                <button type="submit" class="site-nav__logout-btn">Đăng xuất</button>
                            </form>
                        <?php else: ?>
                            <a class="site-nav__account <?= is_current('login') || is_current('customer/login') || is_current('register') || is_current('customer/register') ? 'active' : '' ?>" href="<?= e(url('customer/login')) ?>">
                                <span class="site-nav__account-label">Đăng nhập</span>
                            </a>
                        <?php endif; ?>
                    </div>

                    <a class="site-nav__cart <?= is_current('cart') ? 'active' : '' ?>" href="<?= e(url('cart')) ?>" aria-label="Giỏ hàng">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                        <?php $cartCount = array_sum($_SESSION['cart'] ?? []); ?>
                        <?php if ($cartCount > 0): ?>
                            <span class="site-nav__cart-badge"><?= $cartCount ?></span>
                        <?php endif; ?>
                    </a>
                </nav>
            </div>
        </header>

        <main class="site-main">
            <div class="container flash-wrap">
                <?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>
            </div>
            <?= $content ?>
        </main>

        <footer class="site-footer">
            <div class="footer-ribbon">
                <div class="container footer-ribbon__grid">
                    <article>
                        <span class="footer-ribbon__icon">01</span>
                        <div>
                            <strong>Tên quán</strong>
                            <p><?= e($siteName) ?></p>
                        </div>
                    </article>
                    <article>
                        <span class="footer-ribbon__icon">02</span>
                        <div>
                            <strong>Địa chỉ</strong>
                            <p><?= e($address) ?></p>
                        </div>
                    </article>
                    <article>
                        <span class="footer-ribbon__icon">03</span>
                        <div>
                            <strong>Hotline</strong>
                            <p><?= e($hotline) ?></p>
                        </div>
                    </article>
                    <article>
                        <span class="footer-ribbon__icon">04</span>
                        <div>
                            <strong>Giờ phục vụ</strong>
                            <p><?= e($openingHours) ?></p>
                        </div>
                    </article>
                </div>
            </div>

            <div class="container footer-grid">
                <div class="footer-brand">
                    <span class="site-brand__mark footer-mark">
                        <img src="<?= e($brandLogo) ?>" alt="<?= e($siteName) ?>">
                    </span>
                    <h3><?= e($siteName) ?></h3>
                    <p><?= e($tagline) ?></p>
                </div>
                <div>
                    <h4>Thông tin</h4>
                    <a href="<?= e(url()) ?>">Trang chủ</a>
                    <a href="<?= e(url('menu')) ?>">Thực đơn</a>
                    <a href="<?= e(url('contact')) ?>">Liên hệ</a>
                </div>
                <div>
                    <h4>Liên hệ</h4>
                    <p><?= e($address) ?></p>
                    <a href="tel:<?= e($hotline) ?>"><?= e($hotline) ?></a>
                    <p><?= e($openingHours) ?></p>
                </div>
                <div>
                    <h4>Đặt món</h4>
                    <a href="https://food.grab.com/vn/vi/restaurant/b%C3%A1nh-m%C3%AC-s%E1%BA%A1ch-ho%C3%A0ng-gia-royalbread-delivery/5-C4E2JLBTLA6DCE?" target="_blank" rel="noopener noreferrer">GrabFood</a>
                    <a href="<?= e($shopeefoodUrl) ?>" target="_blank" rel="noopener noreferrer">ShopeeFood</a>
                    <a href="<?= e($facebookUrl) ?>" target="_blank" rel="noopener noreferrer">Facebook</a>
                    <a href="<?= e($zaloUrl) ?>" target="_blank" rel="noopener noreferrer">Zalo</a>
                </div>
            </div>

            <div class="footer-bottom">
                <div class="container">
                    <p>&copy; <?= date('Y') ?> <?= e($siteName) ?>. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </div>

    <script>
        window.RoyalBreadConfig = <?= json_encode([
            'baseUrl' => rtrim(url(), '/') . '/',
            'csrfToken' => Session::csrfToken(),
            'searchUrl' => url('api/search'),
            'chatbotUrl' => url('api/assistant'),
            'deliveryDistanceUrl' => url('api/delivery-distance'),
            'addressSuggestionsUrl' => url('api/address-suggestions'),
            'reverseGeocodeUrl' => url('api/reverse-geocode'),
            'cartAddUrl' => url('cart/add'),
            'buyNowUrl' => url('cart/buy-now'),
            'uiSparkLottieSrc' => $uiSparkLottieSrc,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    </script>
    <script src="<?= e(asset('assets/js/app.js')) ?>?v=<?= e($appJsVersion !== '' ? $appJsVersion : '1') ?>"></script>
</body>
</html>
