<?php

declare(strict_types=1);

class AdminController extends Controller
{
    private const FINAL_ORDER_STATUSES = ['completed', 'cancelled'];
    private const MENU_ITEMS_PER_PAGE = 10;
    private const REVIEW_ITEMS_PER_PAGE = 10;

    private function logAdminFeatureError(Throwable $error): void
    {
        $logDir = ROOT_PATH . '/tmp/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $entry = sprintf(
            "[%s] Admin feature error: %s\n%s\n",
            date('Y-m-d H:i:s'),
            $error->getMessage(),
            $error->getTraceAsString()
        );
        @file_put_contents($logDir . '/admin_features.log', $entry, FILE_APPEND | LOCK_EX);
        error_log('RoyalBread admin feature error: ' . $error->getMessage());
    }

    private function siteSettings(): array
    {
        try {
            return (new Setting())->all();
        } catch (Throwable $featureError) {
            $this->logAdminFeatureError($featureError);
            return [];
        }
    }

    private function requestedExportFormat(): string
    {
        $format = strtolower(trim((string) ($_GET['format'] ?? 'xlsx')));

        return in_array($format, ['csv', 'xlsx', 'pdf'], true) ? $format : 'xlsx';
    }

    private function requestedMenuPage(): int
    {
        return max(1, (int) ($_POST['page'] ?? $_GET['page'] ?? 1));
    }

    private function redirectToAdminMenuPage(int $page): void
    {
        $this->redirectTo('admin/menu?page=' . max(1, $page));
    }

    private function requestedReviewPage(): int
    {
        return max(1, (int) ($_GET['page'] ?? 1));
    }

    private function redirectToAdminReviewsPage(int $page, string $status = ''): void
    {
        $query = [
            'page' => max(1, $page),
        ];

        if ($status !== '') {
            $query['status'] = $status;
        }

        $this->redirectTo('admin/reviews?' . http_build_query($query));
    }

    private function filteredOrders(array $allOrders, string $searchQuery, string $searchDate): array
    {
        $filteredOrders = [];

        foreach ($allOrders as $order) {
            $match = true;

            if ($searchQuery !== '') {
                $haystacks = [
                    (string) ($order['customer_name'] ?? ''),
                    (string) ($order['phone'] ?? ''),
                    (string) ($order['customer_email'] ?? ''),
                    (string) ($order['id'] ?? ''),
                    (string) ($order['payment_reference'] ?? ''),
                    (string) ($order['order_items_text'] ?? ''),
                ];

                $match = false;
                foreach ($haystacks as $haystack) {
                    if (stripos($haystack, $searchQuery) !== false) {
                        $match = true;
                        break;
                    }
                }
            }

            if ($match && $searchDate !== '' && !str_starts_with((string) ($order['created_at'] ?? ''), $searchDate)) {
                $match = false;
            }

            if ($match) {
                $filteredOrders[] = $order;
            }
        }

        return $filteredOrders;
    }

    private function decorateOrdersWithItems(array $orders, Order $orderModel): array
    {
        if ($orders === []) {
            return [];
        }

        $itemsByOrderId = $orderModel->itemsForOrders(array_map(
            static fn(array $order): int => (int) ($order['id'] ?? 0),
            $orders
        ));

        foreach ($orders as &$order) {
            $orderId = (int) ($order['id'] ?? 0);
            $items = $itemsByOrderId[$orderId] ?? [];
            $order['items'] = $items;
            $order['items_count'] = count($items);
            $order['items_quantity_total'] = array_sum(array_map(
                static fn(array $item): int => (int) ($item['quantity'] ?? 0),
                $items
            ));
            $order['order_items_text'] = implode(' | ', array_map(
                static fn(array $item): string => trim((string) ($item['menu_item_name'] ?? '')),
                $items
            ));
        }
        unset($order);

        return $orders;
    }

    private function filteredCompletedOrders(array $allOrders, string $filterDate, string $filterMonth): array
    {
        $filteredOrders = [];

        foreach ($allOrders as $order) {
            if (($order['status'] ?? '') !== 'completed') {
                continue;
            }

            $orderDateStr = substr((string) $order['created_at'], 0, 10);
            $orderMonthStr = substr((string) $order['created_at'], 0, 7);

            $match = false;
            if ($filterDate !== '' && $orderDateStr === $filterDate) {
                $match = true;
            } elseif ($filterMonth !== '' && $orderMonthStr === $filterMonth) {
                $match = true;
            }

            if ($match) {
                $filteredOrders[] = $order;
            }
        }

        return $filteredOrders;
    }

    private function defaultHomeMedia(): array
    {
        $featuredItems = (new MenuItem())->featured(4);
        $signatureImage = $featuredItems[0]['image_url'] ?? asset('assets/images/home-hero-banner-3.png');
        $slideOne = asset('assets/images/storefront-bg.jpg');

        return [
            'home_banner_slide_1' => $slideOne,
            'home_banner_slide_2' => asset('assets/images/home-hero-banner-2.png'),
            'home_banner_slide_3' => asset('assets/images/home-hero-banner-3.png'),
            'home_signature_image' => $signatureImage,
            'contact_hero_image' => $slideOne,
        ];
    }


    private function spotlightCandidates(): array
    {
        return (new MenuItem())->allForAdmin();
    }

    private function defaultSpotlightIds(): array
    {
        return array_map(
            static fn(array $item): int => (int) $item['id'],
            array_slice($this->spotlightCandidates(), 0, 4)
        );
    }

    private function defaultCartRecommendationIds(): array
    {
        return array_map(
            static fn(array $item): int => (int) $item['id'],
            (new MenuItem())->featured(4)
        );
    }

    private function defaultHomeDrinkIds(): array
    {
        return array_map(
            static fn(array $item): int => (int) $item['id'],
            (new MenuItem())->availableByCategorySlugs(['tra-nhiet-doi', 'do-uong-truyen-thong', 'cafe'], [], 5)
        );
    }

    private function settingKeysByPrefix(array $settings, string $prefix, int $minimumCount = 0, bool $includeEmpty = true): array
    {
        $matchedKeys = [];

        foreach (array_keys($settings) as $key) {
            if (preg_match('/^' . preg_quote($prefix, '/') . '(\d+)$/', $key, $matches) === 1) {
                if (!$includeEmpty && trim((string) ($settings[$key] ?? '')) === '') {
                    continue;
                }

                $matchedKeys[(int) $matches[1]] = $key;
            }
        }

        ksort($matchedKeys);

        if ($matchedKeys === [] && $minimumCount > 0) {
            for ($index = 1; $index <= $minimumCount; $index++) {
                $matchedKeys[$index] = $prefix . $index;
            }
        }

        return array_values($matchedKeys);
    }

    private function defaultKeyValueMap(array $values, string $prefix): array
    {
        $mapped = [];

        foreach (array_values($values) as $index => $value) {
            $mapped[$prefix . ($index + 1)] = $value;
        }

        return $mapped;
    }

    private function submittedPostKeysByPrefix(string $prefix): array
    {
        $matchedKeys = [];

        foreach (array_keys($_POST) as $key) {
            if (preg_match('/^' . preg_quote($prefix, '/') . '(\d+)$/', (string) $key, $matches) === 1) {
                $matchedKeys[(int) $matches[1]] = (string) $key;
            }
        }

        ksort($matchedKeys);

        return array_values($matchedKeys);
    }

    private function submittedFileKeysByPrefix(string $prefix): array
    {
        $matchedKeys = [];

        foreach (array_keys($_FILES) as $key) {
            if (preg_match('/^' . preg_quote($prefix, '/') . '(\d+)_file$/', (string) $key, $matches) === 1) {
                $matchedKeys[(int) $matches[1]] = $prefix . $matches[1];
            }
        }

        ksort($matchedKeys);

        return array_values($matchedKeys);
    }

    private function menuSections(array $items): array
    {
        $categoryModel = new Category();
        $categories = $categoryModel->all();
        $itemsByCategoryId = [];

        foreach ($items as $item) {
            $itemsByCategoryId[(int) $item['category_id']][] = $item;
        }

        $sections = [];
        foreach ($categories as $category) {
            $categoryId = (int) $category['id'];
            if (!empty($itemsByCategoryId[$categoryId])) {
                $sections[] = [
                    'category' => $category,
                    'items' => $itemsByCategoryId[$categoryId],
                ];
            }
        }

        return $sections;
    }

    private function handleFileUpload(string $inputName, string $fallbackUrl = ''): string
    {
        if (!isset($_FILES[$inputName]) || $_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
            return trim($fallbackUrl);
        }

        $file = $_FILES[$inputName];
        $tmpName = $file['tmp_name'];

        if ($file['size'] > 5 * 1024 * 1024) {
            return trim($fallbackUrl);
        }

        $ext = strtolower(pathinfo(basename((string) $file['name']), PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'ico'];
        if (!in_array($ext, $allowedExts, true)) {
            return trim($fallbackUrl);
        }

        $allowedMimes = [
            'image/jpeg', 'image/png', 'image/gif',
            'image/webp', 'image/x-icon', 'image/vnd.microsoft.icon',
        ];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmpName);
        if ($mime === false || !in_array($mime, $allowedMimes, true)) {
            return trim($fallbackUrl);
        }

        if ($ext !== 'ico' && @getimagesize($tmpName) === false) {
            return trim($fallbackUrl);
        }

        $newName = bin2hex(random_bytes(16)) . '.' . $ext;
        $uploadPath = ROOT_PATH . '/assets/images/uploads/' . $newName;

        if (move_uploaded_file($tmpName, $uploadPath)) {
            return 'assets/images/uploads/' . $newName;
        }

        return trim($fallbackUrl);
    }

    public function dashboard(): void
    {
        $this->requireAdmin();

        $menuCount = 0;
        $messageCount = 0;
        $newMessageCount = 0;
        $orderCount = 0;
        $newOrdersCount = 0;
        $totalRevenue = 0;
        $customerCount = 0;
        $latestOrders = [];
        $latestMessages = [];
        $featuredItems = [];
        $bestSellingItems = [];
        $topPotentialCustomers = [];
        $activePromotionCount = 0;
        $pendingReviewCount = 0;
        $unreadLiveChatCount = 0;

        try {
            $menuItemModel = new MenuItem();
            $messageModel = new Message();
            $orderModel = new Order();
            $customerModel = new Customer();
            $allOrders = $orderModel->all();

            foreach ($allOrders as $order) {
                if (($order['status'] ?? '') === 'completed') {
                    $totalRevenue += (int) $order['total_amount'];
                }

                if (($order['status'] ?? '') === 'pending') {
                    $newOrdersCount++;
                }
            }

            $menuCount = $menuItemModel->countAll();
            $messageCount = $messageModel->countAll();
            $newMessageCount = $messageModel->countNew();
            $orderCount = count($allOrders);
            $customerCount = $customerModel->countAll();
            $latestOrders = array_slice($allOrders, 0, 5);
            $latestMessages = $messageModel->latest(5);
            $featuredItems = $menuItemModel->featured(6);
            $bestSellingItems = $menuItemModel->bestSelling(6);
            $topPotentialCustomers = $customerModel->topPotential(5);
        } catch (Throwable $featureError) {
            $this->logAdminFeatureError($featureError);
        }

        try {
            $activePromotionCount = count(array_filter(
                (new Promotion())->allForAdmin(),
                static fn(array $promotion): bool => (int) ($promotion['is_active'] ?? 0) === 1
            ));
        } catch (Throwable $featureError) {
            $this->logAdminFeatureError($featureError);
        }

        try {
            $pendingReviewCount = count((new ProductReview())->allForAdmin('pending'));
        } catch (Throwable $featureError) {
            $this->logAdminFeatureError($featureError);
        }

        try {
            $unreadLiveChatCount = (new LiveChat())->countUnreadForAdmin();
        } catch (Throwable $featureError) {
            $this->logAdminFeatureError($featureError);
        }

        $this->render('admin/dashboard', [
            'settings' => $this->siteSettings(),
            'menuCount' => $menuCount,
            'messageCount' => $messageCount,
            'newMessageCount' => $newMessageCount,
            'orderCount' => $orderCount,
            'newOrdersCount' => $newOrdersCount,
            'totalRevenue' => $totalRevenue,
            'customerCount' => $customerCount,
            'latestOrders' => $latestOrders,
            'latestMessages' => $latestMessages,
            'featuredItems' => $featuredItems,
            'bestSellingItems' => $bestSellingItems,
            'topPotentialCustomers' => $topPotentialCustomers,
            'activePromotionCount' => $activePromotionCount,
            'pendingReviewCount' => $pendingReviewCount,
            'unreadLiveChatCount' => $unreadLiveChatCount,
        ], 'admin');
    }

    public function menu(): void
    {
        $this->requireAdmin();

        $menuItemModel = new MenuItem();
        $totalItems = 0;
        $currentPage = $this->requestedMenuPage();
        $perPage = self::MENU_ITEMS_PER_PAGE;
        $totalPages = 1;
        $visibleFrom = 0;
        $visibleTo = 0;
        $menuSections = [];

        try {
            $totalItems = $menuItemModel->countAll();
            $totalPages = max(1, (int) ceil($totalItems / $perPage));
            $currentPage = min($currentPage, $totalPages);
            $offset = ($currentPage - 1) * $perPage;
            $items = $totalItems > 0 ? $menuItemModel->allForAdminPaginated($offset, $perPage) : [];
            $menuSections = $this->menuSections($items);
            $visibleFrom = $totalItems > 0 ? $offset + 1 : 0;
            $visibleTo = $totalItems > 0 ? min($offset + count($items), $totalItems) : 0;
        } catch (Throwable $featureError) {
            $this->logAdminFeatureError($featureError);
        }

        $this->render('admin/menu', [
            'settings' => $this->siteSettings(),
            'categories' => (new Category())->all(),
            'menuSections' => $menuSections,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'perPage' => $perPage,
            'visibleFrom' => $visibleFrom,
            'visibleTo' => $visibleTo,
        ], 'admin');
    }

    public function orders(): void
    {
        $this->requireAdmin();

        $allOrders = [];
        $searchQuery = trim($_GET['search'] ?? '');
        $searchDate = trim($_GET['date'] ?? '');

        try {
            $orderModel = new Order();
            $allOrders = $this->decorateOrdersWithItems($orderModel->all(), $orderModel);
        } catch (Throwable $featureError) {
            $this->logAdminFeatureError($featureError);
        }

        $this->render('admin/orders', [
            'settings' => $this->siteSettings(),
            'orders' => $this->filteredOrders($allOrders, $searchQuery, $searchDate),
            'searchQuery' => $searchQuery,
            'searchDate' => $searchDate,
            'finalStatuses' => self::FINAL_ORDER_STATUSES,
        ], 'admin');
    }

    public function customers(): void
    {
        $this->requireAdmin();

        $searchQuery = trim($_GET['search'] ?? '');
        $customerModel = new Customer();

        $this->render('admin/customers', [
            'settings' => $this->siteSettings(),
            'customers' => $customerModel->allWithStats($searchQuery),
            'topPotentialCustomers' => array_slice($customerModel->topPotential(5), 0, 5),
            'searchQuery' => $searchQuery,
        ], 'admin');
    }

    public function updateOrderStatus(): void
    {
        $this->requireAdmin();

        $id = (int) ($_POST['id'] ?? 0);
        $status = trim((string) ($_POST['status'] ?? ''));

        if ($id <= 0 || !in_array($status, ['pending', 'processing', 'completed', 'cancelled'], true)) {
            Session::flash('error', 'Cập nhật trạng thái thất bại.');
            $this->redirectTo('admin/orders');
        }

        $orderModel = new Order();
        $order = $orderModel->findById($id);

        if ($order === null) {
            Session::flash('error', 'Không tìm thấy đơn hàng cần cập nhật.');
            $this->redirectTo('admin/orders');
        }

        if (in_array((string) ($order['status'] ?? ''), self::FINAL_ORDER_STATUSES, true)) {
            Session::flash('error', 'Đơn đã hoàn thành hoặc đã hủy thì không thể sửa lại nữa.');
            $this->redirectTo('admin/orders');
        }

        $orderModel->updateStatus($id, $status);

        if ($status === 'completed' && (string) ($order['payment_method'] ?? 'cod') === 'cod') {
            $orderModel->updatePaymentStatus($id, 'paid');
        }

        Session::flash('success', 'Đã cập nhật trạng thái đơn hàng.');
        $this->redirectTo('admin/orders');
    }

    public function updatePaymentStatus(): void
    {
        $this->requireAdmin();

        $id = (int) ($_POST['id'] ?? 0);
        $paymentStatus = trim((string) ($_POST['payment_status'] ?? ''));

        if ($id <= 0 || !in_array($paymentStatus, ['unpaid', 'pending_confirmation', 'paid', 'refunded'], true)) {
            Session::flash('error', 'Cập nhật trạng thái thanh toán thất bại.');
            $this->redirectTo('admin/orders');
        }

        $orderModel = new Order();
        if ($orderModel->findById($id) === null) {
            Session::flash('error', 'Không tìm thấy đơn hàng cần cập nhật.');
            $this->redirectTo('admin/orders');
        }

        $orderModel->updatePaymentStatus($id, $paymentStatus);
        Session::flash('success', 'Đã cập nhật trạng thái thanh toán.');
        $this->redirectTo('admin/orders');
    }

    public function messages(): void
    {
        $this->requireAdmin();

        $messageModel = new Message();
        $threads = [];
        $activeThread = null;
        $chatMessages = [];
        $selectedThreadId = 0;

        try {
            $liveChatModel = new LiveChat();
            $threads = $liveChatModel->threadsForAdmin();
            $selectedThreadId = (int) ($_GET['thread'] ?? ($threads[0]['id'] ?? 0));

            if ($selectedThreadId > 0) {
                $activeThread = $liveChatModel->findThreadById($selectedThreadId);
                if ($activeThread !== null) {
                    $liveChatModel->markReadForViewer($selectedThreadId, 'admin');
                    $chatMessages = $liveChatModel->messagesForThread($selectedThreadId);
                }
            }
        } catch (Throwable $featureError) {
            $this->logAdminFeatureError($featureError);
        }

        $this->render('admin/messages', [
            'settings' => $this->siteSettings(),
            'messages' => $messageModel->all(),
            'threads' => $threads,
            'activeThread' => $activeThread,
            'chatMessages' => $chatMessages,
        ], 'admin');
    }

    public function revenue(): void
    {
        $this->requireAdmin();

        $orderModel = new Order();
        $allOrders = $this->decorateOrdersWithItems($orderModel->all(), $orderModel);
        $filterDate = trim((string) ($_GET['date'] ?? ''));
        $filterMonth = trim((string) ($_GET['month'] ?? ''));

        if ($filterDate === '' && $filterMonth === '') {
            $filterMonth = date('Y-m');
        }

        $orders = $this->filteredCompletedOrders($allOrders, $filterDate, $filterMonth);
        $totalRevenue = array_sum(array_map(static fn(array $order): int => (int) ($order['total_amount'] ?? 0), $orders));

        $this->render('admin/revenue', [
            'settings' => $this->siteSettings(),
            'orders' => $orders,
            'filterDate' => $filterDate,
            'filterMonth' => $filterMonth,
            'totalRevenue' => $totalRevenue,
        ], 'admin');
    }

    public function sendLiveChatMessage(): void
    {
        $this->requireAdmin();

        $threadId = (int) ($_POST['thread_id'] ?? 0);
        $message = trim((string) ($_POST['message'] ?? ''));

        if ($threadId <= 0 || $message === '') {
            Session::flash('error', 'Nội dung phản hồi chưa hợp lệ.');
            $this->redirectTo('admin/messages');
        }

        $liveChatModel = new LiveChat();
        $thread = $liveChatModel->findThreadById($threadId);
        if ($thread === null) {
            Session::flash('error', 'Không tìm thấy cuộc trò chuyện cần phản hồi.');
            $this->redirectTo('admin/messages');
        }

        $liveChatModel->addMessage(
            $threadId,
            'admin',
            (int) ($_SESSION['admin_id'] ?? 0) > 0 ? (int) $_SESSION['admin_id'] : null,
            trim((string) ($_SESSION['admin_name'] ?? 'Admin')),
            $message
        );

        Session::flash('success', 'Đã gửi phản hồi cho khách hàng.');
        $this->redirectTo('admin/messages?thread=' . $threadId);
    }

    public function updateLiveChatThreadStatus(): void
    {
        $this->requireAdmin();

        $threadId = (int) ($_POST['thread_id'] ?? 0);
        $action = trim((string) ($_POST['action'] ?? ''));

        if ($threadId <= 0 || !in_array($action, ['close', 'reopen'], true)) {
            Session::flash('error', 'Không thể cập nhật trạng thái chat.');
            $this->redirectTo('admin/messages');
        }

        $liveChatModel = new LiveChat();
        if ($liveChatModel->findThreadById($threadId) === null) {
            Session::flash('error', 'Không tìm thấy cuộc trò chuyện cần cập nhật.');
            $this->redirectTo('admin/messages');
        }

        if ($action === 'close') {
            $liveChatModel->closeThread($threadId);
            Session::flash('success', 'Đã đóng cuộc trò chuyện.');
        } else {
            $liveChatModel->reopenThread($threadId);
            Session::flash('success', 'Đã mở lại cuộc trò chuyện.');
        }

        $this->redirectTo('admin/messages?thread=' . $threadId);
    }

    public function reviews(): void
    {
        $this->requireAdmin();

        $status = trim((string) ($_GET['status'] ?? ''));
        $reviews = [];
        $currentPage = $this->requestedReviewPage();
        $totalReviews = 0;
        $totalPages = 1;
        $visibleFrom = 0;
        $visibleTo = 0;

        try {
            $reviewModel = new ProductReview();
            $totalReviews = $reviewModel->countAllForAdmin($status);
            $totalPages = max(1, (int) ceil($totalReviews / self::REVIEW_ITEMS_PER_PAGE));
            $currentPage = min($currentPage, $totalPages);
            $offset = ($currentPage - 1) * self::REVIEW_ITEMS_PER_PAGE;
            $reviews = $reviewModel->allForAdminPaginated($status, self::REVIEW_ITEMS_PER_PAGE, $offset);
            $visibleFrom = $totalReviews > 0 ? $offset + 1 : 0;
            $visibleTo = $totalReviews > 0 ? min($offset + count($reviews), $totalReviews) : 0;
        } catch (Throwable $featureError) {
            $this->logAdminFeatureError($featureError);
        }

        $this->render('admin/reviews', [
            'settings' => $this->siteSettings(),
            'reviews' => $reviews,
            'currentStatus' => $status,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'totalReviews' => $totalReviews,
            'visibleFrom' => $visibleFrom,
            'visibleTo' => $visibleTo,
        ], 'admin');
    }

    private function updateReviewStatusInternal(): void
    {
        $this->requireAdmin();

        $reviewId = (int) ($_POST['review_id'] ?? $_POST['id'] ?? 0);
        $status = trim((string) ($_POST['status'] ?? ''));
        $page = max(1, (int) ($_POST['page'] ?? 1));
        $currentStatus = trim((string) ($_POST['current_status'] ?? ''));

        if ($reviewId <= 0 || !in_array($status, ['pending', 'approved', 'rejected'], true)) {
            Session::flash('error', 'Cập nhật đánh giá thất bại.');
            $this->redirectToAdminReviewsPage($page, $currentStatus);
            return;
        }

        $reviewModel = new ProductReview();

        try {
            $reviewModel->updateStatus($reviewId, $status);
            Session::flash('success', 'Đã cập nhật trạng thái đánh giá.');
        } catch (Throwable $featureError) {
            $this->logAdminFeatureError($featureError);
            Session::flash('error', 'Không thể cập nhật đánh giá lúc này.');
        }

        $this->redirectToAdminReviewsPage($page, $currentStatus);
    }

    public function updateReviewStatus(): void
    {
        $this->updateReviewStatusInternal();
    }

    public function updateReviewsStatus(): void
    {
        $this->updateReviewStatusInternal();
    }

    public function settings(): void
    {
        $this->requireAdmin();

        $settings = $this->siteSettings();
        $promotions = [];

        try {
            $promotions = (new Promotion())->allForAdmin();
        } catch (Throwable $featureError) {
            $this->logAdminFeatureError($featureError);
        }

        $this->render('admin/settings', [
            'settings' => $settings,
            'defaultHomeMedia' => $this->defaultHomeMedia(),
            'spotlightCandidates' => $this->spotlightCandidates(),
            'defaultSpotlightMap' => $this->defaultKeyValueMap($this->defaultSpotlightIds(), 'home_spotlight_item_'),
            'defaultCartRecommendationMap' => $this->defaultKeyValueMap($this->defaultCartRecommendationIds(), 'cart_recommend_item_'),
            'defaultHomeDrinkMap' => $this->defaultKeyValueMap($this->defaultHomeDrinkIds(), 'home_drink_item_'),
            'spotlightSettingKeys' => $this->settingKeysByPrefix($settings, 'home_spotlight_item_', 4, false),
            'cartRecommendationKeys' => $this->settingKeysByPrefix($settings, 'cart_recommend_item_', 4, false),
            'homeDrinkKeys' => $this->settingKeysByPrefix($settings, 'home_drink_item_', 5, false),
            'bannerSlideKeys' => $this->settingKeysByPrefix($settings, 'home_banner_slide_', 3, false),
            'promotions' => $promotions,
        ], 'admin');
    }

    public function storeMenuItem(): void
    {
        $this->requireAdmin();
        $page = $this->requestedMenuPage();

        if (trim((string) ($_POST['name'] ?? '')) === '' || trim((string) ($_POST['price'] ?? '')) === '') {
            Session::flash('error', 'Tên món và giá bán là bắt buộc.');
            $this->redirectToAdminMenuPage($page);
        }

        $menuItemModel = new MenuItem();
        $menuItemModel->create([
            'category_id' => (int) ($_POST['category_id'] ?? 0),
            'name' => trim((string) ($_POST['name'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'price' => (int) ($_POST['price'] ?? 0),
            'image_url' => $this->handleFileUpload('image_file', trim((string) ($_POST['image_url'] ?? ''))),
            'is_featured' => !empty($_POST['is_featured']) ? 1 : 0,
            'is_available' => !empty($_POST['is_available']) ? 1 : 0,
            'sort_order' => (int) ($_POST['sort_order'] ?? 99),
        ]);

        Session::flash('success', 'Đã thêm món mới.');
        $this->redirectToAdminMenuPage($page);
    }

    public function updateMenuItem(): void
    {
        $this->requireAdmin();
        $page = $this->requestedMenuPage();
        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            Session::flash('error', 'Không tìm thấy món cần cập nhật.');
            $this->redirectToAdminMenuPage($page);
        }

        $menuItemModel = new MenuItem();
        $existingItem = $menuItemModel->findById($id);
        if ($existingItem === null) {
            Session::flash('error', 'Không tìm thấy món cần cập nhật.');
            $this->redirectToAdminMenuPage($page);
        }

        if (trim((string) ($_POST['name'] ?? '')) === '' || trim((string) ($_POST['price'] ?? '')) === '') {
            Session::flash('error', 'Tên món và giá bán là bắt buộc.');
            $this->redirectToAdminMenuPage($page);
        }

        $imageUrl = $this->handleFileUpload(
            'image_file',
            trim((string) ($_POST['image_url'] ?? (string) ($existingItem['image_url'] ?? '')))
        );

        $menuItemModel->update($id, [
            'category_id' => (int) ($_POST['category_id'] ?? $existingItem['category_id'] ?? 0),
            'name' => trim((string) ($_POST['name'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'price' => (int) ($_POST['price'] ?? 0),
            'image_url' => $imageUrl,
            'is_featured' => !empty($_POST['is_featured']) ? 1 : 0,
            'is_available' => !empty($_POST['is_available']) ? 1 : 0,
            'sort_order' => (int) ($_POST['sort_order'] ?? 99),
        ]);

        Session::flash('success', 'Đã cập nhật món.');
        $this->redirectToAdminMenuPage($page);
    }

    public function deleteMenuItem(): void
    {
        $this->requireAdmin();
        $page = $this->requestedMenuPage();
        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            Session::flash('error', 'Không tìm thấy món cần xóa.');
            $this->redirectToAdminMenuPage($page);
        }

        $menuItemModel = new MenuItem();
        if ($menuItemModel->findById($id) === null) {
            Session::flash('error', 'Không tìm thấy món cần xóa.');
            $this->redirectToAdminMenuPage($page);
        }

        $menuItemModel->delete($id);
        Session::flash('success', 'Đã xóa món khỏi thực đơn.');
        $this->redirectToAdminMenuPage($page);
    }

    public function exportOrders(): void
    {
        $this->requireAdmin();

        $orderModel = new Order();
        $allOrders = $this->decorateOrdersWithItems($orderModel->all(), $orderModel);
        $searchQuery = trim($_GET['search'] ?? '');
        $searchDate = trim($_GET['date'] ?? '');
        $filteredOrders = $this->filteredOrders($allOrders, $searchQuery, $searchDate);

        $headers = ['MÃ£ Ä‘Æ¡n', 'KhÃ¡ch hÃ ng', 'Email', 'Sá»‘ Ä‘iá»‡n thoáº¡i', 'Äá»‹a chá»‰', 'Thá»i gian', 'Tá»•ng tiá»n', 'PhÆ°Æ¡ng thá»©c', 'Tráº¡ng thÃ¡i Ä‘Æ¡n', 'Tráº¡ng thÃ¡i thanh toÃ¡n', 'MÃ£ Ä‘á»‘i chiáº¿u'];
        $rows = [];
        $headers = ['Ma don', 'Khach hang', 'Email', 'So dien thoai', 'Mon da dat', 'Dia chi', 'Thoi gian', 'Tong tien', 'Phuong thuc', 'Trang thai don', 'Trang thai thanh toan', 'Ma doi chieu'];

        foreach ($filteredOrders as $order) {
            $itemsSummary = [];
            foreach (($order['items'] ?? []) as $item) {
                $itemsSummary[] = sprintf(
                    '%s x%d',
                    (string) ($item['menu_item_name'] ?? 'Mon'),
                    (int) ($item['quantity'] ?? 0)
                );
            }

            $rows[] = [
                '#' . $order['id'],
                $order['customer_name'],
                $order['customer_email'] ?? '',
                $order['phone'],
                implode('; ', $itemsSummary),
                $order['address'],
                date('d/m/Y H:i', strtotime((string) $order['created_at'])),
                (int) $order['total_amount'],
                payment_method_label((string) ($order['payment_method'] ?? 'cod')),
                (string) ($order['status'] ?? 'pending'),
                payment_status_label((string) ($order['payment_status'] ?? 'unpaid')),
                (string) ($order['payment_reference'] ?? ''),
            ];
        }

        ReportExporter::download(
            'danh-sach-don-hang-' . date('Y-m-d'),
            $headers,
            $rows,
            $this->requestedExportFormat(),
            'Danh sÃ¡ch Ä‘Æ¡n hÃ ng RoyalBread'
        );
    }

    public function exportRevenue(): void
    {
        $this->requireAdmin();

        $allOrders = (new Order())->all();
        $filterDate = trim($_GET['date'] ?? '');
        $filterMonth = trim($_GET['month'] ?? '');

        if ($filterDate === '' && $filterMonth === '') {
            $filterMonth = date('Y-m');
        }

        $filteredOrders = $this->filteredCompletedOrders($allOrders, $filterDate, $filterMonth);
        $headers = ['MÃ£ Ä‘Æ¡n', 'KhÃ¡ch hÃ ng', 'Email', 'Sá»‘ Ä‘iá»‡n thoáº¡i', 'Thá»i gian', 'PhÆ°Æ¡ng thá»©c', 'ThÃ nh tiá»n', 'Giáº£m giÃ¡', 'Thanh toÃ¡n'];
        $rows = [];

        foreach ($filteredOrders as $order) {
            $rows[] = [
                '#' . $order['id'],
                $order['customer_name'],
                $order['customer_email'] ?? '',
                $order['phone'],
                date('d/m/Y H:i', strtotime((string) $order['created_at'])),
                payment_method_label((string) ($order['payment_method'] ?? 'cod')),
                (int) $order['total_amount'],
                (int) ($order['discount_amount'] ?? 0),
                payment_status_label((string) ($order['payment_status'] ?? 'unpaid')),
            ];
        }

        $period = 'all';
        if ($filterDate !== '') {
            $period = $filterDate;
        } elseif ($filterMonth !== '') {
            $period = $filterMonth;
        }

        ReportExporter::download(
            'bao-cao-doanh-thu-' . $period,
            $headers,
            $rows,
            $this->requestedExportFormat(),
            'BÃ¡o cÃ¡o doanh thu RoyalBread'
        );
    }

    public function exportCustomers(): void
    {
        $this->requireAdmin();

        $searchQuery = trim($_GET['search'] ?? '');
        $customers = (new Customer())->allWithStats($searchQuery);
        $headers = ['ID khÃ¡ch', 'Há» tÃªn', 'Username', 'Sá»‘ Ä‘iá»‡n thoáº¡i', 'Email', 'NgÃ y Ä‘Äƒng kÃ½', 'Sá»‘ Ä‘Æ¡n hÃ ng', 'ÄÃ£ chi tiÃªu', 'Háº¡ng thÃ nh viÃªn', 'Äiá»ƒm tiá»m nÄƒng', 'PhÃ¢n nhÃ³m', 'GiÃ¡ trá»‹ Ä‘Æ¡n TB', 'Láº§n mua gáº§n nháº¥t'];
        $rows = [];

        foreach ($customers as $customer) {
            $rows[] = [
                $customer['id'],
                $customer['full_name'],
                '@' . $customer['username'],
                $customer['phone'] ?? '',
                $customer['email'] ?? '',
                date('d/m/Y H:i', strtotime((string) $customer['created_at'])),
                (int) ($customer['orders_count'] ?? 0),
                (int) ($customer['total_spent'] ?? 0),
                (string) ($customer['membership_tier_label'] ?? ''),
                (int) ($customer['customer_score'] ?? 0),
                (string) ($customer['potential_segment'] ?? ''),
                (int) ($customer['average_order_value'] ?? 0),
                trim((string) ($customer['last_order_at'] ?? '')) !== ''
                    ? date('d/m/Y H:i', strtotime((string) $customer['last_order_at']))
                    : '',
            ];
        }

        ReportExporter::download(
            'danh-sach-khach-hang-' . date('Y-m-d'),
            $headers,
            $rows,
            $this->requestedExportFormat(),
            'Danh sÃ¡ch khÃ¡ch hÃ ng RoyalBread'
        );
    }
}
