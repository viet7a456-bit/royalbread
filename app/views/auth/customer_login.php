<?php
$siteName = setting($settings, 'site_name', 'Bánh mì chảo Hoàng Gia - RoyalBread');
$pageTitle = 'Đăng nhập khách hàng | ' . $siteName;
$brandLogo = asset('assets/images/royalbread-logo.png');
?>

<div class="auth-wrapper">
    <div class="auth-box">
        <div class="auth-box__logo">
            <img src="<?= e($brandLogo) ?>" alt="<?= e($siteName) ?>">
            <strong><?= e($siteName) ?></strong>
        </div>

        <div class="auth-box__header">
            <h2>Đăng nhập khách hàng</h2>
            <p>Đăng nhập để xem khu khách hàng và tiếp tục đặt món.</p>
            <div class="auth-box__decor"></div>
        </div>

        <form method="post" action="<?= e(url('customer/login')) ?>" class="auth-form">
            <?= csrf_field() ?>
            <div class="auth-form__group">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <input type="text" name="username" placeholder="Tên đăng nhập khách hàng" required>
            </div>

            <div class="auth-form__group">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <input type="password" name="password" id="authPassword" placeholder="Mật khẩu" required>
                <button type="button" class="auth-form__toggle-pw" onclick="togglePassword()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>

            <div class="auth-form__options">
                <label class="auth-form__checkbox">
                    <input type="checkbox" name="remember">
                    <span>Ghi nhớ đăng nhập</span>
                </label>
                <a href="#" class="auth-form__forgot">Quên mật khẩu?</a>
            </div>

            <button type="submit" class="auth-form__submit">Đăng nhập</button>
        </form>

        <div class="auth-box__footer">
            <div style="margin-bottom: 12px;">
                Chưa có tài khoản? <a href="<?= e(url('customer/register')) ?>" style="color: #d4943a; font-weight: 700; text-decoration: none;">Đăng ký ngay</a>
            </div>
            <div style="border-top: 1px solid rgba(176, 128, 72, 0.15); padding-top: 12px; margin-top: 12px;">
                <a href="<?= e(url('')) ?>" style="color: #8a6c4e; text-decoration: none; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 5px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                    Quay lại trang chủ
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    var pwInput = document.getElementById('authPassword');
    if (pwInput.type === 'password') {
        pwInput.type = 'text';
    } else {
        pwInput.type = 'password';
    }
}
</script>
