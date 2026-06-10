<?php

declare(strict_types=1);

class Chatbot
{
    private array $settings;
    private array $items;
    private array $availableItems;
    private array $groupedByName = [];
    private array $groupedBySlug = [];
    private array $itemIndex = [];
    private array $context = [];
    private array $bestSellingItems = [];

    public function __construct()
    {
        $settingModel = new Setting();
        $menuItemModel = new MenuItem();

        $this->settings = $settingModel->all();
        $this->items = $menuItemModel->all();
        $this->availableItems = array_values(array_filter(
            $this->items,
            static fn(array $item): bool => (int) ($item['is_available'] ?? 0) === 1
        ));

        foreach ($this->availableItems as $item) {
            $categoryName = (string) ($item['category_name'] ?? 'Khác');
            $categorySlug = (string) ($item['category_slug'] ?? '');
            $itemId = (int) ($item['id'] ?? 0);

            $this->groupedByName[$categoryName][] = $item;
            if ($categorySlug !== '') {
                $this->groupedBySlug[$categorySlug][] = $item;
            }
            if ($itemId > 0) {
                $this->itemIndex[$itemId] = $item;
            }
        }

        $this->bestSellingItems = $menuItemModel->bestSelling(8);
        if ($this->bestSellingItems === []) {
            $this->bestSellingItems = $menuItemModel->featured(8);
        }
        if ($this->bestSellingItems === []) {
            $this->bestSellingItems = array_slice($this->availableItems, 0, 8);
        }

        $this->context = is_array($_SESSION['chatbot_context'] ?? null)
            ? $_SESSION['chatbot_context']
            : [];
    }

