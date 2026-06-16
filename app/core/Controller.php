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
        try {
            require $viewFile;
            $content = ob_get_clean();
        } catch (Throwable $error) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }

            throw new RuntimeException(
                sprintf('Loi render view "%s": %s', $view, $error->getMessage()),
                0,
                $error
            );
        }

        try {
            require $layoutFile;
        } catch (Throwable $error) {
            throw new RuntimeException(
                sprintf('Loi render layout "%s" cho view "%s": %s', $layout, $view, $error->getMessage()),
                0,
                $error
            );
        }
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
