<?php

declare(strict_types=1);

class DeliveryEstimator
{
    private const DEFAULT_CITY_CONTEXT = 'Hải Dương, Việt Nam';
    private const DEFAULT_SHOP_QUERY = '28 Dã Tượng, P. Lê Thanh Nghị, TP Hải Dương, Hải Dương, Việt Nam';
    private const DEFAULT_SHOP_LAT = 20.9325516;
    private const DEFAULT_SHOP_LON = 106.3295816;
    private const MAX_REASONABLE_DELIVERY_DISTANCE_KM = 35.0;
    private const GEOCODER_ENDPOINT = 'https://photon.komoot.io/api/?limit=%d&q=%s';
    private const GEOCODER_ENDPOINT_BIASED = 'https://photon.komoot.io/api/?limit=%d&lat=%s&lon=%s&q=%s';
    private const REVERSE_GEOCODER_ENDPOINT = 'https://photon.komoot.io/reverse?lat=%s&lon=%s';
    private const ROUTE_ENDPOINT = 'https://router.project-osrm.org/route/v1/driving/%s,%s;%s,%s?overview=false';
    private const DEFAULT_SUGGESTION_LIMIT = 6;

    private array $settings;

    public function __construct(?array $settings = null)
    {
        $this->settings = $settings ?? (new Setting())->all();
    }

    public function shopLocation(): ?array
    {
        $addressLabel = trim((string) setting($this->settings, 'address', self::DEFAULT_SHOP_QUERY));
        $shortLabel = trim((string) setting($this->settings, 'site_name', 'RoyalBread'));

        $configuredLat = $this->readConfiguredCoordinate('map_lat');
        $configuredLon = $this->readConfiguredCoordinate('map_lon');

        if ($configuredLat !== null && $configuredLon !== null) {
            return [
                'lat' => $configuredLat,
                'lon' => $configuredLon,
                'label' => $addressLabel !== '' ? $addressLabel : self::DEFAULT_SHOP_QUERY,
                'short_label' => $shortLabel !== '' ? $shortLabel : 'RoyalBread',
            ];
        }

        return [
            'lat' => self::DEFAULT_SHOP_LAT,
            'lon' => self::DEFAULT_SHOP_LON,
            'label' => $addressLabel !== '' ? $addressLabel : self::DEFAULT_SHOP_QUERY,
            'short_label' => $shortLabel !== '' ? $shortLabel : 'RoyalBread',
        ];
    }

    public function estimateFromCustomerAddress(string $customerAddress): array
    {
        $customerAddress = trim($customerAddress);
        if ($customerAddress === '') {
            return [
                'success' => false,
                'message' => 'Vui lòng nhập địa chỉ giao hàng cụ thể để RoyalBread tự tính khoảng cách.',
            ];
        }

        $shopLocation = $this->shopLocation();
        if ($shopLocation === null) {
            return [
                'success' => false,
                'message' => 'RoyalBread chưa xác định được vị trí quán để tính phí giao hàng.',
            ];
        }

        $customerQuery = $this->addressWithContext($customerAddress);
        $customerLocation = $this->geocodeAddress($customerQuery, true) ?? $this->geocodeAddress($customerAddress, true);
        if ($customerLocation === null) {
            return [
                'success' => false,
                'message' => 'Chưa xác định được địa chỉ giao hàng. Bạn hãy chọn từ gợi ý hoặc dùng vị trí hiện tại nhé.',
            ];
        }

        if (!$this->isReasonableDeliveryTarget($shopLocation, $customerLocation)) {
            return [
                'success' => false,
                'message' => 'Địa chỉ này đang bị nhận diện lệch khỏi khu vực giao hàng. Bạn hãy chọn lại từ gợi ý hoặc dùng vị trí hiện tại để RoyalBread tính đúng hơn.',
            ];
        }

        return $this->buildDistanceResult($shopLocation, $customerLocation);
    }

