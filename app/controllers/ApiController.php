<?php

declare(strict_types=1);

class ApiController extends Controller
{
    public function searchMenu(): void
    {
        $query = trim($_GET['q'] ?? '');
        $menuItemModel = new MenuItem();
        $allMenuItems = $menuItemModel->all();

        $results = [];
        if ($query !== '') {
            $queryLower = mb_strtolower($query);

            // Check if the query contains drink-related keywords
            $isDrinkQuery = (
                str_contains($queryLower, 'đồ uống') || str_contains($queryLower, 'do uong') ||
                str_contains($queryLower, 'nước') || str_contains($queryLower, 'nuoc') ||
                str_contains($queryLower, 'trà') || str_contains($queryLower, 'tra') ||
                str_contains($queryLower, 'cafe') || str_contains($queryLower, 'cà phê') || str_contains($queryLower, 'ca phe') ||
                str_contains($queryLower, 'uống') || str_contains($queryLower, 'uong')
            );

            // Check if the query contains combo-related keywords
            $isComboQuery = (
                str_contains($queryLower, 'combo') ||
                str_contains($queryLower, 'bộ') || str_contains($queryLower, 'bo') ||
                str_contains($queryLower, 'khuyến mãi') || str_contains($queryLower, 'khuyen mai')
            );

            // Check if the query contains food-related keywords
            $isFoodQuery = (
                str_contains($queryLower, 'bánh mì') || str_contains($queryLower, 'banh mi') ||
                str_contains($queryLower, 'bánh') || str_contains($queryLower, 'banh') ||
                str_contains($queryLower, 'chảo') || str_contains($queryLower, 'chao') ||
                str_contains($queryLower, 'kẹp') || str_contains($queryLower, 'kep') ||
                str_contains($queryLower, 'ăn vặt') || str_contains($queryLower, 'an vat')
            );

            foreach ($allMenuItems as $item) {
                if (!$item['is_available']) {
                    continue;
                }

                $name = (string) ($item['name'] ?? '');
                $description = (string) ($item['description'] ?? '');
                $categoryName = (string) ($item['category_name'] ?? '');
                $categorySlug = (string) ($item['category_slug'] ?? '');

                $nameLower = mb_strtolower($name);
                $descriptionLower = mb_strtolower($description);
                $categoryLower = mb_strtolower($categoryName);

                $score = 0;

                // 1. Direct matches in category name
                if (stripos($categoryName, $query) !== false) {
                    if (strcasecmp($categoryName, $query) === 0) {
                        $score += 150;
                    } else {
                        $score += 80;
                    }
                }

                // 2. Matches in item name
                if (stripos($name, $query) !== false) {
                    if (str_starts_with($nameLower, $queryLower)) {
                        $score += 100;
                    } else {
                        $score += 60;
                    }
                }

                // 3. Matches in description
                if (stripos($description, $query) !== false) {
                    $score += 20;
                }

                // 4. Drink semantic boosting
                $isDrinkCategory = (
                    $categorySlug === 'tra-nhiet-doi' ||
                    $categorySlug === 'cafe' ||
                    $categorySlug === 'do-uong-truyen-thong' ||
                    $categoryLower === 'đồ uống' ||
                    str_contains($categoryLower, 'trà') ||
                    str_contains($categoryLower, 'uống')
                );

                if ($isDrinkCategory) {
                    if ($isDrinkQuery) {
                        // Massively boost drinks for drink queries
                        $score += 200;
                    }
                    if ($isComboQuery) {
                        // De-boost drinks if they specifically searched for combo
                        $score -= 50;
                    }
                }

                // 5. Combo semantic boosting
                $isComboCategory = ($categorySlug === 'combo' || $categoryLower === 'combo');
                if ($isComboCategory) {
                    if ($isComboQuery) {
                        $score += 200;
                    }
                    // If they search specifically for drinks/food and not combo, slightly de-boost combo
                    if ($isDrinkQuery && !$isComboQuery) {
                        $score -= 80;
                    }
                    if ($isFoodQuery && !$isComboQuery) {
                        $score -= 40;
                    }
                }

                // 6. Food semantic boosting
                $isFoodCategory = (
                    $categorySlug === 'banh-mi-chao' ||
                    $categorySlug === 'banh-mi-kep' ||
                    $categorySlug === 'an-vat' ||
                    str_contains($categoryLower, 'bánh')
                );
                if ($isFoodCategory) {
                    if ($isFoodQuery) {
                        $score += 120;
                    }
                    if ($isDrinkQuery) {
                        // De-boost food for drink queries
                        $score -= 80;
                    }
                }

                if ($score > 0) {
                    $results[] = [
                        'id' => $item['id'],
                        'name' => $name,
                        'category_name' => $categoryName,
                        'price' => format_price($item['price']),
                        'image_url' => media_url((string) ($item['image_url'] ?? '')),
                        'score' => $score,
                    ];
                }
            }

            // Sort results by score DESC, and name ASC if score is equal
            usort($results, static function ($a, $b) {
                if ($b['score'] === $a['score']) {
                    return strcasecmp($a['name'], $b['name']);
                }
                return $b['score'] <=> $a['score'];
            });
        }

        $this->jsonResponse(['results' => $results]);
    }

