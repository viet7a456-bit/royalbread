<?php

declare(strict_types=1);

class CartController extends Controller
{
    private const SHIPPING_RATE_PER_KM = 5000;
    private const ALLOWED_PAYMENT_METHODS = ['cod', 'bank_transfer', 'online_qr'];
    private const DEFAULT_CART_SESSION = 'cart';
    private const BUY_NOW_CART_SESSION = 'buy_now_cart';

    private function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    private function resolveCartMode(string|null $mode): string
    {
        return trim((string) $mode) === 'buy-now' ? 'buy-now' : 'cart';
    }

    private function sessionKeyForMode(string $mode): string
    {
        return $mode === 'buy-now' ? self::BUY_NOW_CART_SESSION : self::DEFAULT_CART_SESSION;
    }

    private function cartRedirectPath(string $mode): string
    {
        return $mode === 'buy-now' ? 'cart?mode=buy-now' : 'cart';
    }

    private function buildCartSnapshot(MenuItem $menuItemModel, string $sessionKey = self::DEFAULT_CART_SESSION): array
    {
        $sessionCart = $_SESSION[$sessionKey] ?? [];
        $sanitizedCart = [];
        $cartItems = [];
        $subtotal = 0;
        $removedCount = 0;

        foreach ($sessionCart as $id => $quantity) {
            $itemId = (int) $id;
            $itemQuantity = max(0, min(99, (int) $quantity));

            if ($itemId <= 0 || $itemQuantity <= 0) {
                $removedCount++;
                continue;
            }

            $item = $menuItemModel->findAvailableById($itemId);
            if ($item === null) {
                $removedCount++;
                continue;
            }

            $item['quantity'] = $itemQuantity;
            $item['subtotal'] = (int) $item['price'] * $itemQuantity;
            $subtotal += $item['subtotal'];
            $sanitizedCart[$itemId] = $itemQuantity;
            $cartItems[] = $item;
        }

        if ($sessionCart !== $sanitizedCart) {
            if ($sanitizedCart === []) {
                unset($_SESSION[$sessionKey]);
            } else {
                $_SESSION[$sessionKey] = $sanitizedCart;
            }
        }

        return [
            'cart' => $sanitizedCart,
            'items' => $cartItems,
            'subtotal' => $subtotal,
            'removed_count' => $removedCount,
        ];
    }

    private function settingValuesByPrefix(array $settings, string $prefix): array
    {
        $matchedValues = [];

        foreach ($settings as $key => $value) {
            if (preg_match('/^' . preg_quote($prefix, '/') . '(\d+)$/', (string) $key, $matches) !== 1) {
                continue;
            }

            $matchedValues[(int) $matches[1]] = trim((string) $value);
        }

        ksort($matchedValues);

        return array_values(array_filter($matchedValues, static fn(string $value): bool => $value !== ''));
    }

    private function selectedCartRecommendationItems(MenuItem $menuItemModel, array $settings, int $limit = 4): array
    {
        $selectedIds = array_map('intval', $this->settingValuesByPrefix($settings, 'cart_recommend_item_'));
        $selectedIds = array_values(array_unique(array_filter($selectedIds, static fn(int $id): bool => $id > 0)));

        if ($selectedIds !== []) {
            return $menuItemModel->findAvailableByIds($selectedIds);
        }

        $items = [];
        $selectedItemIds = [];

        foreach ($menuItemModel->featured(max($limit, 8)) as $fallbackItem) {
            if (count($items) >= $limit) {
                break;
            }

            $fallbackId = (int) ($fallbackItem['id'] ?? 0);
            if ($fallbackId <= 0 || in_array($fallbackId, $selectedItemIds, true)) {
                continue;
            }

            $items[] = $fallbackItem;
            $selectedItemIds[] = $fallbackId;
        }

        return array_slice($items, 0, $limit);
    }

    private function resolveDeliveryDistance(
        string $address,
        string|null $manualDistance,
        float|null $latitude = null,
        float|null $longitude = null
    ): array {
        $estimator = new DeliveryEstimator();
        $estimate = ($latitude !== null && $longitude !== null)
            ? $estimator->estimateFromCoordinates($latitude, $longitude, $address)
            : $estimator->estimateFromCustomerAddress($address);

        if (($estimate['success'] ?? false) === true) {
            return [
                'distance_km' => normalize_distance_km($estimate['distance_km'] ?? 0),
                'message' => (string) ($estimate['message'] ?? ''),
                'source' => (string) ($estimate['source'] ?? 'route'),
            ];
        }

        $fallbackDistance = normalize_distance_km($manualDistance);
        if ($fallbackDistance > 0) {
            return [
                'distance_km' => $fallbackDistance,
                'message' => 'Dang dung khoang cach nhap tay vi he thong chua xac dinh duoc dia chi tu dong.',
                'source' => 'manual',
            ];
        }

        return [
            'distance_km' => 0.0,
            'message' => (string) ($estimate['message'] ?? 'RoyalBread chua tu tinh duoc khoang cach cho dia chi nay.'),
            'source' => 'unavailable',
        ];
    }