    public function estimateFromCoordinates(float $lat, float $lon, string $addressLabel = ''): array
    {
        if (!$this->isValidCoordinate($lat, $lon)) {
            return [
                'success' => false,
                'message' => 'Vị trí hiện tại của khách chưa hợp lệ để tính khoảng cách giao hàng.',
            ];
        }

        $shopLocation = $this->shopLocation();
        if ($shopLocation === null) {
            return [
                'success' => false,
                'message' => 'RoyalBread chưa xác định được vị trí quán để tính phí giao hàng.',
            ];
        }

        $customerLocation = [
            'lat' => $lat,
            'lon' => $lon,
            'label' => trim($addressLabel) !== '' ? trim($addressLabel) : 'Vị trí hiện tại của khách',
            'short_label' => trim($addressLabel) !== '' ? trim($addressLabel) : 'Vị trí hiện tại',
        ];

        $reversedLocation = $this->reverseGeocode($lat, $lon);
        if ($reversedLocation !== null) {
            $customerLocation['label'] = $reversedLocation['label'];
            $customerLocation['short_label'] = $reversedLocation['short_label'];
        }

        $result = $this->buildDistanceResult($shopLocation, $customerLocation);
        if (($result['success'] ?? false) === true) {
            $result['message'] = 'RoyalBread đã tự tính khoảng cách theo vị trí hiện tại của khách.';
            $result['source'] = 'current-location';
        }

        return $result;
    }

