<?php

declare(strict_types=1);

class HomeController extends Controller
{
    private function logHomeFeatureError(Throwable $error): void
    {
        $logDir = ROOT_PATH . '/tmp/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $entry = sprintf(
            "[%s] Home feature error: %s\n%s\n",
            date('Y-m-d H:i:s'),
            $error->getMessage(),
            $error->getTraceAsString()
        );
        @file_put_contents($logDir . '/home_features.log', $entry, FILE_APPEND | LOCK_EX);
        error_log('RoyalBread home feature error: ' . $error->getMessage());
    }

    private function menuGroupStartPages(array $menuGroups, int $perPage = 10): array
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

        $flatItems = [];
        foreach ($merged as $groupName => $items) {
            foreach ($items as $item) {
                $flatItems[] = [
                    'group_name' => $groupName,
                    'id' => $item['id'] ?? 0,
                ];
            }
        }

        $pages = [];
        foreach ($flatItems as $index => $item) {
            $groupName = (string) ($item['group_name'] ?? '');
            if ($groupName === '' || isset($pages[$groupName])) {
                continue;
            }

            $pages[$groupName] = (int) floor($index / $perPage) + 1;
        }

        return $pages;
    }

    public function index(): void
    {
        $settings = [];
        $featuredItems = [];
        $menuGroups = [];
        $homeReviews = [];
        $homeReviewCount = 0;

        try {
            $settingModel = new Setting();
            $menuItemModel = new MenuItem();
            $reviewModel = new ProductReview();

            $settings = $settingModel->all();
            $featuredItems = $menuItemModel->featured();
            $menuGroups = $menuItemModel->grouped();

            try {
                $homeReviews = $reviewModel->approvedLatest(4);
                $homeReviewCount = $reviewModel->countApproved();
            } catch (Throwable $featureError) {
                $this->logHomeFeatureError($featureError);
            }
        } catch (Throwable $featureError) {
            $this->logHomeFeatureError($featureError);
        }

        $this->render('home/index', [
            'settings' => $settings,
            'featuredItems' => $featuredItems,
            'menuGroups' => $menuGroups,
            'homeReviews' => $homeReviews,
            'homeReviewCount' => $homeReviewCount,
        ]);
    }
}
