<?php

declare(strict_types=1);

class MenuController extends Controller
{
    private const PER_PAGE = 10;
    private const DRINK_GROUP_SLUG = 'do-uong';
    private const DRINK_GROUP_NAME = 'Đồ uống';
    private const DRINK_CATEGORY_SLUGS = ['tra-nhiet-doi', 'do-uong-truyen-thong', 'cafe'];

    private function logMenuFeatureError(Throwable $error): void
    {
        $logDir = ROOT_PATH . '/tmp/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $entry = sprintf(
            "[%s] Menu feature error: %s\n%s\n",
            date('Y-m-d H:i:s'),
            $error->getMessage(),
            $error->getTraceAsString()
        );

        @file_put_contents($logDir . '/menu_features.log', $entry, FILE_APPEND | LOCK_EX);
        error_log('RoyalBread menu feature error: ' . $error->getMessage());
    }

    private function isDrinkCategorySlug(string $slug): bool
    {
        return in_array($slug, self::DRINK_CATEGORY_SLUGS, true);
    }

    private function normalizeGroupSlug(string $groupName, string $groupSlug = ''): string
    {
        $groupSlug = trim($groupSlug);
        if ($groupSlug !== '') {
            return $groupSlug;
        }

        $ascii = strtolower(ascii_text($groupName));
        $normalized = preg_replace('/[^a-z0-9]+/', '-', $ascii) ?? '';
        $normalized = trim($normalized, '-');

        return $normalized !== '' ? $normalized : 'danh-muc';
    }

    private function withDisplayGroup(array $items, string $groupSlug, string $groupName): array
    {
        $normalized = [];

        foreach ($items as $item) {
            $item['display_group_slug'] = $groupSlug;
            $item['display_group_name'] = $groupName;
            $normalized[] = $item;
        }

        return $normalized;
    }

    private function publicMenuGroups(array $menuGroups): array
    {
        $groups = [];

        foreach ($menuGroups as $groupName => $items) {
            if ($items === []) {
                continue;
            }

            $firstItem = $items[0];
            $categorySlug = trim((string) ($firstItem['category_slug'] ?? ''));

            if ($this->isDrinkCategorySlug($categorySlug)) {
                if (!isset($groups[self::DRINK_GROUP_SLUG])) {
                    $groups[self::DRINK_GROUP_SLUG] = [
                        'slug' => self::DRINK_GROUP_SLUG,
                        'name' => self::DRINK_GROUP_NAME,
                        'legacy_hash' => md5(self::DRINK_GROUP_NAME),
                        'items' => [],
                    ];
                }

                $groups[self::DRINK_GROUP_SLUG]['items'] = array_merge(
                    $groups[self::DRINK_GROUP_SLUG]['items'],
                    $this->withDisplayGroup($items, self::DRINK_GROUP_SLUG, self::DRINK_GROUP_NAME)
                );
                continue;
            }

            $groupSlug = $this->normalizeGroupSlug((string) $groupName, $categorySlug);

            $groups[$groupSlug] = [
                'slug' => $groupSlug,
                'name' => (string) $groupName,
                'legacy_hash' => md5((string) $groupName),
                'items' => $this->withDisplayGroup($items, $groupSlug, (string) $groupName),
            ];
        }

        return $groups;
    }

    private function flattenGroupItems(array $groups): array
    {
        $flatItems = [];

        foreach ($groups as $group) {
            foreach ($group['items'] as $item) {
                $flatItems[] = $item;
            }
        }

        return $flatItems;
    }

    private function resolveSelectedCategory(array $groups): string
    {
        $selected = trim((string) ($_GET['category'] ?? 'all'));

        if ($selected === '' || $selected === 'all') {
            return 'all';
        }

        return isset($groups[$selected]) ? $selected : 'all';
    }

    private function paginateItems(array $items, int $perPage = self::PER_PAGE): array
    {
        $totalItems = count($items);
        $totalPages = max(1, (int) ceil($totalItems / $perPage));
        $requestedPage = max(1, (int) ($_GET['page'] ?? 1));
        $currentPage = min($requestedPage, $totalPages);
        $offset = ($currentPage - 1) * $perPage;
        $pagedItems = array_slice($items, $offset, $perPage);

        return [
            'items' => $pagedItems,
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'total_items' => $totalItems,
            'per_page' => $perPage,
            'visible_from' => $totalItems > 0 ? $offset + 1 : 0,
            'visible_to' => $totalItems > 0 ? min($offset + count($pagedItems), $totalItems) : 0,
        ];
    }

    private function buildMenuTabs(array $groups, int $allItemCount): array
    {
        $tabs = [
            [
                'slug' => 'all',
                'name' => 'Tất cả',
                'item_count' => $allItemCount,
                'legacy_hash' => 'all',
            ],
        ];

        foreach ($groups as $group) {
            $tabs[] = [
                'slug' => (string) $group['slug'],
                'name' => (string) $group['name'],
                'item_count' => count($group['items']),
                'legacy_hash' => (string) $group['legacy_hash'],
            ];
        }

        return $tabs;
    }

    public function index(): void
    {
        $settingModel = new Setting();
        $menuItemModel = new MenuItem();

        $settings = $settingModel->all();
        $menuGroups = $this->publicMenuGroups($menuItemModel->grouped());
        $allMenuItems = $this->flattenGroupItems($menuGroups);
        $selectedCategory = $this->resolveSelectedCategory($menuGroups);

        if ($selectedCategory === 'all') {
            $sourceItems = $allMenuItems;
            $selectedCategoryName = 'Tất cả thực đơn';
            $selectedLegacyHash = 'all';
        } else {
            $selectedGroup = $menuGroups[$selectedCategory];
            $sourceItems = $selectedGroup['items'];
            $selectedCategoryName = (string) $selectedGroup['name'];
            $selectedLegacyHash = (string) $selectedGroup['legacy_hash'];
        }

        $pagination = $this->paginateItems($sourceItems, self::PER_PAGE);
        $visibleItems = $pagination['items'];
        $visibleItemIds = array_map('intval', array_column($visibleItems, 'id'));

        $reviewSummaries = [];
        $recentReviewsByItem = [];
        if ($visibleItemIds !== []) {
            try {
                $reviewModel = new ProductReview();
                $reviewSummaries = $reviewModel->summaryByItemIds($visibleItemIds);
                $recentReviewsByItem = $reviewModel->approvedRecentByItemIds($visibleItemIds, 2);
            } catch (Throwable $featureError) {
                $this->logMenuFeatureError($featureError);
            }
        }

        $favoriteItemIds = [];
        if (!empty($_SESSION['customer_id'])) {
            try {
                $favoriteItemIds = (new Favorite())->itemIdsForCustomer((int) $_SESSION['customer_id']);
            } catch (Throwable $featureError) {
                $this->logMenuFeatureError($featureError);
            }
        }

        $menuSections = [
            [
                'slug' => $selectedCategory === 'all' ? 'all' : $selectedCategory,
                'legacy_hash' => $selectedLegacyHash,
                'name' => $selectedCategoryName,
                'items' => $visibleItems,
            ],
        ];

        $this->render('menu/index', [
            'settings' => $settings,
            'menuTabs' => $this->buildMenuTabs($menuGroups, count($allMenuItems)),
            'menuSections' => $menuSections,
            'selectedCategory' => $selectedCategory,
            'selectedCategoryName' => $selectedCategoryName,
            'currentPage' => $pagination['current_page'],
            'totalPages' => $pagination['total_pages'],
            'totalItems' => $pagination['total_items'],
            'perPage' => $pagination['per_page'],
            'visibleFrom' => $pagination['visible_from'],
            'visibleTo' => $pagination['visible_to'],
            'reviewSummaries' => $reviewSummaries,
            'recentReviewsByItem' => $recentReviewsByItem,
            'favoriteItemIds' => $favoriteItemIds,
        ]);
    }
}