    public function suggestAddresses(string $query, int $limit = self::DEFAULT_SUGGESTION_LIMIT): array
    {
        $query = trim($query);
        $limit = max(1, min(10, $limit));
        $normalizedQuery = $this->normalizeAddress($query);
        $shopLocation = $this->shopLocation();

        $suggestions = [];
        $seen = [];

        foreach ($this->localPickupHints($query) as $hint) {
            $key = $this->normalizeAddress((string) $hint['label']);
            if ($key === '' || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $hint['score'] = $this->scoreSuggestion($hint, $normalizedQuery, $shopLocation);
            $suggestions[] = $hint;
        }

        if ($query !== '') {
            $queries = array_values(array_unique([
                $this->addressWithContext($query),
                $query,
            ]));

            foreach ($queries as $searchQuery) {
                foreach ($this->geocodeSearch($searchQuery, $limit + 6, true) as $feature) {
                    $location = $this->parseLocationFeature($feature, $query);
                    if ($location === null) {
                        continue;
                    }

                    if (!$this->matchesSearchIntent($location, $normalizedQuery)) {
                        continue;
                    }

                    if ($shopLocation !== null && !$this->isReasonableDeliveryTarget($shopLocation, $location)) {
                        continue;
                    }

                    $suggestionKey = $this->normalizeAddress($location['label']);
                    if ($suggestionKey === '' || isset($seen[$suggestionKey])) {
                        continue;
                    }

                    $seen[$suggestionKey] = true;
                    $location['score'] = $this->scoreSuggestion($location, $normalizedQuery, $shopLocation);
                    $suggestions[] = $location;
                }
            }
        }

        usort($suggestions, static function (array $left, array $right): int {
            return ($left['score'] ?? 999) <=> ($right['score'] ?? 999);
        });

        $suggestions = array_slice($suggestions, 0, $limit);

        return array_map(static function (array $item): array {
            return [
                'label' => (string) ($item['label'] ?? ''),
                'short_label' => (string) ($item['short_label'] ?? $item['label'] ?? ''),
                'lat' => (float) ($item['lat'] ?? 0),
                'lon' => (float) ($item['lon'] ?? 0),
            ];
        }, $suggestions);
    }

    public function reverseGeocode(float $lat, float $lon): ?array
    {
        if (!$this->isValidCoordinate($lat, $lon)) {
            return null;
        }

        $cacheKey = md5('reverse:' . $lat . ':' . $lon);
        if (isset($_SESSION['delivery_geocode_cache'][$cacheKey]) && is_array($_SESSION['delivery_geocode_cache'][$cacheKey])) {
            return $_SESSION['delivery_geocode_cache'][$cacheKey];
        }

        $url = sprintf(
            self::REVERSE_GEOCODER_ENDPOINT,
            rawurlencode((string) $lat),
            rawurlencode((string) $lon)
        );

        $payload = $this->requestJson($url);
        $features = is_array($payload['features'] ?? null) ? $payload['features'] : [];
        if ($features === []) {
            return null;
        }

        $location = $this->parseLocationFeature($features[0], 'Vị trí hiện tại');
        if ($location === null) {
            return null;
        }

        $_SESSION['delivery_geocode_cache'][$cacheKey] = $location;

        return $location;
    }

    private function buildDistanceResult(array $shopLocation, array $customerLocation): array
    {
        $distanceKm = $this->routeDistanceKm($shopLocation, $customerLocation);
        $source = 'route';

        if ($distanceKm === null) {
            $distanceKm = $this->haversineDistanceKm(
                (float) $shopLocation['lat'],
                (float) $shopLocation['lon'],
                (float) $customerLocation['lat'],
                (float) $customerLocation['lon']
            );
            $source = 'straight';
        }

        $distanceKm = max(0.0, normalize_distance_km($distanceKm));
        if ($distanceKm < 0) {
            return [
                'success' => false,
                'message' => 'RoyalBread chưa tính được quãng đường giao hàng cho địa chỉ này.',
            ];
        }

        return [
            'success' => true,
            'distance_km' => $distanceKm,
            'distance_text' => format_distance_km($distanceKm),
            'shipping_fee' => calculate_shipping_fee($distanceKm),
            'resolved_customer_address' => (string) ($customerLocation['label'] ?? ''),
            'resolved_shop_address' => (string) ($shopLocation['label'] ?? ''),
            'customer_lat' => (float) ($customerLocation['lat'] ?? 0),
            'customer_lon' => (float) ($customerLocation['lon'] ?? 0),
            'source' => $source,
            'message' => $source === 'route'
                ? 'RoyalBread đã tự tính khoảng cách theo đường đi giao hàng.'
                : 'RoyalBread đang dùng ước tính khoảng cách theo vị trí gần đúng.',
        ];
    }

    private function addressWithContext(string $address): string
    {
        $address = trim($address);
        $normalized = $this->normalizeAddress($address);

        if ($normalized === '') {
            return self::DEFAULT_SHOP_QUERY;
        }

        if (!str_contains($normalized, 'hai duong')) {
            return rtrim($address, ', ') . ', ' . self::DEFAULT_CITY_CONTEXT;
        }

        if (!str_contains($normalized, 'viet nam')) {
            return rtrim($address, ', ') . ', Việt Nam';
        }

        return $address;
    }

    private function geocodeAddress(string $query, bool $preferLocal = true): ?array
    {
        $query = trim($query);
        if ($query === '') {
            return null;
        }

        $cacheKey = md5('geocode:' . ($preferLocal ? 'local:' : 'global:') . $query);
        if (isset($_SESSION['delivery_geocode_cache'][$cacheKey]) && is_array($_SESSION['delivery_geocode_cache'][$cacheKey])) {
            return $_SESSION['delivery_geocode_cache'][$cacheKey];
        }

        $features = $this->geocodeSearch($query, 1, $preferLocal);
        if ($features === []) {
            return null;
        }

        $location = $this->parseLocationFeature($features[0], $query);
        if ($location === null) {
            return null;
        }

        $_SESSION['delivery_geocode_cache'][$cacheKey] = $location;

        return $location;
    }

    private function geocodeSearch(string $query, int $limit, bool $preferLocal = true): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $limit = max(1, min(10, $limit));
        $responses = [];

        if ($preferLocal) {
            $bias = $this->shopBiasCoordinates();
            $biasedUrl = sprintf(
                self::GEOCODER_ENDPOINT_BIASED,
                $limit,
                rawurlencode((string) $bias['lat']),
                rawurlencode((string) $bias['lon']),
                rawurlencode($query)
            );
            $responses[] = $this->requestJson($biasedUrl);
        }

        $responses[] = $this->requestJson(sprintf(self::GEOCODER_ENDPOINT, $limit, rawurlencode($query)));

        $features = [];
        foreach ($responses as $payload) {
            if (!is_array($payload['features'] ?? null)) {
                continue;
            }

            foreach ($payload['features'] as $feature) {
                if (is_array($feature)) {
                    $features[] = $feature;
                }
            }
        }

        return $features;
    }