    public function chatbot(): void
    {
        $rawInput = file_get_contents('php://input') ?: '';
        $payload = json_decode($rawInput, true);

        if (!is_array($payload)) {
            $payload = [];
            if ($rawInput !== '') {
                parse_str($rawInput, $payload);
            }
        }

        $message = trim((string) ($_POST['message'] ?? $payload['message'] ?? ''));
        $response = (new Chatbot())->answer($message);

        $this->jsonResponse($response);
    }

    public function deliveryDistance(): void
    {
        $address = trim((string) ($_GET['address'] ?? $_POST['address'] ?? ''));
        $label = trim((string) ($_GET['label'] ?? $_POST['label'] ?? $address));
        $lat = $this->readFloat($_GET['lat'] ?? $_POST['lat'] ?? null);
        $lon = $this->readFloat($_GET['lon'] ?? $_POST['lon'] ?? null);

        $estimator = new DeliveryEstimator();
        if ($lat !== null && $lon !== null) {
            $result = $estimator->estimateFromCoordinates($lat, $lon, $label);
        } else {
            $result = $estimator->estimateFromCustomerAddress($address);
        }

        if (($result['success'] ?? false) !== true) {
            http_response_code(($address === '' && ($lat === null || $lon === null)) ? 422 : 200);
        }

        $this->jsonResponse($result);
    }

    public function addressSuggestions(): void
    {
        $query = trim((string) ($_GET['q'] ?? ''));
        $suggestions = (new DeliveryEstimator())->suggestAddresses($query);

        $this->jsonResponse([
            'query' => $query,
            'suggestions' => $suggestions,
        ]);
    }

    public function reverseGeocode(): void
    {
        $lat = $this->readFloat($_GET['lat'] ?? $_POST['lat'] ?? null);
        $lon = $this->readFloat($_GET['lon'] ?? $_POST['lon'] ?? null);

        if ($lat === null || $lon === null) {
            http_response_code(422);
            $this->jsonResponse([
                'success' => false,
                'message' => 'Thiếu tọa độ vị trí hiện tại của khách.',
            ]);
        }

        $location = (new DeliveryEstimator())->reverseGeocode($lat, $lon);
        if ($location === null) {
            http_response_code(200);
            $this->jsonResponse([
                'success' => false,
                'message' => 'RoyalBread chưa nhận diện được địa chỉ từ vị trí hiện tại này.',
            ]);
        }

        $this->jsonResponse([
            'success' => true,
            'label' => (string) ($location['label'] ?? ''),
            'short_label' => (string) ($location['short_label'] ?? $location['label'] ?? ''),
            'lat' => (float) ($location['lat'] ?? 0),
            'lon' => (float) ($location['lon'] ?? 0),
        ]);
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

    private function jsonResponse(array $payload): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
