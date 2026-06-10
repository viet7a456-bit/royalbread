<?php
$siteName = setting($settings ?? [], 'site_name', 'Bánh mì chảo Hoàng Gia - RoyalBread');
$tagline = setting($settings ?? [], 'tagline', 'Bánh mì chảo nóng giòn, topping đầy đặn, lên món nhanh cho khách tại Hải Dương.');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? $siteName . ' | Đăng nhập') ?></title>
    <meta name="description" content="<?= e($tagline) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Mulish:wght@400;500;600;700;800&family=Allura&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>">
</head>
<body class="auth-layout auth-layout--vintage">
    <div class="auth-layout__flash">
        <?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>
    </div>
    <?= $content ?>
</body>
</html>
