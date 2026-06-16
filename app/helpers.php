<?php

declare(strict_types=1);

function app_url_base(): string
{
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $base = str_replace('\\', '/', dirname($scriptName));

    if ($base === '/' || $base === '.') {
        return '';
    }

    return rtrim($base, '/');
}

function url(string $path = ''): string
{
    $base = app_url_base();
    $path = trim($path, '/');

    if ($path === '') {
        return $base !== '' ? $base : '/';
    }

    return ($base !== '' ? $base : '') . '/' . $path;
}

function site_origin(): string
{
    $scheme = Environment::isHttps() ? 'https' : 'http';
    $host = trim((string) ($_SERVER['HTTP_HOST'] ?? Environment::currentHost() ?? 'localhost'));

    if ($host === '') {
        $host = 'localhost';
    }

    return $scheme . '://' . $host;
}

function full_url(string $path = ''): string
{
    $relativeUrl = url($path);
    if (
        str_starts_with($relativeUrl, 'http://') ||
        str_starts_with($relativeUrl, 'https://')
    ) {
        return $relativeUrl;
    }

    return site_origin() . $relativeUrl;
}

function current_full_url(bool $includeQuery = false): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    if (!$includeQuery) {
        $uri = parse_url($uri, PHP_URL_PATH) ?: '/';
    }

    return site_origin() . $uri;
}

function asset(string $path): string
{
    return url($path);
}

function media_url(string|null $path): string
{
    $value = trim((string) $path);
    if ($value === '') {
        return '';
    }

    $base = app_url_base();
    if ($base !== '' && str_starts_with($value, '/assets/')) {
        return url(ltrim($value, '/'));
    }

    if (str_starts_with($value, '/')) {
        $assetsPosition = strpos($value, '/assets/');
        if ($assetsPosition !== false) {
            return url(ltrim(substr($value, $assetsPosition + 1), '/'));
        }
    }

    if (
        str_starts_with($value, 'http://') ||
        str_starts_with($value, 'https://') ||
        str_starts_with($value, 'data:') ||
        str_starts_with($value, '/')
    ) {
        return $value;
    }

    return asset(ltrim($value, '/'));
}

