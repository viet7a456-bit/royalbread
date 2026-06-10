<?php
$siteName = setting($settings, 'site_name', 'Bánh mì chảo Hoàng Gia - RoyalBread');
$pageTitle = 'Đăng ký tài khoản khách hàng | ' . $siteName;
$brandLogo = asset('assets/images/royalbread-logo.png');
?>

<div class="auth-wrapper">
    <div class="auth-box">
        <div class="auth-box__logo">
            <img src="<?= e($brandLogo) ?>" alt="<?= e($siteName) ?>">
            <strong><?= e($siteName) ?></strong>
        </div>

        <div class="auth-box__header">
            <h2>Đăng ký khách hàng</h2>
            <p>Tạo tài khoản khách hàng mới để đặt món nhanh hơn.</p>
            <div class="auth-box__decor"></div>
        </div>

        <form method="post" action="<?= e(url('customer/register')) ?>" class="auth-form">
            <?= csrf_field() ?>
            <div class="auth-form__group">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <input type="text" name="full_name" placeholder="Họ và tên" required>
            </div>

            <div class="auth-form__group">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <input type="text" name="username" placeholder="Tên đăng nhập" required>
            </div>

            <div class="auth-form__group">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                <input type="email" name="email" placeholder="Email (không bắt buộc)">
            </div>

            <div class="auth-form__group">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                <input type="tel" name="phone" placeholder="Số điện thoại (không bắt buộc)">
            </div>

            <div class="auth-form__group">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <input type="password" name="password" id="authPassword" placeholder="Mật khẩu" required>
                <button type="button" class="auth-form__toggle-pw" onclick="togglePassword('authPassword')">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>

            <div class="auth-form__group">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <input type="password" name="password_confirm" id="authPasswordConfirm" placeholder="Xác nhận mật khẩu" required>
                <button type="button" class="auth-form__toggle-pw" onclick="togglePassword('authPasswordConfirm')">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>

            <button type="submit" class="auth-form__submit" style="margin-top: 10px;">Đăng ký</button>
        </form>



        <div class="auth-box__footer">
            Đã có tài khoản? <a href="<?= e(url('customer/login')) ?>">Đăng nhập khách hàng</a>
        </div>
    </div>
</div>

<script>
function togglePassword(id) {
    var pwInput = document.getElementById(id);
    if (pwInput.type === 'password') {
        pwInput.type = 'text';
    } else {
        pwInput.type = 'password';
    }
}
</script>
