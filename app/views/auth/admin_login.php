<?php
$siteName = setting($settings, 'site_name', 'Bánh mì chảo Hoàng Gia - RoyalBread');
$pageTitle = 'Đăng nhập admin | ' . $siteName;
$brandLogo = asset('assets/images/royalbread-logo.png');
?>

<style>
/* Custom overrides for secure admin login area */
body.auth-layout--vintage {
    background: #0f172a !important;
    background-image: radial-gradient(circle at 30% 20%, #1e293b 0%, #0f172a 100%) !important;
    color: #f8fafc !important;
}

.auth-box {
    background: #1e293b !important;
    background-image: radial-gradient(circle at center, rgba(212, 148, 58, 0.05) 0%, transparent 80%) !important;
    border: 1px solid rgba(212, 148, 58, 0.25) !important;
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.55) !important;
    color: #f1f5f9 !important;
}

.auth-box__logo img {
    filter: drop-shadow(0 4px 10px rgba(212, 148, 58, 0.3));
}

.auth-box__logo strong {
    color: #f59e0b !important;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.auth-box__header h2 {
    color: #ffffff !important;
    font-weight: 700;
    letter-spacing: 0.01em;
}

.auth-box__header p {
    color: #94a3b8 !important;
}

.auth-box__decor {
    background: #f59e0b !important;
}

.auth-box__decor::before {
    background: #1e293b !important;
    border-color: #f59e0b !important;
}

/* Security warning panel */
.auth-admin-warning {
    background: rgba(239, 68, 68, 0.12);
    border: 1px solid rgba(239, 68, 68, 0.35);
    border-radius: 10px;
    padding: 12px 16px;
    margin-bottom: 24px;
    color: #fca5a5;
    font-size: 0.85rem;
    display: flex;
    align-items: flex-start;
    gap: 10px;
    text-align: left;
    line-height: 1.4;
}

.auth-admin-warning svg {
    flex-shrink: 0;
    color: #ef4444;
    margin-top: 2px;
}

.auth-form__group input {
    background: #0f172a !important;
    border: 1px solid #334155 !important;
    color: #ffffff !important;
}

.auth-form__group input::placeholder {
    color: #475569;
}

.auth-form__group input:focus {
    border-color: #f59e0b !important;
    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2) !important;
}

.auth-form__group svg, .auth-form__toggle-pw {
    color: #64748b !important;
}

.auth-form__toggle-pw:hover {
    color: #f59e0b !important;
}

.auth-form__options {
    color: #94a3b8 !important;
}

.auth-form__checkbox {
    color: #94a3b8 !important;
}

.auth-form__checkbox input[type="checkbox"] {
    accent-color: #f59e0b;
}

.auth-form__forgot {
    color: #f59e0b !important;
}

.auth-form__forgot:hover {
    color: #d97706 !important;
}

.auth-form__submit {
    background: #f59e0b !important;
    color: #0f172a !important;
    font-weight: 800;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}

.auth-form__submit:hover {
    background: #d97706 !important;
    transform: translateY(-2px);
    box-shadow: 0 8px 22px rgba(245, 158, 11, 0.35) !important;
}

.auth-box__footer {
    border-top: 1px solid #334155 !important;
    padding-top: 16px;
    margin-top: 20px;
    color: #94a3b8 !important;
}

.auth-box__footer a {
    color: #f59e0b !important;
    font-weight: 700;
}
</style>

<div class="auth-wrapper">
    <div class="auth-box">
        <div class="auth-box__logo">
            <img src="<?= e($brandLogo) ?>" alt="<?= e($siteName) ?>">
            <strong><?= e($siteName) ?></strong>
        </div>

        <div class="auth-box__header">
            <h2>Hệ thống quản trị</h2>
            <p>Khu đăng nhập bảo mật cho quản lý RoyalBread</p>
            <div class="auth-box__decor"></div>
        </div>

        <div class="auth-admin-warning">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/>
                <line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
            <div>
                <strong>Chú ý:</strong> Đây là khu vực dành riêng cho người vận hành hệ thống. Bất kỳ hành vi truy cập trái phép nào cũng đều bị ghi lại lịch sử.
            </div>
        </div>

        <form method="post" action="<?= e(url('admin/login')) ?>" class="auth-form">
            <?= csrf_field() ?>
            <div class="auth-form__group">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <input type="text" name="username" placeholder="Tài khoản admin" required>
            </div>

            <div class="auth-form__group">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <input type="password" name="password" id="authPassword" placeholder="Mật khẩu admin" required>
                <button type="button" class="auth-form__toggle-pw" onclick="togglePassword()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>

            <div class="auth-form__options">
                <label class="auth-form__checkbox">
                    <input type="checkbox" name="remember">
                    <span>Ghi nhớ phiên</span>
                </label>
                <a href="#" class="auth-form__forgot">Quên mật khẩu?</a>
            </div>

            <button type="submit" class="auth-form__submit">Đăng nhập Quản trị</button>
        </form>

        <div class="auth-box__footer">
            <div style="margin-bottom: 12px;">
                Bạn là khách hàng? <a href="<?= e(url('customer/login')) ?>">Đăng nhập mua hàng</a>
            </div>
            <div style="border-top: 1px solid #334155; padding-top: 12px; margin-top: 12px;">
                <a href="<?= e(url('')) ?>" style="color: #94a3b8; text-decoration: none; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 5px;">
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
