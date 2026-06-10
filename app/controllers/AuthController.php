<?php

declare(strict_types=1);

class AuthController extends Controller
{
    public function customerLoginForm(): void
    {
        if (!empty($_SESSION['customer_id'])) {
            $this->redirectTo('account');
        }

        $settingModel = new Setting();
        $this->render('auth/customer_login', [
            'settings' => $settingModel->all(),
        ], 'auth');
    }

    public function adminLoginForm(): void
    {
        if (!empty($_SESSION['admin_id'])) {
            $this->redirectTo('admin/dashboard');
        }

        $settingModel = new Setting();
        $this->render('auth/admin_login', [
            'settings' => $settingModel->all(),
        ], 'auth');
    }

    public function customerLogin(): void
    {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            Session::flash('error', 'Vui lòng nhập tài khoản và mật khẩu.');
            $this->redirectTo('customer/login');
        }

        $customerModel = new Customer();
        $customer = $customerModel->findByUsername($username);

        if ($customer !== null && password_verify($password, $customer['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['customer_id'] = $customer['id'];
            $_SESSION['customer_name'] = $customer['full_name'];
            Session::flash('success', 'Đăng nhập khách hàng thành công.');
            $this->redirectTo('account');
        }

        Session::flash('error', 'Tài khoản khách hàng hoặc mật khẩu không đúng.');
        $this->redirectTo('customer/login');
    }

    public function adminLogin(): void
    {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            Session::flash('error', 'Vui lòng nhập tài khoản và mật khẩu admin.');
            $this->redirectTo('admin/login');
        }

        $adminModel = new Admin();
        $admin = $adminModel->findByUsername($username);

        if ($admin !== null && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['full_name'];
            Session::flash('success', 'Đăng nhập quản trị thành công.');
            $this->redirectTo('admin/dashboard');
        }

        Session::flash('error', 'Tài khoản admin hoặc mật khẩu không đúng.');
        $this->redirectTo('admin/login');
    }

    public function customerLogout(): void
    {
        unset($_SESSION['customer_id'], $_SESSION['customer_name']);
        Session::clearOld();
        Session::rotateCsrf();
        session_regenerate_id(true);
        Session::flash('success', 'Bạn đã đăng xuất tài khoản khách hàng.');
        $this->redirectTo('customer/login');
    }

    public function adminLogout(): void
    {
        unset($_SESSION['admin_id'], $_SESSION['admin_name']);
        Session::clearOld();
        Session::rotateCsrf();
        session_regenerate_id(true);
        Session::flash('success', 'Bạn đã đăng xuất quản trị viên.');
        $this->redirectTo('admin/login');
    }


    public function registerForm(): void
    {
        if (!empty($_SESSION['customer_id'])) {
            $this->redirectTo('account');
        }

        $settingModel = new Setting();
        $this->render('auth/register', [
            'settings' => $settingModel->all(),
        ], 'auth');
    }

    public function register(): void
    {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $passwordConfirm = trim($_POST['password_confirm'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');

        if ($username === '' || $password === '' || $fullName === '') {
            Session::flash('error', 'Vui lòng điền đầy đủ thông tin bắt buộc.');
            $this->redirectTo('customer/register');
        }

        if ($password !== $passwordConfirm) {
            Session::flash('error', 'Mật khẩu xác nhận không khớp.');
            $this->redirectTo('customer/register');
        }

        $customerModel = new Customer();
        if ($customerModel->findByUsername($username) !== null) {
            Session::flash('error', 'Tên đăng nhập này đã được sử dụng.');
            $this->redirectTo('customer/register');
        }

        $adminModel = new Admin();
        if ($adminModel->findByUsername($username) !== null) {
            Session::flash('error', 'Tên đăng nhập này không hợp lệ.');
            $this->redirectTo('customer/register');
        }

        $customerModel->create([
            'username' => $username,
            'password' => $password,
            'full_name' => $fullName,
            'email' => $_POST['email'] ?? '',
            'phone' => $_POST['phone'] ?? '',
        ]);

        Session::flash('success', 'Đăng ký thành công! Vui lòng đăng nhập khách hàng.');
        $this->redirectTo('customer/login');
    }
}
