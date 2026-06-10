<?php

declare(strict_types=1);

class AdminController extends Controller
{
    private const FINAL_ORDER_STATUSES = ['completed', 'cancelled'];

    private function siteSettings(): array
    {
        return (new Setting())->all();
    }

    private function requestedExportFormat(): string
    {
        $format = strtolower(trim((string) ($_GET['format'] ?? 'xlsx')));

        return in_array($format, ['csv', 'xlsx', 'pdf'], true) ? $format : 'xlsx';
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

    private function menuSections(): array
    {
        $categoryModel = new Category();
        $menuItemModel = new MenuItem();

        $categories = $categoryModel->all();
        $items = $menuItemModel->allForAdmin();
        $itemsByCategoryId = [];

        foreach ($items as $item) {
            $itemsByCategoryId[(int) $item['category_id']][] = $item;
        }

        $sections = [];
        foreach ($categories as $category) {
            $categoryId = (int) $category['id'];
            $sections[] = [
                'category' => $category,
                'items' => $itemsByCategoryId[$categoryId] ?? [],
            ];
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

        $menuItemModel = new MenuItem();
        $messageModel = new Message();
        $orderModel = new Order();
        $customerModel = new Customer();
        $reviewModel = new ProductReview();
        $liveChatModel = new LiveChat();
        $promotionModel = new Promotion();
        $allOrders = $orderModel->all();

        $totalRevenue = 0;
        $newOrdersCount = 0;
        foreach ($allOrders as $order) {
            if (($order['status'] ?? '') === 'completed') {
                $totalRevenue += (int) $order['total_amount'];
            }

            if (($order['status'] ?? '') === 'pending') {
                $newOrdersCount++;
            }
        }

        $this->render('admin/dashboard', [
            'settings' => $this->siteSettings(),
            'menuCount' => $menuItemModel->countAll(),
            'messageCount' => $messageModel->countAll(),
            'newMessageCount' => $messageModel->countNew(),
            'orderCount' => count($allOrders),
            'newOrdersCount' => $newOrdersCount,
            'totalRevenue' => $totalRevenue,
            'customerCount' => $customerModel->countAll(),
            'latestOrders' => array_slice($allOrders, 0, 5),
            'latestMessages' => $messageModel->latest(5),
            'featuredItems' => $menuItemModel->featured(6),
            'bestSellingItems' => $menuItemModel->bestSelling(6),
            'topPotentialCustomers' => $customerModel->topPotential(5),
            'activePromotionCount' => count(array_filter(
                $promotionModel->allForAdmin(),
                static fn(array $promotion): bool => (int) ($promotion['is_active'] ?? 0) === 1
            )),
            'pendingReviewCount' => count($reviewModel->allForAdmin('pending')),
            'unreadLiveChatCount' => $liveChatModel->countUnreadForAdmin(),
        ], 'admin');
    }

    public function orders(): void
    {
        $this->requireAdmin();

        $allOrders = (new Order())->all();
        $searchQuery = trim($_GET['search'] ?? '');
        $searchDate = trim($_GET['date'] ?? '');

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

    public function revenue(): void
    {
        $this->requireAdmin();

        $allOrders = (new Order())->all();
        $filterDate = trim($_GET['date'] ?? '');
        $filterMonth = trim($_GET['month'] ?? '');

        if ($filterDate === '' && $filterMonth === '') {
            $filterMonth = date('Y-m');
        }

        $filteredOrders = $this->filteredCompletedOrders($allOrders, $filterDate, $filterMonth);
        $totalRevenue = 0;
        foreach ($filteredOrders as $order) {
            $totalRevenue += (int) $order['total_amount'];
        }

        $this->render('admin/revenue', [
            'settings' => $this->siteSettings(),
            'orders' => $filteredOrders,
            'totalRevenue' => $totalRevenue,
            'filterDate' => $filterDate,
            'filterMonth' => $filterMonth,
        ], 'admin');
    }

    public function menu(): void
    {
        $this->requireAdmin();

        $categoryModel = new Category();
        $menuItemModel = new MenuItem();

        $this->render('admin/menu', [
            'settings' => $this->siteSettings(),
            'categories' => $categoryModel->all(),
            'items' => $menuItemModel->allForAdmin(),
            'menuSections' => $this->menuSections(),
        ], 'admin');
    }

    public function messages(): void
    {
        $this->requireAdmin();

        $messageModel = new Message();
        $liveChatModel = new LiveChat();
        $threads = $liveChatModel->threadsForAdmin();
        $selectedThreadId = (int) ($_GET['thread'] ?? ($threads[0]['id'] ?? 0));
        $activeThread = null;
        $chatMessages = [];

        if ($selectedThreadId > 0) {
            $activeThread = $liveChatModel->findThreadById($selectedThreadId);
            if ($activeThread !== null) {
                $liveChatModel->markReadForViewer($selectedThreadId, 'admin');
                $chatMessages = $liveChatModel->messagesForThread($selectedThreadId);
            }
        }

        $this->render('admin/messages', [
            'settings' => $this->siteSettings(),
            'messages' => $messageModel->all(),
            'threads' => $threads,
            'activeThread' => $activeThread,
            'chatMessages' => $chatMessages,
        ], 'admin');
    }

    public function reviews(): void
    {
        $this->requireAdmin();

        $status = trim((string) ($_GET['status'] ?? ''));

        $this->render('admin/reviews', [
            'settings' => $this->siteSettings(),
            'reviews' => (new ProductReview())->allForAdmin($status),
            'currentStatus' => $status,
        ], 'admin');
    }

    public function settings(): void
    {
        $this->requireAdmin();

        $settings = $this->siteSettings();

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
            'promotions' => (new Promotion())->allForAdmin(),
        ], 'admin');
    }

    public function storeMenuItem(): void
    {
        $this->requireAdmin();

        if (trim((string) ($_POST['name'] ?? '')) === '' || trim((string) ($_POST['price'] ?? '')) === '') {
            Session::flash('error', 'Tên món và giá bán là bắt buộc.');
            $this->redirectTo('admin/menu');
        }

        $payload = $_POST;
        $payload['image_url'] = $this->handleFileUpload('image_file', trim((string) ($_POST['image_url'] ?? '')));

        (new MenuItem())->create($payload);
        Session::flash('success', 'Đã thêm món mới vào thực đơn.');
        $this->redirectTo('admin/menu');
    }

    public function updateMenuItem(): void
    {
        $this->requireAdmin();

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            Session::flash('error', 'Không tìm thấy món cần cập nhật.');
            $this->redirectTo('admin/menu');
        }

        $payload = $_POST;
        $payload['image_url'] = $this->handleFileUpload('image_file', trim((string) ($_POST['image_url'] ?? '')));

        (new MenuItem())->update($id, $payload);
        Session::flash('success', 'Đã cập nhật món thành công.');
        $this->redirectTo('admin/menu');
    }

