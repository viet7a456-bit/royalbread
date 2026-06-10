<?php

declare(strict_types=1);

class HomeController extends Controller
{
    public function index(): void
    {
        $settingModel = new Setting();
        $menuItemModel = new MenuItem();

        $this->render('home/index', [
            'settings' => $settingModel->all(),
            'featuredItems' => $menuItemModel->featured(),
            'menuGroups' => $menuItemModel->grouped(),
        ]);
    }
}