function e(string|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function format_price(int|float|string $amount): string
{
    return number_format((float) $amount, 0, ',', '.') . 'đ';
}

function setting(array $settings, string $key, string $default = ''): string
{
    return $settings[$key] ?? $default;
}

function old(string $key, string $default = '', string $form = '_default'): string
{
    return Session::old($key, $default, $form);
}

function current_path(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = app_url_base();

    if ($base !== '' && str_starts_with($uri, $base)) {
        $uri = substr($uri, strlen($base));
    }

    return trim($uri, '/');
}

function is_current(string $path): bool
{
    return trim($path, '/') === current_path();
}

function normalize_distance_km(float|int|string|null $distance): float
{
    $normalized = str_replace(',', '.', trim((string) $distance));
    if ($normalized === '' || !is_numeric($normalized)) {
        return 0.0;
    }

    return max(0.0, ceil(round((float) $normalized, 4) * 10) / 10);
}

function calculate_shipping_fee(float|int|string|null $distanceKm, int $ratePerKm = 5000): int
{
    $distance = normalize_distance_km($distanceKm);
    return (int) round($distance * $ratePerKm);
}

function format_distance_km(float|int|string|null $distanceKm): string
{
    $distance = normalize_distance_km($distanceKm);
    $formatted = number_format($distance, 1, ',', '.');
    $formatted = rtrim(rtrim($formatted, '0'), ',');

    return $formatted . ' km';
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf_token" value="' . e(Session::csrfToken()) . '">';
}

function payment_method_label(string $paymentMethod): string
{
    return match (trim($paymentMethod)) {
        'bank_transfer', 'banking' => 'Chuyen khoan ngan hang',
        'online_qr' => 'Thanh toan online qua QR',
        default => 'Thanh toan khi nhan hang (COD)',
    };
}

function payment_status_label(string $status): string
{
    return match (trim($status)) {
        'pending_confirmation' => 'Cho xac nhan thanh toan',
        'paid' => 'Da thanh toan',
        'refunded' => 'Da hoan tien',
        default => 'Chua thanh toan',
    };
}

function payment_status_class(string $status): string
{
    return match (trim($status)) {
        'pending_confirmation' => 'is-processing',
        'paid' => 'is-completed',
        'refunded' => 'is-cancelled',
        default => 'is-pending',
    };
}

function promotion_target_label(string $targetTier): string
{
    return match (trim($targetTier)) {
        'new' => 'Thanh vien moi',
        'silver' => 'Thanh vien Bac',
        'gold' => 'Thanh vien Vang',
        default => 'Tat ca khach hang',
    };
}

function bank_transfer_details(array $settings = []): array
{
    return [
        'bank_name' => setting($settings, 'bank_name', 'MB Bank'),
        'account_number' => setting($settings, 'bank_account_number', '0394348389'),
        'account_holder' => setting($settings, 'bank_account_holder', 'Ngo Van Viet'),
        'transfer_note' => setting($settings, 'bank_transfer_note', 'So dien thoai - Ten khach hang'),
        'bank_bin' => setting($settings, 'bank_bin', 'mbbank'),
    ];
}

function is_ajax(): bool
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function membership_tier_meta(int $orderCount, int $totalSpent): array
{
    $points = max($orderCount * 120, (int) floor($totalSpent / 10000));

    $tierLabel = 'Thanh vien moi';
    $tierSlug = 'new';
    $nextTarget = 500;
    $nextLabel = 'moc Bac';

    if ($points >= 1200) {
        $tierLabel = 'Thanh vien Vang';
        $tierSlug = 'gold';
        $nextTarget = 2000;
        $nextLabel = 'moc Kim cuong';
    } elseif ($points >= 500) {
        $tierLabel = 'Thanh vien Bac';
        $tierSlug = 'silver';
        $nextTarget = 1200;
        $nextLabel = 'moc Vang';
    }

    $remainingPoints = max(0, $nextTarget - $points);
    $progress = $nextTarget > 0 ? min(100, (int) round(($points / $nextTarget) * 100)) : 0;

    return [
        'tier' => $tierLabel,
        'tier_slug' => $tierSlug,
        'points' => $points,
        'next_target' => $nextTarget,
        'next_label' => $nextLabel,
        'progress' => $progress,
        'order_count' => $orderCount,
        'total_spent' => $totalSpent,
        'remaining_points' => $remainingPoints,
    ];
}

function ascii_text(string $value): string
{
    $map = [
        'à'=>'a', 'á'=>'a', 'ả'=>'a', 'ã'=>'a', 'ạ'=>'a', 'â'=>'a', 'ầ'=>'a', 'ấ'=>'a', 'ẩ'=>'a', 'ẫ'=>'a', 'ậ'=>'a', 'ă'=>'a', 'ằ'=>'a', 'ắ'=>'a', 'ẳ'=>'a', 'ẵ'=>'a', 'ặ'=>'a',
        'À'=>'A', 'Á'=>'A', 'Ả'=>'A', 'Ã'=>'A', 'Ạ'=>'A', 'Â'=>'A', 'Ầ'=>'A', 'Ấ'=>'A', 'Ẩ'=>'A', 'Ẫ'=>'A', 'Ậ'=>'A', 'Ă'=>'A', 'Ằ'=>'A', 'Ắ'=>'A', 'Ẳ'=>'A', 'Ẵ'=>'A', 'Ặ'=>'A',
        'đ'=>'d', 'Đ'=>'D',
        'è'=>'e', 'é'=>'e', 'ẻ'=>'e', 'ẽ'=>'e', 'ẹ'=>'e', 'ê'=>'e', 'ề'=>'e', 'ế'=>'e', 'ể'=>'e', 'ễ'=>'e', 'ệ'=>'e',
        'È'=>'E', 'É'=>'E', 'Ẻ'=>'E', 'Ẽ'=>'E', 'Ẹ'=>'E', 'Ê'=>'E', 'Ề'=>'E', 'Ế'=>'E', 'Ể'=>'E', 'Ễ'=>'E', 'Ệ'=>'E',
        'ì'=>'i', 'í'=>'i', 'ỉ'=>'i', 'ĩ'=>'i', 'ị'=>'i',
        'Ì'=>'I', 'Í'=>'I', 'Ỉ'=>'I', 'Ĩ'=>'I', 'Ị'=>'I',
        'ò'=>'o', 'ó'=>'o', 'ỏ'=>'o', 'õ'=>'o', 'ọ'=>'o', 'ô'=>'o', 'ồ'=>'o', 'ố'=>'o', 'ổ'=>'o', 'ỗ'=>'o', 'ộ'=>'o', 'ơ'=>'o', 'ờ'=>'o', 'ớ'=>'o', 'ở'=>'o', 'ỡ'=>'o', 'ợ'=>'o',
        'Ò'=>'O', 'Ó'=>'O', 'Ỏ'=>'O', 'Õ'=>'O', 'Ô'=>'O', 'Ồ'=>'O', 'Ố'=>'O', 'Ổ'=>'O', 'Ỗ'=>'O', 'Ộ'=>'O', 'Ơ'=>'O', 'Ờ'=>'O', 'Ớ'=>'O', 'Ở'=>'O', 'Ỡ'=>'O', 'Ợ'=>'O',
        'ù'=>'u', 'ú'=>'u', 'ủ'=>'u', 'ũ'=>'u', 'ụ'=>'u', 'ư'=>'u', 'ừ'=>'u', 'ứ'=>'u', 'ử'=>'u', 'ữ'=>'u', 'ự'=>'u',
        'Ù'=>'U', 'Ú'=>'U', 'Ủ'=>'U', 'Ũ'=>'U', 'Ụ'=>'U', 'Ư'=>'U', 'Ừ'=>'U', 'Ứ'=>'U', 'Ử'=>'U', 'Ữ'=>'U', 'Ự'=>'U',
        'ỳ'=>'y', 'ý'=>'y', 'ỷ'=>'y', 'ỹ'=>'y', 'ỵ'=>'y',
        'Ỳ'=>'Y', 'Ý'=>'Y', 'Ỷ'=>'Y', 'Ỹ'=>'Y', 'Ỵ'=>'Y'
    ];
    
    $value = strtr($value, $map);

    $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    if ($transliterated === false || $transliterated === '') {
        return preg_replace('/[^\x20-\x7E]/', '', $value) ?? $value;
    }

    return preg_replace('/[^\x20-\x7E]/', '', $transliterated) ?? $transliterated;
}

function build_vietqr_url(array $settings, string $reference, int $amount): string
{
    $bank = bank_transfer_details($settings);
    $bankBin = trim((string) ($bank['bank_bin'] ?? 'mbbank'));
    $accountNumber = preg_replace('/\D+/', '', (string) ($bank['account_number'] ?? ''));
    $accountHolder = strtoupper(trim((string) ($bank['account_holder'] ?? 'ROYALBREAD')));
    $reference = strtoupper(substr(preg_replace('/[^A-Z0-9 ]/', '', ascii_text($reference)) ?? 'ROYALBREAD', 0, 25));

    if ($bankBin === '' || $accountNumber === '') {
        return '';
    }

    $query = http_build_query([
        'amount' => max(0, $amount),
        'addInfo' => $reference,
        'accountName' => $accountHolder,
    ]);

    return sprintf(
        'https://img.vietqr.io/image/%s-%s-compact2.png?%s',
        rawurlencode($bankBin),
        rawurlencode($accountNumber),
        $query
    );
}