    public function deleteMenuItem(): void
    {
        $this->requireAdmin();

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            Session::flash('error', 'Không tìm thấy món cần xóa.');
            $this->redirectTo('admin/menu');
        }

        (new MenuItem())->delete($id);
        Session::flash('success', 'Đã xóa món khỏi thực đơn.');
        $this->redirectTo('admin/menu');
    }

    public function updateSettings(): void
    {
        $this->requireAdmin();

        $settingsModel = new Setting();
        $currentSettings = $settingsModel->all();

        $fixedKeys = [
            'site_name',
            'tagline',
            'address',
            'map_query',
            'hotline',
            'opening_hours',
            'shopeefood_url',
            'about_text',
            'bank_name',
            'bank_bin',
            'bank_account_number',
            'bank_account_holder',
            'bank_transfer_note',
            'seo_default_keywords',
        ];

        $payload = [];
        foreach ($fixedKeys as $key) {
            $payload[$key] = trim((string) ($_POST[$key] ?? ''));
        }

        foreach (['home_spotlight_item_', 'cart_recommend_item_', 'home_drink_item_'] as $prefix) {
            $settingKeys = array_unique(array_merge(
                $this->settingKeysByPrefix($currentSettings, $prefix),
                $this->submittedPostKeysByPrefix($prefix)
            ));

            foreach ($settingKeys as $key) {
                $payload[$key] = trim((string) ($_POST[$key] ?? ''));
            }
        }

        $bannerSlideKeys = array_unique(array_merge(
            $this->settingKeysByPrefix($currentSettings, 'home_banner_slide_', 3),
            $this->submittedPostKeysByPrefix('home_banner_slide_'),
            $this->submittedFileKeysByPrefix('home_banner_slide_')
        ));

        foreach ($bannerSlideKeys as $key) {
            $payload[$key] = $this->handleFileUpload($key . '_file', trim((string) ($_POST[$key] ?? '')));
        }

        foreach ([
            'home_signature_image',
            'contact_hero_image',
            'home_category_card_bread_image',
            'home_category_card_pan_image',
            'home_category_card_drink_image',
        ] as $imageKey) {
            $payload[$imageKey] = $this->handleFileUpload($imageKey . '_file', trim((string) ($_POST[$imageKey] ?? '')));
        }

        $settingsModel->updateMany($payload);
        Session::flash('success', 'Đã cập nhật thông tin website, món hiển thị và ảnh banner.');
        $this->redirectTo('admin/settings');
    }

    public function storePromotion(): void
    {
        $this->requireAdmin();

        $title = trim((string) ($_POST['title'] ?? ''));
        $content = trim((string) ($_POST['content'] ?? ''));
        $targetTier = trim((string) ($_POST['target_tier'] ?? 'all'));
        $discountPercent = max(0, (int) ($_POST['discount_percent'] ?? 0));
        $discountAmount = max(0, (int) ($_POST['discount_amount'] ?? 0));

        if ($title === '' || $content === '') {
            Session::flash('error', 'Tiêu đề và nội dung khuyến mãi là bắt buộc.');
            $this->redirectTo('admin/settings');
        }

        if ($discountPercent <= 0 && $discountAmount <= 0) {
            Session::flash('error', 'Cần nhập ít nhất một giá trị giảm phần trăm hoặc giảm tiền.');
            $this->redirectTo('admin/settings');
        }

        $promotionModel = new Promotion();
        $promotionId = $promotionModel->create([
            'title' => $title,
            'content' => $content,
            'target_tier' => $targetTier,
            'discount_percent' => $discountPercent,
            'discount_amount' => $discountAmount,
            'coupon_code' => trim((string) ($_POST['coupon_code'] ?? '')),
            'expires_at' => trim((string) ($_POST['expires_at'] ?? '')),
            'is_active' => !empty($_POST['is_active']) ? 1 : 0,
        ]);

        $promotion = $promotionModel->findById($promotionId);
        if ($promotion !== null && !empty($_POST['send_email'])) {
            $recipients = (new Customer())->recipientsForPromotion((string) ($promotion['target_tier'] ?? 'all'));
            $settings = $this->siteSettings();

            foreach ($recipients as $recipient) {
                EmailService::sendPromotionAnnouncement($recipient, $promotion, $settings);
            }
        }

        Session::flash('success', 'Đã tạo chương trình khuyến mãi mới.');
        $this->redirectTo('admin/settings');
    }

    public function deletePromotion(): void
    {
        $this->requireAdmin();

        $promotionId = (int) ($_POST['promotion_id'] ?? 0);
        if ($promotionId <= 0) {
            Session::flash('error', 'Không tìm thấy khuyến mãi cần xóa.');
            $this->redirectTo('admin/settings');
        }

        (new Promotion())->delete($promotionId);
        Session::flash('success', 'Đã xóa chương trình khuyến mãi.');
        $this->redirectTo('admin/settings');
    }

    public function updateReviewStatus(): void
    {
        $this->requireAdmin();

        $reviewId = (int) ($_POST['review_id'] ?? 0);
        $status = trim((string) ($_POST['status'] ?? ''));

        if ($reviewId <= 0 || !in_array($status, ['pending', 'approved', 'rejected'], true)) {
            Session::flash('error', 'Không thể cập nhật trạng thái đánh giá.');
            $this->redirectTo('admin/reviews');
        }

        (new ProductReview())->updateStatus($reviewId, $status);
        Session::flash('success', 'Đã cập nhật trạng thái đánh giá.');
        $this->redirectTo('admin/reviews');
    }

    public function sendLiveChatMessage(): void
    {
        $this->requireAdmin();

        $threadId = (int) ($_POST['thread_id'] ?? 0);
        $message = trim((string) ($_POST['message'] ?? ''));

        if ($threadId <= 0 || $message === '') {
            Session::flash('error', 'Vui lòng chọn đúng cuộc trò chuyện và nhập nội dung phản hồi.');
            $this->redirectTo('admin/messages');
        }

        $liveChatModel = new LiveChat();
        $thread = $liveChatModel->findThreadById($threadId);
        if ($thread === null) {
            Session::flash('error', 'Không tìm thấy cuộc trò chuyện cần phản hồi.');
            $this->redirectTo('admin/messages');
        }

        if (($thread['status'] ?? 'open') === 'closed') {
            $liveChatModel->reopenThread($threadId);
        }

        $liveChatModel->addMessage(
            $threadId,
            'admin',
            !empty($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : null,
            (string) ($_SESSION['admin_name'] ?? 'RoyalBread Admin'),
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
            Session::flash('error', 'Không thể cập nhật cuộc trò chuyện.');
            $this->redirectTo('admin/messages');
        }

        $liveChatModel = new LiveChat();
        if ($action === 'close') {
            $liveChatModel->closeThread($threadId);
            Session::flash('success', 'Đã đóng cuộc trò chuyện.');
        } else {
            $liveChatModel->reopenThread($threadId);
            Session::flash('success', 'Đã mở lại cuộc trò chuyện.');
        }

        $this->redirectTo('admin/messages?thread=' . $threadId);
    }

    public function exportOrders(): void
    {
        $this->requireAdmin();

        $allOrders = (new Order())->all();
        $searchQuery = trim($_GET['search'] ?? '');
        $searchDate = trim($_GET['date'] ?? '');
        $filteredOrders = $this->filteredOrders($allOrders, $searchQuery, $searchDate);

        $headers = ['Mã đơn', 'Khách hàng', 'Email', 'Số điện thoại', 'Địa chỉ', 'Thời gian', 'Tổng tiền', 'Phương thức', 'Trạng thái đơn', 'Trạng thái thanh toán', 'Mã đối chiếu'];
        $rows = [];

        foreach ($filteredOrders as $order) {
            $rows[] = [
                '#' . $order['id'],
                $order['customer_name'],
                $order['customer_email'] ?? '',
                $order['phone'],
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
            'Danh sách đơn hàng RoyalBread'
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
        $headers = ['Mã đơn', 'Khách hàng', 'Email', 'Số điện thoại', 'Thời gian', 'Phương thức', 'Thành tiền', 'Giảm giá', 'Thanh toán'];
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
            'Báo cáo doanh thu RoyalBread'
        );
    }

    public function exportCustomers(): void
    {
        $this->requireAdmin();

        $searchQuery = trim($_GET['search'] ?? '');
        $customers = (new Customer())->allWithStats($searchQuery);
        $headers = ['ID khách', 'Họ tên', 'Username', 'Số điện thoại', 'Email', 'Ngày đăng ký', 'Số đơn hàng', 'Đã chi tiêu', 'Hạng thành viên', 'Điểm tiềm năng', 'Phân nhóm', 'Giá trị đơn TB', 'Lần mua gần nhất'];
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
            'Danh sách khách hàng RoyalBread'
        );
    }
}
