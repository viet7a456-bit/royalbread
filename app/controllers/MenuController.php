<?php

declare(strict_types=1);

class MenuController extends Controller
{
    private function mergedPublicMenuGroups(array $menuGroups): array
    {
        $merged = [];
        $drinkSlugs = ['tra-nhiet-doi', 'do-uong-truyen-thong', 'cafe'];

        foreach ($menuGroups as $groupName => $items) {
            $groupSlug = (string) ($items[0]['category_slug'] ?? '');

            if (in_array($groupSlug, $drinkSlugs, true)) {
                $merged['Đồ uống'] = array_merge($merged['Đồ uống'] ?? [], $items);
                continue;
            }

            $merged[$groupName] = $items;
        }

        return $merged;
    }

    public function index(): void
    {
        $settingModel = new Setting();
        $menuItemModel = new MenuItem();
        $reviewModel = new ProductReview();
        $favoriteModel = new Favorite();
        $menuGroups = $this->mergedPublicMenuGroups($menuItemModel->grouped());

        $itemIds = [];
        foreach ($menuGroups as $items) {
            foreach ($items as $item) {
                $itemIds[] = (int) ($item['id'] ?? 0);
            }
        }

        $this->render('menu/index', [
            'settings' => $settingModel->all(),
            'menuGroups' => $menuGroups,
            'reviewSummaries' => $reviewModel->summaryByItemIds($itemIds),
            'recentReviewsByItem' => $reviewModel->approvedRecentByItemIds($itemIds, 1),
            'favoriteItemIds' => !empty($_SESSION['customer_id'])
                ? $favoriteModel->itemIdsForCustomer((int) $_SESSION['customer_id'])
                : [],
        ]);
    }
}