    private function parseLocationFeature(array $feature, string $fallbackLabel = ''): ?array
    {
        $coordinates = $feature['geometry']['coordinates'] ?? null;
        if (!is_array($coordinates) || count($coordinates) < 2) {
            return null;
        }

        $properties = is_array($feature['properties'] ?? null) ? $feature['properties'] : [];
        $name = trim((string) ($properties['name'] ?? ''));
        $street = trim((string) ($properties['street'] ?? ''));
        $district = trim((string) ($properties['district'] ?? ($properties['suburb'] ?? ($properties['locality'] ?? ''))));
        $city = trim((string) ($properties['city'] ?? ''));
        $state = trim((string) ($properties['state'] ?? ''));
        $countryCode = strtoupper(trim((string) ($properties['countrycode'] ?? '')));

        $primary = $name !== '' ? $name : $street;
        $secondary = $street !== '' && $street !== $primary ? $street : '';

        $labelParts = array_values(array_unique(array_filter([
            $primary,
            $secondary,
            $district,
        ], static fn (string $value): bool => $value !== '')));

        if (count($labelParts) < 2 && $city !== '') {
            $labelParts[] = $city;
        }

        $shortLabelParts = array_values(array_unique(array_filter([
            $primary,
            $district,
            count($labelParts) < 2 ? $city : '',
        ], static fn (string $value): bool => $value !== '')));

        $label = $labelParts !== [] ? implode(', ', $labelParts) : trim($fallbackLabel);
        if ($label === '') {
            return null;
        }

        $shortLabel = $shortLabelParts !== [] ? implode(' · ', $shortLabelParts) : $label;

        return [
            'lat' => (float) $coordinates[1],
            'lon' => (float) $coordinates[0],
            'label' => $label,
            'short_label' => $shortLabel,
            'city' => $city,
            'state' => $state,
            'countrycode' => $countryCode,
        ];
    }

    private function scoreSuggestion(array $location, string $normalizedQuery, ?array $shopLocation = null): int
    {
        $label = $this->normalizeAddress((string) ($location['label'] ?? ''));
        $shortLabel = $this->normalizeAddress((string) ($location['short_label'] ?? ''));
        $city = $this->normalizeAddress((string) ($location['city'] ?? ''));
        $state = $this->normalizeAddress((string) ($location['state'] ?? ''));
        $score = 100;

        if ($normalizedQuery !== '' && str_contains($shortLabel, $normalizedQuery)) {
            $score -= 35;
        }

        if ($normalizedQuery !== '' && str_contains($label, $normalizedQuery)) {
            $score -= 25;
        }

        if (str_contains($city, 'hai duong') || str_contains($state, 'hai duong') || str_contains($label, 'hai duong')) {
            $score -= 30;
        }

        if (($location['countrycode'] ?? '') === 'VN') {
            $score -= 10;
        }

        if ($shopLocation !== null) {
            $distanceFromShop = $this->haversineDistanceKm(
                (float) $shopLocation['lat'],
                (float) $shopLocation['lon'],
                (float) ($location['lat'] ?? 0),
                (float) ($location['lon'] ?? 0)
            );

            $score += (int) min(60, round($distanceFromShop * 2));
        }

        return $score;
    }

    private function localPickupHints(string $query = ''): array
    {
        $query = $this->normalizeAddress($query);

        $hints = [
            [
                'label' => 'Nhà thi đấu Hải Dương, Phố Dã Tượng, Lê Thanh Nghị',
                'short_label' => 'Nhà thi đấu Hải Dương',
                'lat' => self::DEFAULT_SHOP_LAT,
                'lon' => self::DEFAULT_SHOP_LON,
                'city' => 'Hải Dương',
                'state' => 'Hải Dương',
                'countrycode' => 'VN',
                'keywords' => ['nha thi dau', 'da tuong', 'royalbread', 'quan'],
            ],
            [
                'label' => 'Phố Hào Thành, Lê Thanh Nghị',
                'short_label' => 'Phố Hào Thành',
                'lat' => 20.9394175,
                'lon' => 106.3245350,
                'city' => 'Hải Dương',
                'state' => 'Hải Dương',
                'countrycode' => 'VN',
                'keywords' => ['hao thanh', 'le thanh nghi'],
            ],
            [
                'label' => 'Khu vực Lê Thanh Nghị, Hải Dương',
                'short_label' => 'Lê Thanh Nghị',
                'lat' => 20.9288814,
                'lon' => 106.3287490,
                'city' => 'Hải Dương',
                'state' => 'Hải Dương',
                'countrycode' => 'VN',
                'keywords' => ['le thanh nghi'],
            ],
        ];

        if ($query === '') {
            return $hints;
        }

        return array_values(array_filter($hints, function (array $hint) use ($query): bool {
            if (str_contains($this->normalizeAddress((string) $hint['label']), $query)) {
                return true;
            }

            foreach ($hint['keywords'] as $keyword) {
                if (str_contains($query, $keyword) || str_contains($keyword, $query)) {
                    return true;
                }
            }

            return false;
        }));
    }