    private function readFloat(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $normalized = str_replace(',', '.', trim((string) $value));
        if ($normalized === '' || !is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }

    private function currentCustomerStats(): ?array
    {
        if (empty($_SESSION['customer_id'])) {
            return null;
        }

        return (new Customer())->findWithStatsById((int) $_SESSION['customer_id']);
    }

    private function promotionPreview(?array $customer, int $subtotal): ?array
    {
        if ($customer === null || $subtotal <= 0) {
            return null;
        }

        $promotion = (new Promotion())->bestForCustomerTier(
            (string) ($customer['membership_tier_slug'] ?? 'new'),
            $subtotal
        );

        if ($promotion === null) {
            return null;
        }

        return [
            'promotion' => $promotion,
            'discount_amount' => (int) ($promotion['computed_discount'] ?? 0),
        ];
    }

    private function paymentReferenceForOrder(int $orderId, string $phone): string
    {
        $phoneDigits = preg_replace('/\D+/', '', $phone) ?? '';
        $phoneSuffix = $phoneDigits !== '' ? substr($phoneDigits, -4) : 'RB';

        return strtoupper('RB' . $orderId . ' ' . $phoneSuffix);
    }

    private function paymentStatusForMethod(string $paymentMethod): string
    {
        return $paymentMethod === 'cod' ? 'unpaid' : 'pending_confirmation';
    }

    public function index(): void
    {
        $cartMode = $this->resolveCartMode($_GET['mode'] ?? 'cart');
        $cartSessionKey = $this->sessionKeyForMode($cartMode);
        $menuItemModel = new MenuItem();
        $cartSnapshot = $this->buildCartSnapshot($menuItemModel, $cartSessionKey);

        if ($cartSnapshot['removed_count'] > 0) {
            Session::flash('error', 'Mot so mon trong phan chon hien khong con ban nen da duoc go ra.');
        }

        $cartItems = $cartSnapshot['items'];
        $subtotal = $cartSnapshot['subtotal'];

        $distanceKm = normalize_distance_km(
            old('distance_km', (string) ($_SESSION['cart_distance_km'] ?? '0'))
        );
        $shippingFee = $subtotal > 0 ? calculate_shipping_fee($distanceKm, self::SHIPPING_RATE_PER_KM) : 0;
        $total = $subtotal + $shippingFee;

        $settingModel = new Setting();
        $settings = $settingModel->all();

        $suggestedToppings = $menuItemModel->availableByCategoryNames(['Topping'], [], null);
        $suggestedDrinks = $menuItemModel->availableByCategorySlugs(
            ['tra-nhiet-doi', 'do-uong-truyen-thong', 'cafe'],
            [],
            null
        );

        $customerStats = $this->currentCustomerStats();
        $promotionPreview = $this->promotionPreview($customerStats, $subtotal);
        $discountPreview = (int) ($promotionPreview['discount_amount'] ?? 0);

        $this->render('cart/index', [
            'settings' => $settings,
            'cartItems' => $cartItems,
            'subtotal' => $subtotal,
            'shippingFee' => $shippingFee,
            'total' => $total,
            'distanceKm' => $distanceKm,
            'shippingRatePerKm' => self::SHIPPING_RATE_PER_KM,
            'featuredItems' => $this->selectedCartRecommendationItems($menuItemModel, $settings),
            'customer' => $customerStats,
            'promotionPreview' => $promotionPreview,
            'discountPreview' => $discountPreview,
            'totalAfterDiscount' => max(0, $total - $discountPreview),
            'suggestedToppings' => $suggestedToppings,
            'suggestedDrinks' => $suggestedDrinks,
            'cartMode' => $cartMode,
            'isBuyNowMode' => $cartMode === 'buy-now',
        ]);
    }

    public function payment(): void
    {
        $orderId = (int) ($_GET['order'] ?? 0);
        if ($orderId <= 0) {
            Session::flash('error', 'Khong tim thay don hang thanh toan.');
            $this->redirectTo('cart');
        }

        $orderModel = new Order();
        $order = $orderModel->findById($orderId);
        if ($order === null) {
            Session::flash('error', 'Don hang khong ton tai hoac da bi xoa.');
            $this->redirectTo('cart');
        }

        if (!in_array((string) ($order['payment_method'] ?? ''), ['bank_transfer', 'online_qr'], true)) {
            $this->redirectTo('');
        }

        $settings = (new Setting())->all();
        $reference = trim((string) ($order['payment_reference'] ?? ''));
        if ($reference === '') {
            $reference = $this->paymentReferenceForOrder((int) $order['id'], (string) ($order['phone'] ?? ''));
        }

        $this->render('cart/payment', [
            'settings' => $settings,
            'order' => $order,
            'orderItems' => $orderModel->getItems((int) $order['id']),
            'paymentReference' => $reference,
            'paymentMethodLabel' => payment_method_label((string) ($order['payment_method'] ?? 'cod')),
            'paymentStatusLabel' => payment_status_label((string) ($order['payment_status'] ?? 'unpaid')),
            'qrImageUrl' => build_vietqr_url($settings, $reference, (int) ($order['total_amount'] ?? 0)),
        ]);
    }

    public function confirmPayment(): void
    {
        $orderId = (int) ($_POST['order_id'] ?? 0);
        if ($orderId <= 0) {
            Session::flash('error', 'Khong tim thay don hang can xac nhan thanh toan.');
            $this->redirectTo('cart');
        }

        $orderModel = new Order();
        $order = $orderModel->findById($orderId);
        if ($order === null) {
            Session::flash('error', 'Don hang khong ton tai hoac da bi xoa.');
            $this->redirectTo('cart');
        }

        $orderModel->updatePaymentStatus($orderId, 'paid');
        Session::flash('success', 'RoyalBread da ghi nhan thanh toan online cua ban va se doi chieu som.');
        $this->redirectTo('cart/payment?order=' . $orderId);
    }

    public function add(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $quantity = max(1, min(99, (int) ($_POST['quantity'] ?? 1)));
        $redirectPath = ltrim(trim((string) ($_POST['redirect_to'] ?? 'menu')), '/');

        if (str_contains($redirectPath, '://')) {
            $redirectPath = 'menu';
        }

        $menuItemModel = new MenuItem();
        $item = $id > 0 ? $menuItemModel->findAvailableById($id) : null;

        if ($item === null) {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode([
                    'success' => false,
                    'message' => 'Mon nay hien khong con ban hoac khong ton tai.',
                    'csrf_token' => Session::csrfToken(),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }

            Session::flash('error', 'Mon nay hien khong con ban hoac khong ton tai.');
            $this->redirectTo($redirectPath);
        }

        if (!isset($_SESSION[self::DEFAULT_CART_SESSION]) || !is_array($_SESSION[self::DEFAULT_CART_SESSION])) {
            $_SESSION[self::DEFAULT_CART_SESSION] = [];
        }

        $currentQuantity = (int) ($_SESSION[self::DEFAULT_CART_SESSION][$id] ?? 0);
        $_SESSION[self::DEFAULT_CART_SESSION][$id] = min(99, $currentQuantity + $quantity);

        Session::flash('success', 'Da them mon vao gio hang.');

        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'success' => true,
                'cart_count' => array_sum($_SESSION[self::DEFAULT_CART_SESSION] ?? []),
                'csrf_token' => Session::csrfToken(),
                'redirect' => url($redirectPath),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $this->redirectTo($redirectPath);
    }

    public function buyNow(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $quantity = max(1, min(99, (int) ($_POST['quantity'] ?? 1)));
        $menuItemModel = new MenuItem();
        $item = $id > 0 ? $menuItemModel->findAvailableById($id) : null;

        if ($item === null) {
            Session::flash('error', 'Mon nay hien khong con ban hoac khong ton tai.');
            $this->redirectTo('menu');
        }

        $_SESSION[self::BUY_NOW_CART_SESSION] = [$id => $quantity];
        Session::flash('success', 'RoyalBread da mo luong dat hang ngay cho mon ban chon.');

        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'success' => true,
                'redirect' => url('cart?mode=buy-now'),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $this->redirectTo('cart?mode=buy-now');
    }

    public function update(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $quantity = max(0, min(99, (int) ($_POST['quantity'] ?? 0)));
        $cartMode = $this->resolveCartMode($_POST['mode'] ?? 'cart');
        $sessionKey = $this->sessionKeyForMode($cartMode);
        $redirectPath = $this->cartRedirectPath($cartMode);

        if ($id <= 0) {
            Session::flash('error', 'Khong tim thay mon can cap nhat.');
            $this->redirectTo($redirectPath);
        }

        if ($quantity === 0) {
            unset($_SESSION[$sessionKey][$id]);
            $this->redirectTo($redirectPath);
        }

        $menuItemModel = new MenuItem();
        if ($menuItemModel->findAvailableById($id) === null) {
            unset($_SESSION[$sessionKey][$id]);
            Session::flash('error', 'Mon nay hien khong con ban nen da duoc go khoi danh sach chon.');
            $this->redirectTo($redirectPath);
        }

        $_SESSION[$sessionKey][$id] = $quantity;

        $this->redirectTo($redirectPath);
    }

    public function remove(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $cartMode = $this->resolveCartMode($_POST['mode'] ?? 'cart');
        $sessionKey = $this->sessionKeyForMode($cartMode);

        if ($id > 0 && isset($_SESSION[$sessionKey][$id])) {
            unset($_SESSION[$sessionKey][$id]);
        }

        $this->redirectTo($this->cartRedirectPath($cartMode));
    }

    public function checkout(): void
    {
        $cartMode = $this->resolveCartMode($_POST['checkout_mode'] ?? 'cart');
        $sessionKey = $this->sessionKeyForMode($cartMode);
        $redirectPath = $this->cartRedirectPath($cartMode);

        $menuItemModel = new MenuItem();
        $cartSnapshot = $this->buildCartSnapshot($menuItemModel, $sessionKey);
        $cartItems = $cartSnapshot['items'];

        if ($cartItems === []) {
            Session::flash('error', 'Danh sach dat mon dang trong.');
            $this->redirectTo($redirectPath);
        }

        $customerName = trim((string) ($_POST['customer_name'] ?? ''));
        $customerEmail = trim((string) ($_POST['customer_email'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $address = trim((string) ($_POST['address'] ?? ''));
        $addressDetail = trim((string) ($_POST['address_detail'] ?? ''));
        $resolvedAddress = trim((string) ($_POST['resolved_address'] ?? ''));
        $deliveryLatitude = $this->readFloat($_POST['delivery_lat'] ?? null);
        $deliveryLongitude = $this->readFloat($_POST['delivery_lon'] ?? null);
        $note = trim((string) ($_POST['note'] ?? ''));
        $paymentMethod = trim((string) ($_POST['payment_method'] ?? 'cod'));

        Session::setOld($_POST, 'checkout');

        if ($cartSnapshot['removed_count'] > 0) {
            Session::flash('error', 'Mot so mon trong phan chon da het hoac tam an. Vui long kiem tra lai truoc khi dat.');
            $this->redirectTo($redirectPath);
        }

        if ($customerName === '' || $phone === '' || $address === '') {
            Session::flash('error', 'Vui long dien day du thong tin giao hang.');
            $this->redirectTo($redirectPath);
        }

        if ($customerEmail !== '' && filter_var($customerEmail, FILTER_VALIDATE_EMAIL) === false) {
            Session::flash('error', 'Email nhan xac nhan don hang chua dung dinh dang.');
            $this->redirectTo($redirectPath);
        }

        if (!in_array($paymentMethod, self::ALLOWED_PAYMENT_METHODS, true)) {
            Session::flash('error', 'Phuong thuc thanh toan khong hop le.');
            $this->redirectTo($redirectPath);
        }

        $hasCoordinates = ($deliveryLatitude !== null && $deliveryLongitude !== null);
        $hasResolvedAddress = ($resolvedAddress !== '');

        if (!$hasCoordinates && !$hasResolvedAddress) {
            Session::flash('error', 'Vui long chon dia chi tu goi y hoac dung vi tri hien tai de RoyalBread tinh duoc phi giao hang chinh xac.');
            $this->redirectTo($redirectPath);
        }

        $fullAddress = $address;
        if ($addressDetail !== '') {
            $fullAddress = $addressDetail . ', ' . $address;
        }

        $addressForDistance = $resolvedAddress !== '' ? $resolvedAddress : $address;
        $delivery = $this->resolveDeliveryDistance(
            $addressForDistance,
            null,
            $deliveryLatitude,
            $deliveryLongitude
        );
        $distanceKm = normalize_distance_km($delivery['distance_km'] ?? 0);
        $_SESSION['cart_distance_km'] = $distanceKm;

        if ($distanceKm <= 0) {
            Session::flash('error', 'RoyalBread chua tinh duoc khoang cach giao hang. Vui long chon lai dia chi tu goi y hoac dung vi tri hien tai.');
            $this->redirectTo($redirectPath);
        }

        $subtotal = 0;
        $itemsToInsert = [];

        foreach ($cartItems as $item) {
            $quantity = (int) ($item['quantity'] ?? 0);
            if ($quantity <= 0) {
                continue;
            }

            $subtotal += (int) ($item['subtotal'] ?? 0);
            $itemsToInsert[] = [
                'menu_item_id' => (int) $item['id'],
                'menu_item_name' => (string) $item['name'],
                'menu_item_image_url' => (string) ($item['image_url'] ?? ''),
                'quantity' => $quantity,
                'price' => (int) $item['price'],
            ];
        }

        if ($itemsToInsert === []) {
            Session::flash('error', 'Khong con mon hop le de tao don hang.');
            $this->redirectTo($redirectPath);
        }

        $shippingFee = calculate_shipping_fee($distanceKm, self::SHIPPING_RATE_PER_KM);
        $promotionDiscount = 0;
        $appliedPromotion = null;
        $customerStats = $this->currentCustomerStats();
        if ($customerStats !== null) {
            $promotionPreview = $this->promotionPreview($customerStats, $subtotal);
            if ($promotionPreview !== null) {
                $promotionDiscount = (int) ($promotionPreview['discount_amount'] ?? 0);
                $appliedPromotion = $promotionPreview['promotion'] ?? null;
            }
        }

        $total = max(0, ($subtotal + $shippingFee) - $promotionDiscount);

        $shippingMeta = sprintf(
            'Khoang cach giao hang: %s | Phi ship: %s | Cach tinh: %s',
            format_distance_km($distanceKm),
            format_price($shippingFee),
            $delivery['source'] === 'manual' ? 'khach nhap tay' : 'tu dong theo dia chi'
        );
        if ($promotionDiscount > 0 && $appliedPromotion !== null) {
            $shippingMeta .= ' | Uu dai ap dung: '
                . trim((string) ($appliedPromotion['title'] ?? 'Khuyen mai'))
                . ' (-' . format_price($promotionDiscount) . ')';
        }
        if ($resolvedAddress !== '' && $resolvedAddress !== $address) {
            $shippingMeta .= ' | Diem nhan da chon: ' . $resolvedAddress;
        }
        $orderNote = $note !== '' ? $note . PHP_EOL . $shippingMeta : $shippingMeta;

        $customerId = null;
        if (!empty($_SESSION['customer_id'])) {
            $customer = (new Customer())->findById((int) $_SESSION['customer_id']);
            if ($customer !== null) {
                $customerId = (int) $customer['id'];
                if ($customerEmail === '' && trim((string) ($customer['email'] ?? '')) !== '') {
                    $customerEmail = trim((string) $customer['email']);
                }
            }
        }

        $orderModel = new Order();
        $orderItemModel = new OrderItem();
        $db = Database::connection();

        try {
            $db->beginTransaction();

            $orderId = $orderModel->create([
                'customer_id' => $customerId,
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'phone' => $phone,
                'address' => $fullAddress,
                'note' => $orderNote,
                'total_amount' => $total,
                'payment_method' => $paymentMethod,
                'discount_amount' => $promotionDiscount,
                'payment_status' => $this->paymentStatusForMethod($paymentMethod),
            ]);

            $paymentReference = $this->paymentReferenceForOrder($orderId, $phone);
            $orderModel->updatePaymentReference($orderId, $paymentReference);

            foreach ($itemsToInsert as $item) {
                $item['order_id'] = $orderId;
                $orderItemModel->create($item);
            }

            $db->commit();
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            Session::flash('error', 'Khong the tao don hang luc nay. Vui long thu lai sau it phut.');
            $this->redirectTo($redirectPath);
        }

        $createdOrder = $orderModel->findById($orderId);
        if ($createdOrder !== null) {
            $createdOrder['payment_method_label'] = payment_method_label((string) ($createdOrder['payment_method'] ?? 'cod'));
            (new OrderMailer())->sendOrderConfirmation($createdOrder, $itemsToInsert);
        }

        unset($_SESSION[$sessionKey], $_SESSION['cart_distance_km']);
        Session::clearOld('checkout');

        Session::flash(
            'success',
            $cartMode === 'buy-now'
                ? 'Dat hang ngay thanh cong! RoyalBread se lien he xac nhan som.'
                : 'Dat hang thanh cong! RoyalBread se lien he xac nhan som.'
        );

        if (in_array($paymentMethod, ['bank_transfer', 'online_qr'], true)) {
            $this->redirectTo('cart/payment?order=' . $orderId);
        }

        $this->redirectTo('');
    }
}
