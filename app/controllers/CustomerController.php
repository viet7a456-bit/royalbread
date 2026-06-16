<?php

declare(strict_types=1);

class CustomerController extends Controller
{
    private function logAccountFeatureError(Throwable $error): void
    {
        $logDir = ROOT_PATH . '/tmp/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $entry = sprintf(
            "[%s] Customer account feature error: %s\n%s\n",
            date('Y-m-d H:i:s'),
            $error->getMessage(),
            $error->getTraceAsString()
        );
        @file_put_contents($logDir . '/customer_features.log', $entry, FILE_APPEND | LOCK_EX);
        error_log('RoyalBread customer feature error: ' . $error->getMessage());
    }

    private function logCustomerPageError(Throwable $error): void
    {
        $logDir = ROOT_PATH . '/tmp/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $entry = sprintf(
            "[%s] Customer page error: %s\n%s\n",
            date('Y-m-d H:i:s'),
            $error->getMessage(),
            $error->getTraceAsString()
        );
        @file_put_contents($logDir . '/customer_page.log', $entry, FILE_APPEND | LOCK_EX);
        error_log('RoyalBread customer page error: ' . $error->getMessage());
    }

    private function resolveCustomerOrRedirect(): array
    {
        if (empty($_SESSION['customer_id'])) {
            Session::flash('error', 'Vui long dang nhap tai khoan khach hang truoc.');
            $this->redirectTo('customer/login');
        }

        $customer = (new Customer())->findById((int) $_SESSION['customer_id']);
        if ($customer === null) {
            unset($_SESSION['customer_id'], $_SESSION['customer_name']);
            Session::flash('error', 'Tai khoan khach hang khong con hop le. Vui long dang nhap lai.');
            $this->redirectTo('customer/login');
        }

        $_SESSION['customer_name'] = $customer['full_name'];

        return $customer;
    }

    public function index(): void
    {
        $categoryModel = new Category();
        $customerModel = new Customer();
        $orderModel = new Order();
        $favoriteModel = new Favorite();
        $reviewModel = new ProductReview();
        $liveChatModel = new LiveChat();

        $settings = [];
        $menuGroups = [];
        $featuredItems = [];
        $bestSellingItems = [];

        try {
            $settingModel = new Setting();
            $menuItemModel = new MenuItem();

            $settings = $settingModel->all();
            $menuGroups = $menuItemModel->grouped();
            $featuredItems = $menuItemModel->featured(8);
            $bestSellingItems = $menuItemModel->bestSelling(6);
        } catch (Throwable $pageError) {
            $this->logCustomerPageError($pageError);
        }

        $totalItems = 0;
        foreach ($menuGroups as $items) {
            $totalItems += count($items);
        }

        $customer = null;
        $profile = [];
        $recentOrders = [];
        $favoriteItems = [];
        $suggestedItems = [];
        $notifications = [];
        $supportMessages = [];
        $priceHighlights = $this->buildPriceHighlights($menuGroups);
        $addressCards = [];
        $reviewableItems = [];
        $customerReviews = [];
        $customerReviewsPage = 1;
        $customerReviewsTotal = 0;
        $customerReviewsTotalPages = 1;
        $customerReviewsVisibleFrom = 0;
        $customerReviewsVisibleTo = 0;
        $chatThread = null;
        $chatMessages = [];
        $membership = membership_tier_meta(0, 0);

        if (!empty($_SESSION['customer_id'])) {
            $customer = $customerModel->findWithStatsById((int) $_SESSION['customer_id']);

            if ($customer === null) {
                unset($_SESSION['customer_id'], $_SESSION['customer_name']);
            } else {
                $_SESSION['customer_name'] = $customer['full_name'];

                $orders = $orderModel->forCustomer((int) $customer['id']);
                $orderInsights = $this->buildOrderInsights($orders, $orderModel);
                $latestOrder = $orders[0] ?? null;

                $recentOrders = $orderInsights['recent_orders'];
                $suggestedItems = $orderInsights['frequently_ordered_items'];
                $membership = membership_tier_meta(
                    $orderInsights['order_count'],
                    $orderInsights['total_spent']
                );

                try {
                    $favoriteItems = $favoriteModel->itemsForCustomer((int) $customer['id'], 8);
                    $reviewableItems = $reviewModel->reviewableItemsForCustomer((int) $customer['id'], 6);
                    $customerReviewsPage = max(1, (int) ($_GET['review_page'] ?? 1));
                    $customerReviewsTotal = $reviewModel->countForCustomer((int) $customer['id']);
                    $customerReviewsTotalPages = max(1, (int) ceil($customerReviewsTotal / 5));
                    $customerReviewsPage = min($customerReviewsPage, $customerReviewsTotalPages);
                    $customerReviewsOffset = ($customerReviewsPage - 1) * 5;
                    $customerReviews = $reviewModel->forCustomerPaginated((int) $customer['id'], 5, $customerReviewsOffset);
                    $customerReviewsVisibleFrom = $customerReviewsTotal > 0 ? $customerReviewsOffset + 1 : 0;
                    $customerReviewsVisibleTo = $customerReviewsTotal > 0 ? min($customerReviewsOffset + count($customerReviews), $customerReviewsTotal) : 0;
                } catch (Throwable $featureError) {
                    $this->logAccountFeatureError($featureError);
                    $favoriteItems = [];
                    $reviewableItems = [];
                    $customerReviews = [];
                }

                if ($suggestedItems === []) {
                    $suggestedItems = $bestSellingItems !== [] ? $bestSellingItems : array_slice($featuredItems, 0, 4);
                }

                $profile = [
                    'name' => $customer['full_name'],
                    'username' => $customer['username'],
                    'email' => trim((string) ($customer['email'] ?? '')),
                    'phone' => trim((string) ($customer['phone'] ?? '')) !== ''
                        ? trim((string) $customer['phone'])
                        : trim((string) ($latestOrder['phone'] ?? '')),
                    'joined_on' => $this->formatDate((string) ($customer['created_at'] ?? '')),
                ];

                $addressCards = $this->buildAddressCards($settings, $latestOrder, $profile);
                $notifications = $this->buildNotifications($settings, $membership, $priceHighlights, $latestOrder);
                $supportMessages = $this->buildSupportMessages($settings, $membership, $latestOrder);

                try {
                    $chatThread = $liveChatModel->getOrCreateOpenThreadForCustomer((int) $customer['id']);
                    $liveChatModel->markReadForViewer((int) $chatThread['id'], 'customer');
                    $chatMessages = $liveChatModel->messagesForThread((int) $chatThread['id']);
                } catch (Throwable $featureError) {
                    $this->logAccountFeatureError($featureError);
                    $chatThread = null;
                    $chatMessages = [];
                }
            }
        }

        if ($customer === null) {
            $suggestedItems = $bestSellingItems !== [] ? $bestSellingItems : $featuredItems;
        }

        $this->render('customer/index', [
            'settings' => $settings,
            'featuredItems' => $featuredItems,
            'bestSellingItems' => $bestSellingItems,
            'menuGroups' => $menuGroups,
            'categoryCount' => $menuGroups !== [] ? $categoryModel->countAll() : 0,
            'totalItems' => $totalItems,
            'customer' => $customer,
            'profile' => $profile,
            'recentOrders' => $recentOrders,
            'favoriteItems' => $favoriteItems,
            'suggestedItems' => $suggestedItems,
            'notifications' => $notifications,
            'supportMessages' => $supportMessages,
            'priceHighlights' => $priceHighlights,
            'addressCards' => $addressCards,
            'membership' => $membership,
            'reviewableItems' => $reviewableItems,
            'customerReviews' => $customerReviews,
            'customerReviewsPage' => $customerReviewsPage,
            'customerReviewsTotal' => $customerReviewsTotal,
            'customerReviewsTotalPages' => $customerReviewsTotalPages,
            'customerReviewsVisibleFrom' => $customerReviewsVisibleFrom,
            'customerReviewsVisibleTo' => $customerReviewsVisibleTo,
            'chatThread' => $chatThread,
            'chatMessages' => $chatMessages,
        ]);
    }

    public function toggleFavorite(): void
    {
        $customer = $this->resolveCustomerOrRedirect();
        $menuItemId = (int) ($_POST['menu_item_id'] ?? 0);
        $redirectTo = trim((string) ($_POST['redirect_to'] ?? 'account#mon-yeu-thich'));

        if ($menuItemId <= 0 || (new MenuItem())->findById($menuItemId) === null) {
            Session::flash('error', 'Mon ban chon khong con hop le.');
            $this->redirectTo($redirectTo);
        }

        try {
            $isFavorite = (new Favorite())->toggle((int) $customer['id'], $menuItemId);
        } catch (Throwable $featureError) {
            $this->logAccountFeatureError($featureError);
            Session::flash('error', 'Tinh nang yeu thich tam thoi chua san sang tren hosting.');
            $this->redirectTo($redirectTo);
        }

        Session::flash('success', $isFavorite ? 'Da them mon vao danh sach yeu thich.' : 'Da bo mon khoi danh sach yeu thich.');
        $this->redirectTo($redirectTo);
    }

    public function storeReview(): void
    {
        $customer = $this->resolveCustomerOrRedirect();
        $menuItemId = (int) ($_POST['menu_item_id'] ?? 0);
        $orderId = (int) ($_POST['order_id'] ?? 0);
        $rating = max(1, min(5, (int) ($_POST['rating'] ?? 0)));
        $reviewTitle = trim((string) ($_POST['review_title'] ?? ''));
        $reviewComment = trim((string) ($_POST['review_comment'] ?? ''));

        if ($menuItemId <= 0 || $orderId <= 0 || $rating < 1 || $reviewComment === '') {
            Session::flash('error', 'Vui long nhap du danh gia truoc khi gui.');
            $this->redirectTo('account#danh-gia');
        }

        try {
            (new ProductReview())->createOrUpdate([
                'customer_id' => (int) $customer['id'],
                'menu_item_id' => $menuItemId,
                'order_id' => $orderId,
                'rating' => $rating,
                'review_title' => $reviewTitle,
                'review_comment' => $reviewComment,
                'status' => 'pending',
            ]);
        } catch (Throwable $featureError) {
            $this->logAccountFeatureError($featureError);
            Session::flash('error', 'Tinh nang danh gia tam thoi chua san sang tren hosting.');
            $this->redirectTo('account#danh-gia');
        }

        Session::flash('success', 'RoyalBread da nhan danh gia cua ban va se hien thi sau khi duyet.');
        $this->redirectTo('account#danh-gia');
    }

    public function sendLiveChatMessage(): void
    {
        $customer = $this->resolveCustomerOrRedirect();
        $message = trim((string) ($_POST['message'] ?? ''));

        if ($message === '') {
            Session::flash('error', 'Vui long nhap noi dung truoc khi gui tin nhan.');
            $this->redirectTo('account#ho-tro');
        }

        try {
            $liveChatModel = new LiveChat();
            $thread = $liveChatModel->getOrCreateOpenThreadForCustomer((int) $customer['id']);
            $liveChatModel->addMessage(
                (int) $thread['id'],
                'customer',
                (int) $customer['id'],
                (string) $customer['full_name'],
                $message
            );
        } catch (Throwable $featureError) {
            $this->logAccountFeatureError($featureError);
            Session::flash('error', 'Tinh nang chat truc tiep tam thoi chua san sang tren hosting.');
            $this->redirectTo('account#ho-tro');
        }

        Session::flash('success', 'Da gui tin nhan toi RoyalBread.');
        $this->redirectTo('account#ho-tro');
    }

    private function buildOrderInsights(array $orders, Order $orderModel): array
    {
        $recentOrders = [];
        $favoriteCounts = [];
        $favoriteItems = [];
        $totalSpent = 0;

        foreach ($orders as $index => $order) {
            $totalSpent += (int) ($order['total_amount'] ?? 0);
            $items = $orderModel->getItems((int) $order['id']);

            foreach ($items as $item) {
                $menuItemId = (int) $item['menu_item_id'];
                $favoriteCounts[$menuItemId] = ($favoriteCounts[$menuItemId] ?? 0) + (int) $item['quantity'];
                $favoriteItems[$menuItemId] = [
                    'id' => $menuItemId,
                    'name' => $item['menu_item_name'],
                    'image_url' => $item['image_url'],
                    'price' => (int) $item['price'],
                    'quantity' => $favoriteCounts[$menuItemId],
                    'category_name' => '',
                ];
            }

            if ($index < 3) {
                $order['items'] = array_slice($items, 0, 3);
                $order['created_label'] = $this->formatDateTime((string) ($order['created_at'] ?? ''));
                $order['status_label'] = $this->statusLabel((string) ($order['status'] ?? 'pending'));
                $order['status_class'] = $this->statusClass((string) ($order['status'] ?? 'pending'));
                $order['payment_label'] = payment_method_label((string) ($order['payment_method'] ?? 'cod'));
                $order['payment_status_label'] = payment_status_label((string) ($order['payment_status'] ?? 'unpaid'));
                $recentOrders[] = $order;
            }
        }

        arsort($favoriteCounts);

        $frequentlyOrderedItems = [];
        foreach (array_keys($favoriteCounts) as $menuItemId) {
            $frequentlyOrderedItems[] = $favoriteItems[$menuItemId];
            if (count($frequentlyOrderedItems) >= 4) {
                break;
            }
        }

        return [
            'recent_orders' => $recentOrders,
            'frequently_ordered_items' => $frequentlyOrderedItems,
            'order_count' => count($orders),
            'total_spent' => $totalSpent,
        ];
    }

    private function buildAddressCards(array $settings, ?array $latestOrder, array $profile): array
    {
        $cards = [];

        if ($latestOrder !== null && trim((string) ($latestOrder['address'] ?? '')) !== '') {
            $cards[] = [
                'tag' => 'Don gan nhat',
                'title' => 'Dia chi giao hang gan day',
                'address' => trim((string) $latestOrder['address']),
                'contact' => trim((string) ($latestOrder['phone'] ?? '')),
            ];
        }

        $cards[] = [
            'tag' => 'RoyalBread',
            'title' => 'Nhan tai quan',
            'address' => setting($settings, 'address'),
            'contact' => setting($settings, 'hotline'),
        ];

        if ($profile !== [] && trim((string) ($profile['email'] ?? '')) !== '') {
            $cards[] = [
                'tag' => 'Tai khoan',
                'title' => 'Email lien he',
                'address' => trim((string) $profile['email']),
                'contact' => trim((string) ($profile['phone'] ?? '')),
            ];
        }

        return array_slice($cards, 0, 3);
    }

    private function buildNotifications(array $settings, array $membership, array $priceHighlights, ?array $latestOrder): array
    {
        $notes = [
            [
                'title' => 'Diem thanh vien dang duoc cap nhat',
                'body' => 'Hien ban co ' . number_format((int) $membership['points'], 0, ',', '.') . ' diem tam tinh tren website.',
            ],
            [
                'title' => 'Quan mo cua moi ngay',
                'body' => setting($settings, 'opening_hours') . ' tai ' . setting($settings, 'address'),
            ],
        ];

        if ($latestOrder !== null) {
            $notes[] = [
                'title' => 'Don gan nhat cua ban',
                'body' => 'Trang thai hien tai: ' . $this->statusLabel((string) ($latestOrder['status'] ?? 'pending')) . '.',
            ];
        }

        if ($priceHighlights !== []) {
            $firstHighlight = $priceHighlights[0];
            $notes[] = [
                'title' => $firstHighlight['title'],
                'body' => $firstHighlight['description'],
            ];
        }

        $promotions = (new Promotion())->activeForTier((string) ($membership['tier_slug'] ?? 'new'));
        foreach (array_slice($promotions, 0, 3) as $promotion) {
            $bodyParts = [trim((string) ($promotion['content'] ?? ''))];

            if ((int) ($promotion['discount_percent'] ?? 0) > 0) {
                $bodyParts[] = 'Uu dai: giam ' . (int) $promotion['discount_percent'] . '%.';
            } elseif ((int) ($promotion['discount_amount'] ?? 0) > 0) {
                $bodyParts[] = 'Uu dai: giam ' . format_price((int) $promotion['discount_amount']) . '.';
            }

            if (trim((string) ($promotion['coupon_code'] ?? '')) !== '') {
                $bodyParts[] = 'Ma uu dai: ' . strtoupper(trim((string) ($promotion['coupon_code'] ?? ''))) . '.';
            }

            if (trim((string) ($promotion['expires_at'] ?? '')) !== '') {
                $bodyParts[] = 'Han su dung: ' . date('d/m/Y H:i', strtotime((string) ($promotion['expires_at'] ?? ''))) . '.';
            }

            $notes[] = [
                'title' => trim((string) ($promotion['title'] ?? 'Khuyen mai RoyalBread')),
                'body' => trim(implode(' ', array_filter($bodyParts))),
            ];
        }

        return array_slice($notes, 0, 6);
    }

    private function buildSupportMessages(array $settings, array $membership, ?array $latestOrder): array
    {
        $messages = [
            [
                'author' => 'RoyalBread',
                'time' => 'Hom nay',
                'content' => 'Hotline ' . setting($settings, 'hotline') . ' luon san sang ho tro doi mon va xac nhan don.',
            ],
            [
                'author' => 'Bep RoyalBread',
                'time' => 'Trong ngay',
                'content' => 'Cac mon banh mi chao, banh mi kep va do uong van dang dong bo dung du lieu thuc don hien tai.',
            ],
        ];

        if ($latestOrder !== null) {
            $messages[] = [
                'author' => 'He thong',
                'time' => $this->formatDate((string) ($latestOrder['created_at'] ?? '')),
                'content' => 'Don #' . (int) $latestOrder['id'] . ' da duoc ghi nhan voi hinh thuc thanh toan ' . payment_method_label((string) ($latestOrder['payment_method'] ?? 'cod')) . '.',
            ];
        }

        $messages[] = [
            'author' => 'Thanh vien',
            'time' => 'Tu dong',
            'content' => 'Ban con ' . number_format((int) $membership['remaining_points'], 0, ',', '.') . ' diem nua de cham ' . $membership['next_label'] . '.',
        ];

        return array_slice($messages, 0, 4);
    }

    private function buildPriceHighlights(array $menuGroups): array
    {
        $highlights = [];
        $mapping = [
            'Combo' => 'Combo tiet kiem dang co tren thuc don.',
            'Bánh Mì Chảo' => 'Banh mi chao nong hoi, topping day dan.',
            'Trà nhiệt đới' => 'Do uong mat lanh cho buoi sang va buoi chieu.',
        ];

        foreach ($mapping as $groupName => $description) {
            if (!isset($menuGroups[$groupName]) || $menuGroups[$groupName] === []) {
                continue;
            }

            $prices = array_map(
                static fn(array $item): int => (int) ($item['price'] ?? 0),
                $menuGroups[$groupName]
            );

            $highlights[] = [
                'title' => $groupName . ' tu ' . format_price(min($prices)),
                'description' => $description,
            ];
        }

        return $highlights;
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'completed' => 'Da giao',
            'processing' => 'Dang xu ly',
            'shipping' => 'Dang giao',
            'preparing' => 'Dang chuan bi',
            'cancelled' => 'Da huy',
            default => 'Cho xac nhan',
        };
    }

    private function statusClass(string $status): string
    {
        return match ($status) {
            'completed' => 'is-completed',
            'processing' => 'is-processing',
            'shipping' => 'is-shipping',
            'preparing' => 'is-preparing',
            'cancelled' => 'is-cancelled',
            default => 'is-pending',
        };
    }

    private function formatDate(string $dateTime): string
    {
        $timestamp = strtotime($dateTime);
        if ($timestamp === false) {
            return 'Chua cap nhat';
        }

        return date('d/m/Y', $timestamp);
    }

    private function formatDateTime(string $dateTime): string
    {
        $timestamp = strtotime($dateTime);
        if ($timestamp === false) {
            return 'Chua cap nhat';
        }

        return date('d/m/Y - H:i', $timestamp);
    }
}