    private function matchesSearchIntent(array $location, string $normalizedQuery): bool
    {
        if ($normalizedQuery === '') {
            return true;
        }

        $haystack = $this->normalizeAddress(implode(' ', array_filter([
            (string) ($location['label'] ?? ''),
            (string) ($location['short_label'] ?? ''),
            (string) ($location['city'] ?? ''),
            (string) ($location['state'] ?? ''),
        ])));

        if ($haystack === '') {
            return false;
        }

        if (str_contains($haystack, $normalizedQuery)) {
            return true;
        }

        $ignoredTokens = ['hai', 'duong', 'viet', 'nam', 'tp', 'pho', 'duong', 'phuong', 'khu', 'vuc'];
        $tokens = array_values(array_filter(explode(' ', $normalizedQuery), static function (string $token) use ($ignoredTokens): bool {
            return strlen($token) >= 3 && !in_array($token, $ignoredTokens, true);
        }));

        if ($tokens === []) {
            return true;
        }

        foreach ($tokens as $token) {
            if (str_contains($haystack, $token)) {
                return true;
            }
        }

        return false;
    }

    private function isReasonableDeliveryTarget(array $shopLocation, array $candidateLocation): bool
    {
        $distance = $this->haversineDistanceKm(
            (float) $shopLocation['lat'],
            (float) $shopLocation['lon'],
            (float) ($candidateLocation['lat'] ?? 0),
            (float) ($candidateLocation['lon'] ?? 0)
        );

        return $distance <= self::MAX_REASONABLE_DELIVERY_DISTANCE_KM;
    }

    private function shopBiasCoordinates(): array
    {
        $configuredLat = $this->readConfiguredCoordinate('map_lat');
        $configuredLon = $this->readConfiguredCoordinate('map_lon');

        return [
            'lat' => $configuredLat ?? self::DEFAULT_SHOP_LAT,
            'lon' => $configuredLon ?? self::DEFAULT_SHOP_LON,
        ];
    }

    private function readConfiguredCoordinate(string $key): ?float
    {
        $rawValue = trim((string) setting($this->settings, $key, ''));
        if ($rawValue === '') {
            return null;
        }

        $value = (float) str_replace(',', '.', $rawValue);
        if (!is_finite($value)) {
            return null;
        }

        return $value;
    }

    private function routeDistanceKm(array $origin, array $destination): ?float
    {
        $url = sprintf(
            self::ROUTE_ENDPOINT,
            $origin['lon'],
            $origin['lat'],
            $destination['lon'],
            $destination['lat']
        );

        $payload = $this->requestJson($url);
        $routes = $payload['routes'] ?? null;
        if (!is_array($routes) || empty($routes[0]['distance'])) {
            return null;
        }

        return round(((float) $routes[0]['distance']) / 1000, 2);
    }

    private function requestJson(string $url): ?array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return null;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'User-Agent: RoyalBreadLocation/1.0',
            ],
        ]);

        $response = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if (!is_string($response) || $response === '' || $statusCode >= 400) {
            return null;
        }

        $payload = json_decode($response, true);
        return is_array($payload) ? $payload : null;
    }

    private function haversineDistanceKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371.0;

        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLon = deg2rad($lon2 - $lon1);
        $a = sin($deltaLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($deltaLon / 2) ** 2;

        return round($earthRadius * 2 * asin(min(1, sqrt($a))), 2);
    }

    private function isValidCoordinate(float $lat, float $lon): bool
    {
        return $lat >= -90 && $lat <= 90 && $lon >= -180 && $lon <= 180;
    }

    private function normalizeAddress(string $text): string
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
        $text = preg_replace('/[^a-z0-9]+/u', ' ', $text) ?? $text;

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }
}
