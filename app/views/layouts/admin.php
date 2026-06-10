<?php
$siteName = setting($settings ?? [], 'site_name', 'Bánh mì chảo Hoàng Gia - RoyalBread');
$hotline = setting($settings ?? [], 'hotline', '0879866636');
$address = setting($settings ?? [], 'address', '28 Dã Tượng - TP Hải Dương (Sau nhà thi đấu Hải Dương)');
$brandLogo = asset('assets/images/royalbread-logo.png');
$adminCssVersion = (string) @filemtime(ROOT_PATH . '/assets/css/admin.css');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Quản trị RoyalBread') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Mulish:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.9.14/dist/dotlottie-wc.js" type="module"></script>
    <link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>?v=<?= e($adminCssVersion !== '' ? $adminCssVersion : '1') ?>">
</head>
<body class="admin-body">
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <a class="admin-brand" href="<?= e(url('admin/dashboard')) ?>">
                <span class="admin-brand__mark">
                    <img src="<?= e($brandLogo) ?>" alt="<?= e($siteName) ?>">
                </span>
                <div>
                    <strong><?= e($siteName) ?></strong>
                    <small>Trang quản trị bán hàng và chăm sóc khách</small>
                </div>
            </a>

            <nav class="admin-nav">
                <a class="<?= is_current('admin') || is_current('admin/dashboard') ? 'active' : '' ?>" href="<?= e(url('admin/dashboard')) ?>">Tổng quan</a>
                <a class="<?= is_current('admin/orders') ? 'active' : '' ?>" href="<?= e(url('admin/orders')) ?>">Đơn hàng</a>
                <a class="<?= is_current('admin/revenue') ? 'active' : '' ?>" href="<?= e(url('admin/revenue')) ?>">Doanh thu</a>
                <a class="<?= is_current('admin/menu') ? 'active' : '' ?>" href="<?= e(url('admin/menu')) ?>">Sản phẩm</a>
                <a class="<?= is_current('admin/customers') ? 'active' : '' ?>" href="<?= e(url('admin/customers')) ?>">Khách hàng</a>
                <a class="<?= is_current('admin/messages') ? 'active' : '' ?>" href="<?= e(url('admin/messages')) ?>">Tin nhắn</a>
                <a class="<?= is_current('admin/reviews') ? 'active' : '' ?>" href="<?= e(url('admin/reviews')) ?>">Đánh giá</a>
                <a class="<?= is_current('admin/settings') ? 'active' : '' ?>" href="<?= e(url('admin/settings')) ?>">Cài đặt</a>
                <a href="<?= e(url()) ?>" target="_blank" rel="noopener noreferrer">Xem website</a>
            </nav>

            <div class="admin-sidebar__card">
                <span class="admin-sidebar__label">Thông tin quán</span>
                <p><?= e($address) ?></p>
                <a href="tel:<?= e($hotline) ?>"><?= e($hotline) ?></a>
            </div>

            <form method="post" action="<?= e(url('admin/logout')) ?>">
                <?= csrf_field() ?>
                <button class="logout-btn" type="submit">Đăng xuất</button>
            </form>
        </aside>

        <main class="admin-main">
            <header class="admin-topbar">
                <div>
                    <p class="admin-kicker">Quản trị vận hành</p>
                    <h1><?= e($_SESSION['admin_name'] ?? 'Quản trị viên') ?></h1>
                </div>
                <div class="admin-topbar__meta">
                    <span><?= e($siteName) ?></span>
                    <span><?= date('d/m/Y') ?></span>
                </div>
            </header>

            <div class="admin-flash-wrap">
                <?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>
            </div>

            <?= $content ?>
        </main>
    </div>
</body>
</html>