    public function answer(string $message): array
    {
        $message = trim($message);
        $normalized = $this->normalize($message);

        if ($normalized === '') {
            return $this->buildResponse(
                'Chào bạn! Mình có thể tư vấn món ăn, đồ uống, phí ship, cách đặt hàng, địa chỉ quán, đơn gần đây và món bán chạy của RoyalBread.',
                ['Món bán chạy', 'Xem thực đơn', 'Phí ship', 'Địa chỉ quán']
            );
        }

        if ($this->isGreeting($normalized)) {
            return $this->buildResponse(
                'Chào bạn! Bạn muốn mình gợi ý món no bụng, đồ uống dễ chọn, báo giá hay hướng dẫn đặt món luôn?',
                ['Món bán chạy', 'Món dưới 30k', 'Đồ uống', 'Đặt hàng thế nào']
            );
        }

        if ($this->containsAny($normalized, ['dia chi', 'ban do', 'o dau', 'vi tri'])) {
            return $this->buildResponse(
                'RoyalBread ở ' . setting($this->settings, 'address') . '. Nếu cần, mình có thể chỉ luôn giờ mở cửa, hotline hoặc link ShopeeFood.',
                ['Giờ mở cửa', 'Hotline', 'Đặt qua ShopeeFood']
            );
        }

        if ($this->containsAny($normalized, ['gio mo cua', 'may gio', 'mo cua', 'dong cua'])) {
            return $this->buildResponse(
                'RoyalBread phục vụ ' . setting($this->settings, 'opening_hours') . '.',
                ['Địa chỉ quán', 'Hotline', 'Món bán chạy']
            );
        }

        if ($this->containsAny($normalized, ['hotline', 'so dien thoai', 'goi quan', 'lien he'])) {
            return $this->buildResponse(
                'Hotline của RoyalBread là ' . setting($this->settings, 'hotline') . '. Bạn có thể gọi trực tiếp để được hỗ trợ nhanh nhất.',
                ['Địa chỉ quán', 'Giờ mở cửa', 'Đặt hàng thế nào']
            );
        }

        if ($this->containsAny($normalized, ['facebook', 'fanpage'])) {
            return $this->buildResponse(
                'Bạn có thể xem fanpage RoyalBread tại https://www.facebook.com/profile.php?id=61582626099340.',
                ['Zalo', 'Hotline', 'Địa chỉ quán']
            );
        }

        if ($this->containsAny($normalized, ['zalo'])) {
            return $this->buildResponse(
                'RoyalBread có Zalo tại https://zalo.me/pc để khách liên hệ nhanh.',
                ['Facebook', 'Hotline', 'Đặt qua ShopeeFood']
            );
        }

        if ($this->containsAny($normalized, ['shopeefood', 'dat qua shopeefood'])) {
            return $this->buildResponse(
                'Bạn có thể mở gian hàng RoyalBread trên ShopeeFood tại: ' . setting($this->settings, 'shopeefood_url', '#') . '.',
                ['Xem thực đơn', 'Địa chỉ quán', 'Hotline']
            );
        }

        if ($this->containsAny($normalized, ['phuong thuc thanh toan', 'thanh toan', 'cod'])) {
            return $this->buildResponse(
                'Hiện RoyalBread hỗ trợ thanh toán khi nhận hàng (COD) và chuyển khoản trước qua MB Bank.',
                ['Chuyển khoản', 'Phí ship', 'Đặt hàng thế nào']
            );
        }

        if ($this->containsAny($normalized, ['mb bank', 'chuyen khoan', 'stk', 'so tai khoan', 'ngo van viet'])) {
            $bankTransfer = bank_transfer_details($this->settings);

            return $this->buildResponse(
                'Thông tin chuyển khoản: ' . $bankTransfer['bank_name'] . ' - STK ' . $bankTransfer['account_number'] . ' - Chủ tài khoản ' . $bankTransfer['account_holder'] . '. Nội dung: ' . $bankTransfer['transfer_note'] . '.',
                ['Thanh toán COD', 'Đặt hàng thế nào', 'Hotline']
            );
        }

        $distanceKm = $this->extractDistanceKm($normalized);
        if ($distanceKm !== null && $this->containsAny($normalized, ['ship', 'phi ship', 'giao hang', 'km'])) {
            $shippingFee = calculate_shipping_fee($distanceKm);

            return $this->buildResponse(
                'Phí ship của RoyalBread đang tính 5.000đ / 1km. Với khoảng ' . format_distance_km($distanceKm) . ', phí ship dự kiến là ' . format_price($shippingFee) . '.',
                ['Đặt hàng thế nào', 'Dùng vị trí hiện tại', 'Địa chỉ quán']
            );
        }

        if ($this->containsAny($normalized, ['phi ship', 'ship bao nhieu', 'giao hang'])) {
            return $this->buildResponse(
                'RoyalBread đang tính phí giao hàng theo 5.000đ / 1km. Khi khách nhập địa chỉ hoặc dùng vị trí hiện tại ở giỏ hàng, hệ thống sẽ tự tính khoảng cách.',
                ['Ship 3km bao nhiêu', 'Đặt hàng thế nào', 'Dùng vị trí hiện tại']
            );
        }

        if ($this->containsAny($normalized, ['them vao gio', 'dat ngay', 'mua ngay'])) {
            return $this->buildResponse(
                '"Thêm vào giỏ" dùng để gom nhiều món rồi thanh toán một lần. "Đặt ngay" sẽ chuyển thẳng sang bước điền thông tin giao hàng cho món bạn vừa chọn.',
                ['Xem thực đơn', 'Giỏ hàng của tôi', 'Phí ship']
            );
        }

        if ($this->containsAny($normalized, ['gio hang', 'cart'])) {
            return $this->answerCart();
        }

        if ($this->containsAny($normalized, ['don gan day', 'lich su mua', 'trang thai don', 'don moi nhat'])) {
            return $this->answerLatestOrder();
        }

        if ($this->containsAny($normalized, ['yeu thich', 'mon yeu thich'])) {
            return $this->answerFavorites();
        }

        if ($this->containsAny($normalized, ['review', 'danh gia', 'binh luan'])) {
            return $this->buildResponse(
                'Khách hàng đã đăng nhập có thể vào khu tài khoản để viết đánh giá, bình luận và chấm sao cho món đã mua.',
                ['Tài khoản khách hàng', 'Đơn gần đây', 'Xem thực đơn']
            );
        }

        if ($this->containsAny($normalized, ['nhan vien', 'ho tro truc tuyen', 'live chat', 'chat truc tiep'])) {
            return $this->buildResponse(
                'RoyalBread đã có khung chat hỗ trợ trực tiếp trong tài khoản khách hàng. Bạn đăng nhập, vào mục "Hỗ trợ" là có thể nhắn ngay với cửa hàng.',
                ['Tài khoản khách hàng', 'Hotline', 'Địa chỉ quán']
            );
        }

        if ($this->containsAny($normalized, ['email xac nhan', 'xac nhan don', 'gui email'])) {
            return $this->buildResponse(
                'Nếu khách điền email khi checkout, RoyalBread sẽ tự động gửi email xác nhận đơn hàng sau khi đặt thành công.',
                ['Đặt hàng thế nào', 'Thanh toán', 'Đơn gần đây']
            );
        }

        if ($this->containsAny($normalized, ['xem thuc don', 'mo thuc don', 'thuc don', 'menu'])) {
            return $this->answerMenuSummary();
        }

        if ($this->containsAny($normalized, ['mon ban chay', 'best seller', 'noi bat', 'ban chay'])) {
            return $this->answerBestSelling();
        }

        if ($this->containsAny($normalized, ['mon re nhat', 'mon duoi 30k', 'duoi 30'])) {
            return $this->answerBudget(30000);
        }

        if ($this->containsAny($normalized, ['an sang', 'bua sang'])) {
            return $this->answerCategory('banh-mi-kep', 'Bữa sáng của RoyalBread có nhiều bánh mì kẹp dễ ăn và lên món nhanh.');
        }

        if ($this->containsAny($normalized, ['an no', 'no bung', 'banh mi chao'])) {
            return $this->answerCategory('banh-mi-chao', 'Nếu muốn ăn no bụng, bánh mì chảo là lựa chọn rất hợp vì phần ăn đầy đặn và dễ gọi thêm topping.');
        }

        if ($this->containsAny($normalized, ['combo'])) {
            return $this->answerCategory('combo', 'Combo phù hợp khi bạn muốn gọi món chính kèm đồ uống cho gọn và tiết kiệm.');
        }

        if ($this->containsAny($normalized, ['topping', 'goi them'])) {
            return $this->answerCategory('topping', 'RoyalBread có nhiều topping gọi thêm để khách tuỳ chỉnh phần bánh mì chảo hoặc bánh mì kẹp.');
        }

        if ($this->containsAny($normalized, ['do uong', 'nuoc uong', 'uong gi', 'tra', 'cafe', 'ca phe'])) {
            return $this->answerDrinkCategory();
        }

        if ($this->containsAny($normalized, ['an vat', 'mi y', 'pizza', 'khoai'])) {
            return $this->answerCategory('an-vat', 'RoyalBread cũng có thêm nhóm ăn vặt để khách gọi kèm hoặc đổi vị.');
        }

        $resolvedItem = $this->resolveItemFromMessage($normalized);
        if ($resolvedItem !== null) {
            return $this->answerItem($resolvedItem);
        }

        $searchMatches = $this->findItemsByQuery($normalized, 6);
        if ($searchMatches !== []) {
            $this->rememberContext('search', null, $searchMatches);

            return $this->buildResponse(
                'Mình tìm được vài món gần nhất với nội dung bạn hỏi. Bạn xem thử nhóm này nhé.',
                ['Món bán chạy', 'Đồ uống', 'Phí ship'],
                $searchMatches
            );
        }

        return $this->buildResponse(
            'Mình chưa hiểu trọn ý bạn. Bạn có thể hỏi về món bán chạy, đồ uống, phí ship, cách đặt hàng, địa chỉ quán hoặc đơn gần đây.',
            ['Món bán chạy', 'Đồ uống', 'Đặt hàng thế nào', 'Địa chỉ quán']
        );
    }

