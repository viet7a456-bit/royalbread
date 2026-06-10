<?php

declare(strict_types=1);

class Controller
{
    protected function render(string $view, array $data = [], string $layout = 'main'): void
    {
        $viewFile = ROOT_PATH . '/app/views/' . $view . '.php';
        $layoutFile = ROOT_PATH . '/app/views/layouts/' . $layout . '.php';

        if (!file_exists($viewFile) || !file_exists($layoutFile)) {
            throw new RuntimeException('Không tìm thấy view hoặc layout.');
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        require $layoutFile;
    }

    protected function redirectTo(string $path): void
    {
        header('Location: ' . url($path));
        exit;
    }

    protected function requireAdmin(): void
    {
        if (empty($_SESSION['admin_id'])) {
            Session::flash('error', 'Vui lòng đăng nhập để truy cập trang quản trị.');
            $this->redirectTo('admin/login');
        }
    }
}
