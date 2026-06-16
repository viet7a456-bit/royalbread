<?php

declare(strict_types=1);

class App
{
    private array $routes = [
        'GET' => [
            '' => [HomeController::class, 'index'],
            'menu' => [MenuController::class, 'index'],
            'contact' => [ContactController::class, 'index'],
            'account' => [CustomerController::class, 'index'],
            'robots.txt' => [SeoController::class, 'robots'],
            'sitemap.xml' => [SeoController::class, 'sitemap'],
            'login' => [AuthController::class, 'customerLoginForm'],
            'customer/login' => [AuthController::class, 'customerLoginForm'],
            'register' => [AuthController::class, 'registerForm'],
            'customer/register' => [AuthController::class, 'registerForm'],
            'cart' => [CartController::class, 'index'],
            'cart/payment' => [CartController::class, 'payment'],
            'views/admin' => [AuthController::class, 'adminLoginForm'],
            'views/admin/login.php' => [AuthController::class, 'adminLoginForm'],
            'views/admin/dashboard.php' => [AdminController::class, 'dashboard'],
            'views/admin/menu.php' => [AdminController::class, 'menu'],
            'views/admin/messages.php' => [AdminController::class, 'messages'],
            'views/admin/settings.php' => [AdminController::class, 'settings'],
            'views/admin/customers.php' => [AdminController::class, 'customers'],
            'admin' => [AdminController::class, 'dashboard'],
            'admin/login' => [AuthController::class, 'adminLoginForm'],
            'admin/dashboard' => [AdminController::class, 'dashboard'],
            'admin/orders' => [AdminController::class, 'orders'],
            'admin/orders/export' => [AdminController::class, 'exportOrders'],
            'admin/revenue' => [AdminController::class, 'revenue'],
            'admin/revenue/export' => [AdminController::class, 'exportRevenue'],
            'admin/menu' => [AdminController::class, 'menu'],
            'admin/customers' => [AdminController::class, 'customers'],
            'admin/customers/export' => [AdminController::class, 'exportCustomers'],
            'admin/messages' => [AdminController::class, 'messages'],
            'admin/reviews' => [AdminController::class, 'reviews'],
            'admin/settings' => [AdminController::class, 'settings'],
            'api/search' => [ApiController::class, 'searchMenu'],
            'api/delivery-distance' => [ApiController::class, 'deliveryDistance'],
            'api/address-suggestions' => [ApiController::class, 'addressSuggestions'],
            'api/reverse-geocode' => [ApiController::class, 'reverseGeocode'],
        ],
        'POST' => [
            'contact' => [ContactController::class, 'store'],
            'login' => [AuthController::class, 'customerLogin'],
            'customer/login' => [AuthController::class, 'customerLogin'],
            'register' => [AuthController::class, 'register'],
            'customer/register' => [AuthController::class, 'register'],
            'logout' => [AuthController::class, 'customerLogout'],
            'customer/logout' => [AuthController::class, 'customerLogout'],
            'customer/favorites/toggle' => [CustomerController::class, 'toggleFavorite'],
            'customer/reviews/store' => [CustomerController::class, 'storeReview'],
            'customer/support/send' => [CustomerController::class, 'sendLiveChatMessage'],
            'cart/add' => [CartController::class, 'add'],
            'cart/buy-now' => [CartController::class, 'buyNow'],
            'cart/update' => [CartController::class, 'update'],
            'cart/remove' => [CartController::class, 'remove'],
            'cart/checkout' => [CartController::class, 'checkout'],
            'views/admin/login.php' => [AuthController::class, 'adminLogin'],
            'admin/login' => [AuthController::class, 'adminLogin'],
            'admin/logout' => [AuthController::class, 'adminLogout'],
            'admin/orders/update-status' => [AdminController::class, 'updateOrderStatus'],
            'admin/orders/update-payment-status' => [AdminController::class, 'updatePaymentStatus'],
            'admin/reviews/update-status' => [AdminController::class, 'updateReviewStatus'],
            'admin/reviews/update-statuses' => [AdminController::class, 'updateReviewsStatus'],
            'admin/messages/send' => [AdminController::class, 'sendLiveChatMessage'],
            'admin/messages/thread-status' => [AdminController::class, 'updateLiveChatThreadStatus'],
            'admin/menu/store' => [AdminController::class, 'storeMenuItem'],
            'admin/menu/update' => [AdminController::class, 'updateMenuItem'],
            'admin/menu/delete' => [AdminController::class, 'deleteMenuItem'],
            'admin/settings' => [AdminController::class, 'updateSettings'],
            'admin/promotions/store' => [AdminController::class, 'storePromotion'],
            'admin/promotions/delete' => [AdminController::class, 'deletePromotion'],
            'cart/payment/confirm' => [CartController::class, 'confirmPayment'],
            'api/assistant' => [ApiController::class, 'chatbot'],
        ],
    ];

    public function run(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path = $this->resolvePath();

        $handler = $this->routes[$method][$path] ?? null;

        if ($handler === null) {
            http_response_code(404);
            echo '404 - Trang không tồn tại.';
            return;
        }

        // CSRF protection for POST routes (skip API endpoints)
        if ($method === 'POST' && !str_starts_with($path, 'api/')) {
            $token = $_POST['_csrf_token'] ?? '';
            if (!Session::verifyCsrf($token)) {
                Session::rotateCsrf();
                if (is_ajax()) {
                    header('Content-Type: application/json; charset=UTF-8');
                    http_response_code(419);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Phiên làm việc đã hết hạn. Vui lòng thử lại.',
                        'csrf_token' => Session::csrfToken(),
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    exit;
                }
                Session::flash('error', 'Phiên làm việc đã hết hạn. Vui lòng thử lại.');
                $referer = $_SERVER['HTTP_REFERER'] ?? url();
                header('Location: ' . $referer);
                exit;
            }
            Session::rotateCsrf();
        }

        [$controllerName, $action] = $handler;
        $controller = new $controllerName();
        $controller->$action();
    }

    private function resolvePath(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base = app_url_base();

        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }

        return trim($uri, '/');
    }
}