    private function answerMenuSummary(): array
    {
        $categoryNames = array_keys($this->groupedByName);
        $summary = 'Hiện RoyalBread có ' . count($this->availableItems) . ' món trong ' . count($categoryNames) . ' nhóm: ' . implode(', ', $categoryNames) . '.';

        return $this->buildResponse(
            $summary,
            ['Bánh mì chảo', 'Bánh mì kẹp', 'Đồ uống', 'Món bán chạy']
        );
    }

    private function answerBestSelling(): array
    {
        $items = array_slice($this->bestSellingItems, 0, 6);
        $this->rememberContext('best-selling', null, $items);

        return $this->buildResponse(
            'Đây là những món bán chạy thực tế của RoyalBread, thống kê theo đơn hàng hoàn thành.',
            ['Món dưới 30k', 'Đồ uống', 'Đặt hàng thế nào'],
            $items
        );
    }

    private function answerBudget(int $budget): array
    {
        $items = array_values(array_filter(
            $this->availableItems,
            static fn(array $item): bool => (int) ($item['price'] ?? 0) <= $budget
        ));

        usort($items, static function (array $left, array $right): int {
            $priceCompare = (int) ($left['price'] ?? 0) <=> (int) ($right['price'] ?? 0);
            if ($priceCompare !== 0) {
                return $priceCompare;
            }

            return strcmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? ''));
        });

        $items = array_slice($items, 0, 6);
        $this->rememberContext('budget', null, $items);

        return $this->buildResponse(
            'Mình lọc giúp bạn các món giá dưới ' . format_price($budget) . '.',
            ['Món bán chạy', 'Đồ uống', 'Bánh mì chảo'],
            $items
        );
    }

    private function answerCategory(string $categorySlug, string $message): array
    {
        $items = $this->groupedBySlug[$categorySlug] ?? [];
        if ($items === []) {
            return $this->buildResponse(
                'Hiện mình chưa thấy nhóm món này trong dữ liệu đang bán.',
                ['Xem thực đơn', 'Món bán chạy', 'Hotline']
            );
        }

        $items = array_slice($items, 0, 6);
        $this->rememberContext('category', $categorySlug, $items);

        return $this->buildResponse(
            $message,
            ['Món bán chạy', 'Đồ uống', 'Phí ship'],
            $items
        );
    }

    private function answerDrinkCategory(): array
    {
        $items = array_merge(
            $this->groupedBySlug['tra-nhiet-doi'] ?? [],
            $this->groupedBySlug['do-uong-truyen-thong'] ?? [],
            $this->groupedBySlug['cafe'] ?? []
        );

        $items = array_slice($items, 0, 6);
        $this->rememberContext('category', 'drink', $items);

        return $this->buildResponse(
            'RoyalBread có đủ trà nhiệt đới, đồ uống truyền thống và cafe. Mình gợi ý vài món dễ chọn ngay dưới đây.',
            ['Món bán chạy', 'Món dưới 30k', 'Bánh mì kẹp'],
            $items
        );
    }

    private function answerItem(array $item): array
    {
        $companions = $this->companionItemsFor($item);
        $products = array_merge([$item], array_slice($companions, 0, 2));
        $this->rememberContext('item', (string) ($item['category_slug'] ?? ''), [$item]);

        return $this->buildResponse(
            $item['name'] . ' hiện có giá ' . format_price((int) ($item['price'] ?? 0)) . '. ' . $this->itemDescription($item),
            ['Đặt hàng thế nào', 'Đồ uống', 'Phí ship'],
            $products
        );
    }

    private function answerCart(): array
    {
        $normalCart = is_array($_SESSION['cart'] ?? null) ? $_SESSION['cart'] : [];
        $buyNowCart = is_array($_SESSION['buy_now_cart'] ?? null) ? $_SESSION['buy_now_cart'] : [];

        $products = [];
        $totalQuantity = 0;

        foreach ([$normalCart, $buyNowCart] as $cart) {
            foreach ($cart as $itemId => $quantity) {
                $itemId = (int) $itemId;
                $quantity = (int) $quantity;
                if ($itemId <= 0 || $quantity <= 0 || !isset($this->itemIndex[$itemId])) {
                    continue;
                }

                $products[] = $this->itemIndex[$itemId];
                $totalQuantity += $quantity;
            }
        }

        if ($products === []) {
            return $this->buildResponse(
                'Giỏ hàng của bạn đang trống. Mình có thể gợi ý món bán chạy hoặc đồ uống dễ chọn để bắt đầu.',
                ['Món bán chạy', 'Đồ uống', 'Xem thực đơn']
            );
        }

        $products = array_slice($this->uniqueItems($products), 0, 6);
        $this->rememberContext('cart', null, $products);

        return $this->buildResponse(
            'Hiện bạn đang có khoảng ' . $totalQuantity . ' món trong phần chọn. Bạn có thể thêm tiếp hoặc vào giỏ hàng để chốt đơn.',
            ['Đặt hàng thế nào', 'Phí ship', 'Món bán chạy'],
            $products
        );
    }

    private function answerLatestOrder(): array
    {
        $customerId = (int) ($_SESSION['customer_id'] ?? 0);
        if ($customerId <= 0) {
            return $this->buildResponse(
                'Bạn cần đăng nhập tài khoản khách hàng để xem lịch sử mua hàng và trạng thái đơn gần đây.',
                ['Đăng nhập khách hàng', 'Món bán chạy', 'Hotline']
            );
        }

        $orderModel = new Order();
        $orders = $orderModel->forCustomer($customerId, 1);
        if ($orders === []) {
            return $this->buildResponse(
                'Tài khoản này chưa có đơn hàng nào trên website.',
                ['Xem thực đơn', 'Món bán chạy', 'Đồ uống']
            );
        }

        $order = $orders[0];
        $items = $orderModel->getItems((int) $order['id']);
        $products = [];
        foreach ($items as $orderItem) {
            $menuItemId = (int) ($orderItem['menu_item_id'] ?? 0);
            if ($menuItemId > 0 && isset($this->itemIndex[$menuItemId])) {
                $products[] = $this->itemIndex[$menuItemId];
            }
        }

        return $this->buildResponse(
            'Đơn gần đây nhất của bạn là #' . (int) $order['id'] . ', trạng thái ' . $this->statusLabel((string) ($order['status'] ?? 'pending')) . ', tổng tiền ' . format_price((int) ($order['total_amount'] ?? 0)) . '.',
            ['Giỏ hàng của tôi', 'Món yêu thích', 'Hỗ trợ trực tuyến'],
            array_slice($products, 0, 4)
        );
    }

    private function answerFavorites(): array
    {
        $customerId = (int) ($_SESSION['customer_id'] ?? 0);
        if ($customerId <= 0) {
            return $this->buildResponse(
                'Bạn cần đăng nhập tài khoản khách hàng để lưu và xem món yêu thích.',
                ['Đăng nhập khách hàng', 'Món bán chạy', 'Xem thực đơn']
            );
        }

        $items = (new Favorite())->itemsForCustomer($customerId, 6);
        if ($items === []) {
            return $this->buildResponse(
                'Bạn chưa lưu món yêu thích nào. Khi vào thực đơn, chỉ cần bấm biểu tượng tim để lưu món muốn gọi lại nhanh.',
                ['Xem thực đơn', 'Món bán chạy', 'Đồ uống']
            );
        }

        return $this->buildResponse(
            'Đây là những món yêu thích bạn đã lưu trong tài khoản RoyalBread.',
            ['Xem thực đơn', 'Đơn gần đây', 'Đồ uống'],
            $items
        );
    }

    private function resolveItemFromMessage(string $normalizedMessage): ?array
    {
        $contextItemIds = $this->context['item_ids'] ?? [];
        if ($this->containsAny($normalizedMessage, ['mon nay', 'cai nay', 'loai nay']) && $contextItemIds !== []) {
            $firstItemId = (int) $contextItemIds[0];
            return $this->itemIndex[$firstItemId] ?? null;
        }

        $matches = $this->findItemsByQuery($normalizedMessage, 1);
        return $matches[0] ?? null;
    }

    private function findItemsByQuery(string $normalizedQuery, int $limit = 5): array
    {
        $tokens = array_values(array_filter(explode(' ', $normalizedQuery)));
        $scored = [];

        foreach ($this->availableItems as $item) {
            $name = $this->normalize((string) ($item['name'] ?? ''));
            $description = $this->normalize((string) ($item['description'] ?? ''));
            $category = $this->normalize((string) ($item['category_name'] ?? ''));
            $score = 0;

            if ($name === $normalizedQuery) {
                $score += 120;
            }
            if (str_contains($name, $normalizedQuery) || str_contains($normalizedQuery, $name)) {
                $score += 60;
            }
            if (str_contains($description, $normalizedQuery)) {
                $score += 18;
            }
            if (str_contains($category, $normalizedQuery)) {
                $score += 14;
            }

            foreach ($tokens as $token) {
                if (mb_strlen($token, 'UTF-8') < 2) {
                    continue;
                }

                if (str_contains($name, $token)) {
                    $score += 12;
                }
                if (str_contains($description, $token)) {
                    $score += 4;
                }
            }

            if ($score > 0) {
                $scored[] = [
                    'score' => $score,
                    'item' => $item,
                ];
            }
        }

        usort($scored, static function (array $left, array $right): int {
            if ($left['score'] === $right['score']) {
                return (int) ($left['item']['price'] ?? 0) <=> (int) ($right['item']['price'] ?? 0);
            }

            return $right['score'] <=> $left['score'];
        });

        return array_map(
            static fn(array $row): array => $row['item'],
            array_slice($scored, 0, $limit)
        );
    }

    private function companionItemsFor(array $item): array
    {
        $categorySlug = (string) ($item['category_slug'] ?? '');

        if ($categorySlug === 'banh-mi-chao') {
            return array_merge(
                array_slice($this->groupedBySlug['topping'] ?? [], 0, 3),
                array_slice($this->groupedBySlug['tra-nhiet-doi'] ?? [], 0, 2)
            );
        }

        if ($categorySlug === 'banh-mi-kep') {
            return array_merge(
                array_slice($this->groupedBySlug['tra-nhiet-doi'] ?? [], 0, 2),
                array_slice($this->groupedBySlug['an-vat'] ?? [], 0, 2)
            );
        }

        if ($categorySlug === 'combo') {
            return array_slice($this->groupedBySlug['tra-nhiet-doi'] ?? [], 0, 3);
        }

        return array_slice($this->bestSellingItems, 0, 3);
    }

    private function itemDescription(array $item): string
    {
        $description = trim((string) ($item['description'] ?? ''));
        if ($description === '') {
            return 'Món này đang có mặt trên thực đơn hiện tại của quán.';
        }

        return $description;
    }

    private function uniqueItems(array $items): array
    {
        $seen = [];
        $unique = [];

        foreach ($items as $item) {
            $itemId = (int) ($item['id'] ?? 0);
            if ($itemId <= 0 || isset($seen[$itemId])) {
                continue;
            }

            $seen[$itemId] = true;
            $unique[] = $item;
        }

        return $unique;
    }

    private function rememberContext(string $topic, ?string $category, array $items): void
    {
        $this->context = [
            'topic' => $topic,
            'category' => $category,
            'item_ids' => array_values(array_map(
                static fn(array $item): int => (int) ($item['id'] ?? 0),
                array_slice($items, 0, 6)
            )),
            'updated_at' => time(),
        ];
    }

    private function buildResponse(string $answer, array $suggestions = [], array $products = []): array
    {
        $_SESSION['chatbot_context'] = $this->context;

        $serializedProducts = array_map(static function (array $item): array {
            return [
                'id' => (int) ($item['id'] ?? 0),
                'name' => (string) ($item['name'] ?? ''),
                'category_name' => (string) ($item['category_name'] ?? ''),
                'price' => format_price((int) ($item['price'] ?? 0)),
                'image_url' => media_url((string) ($item['image_url'] ?? '')),
                'description' => trim((string) ($item['description'] ?? '')),
            ];
        }, array_slice($this->uniqueItems($products), 0, 6));

        return [
            'answer' => $answer,
            'suggestions' => array_values(array_slice(array_unique(array_filter($suggestions)), 0, 5)),
            'products' => $serializedProducts,
        ];
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'completed' => 'đã hoàn thành',
            'processing' => 'đang xử lý',
            'cancelled' => 'đã hủy',
            default => 'đang chờ',
        };
    }

    private function extractDistanceKm(string $normalizedMessage): ?float
    {
        if (!preg_match('/(\d+(?:[.,]\d+)?)\s*km/u', $normalizedMessage, $matches)) {
            return null;
        }

        return normalize_distance_km($matches[1]);
    }

    private function isGreeting(string $normalized): bool
    {
        foreach (['xin chao', 'chao', 'hello', 'hi', 'alo', 'ad oi'] as $greeting) {
            if ($normalized === $greeting || str_starts_with($normalized, $greeting . ' ')) {
                return true;
            }
        }

        return false;
    }

    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $this->normalize($needle))) {
                return true;
            }
        }

        return false;
    }

    private function normalize(string $text): string
    {
        $text = trim(mb_strtolower($text, 'UTF-8'));

        $map = [
            'à' => 'a', 'á' => 'a', 'ạ' => 'a', 'ả' => 'a', 'ã' => 'a',
            'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ậ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a',
            'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ặ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a',
            'è' => 'e', 'é' => 'e', 'ẹ' => 'e', 'ẻ' => 'e', 'ẽ' => 'e',
            'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ệ' => 'e', 'ể' => 'e', 'ễ' => 'e',
            'ì' => 'i', 'í' => 'i', 'ị' => 'i', 'ỉ' => 'i', 'ĩ' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ọ' => 'o', 'ỏ' => 'o', 'õ' => 'o',
            'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ộ' => 'o', 'ổ' => 'o', 'ỗ' => 'o',
            'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ợ' => 'o', 'ở' => 'o', 'ỡ' => 'o',
            'ù' => 'u', 'ú' => 'u', 'ụ' => 'u', 'ủ' => 'u', 'ũ' => 'u',
            'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ự' => 'u', 'ử' => 'u', 'ữ' => 'u',
            'ỳ' => 'y', 'ý' => 'y', 'ỵ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y',
            'đ' => 'd',
        ];

        $text = strtr($text, $map);
        $text = preg_replace('/[^a-z0-9\s]/u', ' ', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }
}
